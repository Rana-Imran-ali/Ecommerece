<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    /**
     * Handle incoming chat requests and proxy to Google Gemini API.
     */
    public function chat(Request $request)
    {
        $userMessage = $this->extractUserMessage($request);

        // Validate user message presence
        if (!$userMessage || trim($userMessage) === '') {
            return response()->json([
                'success' => false,
                'message' => 'The message field is required. Please provide a "message" in your request body.',
                'errors'  => [
                    'message' => ['The message field is required.']
                ],
                'help'    => "In Postman: Click the 'Body' tab (not 'Params') -> select 'raw' -> select 'JSON' -> enter {\"message\": \"your question\"}",
                'debug'   => [
                    'content_type'     => $request->header('Content-Type'),
                    'raw_body_content' => $request->getContent(),
                    'received_params'  => $request->all(),
                ],
            ], 422);
        }

        $apiKey = config('services.gemini.api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Gemini API key is not configured in .env or config/services.php.',
            ], 500);
        }

        // Fast Greeting Interceptor: Instant response for standard greetings
        $cleanMsg = strtolower(trim($userMessage, " \t\n\r\0\x0B.!?,"));
        $standardGreetings = ['hello', 'hi', 'hey', 'salam', 'good morning', 'good afternoon', 'good evening', 'start', 'welcome'];
        if (in_array($cleanMsg, $standardGreetings)) {
            return response()->json([
                'success' => true,
                'model'   => 'quick-greeting',
                'message' => "Hello! Welcome to our store. 🛍️\n\n"
                           . "I'm your AI shopping concierge, and I'm here to help you find the perfect items, answer any questions, or assist with shipping and returns.\n\n"
                           . "Just to help you get started:\n"
                           . "• 🚚 Shipping: Fast 2-4 business days.\n"
                           . "• 🔄 Returns: 30-day money-back guarantee.\n"
                           . "• 🏷️ Special Offer: Use coupon code **WELCOME10** at checkout for 10% off your order!\n\n"
                           . "How can I help you today?",
            ]);
        }

        // Fast FAQ Interceptor: Return instant answers (< 5ms) for common store policies
        if (preg_match('/\b(ship|shipping|delivery|arrive|dispatch|track|tracking)\b/i', $cleanMsg)) {
            return response()->json([
                'success' => true,
                'model'   => 'quick-policy',
                'message' => "📦 **Shipping & Delivery Information**:\n\n"
                           . "• Standard delivery takes **2-4 business days**.\n"
                           . "• Orders over **$50** automatically qualify for **Free Standard Shipping**.\n"
                           . "• You can review real-time order status anytime in your [Orders Dashboard](/orders)!",
            ]);
        }

        if (preg_match('/\b(return|refund|exchange|money back|policy)\b/i', $cleanMsg)) {
            return response()->json([
                'success' => true,
                'model'   => 'quick-policy',
                'message' => "🔄 **Hassle-Free 30-Day Returns**:\n\n"
                           . "• We offer a **30-day money-back guarantee** on all items in original condition.\n"
                           . "• Return requests can be submitted right from your order history.\n"
                           . "• Once inspected, refunds are credited back to your original payment method within 3-5 days.",
            ]);
        }

        if (preg_match('/\b(coupon|discount|promo|voucher|code|deal|sale)\b/i', $cleanMsg)) {
            return response()->json([
                'success' => true,
                'model'   => 'quick-policy',
                'message' => "🏷️ **Active Promotions & Discounts**:\n\n"
                           . "• Use code **WELCOME10** at checkout for **10% off** your order!\n"
                           . "• Free shipping on orders over $50.\n"
                           . "• Check our [Shop Page](/shop) for daily featured specials!",
            ]);
        }

        if (preg_match('/\b(contact|support|human|agent|help|phone|email|whatsapp)\b/i', $cleanMsg)) {
            return response()->json([
                'success' => true,
                'model'   => 'quick-policy',
                'message' => "💬 **Need Human Assistance?**\n\n"
                           . "• Click the green **WhatsApp button** in the bottom corner of your screen for direct chat.\n"
                           . "• Or send an inquiry directly through our [Contact Page](/contact).\n"
                           . "• Our support team is available Mon-Fri, 9am - 6pm.",
            ]);
        }

        // ---------------------------------------------------------------
        // SECURE LARAVEL DATABASE FILTERING (NO RAG, NO DIRECT DB ACCESS FOR GEMINI)
        // Laravel analyzes query intent, queries MySQL via Eloquent, and filters exact data.
        // The resulting filtered data is converted to JSON and sent to Gemini.
        // ---------------------------------------------------------------
        $aiContextService = new \App\Services\ProductAiContextService();
        $dbFilteredContext = $aiContextService->resolveContext($userMessage);
        $dbJsonString = json_encode($dbFilteredContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Build Gemini system prompt with the filtered JSON
        $systemPrompt = "You are the intelligent, polite AI shopping concierge for this online store.\n\n"
            . "CRITICAL INSTRUCTIONS & STRICT TRUTHFULNESS RULES:\n"
            . "1. You do NOT have direct database access. The backend Laravel application has queried the database and provided the filtered records in the JSON below.\n"
            . "2. Answer questions based ONLY on the verified database JSON provided below. Never invent, hallucinate, or assume any product, price, discount, or stock quantity.\n"
            . "3. If the user asks how many products we have or asks for a list/menu, use 'total_active_products', 'categories_summary', and 'store_menu' from the JSON.\n"
            . "4. If the customer asks for the price, availability, or details of a specific item, report the exact price and stock status from 'matched_products'.\n"
            . "5. If 'items_found_count' is 0 or no matching item is found in the JSON, politely explain that the item is currently not in our catalog and offer help with our available categories.\n"
            . "6. Keep answers concise, helpful, friendly, and format key prices and details in markdown bold (e.g. **\$49.99**).\n\n"
            . "=== FILTERED DATABASE JSON (FROM LARAVEL) ===\n"
            . $dbJsonString . "\n\n"
            . "=== STORE POLICIES ===\n"
            . "• Shipping: 2-4 business days. Free on orders over \$50.\n"
            . "• Returns: 30-day money-back guarantee.\n"
            . "• Discount code: WELCOME10 for 10% off.\n";

        // --- RESPONSE CACHE ---
        // Cache AI replies keyed by normalised message AND a catalog version hash.
        // The catalog version hash changes when any product is updated/created,
        // preventing stale AI answers about prices or stock from being served.
        $catalogVersion = Cache::remember('catalog_version_hash', now()->addMinutes(5), function () {
            return md5(Product::where('status', 'active')->max('updated_at') . Product::where('status', 'active')->count());
        });
        $cacheKey = 'ai_reply_' . md5(strtolower(trim($userMessage)) . $catalogVersion);
        if (Cache::has($cacheKey)) {
            return response()->json([
                'success' => true,
                'model'   => 'cache',
                'message' => Cache::get($cacheKey),
            ]);
        }

        // Priority models ordered by reliability and speed
        $models = [
            'gemini-1.5-flash',
            'gemini-2.0-flash',
            'gemini-2.5-flash',
            'gemini-3.5-flash-lite',
            'gemini-3.8-flash',
        ];

        $lastError = null;
        $lastStatus = 500;

        foreach ($models as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

                // 10s timeout per model — if Gemini is slow we fail fast and try the next one
                $response = Http::timeout(10)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type'   => 'application/json',
                    ])
                    ->post($url, [
                        'system_instruction' => [
                            'parts' => [
                                ['text' => $systemPrompt],
                            ],
                        ],
                        'contents' => [
                            [
                                'role'  => 'user',
                                'parts' => [
                                    ['text' => $userMessage],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.2,   // lower = factual, precise
                            'maxOutputTokens' => 350,
                        ],
                    ]);

                if ($response->successful()) {
                    $data      = $response->json();
                    $aiMessage = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                    if ($aiMessage) {
                        // Store in cache for 15 minutes
                        Cache::put($cacheKey, $aiMessage, now()->addMinutes(15));

                        return response()->json([
                            'success' => true,
                            'model'   => $model,
                            'message' => $aiMessage,
                        ]);
                    }
                }

                // If this model returned 4xx/5xx, capture error and try next model
                $lastStatus = $response->status();
                $lastError  = $response->json() ?? ['message' => $response->body()];
                Log::warning("Gemini model {$model} returned {$lastStatus}, trying fallback...", ['error' => $lastError]);

            } catch (\Throwable $e) {
                Log::warning("Gemini model {$model} timed out/errored, trying fallback...", ['message' => $e->getMessage()]);
                $lastError = ['message' => $e->getMessage()];
                $lastStatus = 500;
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'All AI models are currently busy. Please try again shortly.',
            'error'   => $lastError,
        ], $lastStatus);
    }


    /**
     * Resilient message extraction supporting JSON, query params, form-data, and raw text.
     */
    protected function extractUserMessage(Request $request): ?string
    {
        $keys = ['message', 'prompt', 'query', 'text', 'content', 'input', 'question', 'q', 'msg'];

        // 1. Direct input / query parameters
        foreach ($keys as $key) {
            $val = $request->input($key) ?? $request->query($key);
            if (is_string($val) && trim($val) !== '') {
                return trim($val);
            }
        }

        // 2. If any parameter was sent in $request->all()
        foreach ($request->all() as $val) {
            if (is_string($val) && trim($val) !== '') {
                return trim($val);
            }
        }

        // 3. Inspect raw request body
        $raw = trim($request->getContent() ?? '');
        if ($raw !== '') {
            // 3a. Try standard JSON decode
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($keys as $key) {
                    if (isset($decoded[$key]) && is_string($decoded[$key]) && trim($decoded[$key]) !== '') {
                        return trim($decoded[$key]);
                    }
                }
                foreach ($decoded as $val) {
                    if (is_string($val) && trim($val) !== '') {
                        return trim($val);
                    }
                }
            }

            // 3b. Regex match for "message": "..." even if JSON has relaxed syntax (single quotes, trailing commas)
            if (preg_match('/["\']?(?:message|prompt|query|text|content|question|q|msg)["\']?\s*[:=]\s*["\']([^"\']+)["\']/is', $raw, $matches)) {
                return trim($matches[1]);
            }

            // 3c. Multipart form-data extraction (if content-type header was mismatched)
            if (preg_match('/name=["\'](?:message|prompt|query|text|content|question|q|msg)["\']\s*(?:[^\r\n]*\r?\n)*\r?\n([^\r\n]+)/is', $raw, $matches)) {
                return trim($matches[1]);
            }

            // 3d. URL-encoded string parse (e.g. message=hello+world)
            parse_str($raw, $parsedUrl);
            if (is_array($parsedUrl)) {
                foreach ($keys as $key) {
                    if (isset($parsedUrl[$key]) && is_string($parsedUrl[$key]) && trim($parsedUrl[$key]) !== '') {
                        return trim($parsedUrl[$key]);
                    }
                }
            }

            // 3e. Plain text body (if not an empty JSON object/array like '{}' or '[]')
            if ($raw !== '{}' && $raw !== '[]' && strlen($raw) < 10000) {
                $stripped = trim($raw, " \t\n\r\0\x0B\"'");
                if ($stripped !== '') {
                    return $stripped;
                }
            }
        }

        return null;
    }
}