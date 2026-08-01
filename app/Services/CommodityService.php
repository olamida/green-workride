<?php

namespace App\Services;

use App\Enums\OrderPaymentSource;
use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Models\Commodity;
use App\Models\CommodityPosition;
use App\Models\ShopOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Wallet-to-commodity commerce (guide §14 extension): wallet balances buy
 * tradable commodity positions (gold, rice, maize) or physical shop orders.
 *
 * Compliance: subsidy_credits can NEVER buy goods — they are ride-only.
 * Cash balance is spent first, then earnings; positions are backed by the
 * partner exchange/commodity pool on settlement.
 */
class CommodityService
{
    public function __construct(private WalletService $wallet) {}

    /**
     * Buy a tradable commodity with wallet cash→earned (never subsidy).
     */
    public function buy(User $user, Commodity $commodity, float $quantity): CommodityPosition
    {
        $this->assertEnabled();
        $this->assertTradable($commodity);

        $quantity = round($quantity, 4);
        $cost = $this->assertCost($commodity, $quantity);

        return DB::transaction(function () use ($user, $commodity, $quantity, $cost) {
            $wallet = $this->lockWallet($user);

            $cash = round(min((float) $wallet->cash_balance, $cost), 2);
            $earned = round(min((float) $wallet->earned_balance, round($cost - $cash, 2)), 2);

            if (round($cash + $earned, 2) < $cost) {
                throw ValidationException::withMessages(['wallet' => 'Insufficient wallet balance.']);
            }

            $reference = 'COM-BUY-'.$user->id.'-'.$commodity->id.'-'.Str::upper(Str::random(10));

            $this->wallet->debitForTransfer(
                $wallet,
                $cash,
                $earned,
                $reference,
                TransactionType::CommodityBuy,
                "Bought {$quantity} {$commodity->unit}(s) of {$commodity->name}",
                ['commodity_id' => $commodity->id, 'quantity' => $quantity, 'unit_price' => $commodity->current_price_ngn],
            );

            return $this->upsertPosition($user, $commodity, $quantity, $cost);
        });
    }

    /**
     * Sell a tradable commodity back to the wallet (cash). Realizes any gain
     * or loss against the average cost.
     */
    public function sell(User $user, Commodity $commodity, float $quantity): float
    {
        $this->assertEnabled();

        $quantity = round($quantity, 4);

        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be positive.']);
        }

