<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductAiContextService
{
    /**
     * Stop words to filter out when extracting product keywords from natural language queries.
     */
    protected array $stopWords = [
        'what', 'is', 'the', 'price', 'of', 'how', 'much', 'does', 'cost', 'rate',
        'do', 'you', 'have', 'got', 'any', 'available', 'in', 'stock', 'tell', 'me',
        'about', 'can', 'i', 'get', 'buy', 'purchase', 'show', 'list', 'check',
        'find', 'search', 'item', 'items', 'product', 'products', 'please', 'give',
        'for', 'a', 'an', 'and', 'or', 'are', 'there', 'who', 'where', 'which',
        'with', 'to', 'at', 'from', 'this', 'that', 'these', 'those', 'hi', 'hello',
        'hey', 'good', 'day', 'sir', 'madam', 'shop', 'store'
    ];

    /**
     * Analyze user query, filter database records in Laravel, and return structured JSON context.
     * Gemini never accesses the database directly.
     */
    public function resolveContext(string $userMessage): array
    {
        $cleanMessage = strtolower(trim($userMessage));

        // 1. If the user is asking a non-product question (policies, contact, account, etc.),
        //    skip the product DB search entirely and return a general context.
        if ($this->isNonProductQuery($cleanMessage)) {
            return $this->buildGeneralCatalogContext();
        }

        // 2. Detect if the user is asking about total products, count, or menu/catalog list
        if ($this->isCountOrMenuQuery($cleanMessage)) {
            return $this->buildCatalogCountAndMenuContext();
        }

        // 3. Check if the user is inquiring about a specific product, price, or stock
        $productContext = $this->buildProductSpecificContext($userMessage, $cleanMessage);
        if ($productContext !== null) {
            return $productContext;
        }

        // 4. Fallback: general catalog summary context
        return $this->buildGeneralCatalogContext();
    }

    /**
     * Detect non-product queries (FAQ, policies, account, support).
     * These should NOT trigger a product database search.
     */
    protected function isNonProductQuery(string $cleanMessage): bool
    {
        $nonProductPatterns = [
            '/\b(ship|shipping|delivery|arrive|dispatch|track|tracking)\b/i',
            '/\b(return|refund|exchange|money\s*back)\b/i',
            '/\b(contact|support|human|agent|phone|email|whatsapp|help\s*desk)\b/i',
            '/\b(coupon|promo|voucher|discount\s*code|promo\s*code)\b/i',
            '/\b(account|login|register|sign\s*up|sign\s*in|password|forgot)\b/i',
            '/\b(policy|policies|terms|conditions|privacy)\b/i',
            '/\b(payment\s*method|pay\s*with|credit\s*card|debit\s*card|bank\s*transfer|cash\s*on\s*delivery|cod)\b/i',
            '/\b(guarantee|warranty|protect)\b/i',
        ];

        foreach ($nonProductPatterns as $pattern) {
            if (preg_match($pattern, $cleanMessage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if query is asking for total count, full menu, or catalog list.
     */
    protected function isCountOrMenuQuery(string $cleanMessage): bool
    {
        $patterns = [
            '/\bhow\s+many\s+(product|item|good|stock|article|thing)/i',
            '/\btotal\s+(product|item|number\s+of)/i',
            '/\b(list|show|give|display)\s+(me\s+)?(all\s+)?(the\s+)?(product|item|menu|catalog|catalogue|inventory)/i',
            '/\b(menu|catalog|catalogue|product\s+list)\b/i',
            '/\bwhat\s+(all\s+)?(products?|items?)\s+do\s+you\s+(have|sell|carry|offer)/i',
            '/\bwhat\s+do\s+you\s+sell\b/i',
            '/\bhow\s+many\s+do\s+you\s+have\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanMessage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build database context for count & menu inquiries.
     */
    protected function buildCatalogCountAndMenuContext(): array
    {
        $totalProducts = Product::where('status', 'active')->count();

        $categories = Category::withCount(['products' => function ($q) {
            $q->where('status', 'active');
        }])
        ->get()
        ->map(function ($cat) {
            return [
                'category_name' => $cat->name,
                'active_products_count' => (int) $cat->products_count,
            ];
        })
        ->filter(fn($c) => $c['active_products_count'] > 0)
        ->values()
        ->toArray();

        // Sample menu of active products across categories
        $menuItems = Product::with('category')
            ->where('status', 'active')
            ->orderBy('category_id')
            ->orderBy('name')
            ->take(30)
            ->get(['id', 'category_id', 'name', 'price', 'stock'])
            ->map(function ($p) {
                return [
                    'product_name' => $p->name,
                    'category'     => $p->category?->name ?? 'General',
                    'price'        => (float) $p->price,
                    'stock_status' => $p->stock > 0 ? "In Stock ({$p->stock} available)" : "Out of Stock",
                ];
            })
            ->toArray();

        return [
            'query_intent'            => 'catalog_count_and_menu',
            'total_active_products'   => $totalProducts,
            'categories_summary'      => $categories,
            'store_menu'              => $menuItems,
            'instructions_for_ai'     => 'Customer is asking how many products we have or for our menu/catalog. Answer using total_active_products, categories_summary, and list products from store_menu.',
        ];
    }

    /**
     * Build database context for specific product name, price, or stock inquiries.
     */
    protected function buildProductSpecificContext(string $rawMessage, string $cleanMessage): ?array
    {
        // Extract keywords by stripping punctuation and stop words
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanMessage);
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        $filteredKeywords = array_values(array_filter($words, function ($word) {
            return mb_strlen($word) >= 2 && !in_array($word, $this->stopWords, true);
        }));

        // If no meaningful keywords remain, return null to fallback
        if (empty($filteredKeywords)) {
            return null;
        }

        // Search in database: Product name, description, or category name
        $query = Product::with(['category', 'variants'])
            ->where('status', 'active');

        // Check if full cleaned phrase matches directly
        $fullPhrase = implode(' ', $filteredKeywords);

        $query->where(function ($q) use ($filteredKeywords, $fullPhrase) {
            $q->where('name', 'LIKE', "%{$fullPhrase}%");

            foreach ($filteredKeywords as $kw) {
                $q->orWhere('name', 'LIKE', "%{$kw}%")
                  ->orWhere('description', 'LIKE', "%{$kw}%");
            }

            // Also match if user searched for category name
            $q->orWhereHas('category', function ($catQuery) use ($filteredKeywords, $fullPhrase) {
                $catQuery->where('name', 'LIKE', "%{$fullPhrase}%");
                foreach ($filteredKeywords as $kw) {
                    $catQuery->orWhere('name', 'LIKE', "%{$kw}%");
                }
            });
        });

        $matchedProducts = $query->take(8)->get();

        $formattedProducts = $matchedProducts->map(function ($p) {
            $variants = $p->variants->map(function ($v) {
                return [
                    'sku'   => $v->sku,
                    'price' => (float) $v->price,
                    'stock' => (int) $v->stock,
                ];
            })->toArray();

            return [
                'id'           => $p->id,
                'name'         => $p->name,
                'category'     => $p->category?->name ?? 'General',
                'price'        => (float) $p->price,
                'stock'        => (int) $p->stock,
                'stock_status' => $p->stock > 0 ? "In Stock ({$p->stock} units)" : "Out of Stock",
                'description'  => $p->description ? Str::limit(strip_tags($p->description), 120) : null,
                'variants'     => !empty($variants) ? $variants : null,
            ];
        })->toArray();

        return [
            'query_intent'        => 'specific_product_inquiry',
            'extracted_keywords'  => $filteredKeywords,
            'items_found_count'   => count($formattedProducts),
            'matched_products'    => $formattedProducts,
            'instructions_for_ai' => count($formattedProducts) > 0
                ? 'Accurately report the exact price, stock status, and details from matched_products. If multiple items matched, present them clearly.'
                : 'No matching products were found in the database for the given search keywords. Politely inform the customer that the product is currently not in our catalog and offer help with other items.',
        ];
    }

    /**
     * Fallback general catalog overview.
     */
    protected function buildGeneralCatalogContext(): array
    {
        $totalProducts = Product::where('status', 'active')->count();

        $categories = Category::withCount(['products' => function ($q) {
            $q->where('status', 'active');
        }])
        ->get(['id', 'name'])
        ->map(function ($cat) {
            return [
                'category_name' => $cat->name,
                'product_count' => (int) $cat->products_count,
            ];
        })
        ->toArray();

        $featured = Product::where('status', 'active')
            ->take(10)
            ->get(['name', 'price', 'stock'])
            ->map(function ($p) {
                return [
                    'name'  => $p->name,
                    'price' => (float) $p->price,
                    'stock' => $p->stock > 0 ? 'In Stock' : 'Out of Stock',

                ];
            })
            ->toArray();

        return [
            'query_intent'          => 'general_store_inquiry',
            'total_active_products' => $totalProducts,
            'available_categories'  => $categories,
            'featured_products'     => $featured,
            'instructions_for_ai'   => 'Answer store related questions based strictly on the available catalog data provided above.',
        ];
    }
}
 