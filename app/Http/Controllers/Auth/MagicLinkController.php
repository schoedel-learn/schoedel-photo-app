<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MagicLinkController extends Controller
{
    /**
     * Request a magic link to be sent to the email address.
     */
    public function request(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'remember' => ['boolean'],
        ]);

        $email = $request->email;
        $remember = $request->boolean('remember', false);

        // Store remember preference in session for later use
        session(['magic_link_remember' => $remember]);

        // Generate secure random token
        $token = Str::random(64);
        $hashedToken = hash('sha256', $token);

        // Set expiration to 15 minutes from now
        $expiresAt = now()->addMinutes(15);

        // Create magic link record
        MagicLink::create([
            'email' => $email,
            'token' => $hashedToken,
            'expires_at' => $expiresAt,
        ]);

        // Send magic link email
        Mail::to($email)->send(new MagicLinkMail($token));

        return response()->json([
            'message' => 'Magic link sent to your email. Please check your inbox.',
        ], 200);
    }

    /**
     * Verify the magic link token and authenticate the user.
     */
    public function verify(Request $request, string $token)
    {
        if (empty($token)) {
            return redirect()->route('login')->withErrors([
                'token' => 'Invalid magic link. Please request a new one.',
            ]);
        }

        // Hash the token for lookup
        $hashedToken = hash('sha256', $token);

        // Find magic link by hashed token
        $magicLink = MagicLink::where('token', $hashedToken)
            ->whereNull('used_at')
            ->first();

        if (!$magicLink) {
            return redirect()->route('login')->withErrors([
                'token' => 'Invalid or already used magic link. Please request a new one.',
            ]);
        }

        // Check if token has expired
        if ($magicLink->isExpired()) {
            return redirect()->route('login')->withErrors([
                'token' => 'Magic link has expired. Please request a new one.',
            ]);
        }

        // Find or create user with email
        $user = User::firstOrCreate(
            ['email' => $magicLink->email],
            [
                'name' => $this->extractNameFromEmail($magicLink->email),
                'password' => Hash::make(Str::random(32)), // Random password since users won't use it
                'role' => 'client',
            ]
        );

        // Mark magic link as used
        $magicLink->markAsUsed();

        // Get remember preference from session
        $remember = session('magic_link_remember', false);
        session()->forget('magic_link_remember');

        // Authenticate user with client guard
        Auth::guard('client')->login($user, $remember);

        // Regenerate session for security
        $request->session()->regenerate();

        // Redirect to intended URL or home
        $intendedUrl = session()->pull('url.intended', '/');

        return redirect($intendedUrl)->with('success', 'Welcome! You have been logged in successfully.');
    }

    /**
     * Extract a name from email address (for auto-created users).
     */
    private function extractNameFromEmail(string $email): string
    {
        $localPart = explode('@', $email)[0];
        $name = str_replace(['.', '_', '-'], ' ', $localPart);
        return ucwords($name);
    }
}

