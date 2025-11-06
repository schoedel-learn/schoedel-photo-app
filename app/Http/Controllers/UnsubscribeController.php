<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UnsubscribeController extends Controller
{
    /**
     * Handle unsubscribe request.
     */
    public function unsubscribe(Request $request, string $token)
    {
        // Find user by token
        $users = User::where('role', 'client')->get();

        $user = null;
        foreach ($users as $u) {
            $expectedToken = hash('sha256', $u->id . $u->email . config('app.key'));
            if (hash_equals($expectedToken, $token)) {
                $user = $u;
                break;
            }
        }

        if (!$user) {
            return view('emails.unsubscribe-error');
        }

        // Update unsubscribe status
        $user->update(['email_unsubscribed' => true]);

        return view('emails.unsubscribe-success', [
            'user' => $user,
        ]);
    }
}
