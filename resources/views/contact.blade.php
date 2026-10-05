@extends('layouts.app')

@section('title', 'Contact Us - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-12">
    <!-- Header Hero -->
    <div class="relative overflow-hidden bg-gradient-to-br from-navy-975 via-navy-900 to-navy-850 text-white rounded-3xl p-8 sm:p-12 shadow-2xl border border-navy-800">
        <div class="absolute -top-24 -right-24 w-88 h-88 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-2xl relative z-10 space-y-4">
            <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-blue-900/60 text-blue-300 border border-blue-700/50 shadow-inner">
                💬 We're Here to Help
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">Get in Touch With Our Team</h1>
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                Have a question about an order, delivery status, product warranty, or corporate partnership? Send us an inquiry and our support specialists will respond within 24 hours.
            </p>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center space-x-3 shadow-xs">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span class="text-xs sm:text-sm font-bold">{{ session('success') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contact Info Cards (Left Column) -->
        <div class="space-y-6">
            <!-- Email card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-navy-950">Email Support</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Our support inbox is monitored around the clock.</p>
                    <a href="mailto:support@estore.com" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors mt-2 inline-block">
                        support@estore.com
                    </a>
                </div>
            </div>

            <!-- Phone card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-navy-950">Phone &amp; WhatsApp</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Mon – Sat from 9:00 AM to 7:00 PM.</p>
                    <div class="flex items-center space-x-3 mt-2">
                        <a href="tel:18005550199" class="text-xs font-bold text-emerald-700 hover:underline">
                            +1 (800) 555-0199
                        </a>
                        <span class="text-slate-300">|</span>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('whatsapp.support_phone', '18005550199')) }}?text={{ rawurlencode('Hello! I have a question regarding your store.') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center space-x-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 px-3 py-1 rounded-xl transition-all shadow-2xs">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.072-2.193-.55-1.915-.795-3.14-2.753-3.235-2.88-.095-.127-.778-1.034-.778-1.97 0-.936.491-1.396.666-1.587.175-.19.381-.238.508-.238.127 0 .254.001.365.006.118.005.276-.045.431.328.16.386.545 1.332.593 1.43.048.098.08.213.016.341-.064.127-.096.206-.191.318-.095.111-.2.249-.286.334-.095.095-.194.198-.083.389.111.19.493.813 1.058 1.317.728.649 1.341.85 1.531.945.19.095.302.079.413-.048.111-.127.476-.556.603-.746.127-.19.254-.159.429-.095.175.063 1.111.524 1.302.619.19.095.317.143.365.222.048.079.048.46-.096.865z"/>
                            </svg>
                            <span>Chat</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Location card -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-navy-950">Corporate Center</h3>
                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Central Commerce Boulevard<br>Suite 610, Technology Park</p>
                </div>
            </div>
        </div>

        <!-- Contact Form (Right 2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/90 p-8 sm:p-10 shadow-sm">
            <h2 class="text-2xl font-extrabold text-navy-950 tracking-tight mb-1">Send Us a Direct Message</h2>
            <p class="text-xs sm:text-sm text-slate-500 mb-6">Fill in the fields below and our operations team will reply to your verified email.</p>

            <form action="{{ route('contact.submit') }}" method="POST" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-2">Your Name *</label>
                        <input type="text" name="name" id="name" required
                               placeholder="e.g. Alex Morgan"
                               class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label for="email" class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-2">Email Address *</label>
                        <input type="email" name="email" id="email" required
                               placeholder="alex@example.com"
                               class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="subject" class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-2">Subject *</label>
                        <select name="subject" id="subject" class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all cursor-pointer font-medium">
                            <option value="Order Inquiry">Order Inquiry &amp; Tracking</option>
                            <option value="Product Question">Product Specification</option>
                            <option value="Returns & Refunds">Returns &amp; Refund Request</option>
                            <option value="Billing & Payment">Billing &amp; Stripe Checkout</option>
                            <option value="General Feedback">General Partnership / Feedback</option>
                        </select>
                    </div>
                    <div>
                        <label for="order_number" class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-2">Order ID (Optional)</label>
                        <input type="text" name="order_number" id="order_number"
                               placeholder="e.g. #ORD-104"
                               class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label for="message" class="block text-xs font-bold text-navy-950 uppercase tracking-wider mb-2">Your Detailed Inquiry *</label>
                    <textarea name="message" id="message" rows="5" required
                              placeholder="Please describe how we can assist you..."
                              class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all resize-y"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between pt-2 gap-3">
                    <span class="text-xs text-slate-400 font-semibold">Your privacy is protected. No spam guaranteed.</span>
                    <button type="submit" class="px-7 py-3 rounded-xl bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600 hover:brightness-110 text-white font-bold text-xs shadow-md shadow-blue-700/20 transition-all">
                        Send Message &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick FAQ Accordion -->
    <div class="bg-white rounded-3xl border border-slate-200/90 p-8 sm:p-12 shadow-sm">
        <div class="max-w-2xl mx-auto text-center mb-10 space-y-2">
            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Quick Help</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-navy-950 tracking-tight">Frequently Asked Questions</h2>
            <p class="text-xs sm:text-sm text-slate-500">Instant answers to our most common customer inquiries.</p>
        </div>

        <div class="max-w-3xl mx-auto space-y-4">
            <details class="group p-5 rounded-2xl border border-slate-200 bg-slate-50/50 open:bg-white open:border-blue-300 transition-all cursor-pointer">
                <summary class="font-bold text-navy-950 flex justify-between items-center text-sm sm:text-base select-none">
                    <span>How do I track my order status?</span>
                    <span class="text-blue-600 text-lg group-open:rotate-180 transition-transform">▾</span>
                </summary>
                <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    You can track your order at any time under your account's <a href="{{ url('/account#orders') }}" class="text-blue-600 underline font-bold">Orders Dashboard</a>. Each order includes live milestone tracking from confirmation to doorstep fulfillment.
                </p>
            </details>

            <details class="group p-5 rounded-2xl border border-slate-200 bg-slate-50/50 open:bg-white open:border-blue-300 transition-all cursor-pointer">
                <summary class="font-bold text-navy-950 flex justify-between items-center text-sm sm:text-base select-none">
                    <span>What is your return &amp; refund policy?</span>
                    <span class="text-blue-600 text-lg group-open:rotate-180 transition-transform">▾</span>
                </summary>
                <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    We offer a 30-day money-back guarantee on all qualified products. If you are not satisfied, submit a refund or cancellation request directly via your account panel.
                </p>
            </details>

            <details class="group p-5 rounded-2xl border border-slate-200 bg-slate-50/50 open:bg-white open:border-blue-300 transition-all cursor-pointer">
                <summary class="font-bold text-navy-950 flex justify-between items-center text-sm sm:text-base select-none">
                    <span>What payment options are accepted?</span>
                    <span class="text-blue-600 text-lg group-open:rotate-180 transition-transform">▾</span>
                </summary>
                <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    We support Stripe 3D-Secure card payments (Visa, Mastercard, Amex, Discover), Cash on Delivery (COD), and direct bank transfers with receipt verification.
                </p>
            </details>
        </div>
    </div>
</div>
@endsection
