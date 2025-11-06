<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StaffLoginController extends Controller
{
    /**
     * Show the staff login form.
     */
    public function showLoginForm()
    {
        // Redirect if already authenticated as staff
        if (Auth::guard('staff')->check()) {
            return redirect()->route('staff.dashboard');
        }

        return view('auth.staff-login');
    }

    /**
     * Handle staff login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember', false);

        // Attempt authentication
        if (Auth::guard('staff')->attempt($credentials, $remember)) {
            $user = Auth::guard('staff')->user();

            // Verify user has a staff role
            if (!$this->isStaffRole($user->role)) {
                Auth::guard('staff')->logout();
                throw ValidationException::withMessages([
                    'email' => ['These credentials do not have staff access.'],
                ]);
            }

            // Regenerate session for security
            $request->session()->regenerate();

            // Redirect to intended URL or dashboard
            $intendedUrl = session()->pull('url.intended', route('staff.dashboard'));

            return redirect($intendedUrl)->with('success', 'Welcome back!');
        }

        // Authentication failed
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    /**
     * Handle staff logout request.
     */
    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login')->with('success', 'You have been logged out.');
    }

    /**
     * Check if the role is a staff role.
     */
    private function isStaffRole(?string $role): bool
    {
        return in_array($role, ['photographer', 'admin', 'studio_manager', 'developer']);
    }
}

