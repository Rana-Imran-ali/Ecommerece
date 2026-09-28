<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming API request using Sanctum token verification.
     *
     * Unlike the old system (Crypt + Cache blacklist), this checks the
     * personal_access_tokens database table directly, so:
     *  - Revoked tokens are truly deleted — cache flush cannot resurrect them.
     *  - Each device has its own token row that can be deleted independently.
     *  - last_used_at is updated on every authenticated request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawToken = $request->bearerToken();
        if ($rawToken === 'null' || $rawToken === 'undefined' || trim((string)$rawToken) === '') {
            $rawToken = null;
        }

        if ($rawToken) {
            // Look up the hashed token in the personal_access_tokens table
            $accessToken = PersonalAccessToken::findToken($rawToken);

            if ($accessToken && $accessToken->tokenable) {
                // Honour token expiry if expires_at is set
                if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
                    $accessToken->delete();
                } else {
                    $user = $accessToken->tokenable;

                    // Bind the resolved user to the request and the auth guard
                    $request->setUserResolver(fn () => $user);
                    auth()->setUser($user);

                    // Track last activity timestamp on the token row
                    $accessToken->forceFill(['last_used_at' => now()])->save();

                    return $next($request);
                }
            }
        }

        // Web session fallback: If the user is logged into the web browser session
        if (\Illuminate\Support\Facades\Auth::guard('web')->check() || $request->user()) {
            $user = \Illuminate\Support\Facades\Auth::guard('web')->user() ?? $request->user();
            $request->setUserResolver(fn () => $user);
            auth()->setUser($user);

            return $next($request);
        }

        if ($rawToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Invalid or revoked token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Bearer token required.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
