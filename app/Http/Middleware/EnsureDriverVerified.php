<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriverVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canDrivePaid()) {
            abort(403, 'Driver verification (Level 3) is required to publish paid rides.');
        }

        return $next($request);
    }
}
