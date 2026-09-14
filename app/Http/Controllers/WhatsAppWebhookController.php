<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    /**
     * Webhook verification (Meta WhatsApp Cloud API challenge handshake)
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('whatsapp.verify_token', env('WHATSAPP_VERIFY_TOKEN'));

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    /**
     * Handle incoming WhatsApp webhook events (messages, status updates)
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        // Process incoming event / status updates

        return response()->json(['status' => 'success'], 200);
    }
}

