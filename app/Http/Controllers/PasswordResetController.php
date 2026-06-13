<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showRequestForm()
    {
        return view('admin.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink(
            $request->only('email'),
            // Custom delivery so the email matches the rest of the brand.
            function (User $user, string $token) {
                Mail::to($user->email)->send(new PasswordResetMail($user, $token));
            },
        );

        // Don't leak whether the email exists — same message either way.
        return back()->with('status',
            'If an account exists for that email, a reset link is on its way. Check your inbox (and spam folder).');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('admin.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Password updated — you can now log in with your new password.');
        }

        return back()->withInput($request->only('email'))
            ->with('error', match ($status) {
                Password::INVALID_TOKEN => 'This reset link has expired or already been used. Please request a new one.',
                Password::INVALID_USER => 'We could not find an account for that email.',
                default => 'We could not reset your password. Please request a new link.',
            });
    }
}
