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
    'support_phone' => env('WHATSAPP_SUPPORT_PHONE', '18005550199'),

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
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'ecommerce_whatsapp_verify_token'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
];
