<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Queue a password reset request for OSA Admin to fulfill.
     *
     * No email is sent — OSA Admin reviews the request and sets a new
     * password for the account directly.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && ! $user->passwordResetRequests()->pending()->exists()) {
            $user->passwordResetRequests()->create(['status' => 'pending']);
        }

        // Same message regardless of whether the email matched, to avoid
        // revealing which accounts exist.
        return back()->with('status', 'If that account exists, your request has been sent to the OSA office. You will be notified once your password is reset.');
    }
}