        return DB::transaction(function () use ($user, $commodity, $quantity) {
            $position = CommodityPosition::where('user_id', $user->id)
                ->where('commodity_id', $commodity->id)
                ->lockForUpdate()
                ->first();

            if (! $position || (float) $position->quantity < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Not enough of this commodity in your portfolio.']);
            }

            $price = (float) $commodity->current_price_ngn;
            $proceeds = round($price * $quantity, 2);
            $remaining = round((float) $position->quantity - $quantity, 4);

            $wallet = $this->lockWallet($user);

            $reference = 'COM-SELL-'.$user->id.'-'.$commodity->id.'-'.Str::upper(Str::random(10));

            $this->wallet->creditForTransfer(
                $wallet,
                $proceeds,
                0.0,
                $reference,
                TransactionType::CommoditySell,
                "Sold {$quantity} {$commodity->unit}(s) of {$commodity->name}",
                ['commodity_id' => $commodity->id, 'quantity' => $quantity, 'unit_price' => $price],
            );

            if ($remaining <= 0) {
                $position->delete();
            } else {
                $position->update(['quantity' => $remaining]);
            }

            return $proceeds;
        });
    }

    public function portfolio(User $user): Collection
    {
        return CommodityPosition::with('commodity')
            ->where('user_id', $user->id)
            ->get()
            ->map(function (CommodityPosition $position) {
                $position->current_value_ngn = $position->currentValue();

                return $position;
            });
    }

    /**
     * Place a wallet-paid shop order for physical goods (QR collection voucher).
     *
     * @param  array<int, array{commodity_id: int, quantity: float}>  $items
     */
    public function placeOrder(User $user, array $items, OrderPaymentSource $source): ShopOrder
    {
        $this->assertEnabled();

        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'Add at least one item.']);
        }

        return DB::transaction(function () use ($user, $items, $source) {
            $lineItems = [];
            $total = 0.0;

            foreach ($items as $item) {
                $commodity = Commodity::where('id', $item['commodity_id'])
                    ->where('active', true)
                    ->where('is_shop_item', true)
                    ->first();

                if (! $commodity) {
                    throw ValidationException::withMessages(['items' => 'One of the selected items is not available for purchase.']);
                }

                $quantity = round((float) $item['quantity'], 4);

                if ($quantity <= 0) {
                    throw ValidationException::withMessages(['items' => 'Quantity must be positive.']);
                }

                $price = (float) $commodity->current_price_ngn;
                $lineTotal = round($price * $quantity, 2);
                $total = round($total + $lineTotal, 2);

                $lineItems[] = [
                    'commodity_id' => $commodity->id,
                    'symbol' => $commodity->symbol,
                    'name' => $commodity->name,
                    'unit' => $commodity->unit,
                    'quantity' => $quantity,
                    'unit_price_ngn' => $price,
                    'line_total_ngn' => $lineTotal,
                ];
            }

            if ($total <= 0) {
                throw ValidationException::withMessages(['items' => 'Order total must be positive.']);
            }

            $wallet = $this->lockWallet($user);

            $cash = $source === OrderPaymentSource::Cash ? $total : 0.0;
            $earned = $source === OrderPaymentSource::Earned ? $total : 0.0;

            $available = $source === OrderPaymentSource::Cash
                ? (float) $wallet->cash_balance
                : (float) $wallet->earned_balance;

            if ($available < $total) {
                throw ValidationException::withMessages(['wallet' => 'Insufficient '.$source->label().' balance for this order.']);
            }

            $reference = 'ORD-'.$user->id.'-'.Str::upper(Str::random(10));

            $this->wallet->debitForTransfer(
                $wallet,
                $cash,
                $earned,
                $reference,
                TransactionType::Purchase,
                'Shop order '.$reference,
                ['items' => $lineItems, 'total' => $total, 'paid_from' => $source->value],
            );

            return ShopOrder::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'items' => $lineItems,
                'total_ngn' => $total,
                'paid_from' => $source->value,
                'status' => OrderStatus::Placed->value,
            ]);
        });
    }

    public function cancelOrder(User $user, ShopOrder $order): ShopOrder
    {
        if ($order->user_id !== $user->id) {
            throw ValidationException::withMessages(['order' => 'You cannot cancel this order.']);
        }

        if ($order->status !== OrderStatus::Placed) {
            throw ValidationException::withMessages(['order' => 'This order is already closed.']);
        }

        return DB::transaction(function () use ($user, $order) {
            $order = ShopOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $wallet = $this->lockWallet($user);

            $cash = $order->paid_from === OrderPaymentSource::Cash ? (float) $order->total_ngn : 0.0;
            $earned = $order->paid_from === OrderPaymentSource::Earned ? (float) $order->total_ngn : 0.0;

            $this->wallet->creditForTransfer(
                $wallet,
                $cash,
                $earned,
                "ORD-REF-{$order->id}-".Str::upper(Str::random(6)),
                TransactionType::OrderRefund,
                "Refund — {$order->reference}",
                ['order_id' => $order->id],
            );

            $order->update(['status' => OrderStatus::Cancelled->value]);

            return $order->fresh();
        });
    }

    public function fulfillOrder(ShopOrder $order): ShopOrder
    {
        $order->update([
            'status' => OrderStatus::Fulfilled->value,
            'fulfilled_at' => now(),
        ]);

        return $order->fresh();
    }

    private function upsertPosition(User $user, Commodity $commodity, float $quantity, float $cost): CommodityPosition
    {
        $position = CommodityPosition::where('user_id', $user->id)
            ->where('commodity_id', $commodity->id)
            ->lockForUpdate()
            ->first();

        if (! $position) {
            return CommodityPosition::create([
                'user_id' => $user->id,
                'commodity_id' => $commodity->id,
                'quantity' => $quantity,
                'avg_cost_ngn' => round($cost / $quantity, 2),
            ]);
        }

        $newQuantity = round((float) $position->quantity + $quantity, 4);
        $currentCost = round((float) $position->avg_cost_ngn * (float) $position->quantity, 2);
        $newAverage = round(($currentCost + $cost) / $newQuantity, 2);

        $position->update([
            'quantity' => $newQuantity,
            'avg_cost_ngn' => $newAverage,
        ]);

        return $position->refresh();
    }

    private function lockWallet(User $user): Wallet
    {
        $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

        if (! $wallet) {
            throw ValidationException::withMessages(['wallet' => 'No wallet. Please top up first.']);
        }

        return $wallet;
    }

    private function assertTradable(Commodity $commodity): void
    {
        if (! $commodity->active || ! $commodity->is_tradable) {
            throw ValidationException::withMessages(['commodity' => 'This commodity is not tradable right now.']);
        }
    }

    private function assertCost(Commodity $commodity, float $quantity): float
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be positive.']);
        }

        return round((float) $commodity->current_price_ngn * $quantity, 2);
    }

    private function assertEnabled(): void
    {
        if (! (bool) config('workride.commodities.enabled', false)) {
            throw ValidationException::withMessages(['commodities' => 'Commodity trading is not enabled yet.']);
        }
    }
}
