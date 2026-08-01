<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Commodity;
use App\Services\CommodityService;
use Illuminate\Http\Request;

/**
 * Wallet-to-commodity market (guide §14 extension): buy/sell tradable
 * positions funded by wallet cash→earned — subsidy credits are never spendable
 * here. Only tradable commodities appear in this market.
 */
class CommodityController extends Controller
{
    public function index(Request $request, CommodityService $service)
    {
        $market = Commodity::where('active', true)
            ->where('is_tradable', true)
            ->orderBy('category')
            ->get();

        $portfolio = $service->portfolio($request->user());

        $enabled = (bool) config('workride.commodities.enabled', false);

        return view('commodities.index', compact('market', 'portfolio', 'enabled'));
    }

    public function buy(Request $request, CommodityService $service)
    {
        $data = $request->validate([
            'commodity_id' => ['required', 'exists:commodities,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $commodity = Commodity::findOrFail((int) $data['commodity_id']);

        $position = $service->buy($request->user(), $commodity, (float) $data['quantity']);

        return back()->with('status', "Bought {$position->quantity} {$commodity->unit}(s) of {$commodity->name} into your portfolio.");
    }

    public function sell(Request $request, CommodityService $service)
    {
        $data = $request->validate([
            'commodity_id' => ['required', 'exists:commodities,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $commodity = Commodity::findOrFail((int) $data['commodity_id']);

        $proceeds = $service->sell($request->user(), $commodity, (float) $data['quantity']);

        return back()->with('status', 'Sold '.number_format((float) $data['quantity'], 4)." {$commodity->unit}(s) of {$commodity->name} for ₦".number_format($proceeds, 2).'.');
    }
}
