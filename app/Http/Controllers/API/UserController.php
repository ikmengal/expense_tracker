<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Fetch authenticated user details.
     */
    public function profile(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    /**
     * Update General Profile (Name & Profile Picture)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user->name = $request->name;

        // Handle Profile Picture Upload
        if ($request->hasFile('avatar')) {
            // Purani file delete karne ke liye (Aapka database column 'avatar' use karega)
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Nayi image store karein (Yeh 'avatars/filename.jpg' return karega)
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path; // 💡 'avatar_url' ki jagah database column 'avatar' use kiya
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ], 200);
    }

    /**
     * Update Password Securely
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Check if current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Aapka mojooda password durust nahi hy.'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ], 200);
    }
}
