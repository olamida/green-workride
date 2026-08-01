<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __invoke()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('landing', [
            'liveTrips' => Trip::query()
                ->where('status', 'scheduled')
                ->where('departure_time', '>', now())
                ->orderBy('departure_time')
                ->limit(3)
                ->get(),
        ]);
    }
}
