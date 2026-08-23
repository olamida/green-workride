<?php

namespace App\Http\Middleware;

use App\Models\Employer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployerAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Get the employer from the route (if nested under employer)
        $employer = $request->route('employer');

        if (! $employer) {
            abort(404, 'Employer not found');
        }

        // Route model binding might give us an ID string - resolve to model if needed
        $employerModel = $employer instanceof Employer ? $employer : Employer::findOrFail((int) $employer);

        // Check if user is an admin member of this employer
        $membership = $employerModel->members()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('is_employer_admin', true)
            ->first();

        if (! $membership) {
            abort(403, 'You do not have permission to manage this employer\'s roster.');
        }

        return $next($request);
    }
}
