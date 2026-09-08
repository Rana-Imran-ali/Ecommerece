/**
 * Central API Client & Authentication Helper for Backend Testing
 * Features:
 * - Bearer token management
 * - In-flight GET request deduplication
 * - Intelligent client-side caching with TTL
 * - Non-blocking parallel navbar badge count synchronization
 */

const API_TOKEN_KEY = 'ecommerce_auth_token';
const API_USER_KEY = 'ecommerce_auth_user';
const NAV_COUNTS_KEY = 'ecommerce_nav_counts';

// In-flight request tracking for deduplication
const inFlightRequests = new Map();

// Client-side cache for GET requests: key -> { timestamp, data }
const clientCache = new Map();

// Cache TTL configuration in milliseconds
const CACHE_TTL = {
    '/api/categories': 60000,    // 60 seconds
    '/api/products': 25000,      // 25 seconds
    default: 15000               // 15 seconds
};

// Get stored token
function getAuthToken() {
    return localStorage.getItem(API_TOKEN_KEY);
}

// Get stored user info
function getAuthUser() {
    try {
        const user = localStorage.getItem(API_USER_KEY);
        return user ? JSON.parse(user) : null;
    } catch (e) {
        return null;
    }
}

// Store auth token & user data
function setAuthData(token, user) {
    if (token) localStorage.setItem(API_TOKEN_KEY, token);
    if (user) localStorage.setItem(API_USER_KEY, JSON.stringify(user));
    sessionStorage.removeItem(NAV_COUNTS_KEY);
    clearClientCache();
    updateNavAuthUI();
    fetchNavbarCounts(true);
}

// Clear auth data (Logout)
function clearAuth() {
    localStorage.removeItem(API_TOKEN_KEY);
    localStorage.removeItem(API_USER_KEY);
    sessionStorage.removeItem(NAV_COUNTS_KEY);
    clearClientCache();
    updateNavAuthUI();
}

/**
 * Invalidate client cache by URL prefix, or clear all
 */
function clearClientCache(prefix = null) {
    if (!prefix) {
        clientCache.clear();
        return;
    }
    for (const key of clientCache.keys()) {
        if (key.includes(prefix)) {
            clientCache.delete(key);
        }
    }
}

/**
 * Universal apiFetch wrapper with deduplication and client-side caching
 */
async function apiFetch(endpoint, options = {}) {
    const url = endpoint.startsWith('http') ? endpoint : (endpoint.startsWith('/') ? endpoint : `/api/${endpoint}`);
    const method = (options.method || 'GET').toUpperCase();
    const token = getAuthToken();

    const headers = {
        'Accept': 'application/json',
        ...(options.headers || {}),
    };

    if (!(options.body instanceof FormData) && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
    }

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    // Determine cache TTL for GET requests
    const isGet = method === 'GET';
    const bypassCache = options.bypassCache === true;

    // Mutating methods invalidate relevant client cache
    if (!isGet) {
        if (url.includes('/cart')) {
            sessionStorage.removeItem(NAV_COUNTS_KEY);
            clearClientCache('/cart');
        } else if (url.includes('/wishlist')) {
            sessionStorage.removeItem(NAV_COUNTS_KEY);
            clearClientCache('/wishlist');
        } else if (url.includes('/categories')) {
            clearClientCache('/categories');
        } else if (url.includes('/products')) {
            clearClientCache('/products');
        }
    }

    const cacheKey = `${method}:${url}:${token || 'guest'}`;

    // Check client cache for GET requests
    if (isGet && !bypassCache) {
        const cached = clientCache.get(cacheKey);
        let ttl = CACHE_TTL.default;
        for (const [route, routeTtl] of Object.entries(CACHE_TTL)) {
            if (url.startsWith(route)) {
                ttl = routeTtl;
                break;
            }
        }

        if (cached && (Date.now() - cached.timestamp < ttl)) {
            return JSON.parse(JSON.stringify(cached.response));
        }
    }

    // Deduplicate identical in-flight GET requests
    if (isGet && inFlightRequests.has(cacheKey)) {
        return inFlightRequests.get(cacheKey);
    }

    // Dispatch request
    const fetchPromise = (async () => {
        try {
            const response = await fetch(url, {
                ...options,
                headers,
            });

            const data = await response.json().catch(() => ({
                success: false,
                message: response.statusText || 'Unknown server response'
            }));

            if (response.status === 401) {
                clearAuth();
                const protectedPaths = ['/cart', '/wishlist', '/addresses', '/profile', '/checkout', '/orders'];
                if (protectedPaths.some(path => window.location.pathname.startsWith(path))) {
                    window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
                }
            }

            const result = {
                ok: response.ok,
                status: response.status,
                data: data,
            };

            // Cache successful GET responses
            if (isGet && response.ok) {
                clientCache.set(cacheKey, {
                    timestamp: Date.now(),
                    response: result,
                });
            }

            return result;
        } catch (error) {
            console.error('API Request Error:', error);
            return {
                ok: false,
                status: 0,
                data: { success: false, message: 'Network error or backend server is unreachable.' },
            };
        } finally {
            inFlightRequests.delete(cacheKey);
        }
    })();

    if (isGet) {
        inFlightRequests.set(cacheKey, fetchPromise);
    }

    return fetchPromise;
}

