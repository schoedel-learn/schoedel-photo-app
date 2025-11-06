<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('staff')->check()) {
            return redirect()->route('staff.login')
                ->with('error', 'Please login to access this page.');
        }

        $user = Auth::guard('staff')->user();

        // Check if user has a staff role
        $staffRoles = ['photographer', 'admin', 'studio_manager', 'developer'];
        if (!in_array($user->role, $staffRoles)) {
            Auth::guard('staff')->logout();
            return redirect()->route('staff.login')
                ->with('error', 'You do not have permission to access the staff area.');
        }

        return $next($request);
    }
}

