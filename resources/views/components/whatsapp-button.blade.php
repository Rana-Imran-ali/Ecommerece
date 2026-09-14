@php
    $whatsappPhone = config('whatsapp.support_phone', env('WHATSAPP_SUPPORT_PHONE', '18005550199'));
    // Strip everything except digits
    $cleanPhone = preg_replace('/[^0-9]/', '', $whatsappPhone);
    $defaultMsg = rawurlencode(config('whatsapp.default_message', 'Hello! I have a question regarding an order or product on your store.'));
@endphp

<!-- WhatsApp Floating Action Button -->
<div id="whatsapp-widget" class="fixed bottom-6 right-6 z-50 flex flex-col items-end pointer-events-none font-sans">
    <!-- Chat Popup Card (Initially Hidden) -->
    <div id="whatsapp-popup"
         class="pointer-events-auto hidden mb-3 w-80 sm:w-88 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden transform transition-all duration-200 ease-out origin-bottom-right scale-95 opacity-0">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white font-bold">
                        <!-- WhatsApp SVG -->
                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                        </svg>
                    </div>
                    <!-- Online Pulse Indicator -->
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-emerald-600 rounded-full"></span>
                </div>
                <div>
                    <h3 class="font-bold text-sm leading-tight flex items-center gap-1.5">
                        Customer Support
                    </h3>
                    <p class="text-xs text-emerald-100 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-300 animate-pulse"></span>
                        Typically replies instantly
                    </p>
                </div>
            </div>

            <button type="button"
                    onclick="toggleWhatsAppChat(false)"
                    class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors"
                    aria-label="Close Chat">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Chat Body with WhatsApp styling -->
        <div class="p-4 bg-[#ece5dd] space-y-3 max-h-72 overflow-y-auto" style="background-image: radial-gradient(rgba(0,0,0,0.06) 1px, transparent 0); background-size: 16px 16px;">
            <!-- Incoming Welcome Bubble -->
            <div class="flex items-start space-x-2">
                <div class="bg-white rounded-2xl rounded-tl-sm p-3 shadow-sm max-w-[85%] text-xs text-gray-800 leading-relaxed space-y-1">
                    <p class="font-semibold text-emerald-700 text-[11px]">Support Agent</p>
                    <p>👋 Hi there! Welcome to {{ config('app.name', 'our store') }}. How can we assist you today?</p>
                    <p class="text-[9px] text-gray-400 text-right">Just now</p>
                </div>
            </div>

            <!-- Pre-defined quick chips -->
            <div class="pt-1">
                <p class="text-[11px] font-semibold text-gray-600 mb-1.5">Quick questions:</p>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" onclick="setQuickMessage('Hello! I would like to inquire about my order status.')"
                            class="text-[11px] bg-white hover:bg-emerald-50 text-gray-700 hover:text-emerald-700 px-2.5 py-1 rounded-full border border-gray-200 shadow-2xs transition-colors">
                        📦 Track My Order
                    </button>
                    <button type="button" onclick="setQuickMessage('Hello! Do you have any active discount coupons available?')"
                            class="text-[11px] bg-white hover:bg-emerald-50 text-gray-700 hover:text-emerald-700 px-2.5 py-1 rounded-full border border-gray-200 shadow-2xs transition-colors">
                        🏷️ Ask for Discounts
                    </button>
                    <button type="button" onclick="setQuickMessage('Hello! I have a question about a product before placing an order.')"
                            class="text-[11px] bg-white hover:bg-emerald-50 text-gray-700 hover:text-emerald-700 px-2.5 py-1 rounded-full border border-gray-200 shadow-2xs transition-colors">
                        🛍️ Product Inquiry
                    </button>
                </div>
            </div>
        </div>

        <!-- Chat Input / Send Direct Footer -->
        <div class="p-3 bg-white border-t border-gray-100">
            <form onsubmit="sendWhatsAppDirect(event)" class="flex items-center space-x-2">
                <input type="text"
                       id="whatsapp-custom-msg"
                       placeholder="Type your query..."
                       class="flex-1 text-xs border border-gray-200 rounded-xl px-3 py-2 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-gray-800 placeholder-gray-400 bg-gray-50/50" />
                <button type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white p-2 rounded-xl shadow transition-all duration-150 shrink-0 flex items-center justify-center"
                        title="Send via WhatsApp">
                    <svg class="w-4 h-4 translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
            <div class="mt-2 text-center">
                <a id="whatsapp-direct-link"
                   href="https://wa.me/{{ $cleanPhone }}?text={{ $defaultMsg }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="text-[11px] text-emerald-600 hover:text-emerald-700 font-semibold hover:underline inline-flex items-center gap-1">
                    <span>Or open directly in WhatsApp</span> &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Floating Trigger Button -->
    <div class="pointer-events-auto flex items-center space-x-2">
        <!-- Floating Tooltip / Teaser Badge (auto hides on click) -->
        <div id="whatsapp-teaser"
             onclick="toggleWhatsAppChat()"
             class="cursor-pointer bg-white text-gray-800 px-3 py-1.5 rounded-full shadow-lg border border-gray-200 text-xs font-semibold hidden sm:flex items-center space-x-2 hover:bg-gray-50 transition-all">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <span>Need help? Chat with us</span>
        </div>

        <button type="button"
                id="whatsapp-toggle-btn"
                onclick="toggleWhatsAppChat()"
                class="relative bg-[#25D366] hover:bg-[#20ba59] active:scale-95 text-white p-3.5 sm:p-4 rounded-full shadow-xl hover:shadow-2xl transition-all duration-200 flex items-center justify-center group focus:outline-none focus:ring-4 focus:ring-emerald-300"
                aria-label="Open WhatsApp Chat">
            <!-- Green ping ring -->
            <span class="absolute -inset-0.5 rounded-full bg-emerald-400 opacity-50 group-hover:opacity-75 animate-ping -z-10"></span>

            <!-- WhatsApp Icon -->
            <svg id="whatsapp-icon-chat" class="w-7 h-7 fill-current transition-transform duration-200 group-hover:scale-110" viewBox="0 0 24 24">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12c0 1.891.526 3.662 1.438 5.177L2 22l4.982-1.408A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.2c-1.637 0-3.16-.487-4.437-1.325l-.318-.21-2.96.837.854-2.883-.231-.334A8.16 8.16 0 013.8 12c0-4.521 3.679-8.2 8.2-8.2s8.2 3.679 8.2 8.2-3.679 8.2-8.2 8.2z"/>
            </svg>
            <!-- Close 'X' Icon (shown when card is open) -->
            <svg id="whatsapp-icon-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>

