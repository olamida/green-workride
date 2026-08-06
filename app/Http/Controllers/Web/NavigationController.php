<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use Illuminate\Contracts\View\View;

/**
 * Navigation-first rider home ("Where are you going?").
 *
 * The authenticated landing page: a big destination search, live corridor
 * chips, a never-empty map, and a bottom sheet of rides going the rider's way.
 * Read-only — booking still happens on the existing trip pages.
 */
class NavigationController extends Controller
{
    public function __invoke(NavigationService $navigation): View
    {
        $data = $navigation->homeData();

        return view('navigation.home', $data);
    }
}
