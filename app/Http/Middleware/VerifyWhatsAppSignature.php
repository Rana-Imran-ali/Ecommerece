<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * VerifyWhatsAppSignature
 *
 * Validates the X-Hub-Signature-256 header on every incoming WhatsApp webhook POST.
 *
 * Meta Cloud API signs the raw request body with your App Secret using HMAC-SHA256
 * and sends the result as:
 *   X-Hub-Signature-256: sha256=<hex_digest>
 *
 * We recompute the same HMAC locally and use hash_equals() (constant-time comparison)
 * to reject forged or tampered requests before they reach the controller.
 *
 * Setup:
 *   1. Set WHATSAPP_APP_SECRET in your .env (from Meta Developer Portal → App → Settings → Basic)
 *   2. Middleware is applied to POST /api/whatsapp/webhook in routes/api.php
 *
 * References:
 *   https://developers.facebook.com/docs/messenger-platform/webhooks#validate-payloads
 */
class VerifyWhatsAppSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appSecret = config('whatsapp.app_secret', '');

        // ── Configuration guard ───────────────────────────────────────────────
        // If no App Secret is set, block ALL webhook traffic.
        // An unconfigured endpoint that accepts unsigned payloads is a security hole.
        if (empty($appSecret)) {
            Log::error('WhatsApp webhook: WHATSAPP_APP_SECRET is not configured. Rejecting request.');
            return response('Webhook not configured.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // ── Signature header presence check ──────────────────────────────────
        $signatureHeader = $request->header('X-Hub-Signature-256');

        if (empty($signatureHeader)) {
            Log::warning('WhatsApp webhook: missing X-Hub-Signature-256 header.', [
                'ip'   => $request->ip(),
                'path' => $request->path(),
            ]);
            return response('Missing signature.', Response::HTTP_FORBIDDEN);
        }

        // ── Format check: must be "sha256=<64 hex chars>" ────────────────────
        if (!str_starts_with($signatureHeader, 'sha256=')) {
            Log::warning('WhatsApp webhook: malformed X-Hub-Signature-256 header.', [
                'header' => $signatureHeader,
                'ip'     => $request->ip(),
            ]);
            return response('Invalid signature format.', Response::HTTP_FORBIDDEN);
        }

        $providedHash = substr($signatureHeader, 7); // strip "sha256="

        // ── HMAC-SHA256 recomputation on the RAW body ─────────────────────────
        // IMPORTANT: must use getContent() (raw bytes), NOT $request->all(),
        // because JSON-decoding alters whitespace and byte order.
        $rawBody       = $request->getContent();
        $expectedHash  = hash_hmac('sha256', $rawBody, $appSecret);

        // ── Constant-time comparison (prevents timing-oracle attacks) ─────────
        if (!hash_equals($expectedHash, $providedHash)) {
            Log::warning('WhatsApp webhook: signature mismatch — possible forged request.', [
                'ip'       => $request->ip(),
                'expected' => substr($expectedHash, 0, 8) . '…', // log prefix only, never the full secret
                'provided' => substr($providedHash,  0, 8) . '…',
            ]);
            return response('Invalid signature.', Response::HTTP_FORBIDDEN);
        }

        // ── Signature valid — pass request to controller ──────────────────────
        return $next($request);
    }
}