/**
 * Render an alert notification into a container
 */
function showAlert(containerId, message, type = 'danger') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const colors = {
        success: 'bg-green-50 border-green-200 text-green-800',
        danger: 'bg-red-50 border-red-200 text-red-800',
        warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
        info: 'bg-blue-50 border-blue-200 text-blue-800',
    };

    const colorClass = colors[type] || colors.info;

    container.innerHTML = `
        <div class="p-3 mb-4 rounded-md border ${colorClass} text-sm flex items-center justify-between">
            <div>${message}</div>
            <button type="button" onclick="this.parentElement.remove()" class="ml-4 font-bold opacity-75 hover:opacity-100">&times;</button>
        </div>
    `;
}

/**
 * Fetch and update live counts for cart and wishlist in parallel, with sessionStorage caching
 */
async function fetchNavbarCounts(force = false) {
    if (!getAuthToken()) {
        updateBadge('nav-cart-badge', 0);
        updateBadge('nav-wishlist-badge', 0);
        sessionStorage.removeItem(NAV_COUNTS_KEY);
        return;
    }

    // Check cached counts to avoid unnecessary HTTP requests on page navigation
    if (!force) {
        try {
            const raw = sessionStorage.getItem(NAV_COUNTS_KEY);
            if (raw) {
                const cached = JSON.parse(raw);
                if (Date.now() - cached.timestamp < 60000) { // 1 min TTL
                    updateBadge('nav-cart-badge', cached.cart || 0);
                    updateBadge('nav-wishlist-badge', cached.wishlist || 0);
                    return;
                }
            }
        } catch (e) {}
    }

    // Fetch in parallel using Promise.allSettled
    try {
        const [cartRes, wishRes] = await Promise.allSettled([
            apiFetch('/api/cart', { bypassCache: force }),
            apiFetch('/api/wishlist', { bypassCache: force })
        ]);

        let cartCount = 0;
        let wishCount = 0;

        if (cartRes.status === 'fulfilled' && cartRes.value.ok) {
            cartCount = cartRes.value.data?.data?.total_items || 0;
            updateBadge('nav-cart-badge', cartCount);
        }

        if (wishRes.status === 'fulfilled' && wishRes.value.ok) {
            wishCount = wishRes.value.data?.data?.total_items || 0;
            updateBadge('nav-wishlist-badge', wishCount);
        }

        sessionStorage.setItem(NAV_COUNTS_KEY, JSON.stringify({
            timestamp: Date.now(),
            cart: cartCount,
            wishlist: wishCount
        }));
    } catch (e) {
        console.warn('Navbar counts synchronization error:', e);
    }
}

function updateBadge(id, count) {
    const elements = document.querySelectorAll(`.${id}`);
    elements.forEach(el => {
        if (count > 0) {
            el.textContent = count;
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });
}

/**
 * Update UI for authenticated vs guest user in the Navbar
 */
function updateNavAuthUI() {
    const user = getAuthUser();
    const token = getAuthToken();

    const guestElements = document.querySelectorAll('.nav-guest-only');
    const authElements = document.querySelectorAll('.nav-auth-only');
    const userNameElements = document.querySelectorAll('.nav-user-name');
    const userInitialElements = document.querySelectorAll('.nav-user-initial');

    if (token && user) {
        guestElements.forEach(el => el.classList.add('hidden'));
        authElements.forEach(el => el.classList.remove('hidden'));
        userNameElements.forEach(el => el.textContent = user.name || 'User');
        userInitialElements.forEach(el => el.textContent = (user.name || 'U').charAt(0).toUpperCase());
    } else {
        guestElements.forEach(el => el.classList.remove('hidden'));
        authElements.forEach(el => el.classList.add('hidden'));
    }
}

// Global logout/signout handler
async function handleSignOut() {
    await apiFetch('/api/logout', { method: 'POST' });
    clearAuth();
    window.location.href = '/login';
}
const handleLogout = handleSignOut;

// Debounce helper
function debounce(func, delay = 300) {
    let timer;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(this, args), delay);
    };
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    updateNavAuthUI();
    fetchNavbarCounts(false);
});
