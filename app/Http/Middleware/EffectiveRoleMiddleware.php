<?php

namespace App\Http\Middleware;

use App\Services\RoleSwitcherService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares the admin's effective role with every web view.
 *
 * Runs in the global web group so layouts always have `$effectiveRole` /
 * `$viewingAs`. Non-admins leave the shared variables unset — layouts fall
 * back to `auth()->user()->role`. Security gates are untouched by design.
 */
class EffectiveRoleMiddleware
{
    public function __construct(private readonly RoleSwitcherService $roles) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAdmin()) {
            view()->share('effectiveRole', $this->roles->effectiveRole($user));
            view()->share('viewingAs', $this->roles->isViewingAs($user));
        }

        return $next($request);
    }
}
