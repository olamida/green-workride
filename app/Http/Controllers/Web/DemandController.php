<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DemandRequest;
use App\Models\Junction;
use App\Services\DemandService;
use Illuminate\Http\Request;

/**
 * Rider demand check-in (guide §9B Method 5): a passenger at a junction taps
 * "I'm here, need a ride" — even with no driver yet, this is future supply
 * planning for the Control Tower and a source of subsidy evidence.
 */
class DemandController extends Controller
{
    public function index(DemandService $demand)
    {
        $junctions = Junction::where('is_active', true)->orderBy('name')->get();
        $mine = DemandRequest::where('user_id', auth()->id())
            ->latest('requested_at')
            ->limit(10)
            ->get();

        return view('demand.index', compact('junctions', 'mine'));
    }

    public function checkIn(Request $request, DemandService $demand)
    {
        $data = $request->validate([
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'destination_text' => 'required|string|max:255',
            'passengers_count' => 'required|integer|between:1,10',
            'junction_id' => 'nullable|exists:junctions,id',
        ]);

        $demand->checkIn($request->user(), $data);

        return back()->with('status', 'Demand noted. Ops can see "'.auth()->user()->name.' needs a ride to '.$data['destination_text'].' now" on the demand map.');
    }
}
