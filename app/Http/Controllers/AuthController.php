<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Register a new user and generate an auth token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = Crypt::encryptString("{$user->id}|" . time());

        // Synchronize web session if available so protected web routes work seamlessly
        if ($request->hasSession()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'token'   => $token,
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'avatar_url' => $user->avatar_url,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Log in an existing user and return an auth token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = Crypt::encryptString("{$user->id}|" . time());

        // Synchronize web session if available so protected web routes work seamlessly
        if ($request->hasSession()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'token'   => $token,
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'avatar_url' => $user->avatar_url,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Log out the current user (blacklist the bearer token).
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if ($token) {
            // Blacklist the token for 30 days
            Cache::put('token_blacklist_' . sha1($token), true, now()->addDays(30));
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], Response::HTTP_OK);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user'    => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'avatar_url'       => $user->avatar_url,
                'role'             => $user->role,
                'created_at'       => $user->created_at?->format('Y-m-d H:i:s'),
                'orders_count'     => $user->orders()->count(),
                'addresses_count'  => $user->addresses()->count(),
                'wishlist_count'   => $user->wishlist ? $user->wishlist->items()->count() : 0,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Update user profile (name, email, and optional avatar upload).
     * Accepts multipart/form-data (POST with _method=PUT).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            // Remove old avatar from storage
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'avatar_url' => $user->avatar_url,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Change the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided current password does not match our records.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ], Response::HTTP_OK);
    }

    /**
     * Permanently delete the authenticated user's account.
     * Requires current password confirmation for security.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided password is incorrect. Account was not deleted.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Delete avatar from storage if present
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Blacklist the current token before deleting
        $token = $request->bearerToken();
        if ($token) {
            Cache::put('token_blacklist_' . sha1($token), true, now()->addDays(30));
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Your account has been permanently deleted.',
        ], Response::HTTP_OK);
    }
}
