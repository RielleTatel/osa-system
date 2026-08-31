<?php

namespace App\Http\Controllers\OsaAdmin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Notifications\PasswordWasReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function index()
    {
        $requests = PasswordResetRequest::with('user')
            ->pending()
            ->orderBy('requested_at')
            ->get();

        return view('osa-admin.password-resets.index', compact('requests'));
    }

    public function update(Request $request, PasswordResetRequest $passwordResetRequest)
    {
        $password = Str::password(12);

        $passwordResetRequest->user->update(['password' => Hash::make($password)]);

        $passwordResetRequest->update([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
            'fulfilled_by' => $request->user()->id,
        ]);

        $passwordResetRequest->user->notify(new PasswordWasReset);

        return redirect()->route('osa-admin.password-resets.index')
            ->with('status', "Password reset for {$passwordResetRequest->user->email}. Temporary password: {$password}");
    }
}
