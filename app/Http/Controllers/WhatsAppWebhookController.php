<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Webhook verification — Meta challenge-response handshake.
     *
     * Meta sends GET with hub.mode, hub.verify_token, hub.challenge.
     * We verify the token and echo back hub.challenge to confirm ownership.
     * No signature check needed here (no body, no secret).
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('whatsapp.verify_token');

        if ($mode === 'subscribe' && hash_equals((string) $verifyToken, (string) $token)) {
            Log::info('WhatsApp webhook: challenge verified successfully.');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook: verification failed.', [
            'mode'  => $mode,
            'token' => $token ? '(present but wrong)' : '(missing)',
        ]);

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    /**
     * Handle incoming WhatsApp webhook events.
     *
     * At this point the X-Hub-Signature-256 has already been validated by
     * VerifyWhatsAppSignature middleware — the payload is guaranteed authentic.
     */
    public function handleWebhook(Request $request)
    {
        // Payload is authentic — safely decode it
        $payload = $request->json()->all();

        $object = $payload['object'] ?? null;

        if ($object !== 'whatsapp_business_account') {
            // Not a WhatsApp event (unexpected object type) — acknowledge and ignore
            return response()->json(['status' => 'ignored'], 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                $field = $change['field'] ?? null;

                if ($field === 'messages') {
                    // Incoming messages
                    foreach ($value['messages'] ?? [] as $message) {
                        $this->handleIncomingMessage($message, $value['metadata'] ?? []);
                    }

                    // Message status updates (sent / delivered / read / failed)
                    foreach ($value['statuses'] ?? [] as $status) {
                        $this->handleStatusUpdate($status);
                    }
                }
            }
        }

        // Always return 200 so Meta stops retrying
        return response()->json(['status' => 'success'], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function handleIncomingMessage(array $message, array $metadata): void
    {
        $from    = $message['from']    ?? 'unknown';
        $msgId   = $message['id']      ?? null;
        $type    = $message['type']    ?? 'unknown';

        Log::info('WhatsApp: incoming message', [
            'from'    => $from,
            'type'    => $type,
            'msg_id'  => $msgId,
            'display_phone' => $metadata['display_phone_number'] ?? null,
        ]);

        // Add your message handling / auto-reply logic here
    }

    private function handleStatusUpdate(array $status): void
    {
        Log::info('WhatsApp: message status update', [
            'msg_id'    => $status['id']        ?? null,
            'recipient' => $status['recipient_id'] ?? null,
            'status'    => $status['status']    ?? null,
            'timestamp' => $status['timestamp'] ?? null,
        ]);

        // Add your delivery tracking logic here
    }
}
