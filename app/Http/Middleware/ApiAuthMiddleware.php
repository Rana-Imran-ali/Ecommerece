<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
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
            try {
                $decrypted = Crypt::decryptString($token);
                $parts = explode('|', $decrypted);

                if (count($parts) >= 2) {
                    $userId = (int) $parts[0];
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
