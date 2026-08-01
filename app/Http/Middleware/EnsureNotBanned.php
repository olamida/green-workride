<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the user is not banned (web guard version of the API check).
 */
class EnsureNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            abort(403, 'This account has been suspended. Contact support@workride.ng.');
        }

        return $next($request);
    }
}
