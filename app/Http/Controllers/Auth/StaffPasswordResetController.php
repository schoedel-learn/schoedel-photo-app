<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class StaffPasswordResetController extends Controller
{
    /**
     * Display the password reset link request form.
     */
    public function showResetRequestForm()
    {
        return view('auth.staff-password-reset');
    }

    /**
     * Handle a password reset link request.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Verify user exists and has staff role
        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !$this->isStaffRole($user->role)) {
            // Don't reveal if email exists, just return success message
            return back()->with('status', 'If that email address exists in our system, we will send a password reset link.');
        }

        // Send password reset email using staff password broker
        $status = Password::broker('staff')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Display the password reset form.
     */
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.staff-password-reset-form', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle the password reset.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        // Reset password using staff password broker
        $status = Password::broker('staff')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('staff.login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    /**
     * Check if the role is a staff role.
     */
    private function isStaffRole(?string $role): bool
    {
        return in_array($role, ['photographer', 'admin', 'studio_manager', 'developer']);
    }
}

