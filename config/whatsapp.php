<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Support Phone Number
    |--------------------------------------------------------------------------
    |
    | The phone number in international format without + or spaces (e.g. 18005550199 or 923001234567).
    | Used by the frontend floating chat button to open direct WhatsApp conversation.
    |
    */
    'support_phone' => env('WHATSAPP_SUPPORT_PHONE', '923411426679'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Default Greeting Message
    |--------------------------------------------------------------------------
    */
    'default_message' => env('WHATSAPP_DEFAULT_MESSAGE', 'Hello! I have a question regarding an order or product on your store.'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API Webhook / Access Credentials
    |--------------------------------------------------------------------------
    */
    'verify_token'    => env('WHATSAPP_VERIFY_TOKEN', 'ecommerce_whatsapp_verify_token'),
    'access_token'    => env('WHATSAPP_ACCESS_TOKEN', ''),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp App Secret (for X-Hub-Signature-256 webhook verification)
    |--------------------------------------------------------------------------
    |
    | This is the "App Secret" found in Meta Developer Portal → Your App → Settings → Basic.
    | Meta uses it to sign every webhook POST body with HMAC-SHA256.
    | REQUIRED: leave blank only if you intentionally disable signature verification.
    |
    */
    'app_secret' => env('WHATSAPP_APP_SECRET', ''),
];
