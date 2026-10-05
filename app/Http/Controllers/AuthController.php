<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Register a new user and generate a Sanctum API token.
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

        // Issue a Sanctum token for the registering device
        $deviceName = $request->header('User-Agent') ?? 'web-token';
        $token = $user->createToken($deviceName)->plainTextToken;

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
     * Log in an existing user and return a Sanctum API token.
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

        // Issue a Sanctum token for this device/browser
        $deviceName = $request->header('User-Agent') ?? 'web-token';
        $token = $user->createToken($deviceName)->plainTextToken;

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
     * Log out the current user.
     * Revokes the device's PersonalAccessToken if authenticated via Bearer token.
     * If authenticated via web session (TransientToken or session cookie), safely terminates session without error.
     */
    public function logout(Request $request): JsonResponse
    {
        if ($user = $request->user()) {
            $token = $user->currentAccessToken();

            // Only delete if it is an actual database PersonalAccessToken (not TransientToken, not null)
            if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $token->delete();
            } elseif ($rawBearer = $request->bearerToken()) {
                if ($rawBearer !== 'null' && $rawBearer !== 'undefined' && trim($rawBearer) !== '') {
                    \Laravel\Sanctum\PersonalAccessToken::findToken($rawBearer)?->delete();
                }
            }
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
     * Revokes ALL existing tokens across every device for security,
     * then issues a fresh token for the current device.
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

        // Revoke ALL tokens across every device (phone, laptop, other browsers).
        // This closes any compromised session immediately upon password change.
        $user->tokens()->delete();

        // Issue a fresh token for the current device so this session stays active.
        $deviceName = $request->header('User-Agent') ?? 'web-token';
        $newToken = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully. All other devices have been logged out.',
            'token'   => $newToken,
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

        // Revoke all Sanctum tokens across all devices before deletion
        $user->tokens()->delete();

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
