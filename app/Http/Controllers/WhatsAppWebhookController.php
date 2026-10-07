<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
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
        $from   = $message['from']    ?? 'unknown';
        $msgId  = $message['id']      ?? null;
        $type   = $message['type']    ?? 'unknown';

        Log::info('WhatsApp: incoming message', [
            'from'          => $from,
            'type'          => $type,
            'msg_id'        => $msgId,
            'display_phone' => $metadata['display_phone_number'] ?? null,
        ]);

        // Only auto-reply to text messages; media/reactions are acknowledged but not replied to.
        if ($type !== 'text') {
            Log::info("WhatsApp: non-text message type '{$type}' from {$from} — no auto-reply.");
            return;
        }

        $body    = strtolower(trim($message['text']['body'] ?? ''));
        $reply   = $this->resolveAutoReply($body);

        if (!$reply) {
            return; // No matching keyword — stay silent to avoid spam
        }

        // Only send if WhatsApp Cloud API credentials are configured.
        if (empty(config('whatsapp.access_token')) || empty(config('whatsapp.phone_number_id'))) {
            Log::warning('WhatsApp auto-reply skipped: API credentials not configured.');
            return;
        }

        try {
            app(WhatsAppService::class)->sendTextMessage($from, $reply);
            Log::info("WhatsApp: auto-reply sent to {$from} (msg_id: {$msgId})");
        } catch (\Throwable $e) {
            Log::warning("WhatsApp: auto-reply failed for {$from}: " . $e->getMessage());
        }
    }

    /**
     * Resolve a keyword-based auto-reply message.
     * Returns null if no keyword matches (stays silent).
     */
    private function resolveAutoReply(string $body): ?string
    {
        // Greetings
        if (preg_match('/\b(hi|hello|hey|salam|assalam|good morning|good afternoon|start)\b/i', $body)) {
            return "👋 Hello! Welcome to our store. I'm here to help you.\n\n"
                 . "You can ask me about:\n"
                 . "• 🛍️ Products & prices\n"
                 . "• 📦 Order status & tracking\n"
                 . "• 🕐 Store hours & support\n\n"
                 . "Just type your question!";
        }

        // Order tracking
        if (preg_match('/\b(order|tracking|track|delivery|where is|status|shipped|dispatch)\b/i', $body)) {
            return "📦 *Order Status*\n\n"
                 . "To check your order, please log in to your account and visit the *Orders* section:\n"
                 . config('app.url') . "/orders\n\n"
                 . "Need more help? Reply with *support* and a team member will assist you.";
        }

        // Products / prices
        if (preg_match('/\b(product|price|cost|stock|available|buy|shop|catalog|item|items)\b/i', $body)) {
            return "🛍️ *Our Store*\n\n"
                 . "Browse our full catalog at:\n"
                 . config('app.url') . "/shop\n\n"
                 . "You can filter by category, price range, and availability. Let us know if you need help finding something specific!";
        }

        // Returns & refunds
        if (preg_match('/\b(return|refund|exchange|money back|cancel|cancellation)\b/i', $body)) {
            return "🔄 *Returns & Refunds*\n\n"
                 . "• We offer a *30-day money-back guarantee* on all items.\n"
                 . "• To cancel or return an order, go to your Orders page and select Cancel.\n"
                 . "• Card payments are refunded within 5-10 business days.\n\n"
                 . "Need help? Reply with *support*.";
        }

        // Shipping
        if (preg_match('/\b(shipping|ship|deliver|arrival|how long|days)\b/i', $body)) {
            return "🚚 *Shipping Information*\n\n"
                 . "• Standard delivery: *2-4 business days*.\n"
                 . "• Free shipping on orders over *$50*.\n"
                 . "• You'll receive a confirmation email once your order ships.";
        }

        // Coupons / discounts
        if (preg_match('/\b(coupon|discount|promo|code|voucher|offer|deal|sale)\b/i', $body)) {
            return "🏷️ *Active Discounts*\n\n"
                 . "• Use code *WELCOME10* at checkout for *10% off* your first order!\n"
                 . "• Free shipping on orders over $50.\n\n"
                 . "Shop now: " . config('app.url') . "/shop";
        }

        // Contact / human support
        if (preg_match('/\b(support|human|agent|help|contact|call|speak|talk|representative)\b/i', $body)) {
            $supportPhone = config('whatsapp.support_phone');
            return "💬 *Live Support*\n\n"
                 . "Our support team is available *Mon-Fri, 9am – 6pm*.\n"
                 . "• WhatsApp: +{$supportPhone}\n"
                 . "• Contact form: " . config('app.url') . "/contact\n\n"
                 . "We'll get back to you as soon as possible!";
        }

        // Store hours
        if (preg_match('/\b(hours|timing|open|close|when|schedule|working)\b/i', $body)) {
            return "🕐 *Store Hours*\n\n"
                 . "Our online store is open *24/7* — shop anytime!\n\n"
                 . "Customer support is available *Mon–Fri, 9am – 6pm*. ";
        }

        // No keyword matched — stay silent
        return null;
    }

    private function handleStatusUpdate(array $status): void
    {
        $msgId      = $status['id']           ?? null;
        $recipient  = $status['recipient_id'] ?? null;
        $statusCode = $status['status']       ?? null;
        $timestamp  = $status['timestamp']    ?? null;

        // Map status codes to human-readable labels for easier log searching
        $statusLabel = match ($statusCode) {
            'sent'      => 'Sent ✓',
            'delivered' => 'Delivered ✓✓',
            'read'      => 'Read 👁',
            'failed'    => 'Failed ✗',
            default     => $statusCode ?? 'unknown',
        };

        $context = [
            'msg_id'    => $msgId,
            'recipient' => $recipient,
            'status'    => $statusLabel,
            'timestamp' => $timestamp ? date('Y-m-d H:i:s', (int) $timestamp) : null,
        ];

        // Log failures as warnings so they surface in error monitoring
        if ($statusCode === 'failed') {
            $errorInfo = $status['errors'][0] ?? [];
            $context['error_code']    = $errorInfo['code']    ?? null;
            $context['error_message'] = $errorInfo['message'] ?? null;
            Log::warning('WhatsApp: message delivery FAILED', $context);
        } else {
            Log::info('WhatsApp: message status update', $context);
        }
    }
}
