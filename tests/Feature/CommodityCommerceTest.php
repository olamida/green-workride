<?php

namespace Tests\Feature;

use App\Enums\CommodityCategory;
use App\Enums\OrderPaymentSource;
use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Models\Commodity;
use App\Models\CommodityPosition;
use App\Models\ShopOrder;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CommodityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommodityCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workride.commodities.enabled' => true]);
    }

    private function user(float $cash = 0.0, float $earned = 0.0, float $subsidy = 0.0): User
    {
        $user = User::factory()->create();

        Wallet::create([
            'user_id' => $user->id,
            'cash_balance' => $cash,
            'earned_balance' => $earned,
            'subsidy_credits' => $subsidy,
        ]);

        return $user;
    }

    private function commodity(array $overrides = []): Commodity
    {
        return Commodity::create(array_merge([
            'symbol' => 'XAU',
            'name' => 'Gold Gram',
            'category' => CommodityCategory::PreciousMetal,
            'unit' => 'gram',
            'current_price_ngn' => 95000,
            'is_tradable' => true,
            'is_shop_item' => true,
            'active' => true,
        ], $overrides));
    }

    public function test_disabled_gate_blocks_buy(): void
    {
        config(['workride.commodities.enabled' => false]);

        $user = $this->user(cash: 500000);
        $gold = $this->commodity();

        $this->expectException(ValidationException::class);

        app(CommodityService::class)->buy($user, $gold, 1);
    }

    public function test_buy_debits_cash_and_creates_position(): void
    {
        $user = $this->user(cash: 200000);
        $gold = $this->commodity();

        $position = app(CommodityService::class)->buy($user, $gold, 2);

        $this->assertEquals(2, (float) $position->quantity);
        $this->assertEquals(95000, (float) $position->avg_cost_ngn);

        $this->assertDatabaseHas('commodity_positions', [
            'user_id' => $user->id,
            'commodity_id' => $gold->id,
            'quantity' => 2,
        ]);

        $this->assertEquals(10000, (float) $user->wallet->fresh()->cash_balance);

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $user->wallet->id,
            'type' => TransactionType::CommodityBuy->value,
            'amount' => 190000,
        ]);
    }

    public function test_buy_uses_cash_then_earned_never_subsidy(): void
    {
        $user = $this->user(cash: 100000, earned: 150000, subsidy: 900000);
        $gold = $this->commodity();

        app(CommodityService::class)->buy($user, $gold, 2);

        $wallet = $user->wallet->fresh();

        $this->assertEquals(0.0, (float) $wallet->cash_balance);
        $this->assertEquals(60000.0, (float) $wallet->earned_balance);
        $this->assertEquals(900000.0, (float) $wallet->subsidy_credits);
    }

    public function test_buy_with_only_subsidy_fails(): void
    {
        $user = $this->user(subsidy: 500000);
        $gold = $this->commodity();

        try {
            app(CommodityService::class)->buy($user, $gold, 1);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('wallet', $e->errors());
        }

        $this->assertDatabaseMissing('commodity_positions', ['user_id' => $user->id]);
    }

    public function test_buy_insufficient_funds_fails(): void
    {
        $user = $this->user(cash: 1000);
        $gold = $this->commodity();

        $this->expectException(ValidationException::class);

        app(CommodityService::class)->buy($user, $gold, 1);
    }

    public function test_buy_inactive_or_non_tradable_fails(): void
    {
        $user = $this->user(cash: 200000);

        $this->expectException(ValidationException::class);

        app(CommodityService::class)->buy($user, $this->commodity(['active' => false]), 1);

        $this->expectException(ValidationException::class);

        app(CommodityService::class)->buy($user, $this->commodity(['is_tradable' => false]), 1);
    }

    public function test_sell_credits_cash_and_reduces_position(): void
    {
        $user = $this->user(cash: 250000);
        $gold = $this->commodity();

        app(CommodityService::class)->buy($user, $gold, 2);

        $proceeds = app(CommodityService::class)->sell($user, $gold, 1);

        $this->assertEquals(95000, $proceeds);

        $position = CommodityPosition::where('user_id', $user->id)->where('commodity_id', $gold->id)->first();

        $this->assertEquals(1, (float) $position->quantity);
        $this->assertEquals(155000, (float) $user->wallet->fresh()->cash_balance);

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $user->wallet->id,
            'type' => TransactionType::CommoditySell->value,
            'amount' => 95000,
        ]);
    }

    public function test_sell_entire_position_deletes_it(): void
    {
        $user = $this->user(cash: 200000);
        $gold = $this->commodity();

        app(CommodityService::class)->buy($user, $gold, 2);
        app(CommodityService::class)->sell($user, $gold, 2);

        $this->assertDatabaseMissing('commodity_positions', ['user_id' => $user->id]);
        $this->assertEquals(200000, (float) $user->wallet->fresh()->cash_balance);
    }

    public function test_sell_more_than_owned_fails(): void
    {
        $user = $this->user(cash: 200000);
        $gold = $this->commodity();

        app(CommodityService::class)->buy($user, $gold, 1);

        $this->expectException(ValidationException::class);

        app(CommodityService::class)->sell($user, $gold, 3);
    }

    public function test_place_cash_order_creates_debit_and_order(): void
    {
        $user = $this->user(cash: 500000);
        $rice = $this->commodity([
            'symbol' => 'RICE',
            'name' => 'Bag of Rice',
            'category' => CommodityCategory::Agricultural,
            'unit' => 'bag',
            'current_price_ngn' => 75000,
            'is_tradable' => false,
            'is_shop_item' => true,
        ]);

        $order = app(CommodityService::class)->placeOrder(
            $user,
            [['commodity_id' => $rice->id, 'quantity' => 2]],
            OrderPaymentSource::Cash,
        );

        $this->assertEquals(OrderStatus::Placed, $order->status);
        $this->assertEquals(150000, (float) $order->total_ngn);
        $this->assertEquals(350000, (float) $user->wallet->fresh()->cash_balance);

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $user->wallet->id,
            'type' => TransactionType::Purchase->value,
            'amount' => 150000,
        ]);
    }

    public function test_place_earned_order_uses_earned_balance(): void
    {
        $user = $this->user(earned: 300000);
        $maize = $this->commodity([
            'symbol' => 'MAIZ',
            'name' => 'Bag of Maize',
            'category' => CommodityCategory::Agricultural,
            'unit' => 'bag',
            'current_price_ngn' => 40000,
            'is_tradable' => false,
            'is_shop_item' => true,
        ]);

        app(CommodityService::class)->placeOrder(
            $user,
            [['commodity_id' => $maize->id, 'quantity' => 1]],
            OrderPaymentSource::Earned,
        );

        $this->assertEquals(260000, (float) $user->wallet->fresh()->earned_balance);
        $this->assertEquals(0, (float) $user->wallet->fresh()->cash_balance);
    }

    public function test_shop_order_cannot_be_paid_with_subsidy(): void
    {
        $user = $this->user(subsidy: 500000);
        $rice = $this->commodity([
            'symbol' => 'RICE',
            'name' => 'Bag of Rice',
            'category' => CommodityCategory::Agricultural,
            'unit' => 'bag',
            'current_price_ngn' => 75000,
            'is_tradable' => false,
            'is_shop_item' => true,
        ]);

        try {
            app(CommodityService::class)->placeOrder(
                $user,
                [['commodity_id' => $rice->id, 'quantity' => 1]],
                OrderPaymentSource::Cash,
            );
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('wallet', $e->errors());
        }

        $this->assertDatabaseMissing('shop_orders', ['user_id' => $user->id]);
    }

    public function test_place_order_with_inactive_or_non_shop_item_fails(): void
    {
        $user = $this->user(cash: 500000);
        $gold = $this->commodity(['active' => false]);

        $this->expectException(ValidationException::class);

        app(CommodityService::class)->placeOrder(
            $user,
            [['commodity_id' => $gold->id, 'quantity' => 1]],
            OrderPaymentSource::Cash,
        );
    }

    public function test_cancel_order_refunds_to_wallet(): void
    {
        $user = $this->user(cash: 500000);
        $rice = $this->commodity([
            'symbol' => 'RICE',
            'name' => 'Bag of Rice',
            'category' => CommodityCategory::Agricultural,
            'unit' => 'bag',
            'current_price_ngn' => 75000,
            'is_tradable' => false,
            'is_shop_item' => true,
        ]);

        $order = app(CommodityService::class)->placeOrder(
            $user,
            [['commodity_id' => $rice->id, 'quantity' => 1]],
            OrderPaymentSource::Cash,
        );

        $cancelled = app(CommodityService::class)->cancelOrder($user, $order);

        $this->assertEquals(OrderStatus::Cancelled, $cancelled->status);
        $this->assertEquals(500000, (float) $user->wallet->fresh()->cash_balance);

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $user->wallet->id,
            'type' => TransactionType::OrderRefund->value,
            'amount' => 75000,
        ]);
    }

    public function test_cancel_foreign_or_closed_order_fails(): void
    {
        $owner = $this->user(cash: 500000);
        $other = $this->user(cash: 100);
        $rice = $this->commodity([
            'symbol' => 'RICE',
            'name' => 'Bag of Rice',
            'category' => CommodityCategory::Agricultural,
            'unit' => 'bag',
            'current_price_ngn' => 75000,
            'is_tradable' => false,
            'is_shop_item' => true,
        ]);

        $order = app(CommodityService::class)->placeOrder(
            $owner,
            [['commodity_id' => $rice->id, 'quantity' => 1]],
            OrderPaymentSource::Cash,
        );

        $this->expectException(ValidationException::class);

        app(CommodityService::class)->cancelOrder($other, $order);

        app(CommodityService::class)->fulfillOrder($order);

        $this->expectException(ValidationException::class);

        app(CommodityService::class)->cancelOrder($owner, $order->fresh());
    }

    public function test_fulfill_order_marks_completed(): void
    {
        $user = $this->user(cash: 500000);
        $rice = $this->commodity([
            'symbol' => 'RICE',
            'name' => 'Bag of Rice',
            'category' => CommodityCategory::Agricultural,
            'unit' => 'bag',
            'current_price_ngn' => 75000,
            'is_tradable' => false,
            'is_shop_item' => true,
        ]);

        $order = app(CommodityService::class)->placeOrder(
            $user,
            [['commodity_id' => $rice->id, 'quantity' => 1]],
            OrderPaymentSource::Cash,
        );

        $fulfilled = app(CommodityService::class)->fulfillOrder($order);

        $this->assertEquals(OrderStatus::Fulfilled, $fulfilled->status);
        $this->assertNotNull($fulfilled->fulfilled_at);
    }

    public function test_portfolio_returns_positions_with_value(): void
    {
        $user = $this->user(cash: 200000);
        $gold = $this->commodity();

        app(CommodityService::class)->buy($user, $gold, 1);

        $portfolio = app(CommodityService::class)->portfolio($user);

        $this->assertCount(1, $portfolio);
        $this->assertEquals(95000, $portfolio->first()->current_value_ngn);
    }

    public function test_web_market_and_shop_pages_render(): void
    {
        $user = $this->user(cash: 500000);
        $this->commodity();
        $this->commodity([
            'symbol' => 'RICE',
            'name' => 'Bag of Rice',
            'category' => CommodityCategory::Agricultural,
            'unit' => 'bag',
            'current_price_ngn' => 75000,
            'is_tradable' => false,
            'is_shop_item' => true,
        ]);

        $this->actingAs($user)
            ->get(route('commodities.index'))
            ->assertOk()
            ->assertSee('Gold Gram');

        $this->actingAs($user)
            ->get(route('shop.index'))
            ->assertOk()
            ->assertSee('Bag of Rice');
    }

    public function test_web_buy_and_sell_flow(): void
    {
        $user = $this->user(cash: 200000);
        $gold = $this->commodity();

        $this->actingAs($user)
            ->post(route('commodities.buy'), [
                'commodity_id' => $gold->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('commodity_positions', [
            'user_id' => $user->id,
            'commodity_id' => $gold->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('commodities.sell'), [
                'commodity_id' => $gold->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('commodity_positions', ['user_id' => $user->id]);
    }

    public function test_web_shop_order_and_cancel_flow(): void
    {
        $user = $this->user(cash: 500000);
        $rice = $this->commodity([
            'symbol' => 'RICE',
            'name' => 'Bag of Rice',
            'category' => CommodityCategory::Agricultural,
            'unit' => 'bag',
            'current_price_ngn' => 75000,
            'is_tradable' => false,
            'is_shop_item' => true,
        ]);

        $this->actingAs($user)
            ->post(route('shop.store'), [
                'items' => [['commodity_id' => $rice->id, 'quantity' => 1]],
                'paid_from' => 'cash',
            ])
            ->assertRedirect();

        $order = ShopOrder::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(OrderStatus::Placed, $order->status);

        $this->actingAs($user)
            ->post(route('shop.cancel', $order))
            ->assertRedirect();

        $this->assertEquals(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertEquals(500000, (float) $user->wallet->fresh()->cash_balance);
    }

    public function test_web_buy_validation_rejects_bad_quantity(): void
    {
        $user = $this->user(cash: 200000);
        $gold = $this->commodity();

        $this->actingAs($user)
            ->from(route('commodities.index'))
            ->post(route('commodities.buy'), [
                'commodity_id' => $gold->id,
                'quantity' => 0,
            ])
            ->assertRedirect(route('commodities.index'))
            ->assertSessionHasErrors('quantity');
    }
}
