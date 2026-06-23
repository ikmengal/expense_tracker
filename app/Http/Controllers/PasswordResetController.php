<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Mail\ResetPasswordMail; // 👈 Humari new mailable class import ho gayi
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        // $request->validate(['email' => 'required|email']);
        // $user = User::where('email', $request->email)->first();

        // if (!$user) {
        //     return response()->json(['message' => 'We couldn\'t find an account with that email address.'], 404);
        // }

        // // Token creation
        // $token = Str::random(60);
        // DB::table('password_reset_tokens')->updateOrInsert(
        //     ['email' => $request->email],
        //     [
        //         'token' => Hash::make($token),
        //         'created_at' => Carbon::now()
        //     ]
        // );

        // // Verification Dynamic Link Setup
        // $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        // // Raw HTML or dedicated Mailable structure dispatch
        // try {
        //     Mail::send([], [], function ($message) use ($request, $resetUrl) {
        //         $message->to($request->email)
        //                 ->subject('Reset Your Password 🔒')
        //                 ->html("
        //                     <div style='font-family: sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #e8e8e8; border-radius: 8px;'>
        //                         <h2>Password Reset Request</h2>
        //                         <p>You are receiving this email because we received a password reset request for your account.</p>
        //                         <a href='{$resetUrl}' style='display: inline-block; padding: 10px 20px; background-color: #4f46e5; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
        //                         <p style='margin-top: 20px; color: #666; font-size: 12px;'>This password reset link will expire in 60 minutes.</p>
        //                     </div>
        //                 ");
        //     });
        // } catch (\Exception $e) {
        //     return response()->json(['message' => 'Failed to send verification email.'], 500);
        // }

        // return response()->json(['message' => 'A password reset link has been sent to your email address.']);

        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'We couldn\'t find an account with that email address.'
            ], 404);
        }

        // Token creation
        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        // Verification Dynamic Link Setup pointing to Vue Frontend
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        // Dispatched through dedicated beautifully designed Mailable structure
        try {
            // 🌟 Mail::to() use karke new class call ki aur link pass kar diya
            Mail::to($request->email)->send(new ResetPasswordMail($resetUrl));

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send verification email.',
                'error' => $e->getMessage() // Logs tracking ke liye lagaya hai
            ], 500);
        }

        return response()->json([
            'message' => 'A password reset link has been sent to your email address.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Invalid or expired password reset token.'], 400);
        }

        // Token Expiry check (1 hour)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'This token has expired.'], 400);
        }

        // Update password
        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['password' => Hash::make($request->password)]);

        // Clear used tokens
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Your password has been successfully reset.']);
    }
}
