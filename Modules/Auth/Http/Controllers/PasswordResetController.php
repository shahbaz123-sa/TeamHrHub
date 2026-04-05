<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Mail\ResetPasswordMail;
use Modules\Auth\Http\Requests\ResetPasswordRequest;
use Modules\Auth\Http\Requests\ForgotPasswordRequest;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link via email
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $email = $request->email;

        // Create a reset token
        $token = Str::random(60);

        // Delete existing reset token if any
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Create new reset token
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Send email with reset link
        $user = User::where('email', $email)->first();
        $resetUrl = url('/reset-password') . '?token=' . $token . '&email=' . urlencode($email);

        Mail::send(new ResetPasswordMail($user, $resetUrl));

        return response()->json([
            'message' => 'Password reset link sent to your email address.',
        ], 200);
    }

    /**
     * Verify reset token and reset password
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $email = $request->email;
        $token = $request->token;

        // Get the stored token
        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetToken) {
            return response()->json([
                'message' => 'Invalid reset link. Please request a new password reset.',
            ], 400);
        }

        // Check if token matches and is not expired
        if (!Hash::check($token, $resetToken->token) || now()->diffInMinutes($resetToken->created_at) > env('RESET_PASSWORD_LINK_TIMEOUT', 10)) {
            return response()->json([
                'message' => 'This password reset link has expired. Please request a new one.',
            ], 400);
        }

        // Update user password
        $user = User::where('email', $email)->first();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the reset token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'message' => 'Your password has been reset successfully. Please login with your new password.',
        ], 200);
    }
}