<script>
    const WA_PHONE = '{{ $cleanPhone }}';
    const WA_DEFAULT_MSG = '{{ config('whatsapp.default_message', 'Hello! I have a question regarding an order or product on your store.') }}';

    function toggleWhatsAppChat(forceState) {
        const popup = document.getElementById('whatsapp-popup');
        const iconChat = document.getElementById('whatsapp-icon-chat');
        const iconClose = document.getElementById('whatsapp-icon-close');
        const teaser = document.getElementById('whatsapp-teaser');

        const shouldOpen = typeof forceState === 'boolean' 
            ? forceState 
            : popup.classList.contains('hidden');

        if (shouldOpen) {
            popup.classList.remove('hidden');
            // Animate in
            requestAnimationFrame(() => {
                popup.classList.remove('scale-95', 'opacity-0');
                popup.classList.add('scale-100', 'opacity-100');
            });
            iconChat.classList.add('hidden');
            iconClose.classList.remove('hidden');
            if (teaser) teaser.classList.add('hidden');
            setTimeout(() => {
                const input = document.getElementById('whatsapp-custom-msg');
                if (input) input.focus();
            }, 150);
        } else {
            popup.classList.remove('scale-100', 'opacity-100');
            popup.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                popup.classList.add('hidden');
            }, 180);
            iconChat.classList.remove('hidden');
            iconClose.classList.add('hidden');
        }
    }

    function setQuickMessage(text) {
        const input = document.getElementById('whatsapp-custom-msg');
        if (input) {
            input.value = text;
            sendWhatsAppDirect();
        }
    }

    function sendWhatsAppDirect(e) {
        if (e && e.preventDefault) e.preventDefault();
        const input = document.getElementById('whatsapp-custom-msg');
        let msg = (input && input.value.trim()) ? input.value.trim() : WA_DEFAULT_MSG;

        // If user is currently browsing a product page, append current page URL as context
        if (window.location.pathname.includes('/products/')) {
            msg += "\n\n(Product Page: " + window.location.href + ")";
        }

        const encodedMsg = encodeURIComponent(msg);
        const url = `https://wa.me/${WA_PHONE}?text=${encodedMsg}`;
        window.open(url, '_blank', 'noopener,noreferrer');

        if (input) input.value = '';
        toggleWhatsAppChat(false);
    }
</script>
