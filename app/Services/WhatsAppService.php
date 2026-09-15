<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $accessToken;
    protected string $phoneNumberId;
    protected string $apiVersion;

    public function __construct()
    {
        $this->apiUrl = config('whatsapp.api_url', 'https://graph.facebook.com');
        $this->accessToken = config('whatsapp.access_token', '');
        $this->phoneNumberId = config('whatsapp.phone_number_id', '');
        $this->apiVersion = config('whatsapp.api_version', 'v18.0');
    }

      public function sendTextMessage(
        string $to,
        string $message
    ): array {
        if (
            empty($this->accessToken) ||
            empty($this->phoneNumberId)
        ) {
            throw new RuntimeException(
                'WhatsApp API credentials are not configured.'
            );
        }


           $url = "{$this->apiUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages";
          


               $response = Http::withToken($this->accessToken)
            ->acceptJson()
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'WhatsApp API Error: ' . $response->body()
            );
        }

        return $response->json();
    }

}
