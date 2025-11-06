<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsPhotographer
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

        if ($user->role !== 'photographer') {
            abort(403, 'Only photographers can access this page.');
        }

        return $next($request);
    }
}

