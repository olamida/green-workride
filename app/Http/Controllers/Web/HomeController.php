<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __invoke()
    {
        if (Auth::check()) {
            return redirect()->route('go');
        }

        return view('landing', [
            'liveTrips' => Trip::query()
                ->where('status', 'scheduled')
                ->where('departure_time', '>', now())
                ->orderBy('departure_time')
                ->limit(3)
                ->get(),
            'kpis' => [
                'scheduledTrips' => Trip::query()
                    ->where('status', 'scheduled')
                    ->where('departure_time', '>', now())
                    ->count(),
                'ridesToday' => Booking::query()
                    ->whereIn('status', ['completed', 'boarded'])
                    ->whereDate('updated_at', today())
                    ->count(),
                'verifiedWorkers' => User::query()
                    ->where('verification_level', '>=', 1)
                    ->count(),
                'freeRides' => Trip::query()
                    ->where('is_free_volunteer', true)
                    ->where('status', 'completed')
                    ->count(),
            ],
        ]);
    }
}
