<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming API request with bearer token or session authentication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check Bearer Token
        $token = $request->bearerToken();

        if ($token) {
            // Check if token has been revoked/blacklisted
            if (Cache::has('token_blacklist_' . sha1($token))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has been revoked. Please log in again.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            try {
                $decrypted = Crypt::decryptString($token);
                $parts = explode('|', $decrypted);

                if (count($parts) >= 2) {
                    $userId = (int) $parts[0];
                    $issuedAt = (int) $parts[1];

                    // Check token expiration (30 days validity)
                    if (time() - $issuedAt > 30 * 86400) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Token has expired. Please log in again.',
                        ], Response::HTTP_UNAUTHORIZED);
                    }

                    $user = User::find($userId);

                    if ($user) {
                        $request->setUserResolver(fn () => $user);
                        auth()->setUser($user);

                        return $next($request);
                    }
                }
            } catch (\Throwable $e) {
                // Invalid or corrupted token
            }
        }

        // 2. Check Session Auth fallback
        if (auth()->check()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Please log in to continue.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
