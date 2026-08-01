<?php

namespace App\Http\Controllers\Web;

use App\Enums\OrderPaymentSource;
use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Models\ShopOrder;
use App\Services\CommodityService;
use Illuminate\Http\Request;

/**
 * Commodity shop — physical goods bought with wallet cash or earned balance
 * (never subsidy credits) via a QR-collection order. Guide §14 extension.
 */
class ShopController extends Controller
{
    public function index()
    {
        $items = Commodity::where('active', true)
            ->where('is_shop_item', true)
            ->orderBy('name')
            ->get();

        $orders = ShopOrder::with('user')
            ->where('user_id', request()->user()->id)
            ->latest()
            ->limit(10)
            ->get();

        $wallet = request()->user()->wallet;

        $enabled = (bool) config('workride.commodities.enabled', false);

        return view('shop.index', compact('items', 'orders', 'wallet', 'enabled'));
    }

    public function store(Request $request, CommodityService $service)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.commodity_id' => ['required', 'integer', 'exists:commodities,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'paid_from' => ['required', 'in:cash,earned'],
        ]);

        $source = OrderPaymentSource::from($data['paid_from']);

        $order = $service->placeOrder($request->user(), $data['items'], $source);

        return back()->with('status', 'Order '.$order->reference.' placed (₦'.number_format((float) $order->total_ngn, 2).'). Show the QR at the collection desk.');
    }

    public function cancel(Request $request, CommodityService $service, ShopOrder $order)
    {
        $service->cancelOrder($request->user(), $order);

        return back()->with('status', 'Order '.$order->reference.' cancelled and refunded to your wallet.');
    }
}
