<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        // REAL STORE CATALOG CONTEXT
        // Fetch live categories + product samples from the database.
        // Cached for 10 minutes so every request is fast.
        // ---------------------------------------------------------------
        $storeContext = $this->buildStoreContext();

        // Fast Product-Catalog Interceptor:
        // If the user is asking broadly about available products/categories,
        // reply instantly using the real DB data — no Gemini call needed.
        $isCatalogQuery = preg_match(
            '/\b(what|which|list|show|tell me about|do you (have|sell|carry)|types? of|kind of|products?|categories?|catalog|catalogue|collection|available|range|items?)\b/i',
            $cleanMsg
        );
        $isAskingAboutProducts = preg_match(
            '/\b(products?|items?|categories?|catalog|catalogue|collection|available|sell|have|offer|what do you)\b/i',
            $cleanMsg
        );

        if ($isCatalogQuery && $isAskingAboutProducts && strlen($cleanMsg) < 120) {
            return response()->json([
                'success' => true,
                'model'   => 'quick-catalog',
                'message' => $storeContext['summary'],
            ]);
        }

        // Build the Gemini system prompt enriched with REAL store data
        $systemPrompt = "You are the friendly, knowledgeable AI shopping concierge for this store. "
            . "Answer questions based ONLY on the actual store data below — never make up products or prices.\n\n"
            . "=== LIVE STORE CATALOG ===\n"
            . $storeContext['full']
            . "\n=== STORE POLICIES ===\n"
            . "• Shipping: 2-4 business days. Free on orders over \$50.\n"
            . "• Returns: 30-day money-back guarantee.\n"
            . "• Discount code: WELCOME10 for 10% off.\n\n"
            . "Keep answers concise and helpful. Use markdown bullet points. Do not invent any product that is not listed above.";

        // --- RESPONSE CACHE ---
        // Cache AI replies for 30 minutes keyed by normalised message fingerprint.
        // Repeated or near-identical questions are served in < 1ms (no network call).
        $cacheKey = 'ai_reply_' . md5(strtolower(trim($userMessage)));
        if (Cache::has($cacheKey)) {
            return response()->json([
                'success' => true,
                'model'   => 'cache',
                'message' => Cache::get($cacheKey),
            ]);
        }

        // Priority models ordered by speed & availability
        // gemini-3.5-flash-lite  → fastest, cheapest, great for short chat
        // gemini-3.8-flash       → current GA workhorse, reliable
        $models = [
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
                            'temperature'     => 0.3,   // lower = faster, more deterministic
                            'maxOutputTokens' => 250,   // shorter answers = faster tokens
                        ],
                    ]);

                if ($response->successful()) {
                    $data      = $response->json();
                    $aiMessage = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                    if ($aiMessage) {
                        // Store in cache for 30 minutes so identical questions are instant
                        Cache::put($cacheKey, $aiMessage, now()->addMinutes(30));

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
     * Build a real-data store context string from the live database.
     * Returns two formats:
     *  - 'summary'  : short human-readable catalog overview (for fast interceptor)
     *  - 'full'     : detailed context injected into the Gemini system prompt
     *
     * Results are cached for 10 minutes to keep every request fast.
     */
    protected function buildStoreContext(): array
    {
        return Cache::remember('ai_store_context', now()->addMinutes(10), function () {
            // Load categories with their active, in-stock products
            $categories = Category::with([
                'products' => function ($q) {
                    $q->where('status', 'active')
                      ->where('stock', '>', 0)
                      ->orderBy('price')
                      ->limit(10); // up to 10 products per category in context
                },
            ])->get();

            $summaryLines = [];
            $fullLines    = [];
            $totalProducts = 0;

            foreach ($categories as $category) {
                $products = $category->products;
                if ($products->isEmpty()) {
                    continue;
                }

                $totalProducts += $products->count();
                $priceMin = $products->min('price');
                $priceMax = $products->max('price');

                // Summary line (used by fast interceptor)
                $summaryLines[] = "• **{$category->name}** ({$products->count()} items, \${$priceMin}–\${$priceMax})";

                // Detailed lines for Gemini system prompt
                $fullLines[] = "Category: {$category->name}";
                foreach ($products as $p) {
                    $stockNote = $p->stock <= 5 ? ' [Low Stock]' : '';
                    $fullLines[] = "  - {$p->name} | Price: \${$p->price}{$stockNote}";
                    if ($p->description) {
                        // Include first 100 chars of description for context
                        $desc = mb_substr(strip_tags($p->description), 0, 100);
                        $fullLines[] = "    Description: {$desc}";
                    }
                }
                $fullLines[] = ''; // blank separator between categories
            }

            if (empty($summaryLines)) {
                return [
                    'summary' => "We're currently adding products to our store. Please check back soon or visit our [Shop Page](/shop)!",
                    'full'    => "No active products are currently listed in the store.",
                ];
            }

            $categoryCount = count($summaryLines);
            $summary = "🛍️ **Here's what we currently have in our store** ({$totalProducts} products across {$categoryCount} categories):\n\n"
                . implode("\n", $summaryLines)
                . "\n\nBrowse everything on our [Shop Page](/shop), or ask me about a specific category and I'll share more details!";

            $full = implode("\n", $fullLines);

            return compact('summary', 'full');
        });
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