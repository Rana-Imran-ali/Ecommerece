@extends('layouts.app')

@section('title', 'Contact Us - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-12">
    <!-- Header Hero -->
    <div class="bg-gradient-to-r from-gray-900 via-indigo-950 to-purple-950 text-white rounded-3xl p-8 sm:p-12 shadow-lg">
        <div class="max-w-2xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/15 text-indigo-200 mb-3 backdrop-blur-sm">
                💬 We're Here to Help
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Get in Touch With Our Team</h1>
            <p class="mt-2 text-indigo-100 text-sm sm:text-base leading-relaxed">
                Have a question about an order, delivery, product return, or partnership? Send us a message and our support specialists will respond within 24 hours.
            </p>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 flex items-center space-x-3 shadow-sm">
        <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contact Info Cards (Left Column) -->
        <div class="space-y-6">
            <!-- Email card -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Email Support</h3>
                    <p class="text-xs text-gray-500 mt-1">Our support inbox is monitored 24/7.</p>
                    <a href="mailto:support@estore.com" class="text-sm font-semibold text-indigo-600 hover:underline mt-2 inline-block">
                        support@estore.com
                    </a>
                </div>
            </div>

            <!-- Phone card -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Phone & WhatsApp</h3>
                    <p class="text-xs text-gray-500 mt-1">Mon – Fri from 9am to 6pm EST.</p>
                    <a href="tel:+18005550199" class="text-sm font-semibold text-emerald-600 hover:underline mt-2 inline-block">
                        +1 (800) 555-0199
                    </a>
                </div>
            </div>

            <!-- Location card -->
            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Headquarters</h3>
                    <p class="text-xs text-gray-500 mt-1">100 Commerce Boulevard, Suite 400<br>San Francisco, CA 94105, USA</p>
                </div>
            </div>
        </div>

        <!-- Contact Form (Right 2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-200 p-8 sm:p-10 shadow-sm">
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight mb-2">Send Us a Message</h2>
            <p class="text-sm text-gray-500 mb-6">Fill in the fields below and we'll reply to your provided email address.</p>

            <form action="{{ route('contact.submit') }}" method="POST" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Your Name *</label>
                        <input type="text" name="name" id="name" required
                               placeholder="e.g. Alex Morgan"
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Email Address *</label>
                        <input type="email" name="email" id="email" required
                               placeholder="alex@example.com"
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="subject" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Subject *</label>
                        <select name="subject" id="subject" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white">
                            <option value="Order Inquiry">Order Inquiry / Tracking</option>
                            <option value="Product Question">Product Question</option>
                            <option value="Returns & Refunds">Returns & Refunds</option>
                            <option value="Billing & Payment">Billing & Payment</option>
                            <option value="General Feedback">General Feedback</option>
                        </select>
                    </div>
                    <div>
                        <label for="order_number" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Order ID (Optional)</label>
                        <input type="text" name="order_number" id="order_number"
                               placeholder="e.g. #ORD-104"
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label for="message" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Your Message *</label>
                    <textarea name="message" id="message" rows="5" required
                              placeholder="Please describe how we can assist you..."
                              class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all resize-y"></textarea>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <span class="text-xs text-gray-400">All inquiries are kept confidential.</span>
                    <button type="submit" class="px-7 py-3 rounded-xl bg-indigo-600 text-white font-bold text-sm shadow hover:bg-indigo-700 transition-colors">
                        Send Message →
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick FAQ Accordion -->
    <div class="bg-white rounded-3xl border border-gray-200 p-8 sm:p-12 shadow-sm">
        <div class="max-w-2xl mx-auto text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Frequently Asked Questions</h2>
            <p class="text-sm text-gray-500 mt-2">Get quick answers to our most common customer inquiries.</p>
        </div>

        <div class="max-w-3xl mx-auto space-y-4">
            <details class="group p-5 rounded-2xl border border-gray-200 bg-gray-50/50 open:bg-white open:border-indigo-200 transition-all cursor-pointer">
                <summary class="font-bold text-gray-900 flex justify-between items-center text-sm sm:text-base select-none">
                    <span>How do I track my order status?</span>
                    <span class="text-indigo-600 text-lg group-open:rotate-180 transition-transform">▾</span>
                </summary>
                <p class="mt-3 text-sm text-gray-600 leading-relaxed">
                    You can track your order at any time under your account's <a href="{{ url('/orders') }}" class="text-indigo-600 underline font-medium">Orders Page</a>. Each order includes live status updates from pending to shipped and delivered.
                </p>
            </details>

            <details class="group p-5 rounded-2xl border border-gray-200 bg-gray-50/50 open:bg-white open:border-indigo-200 transition-all cursor-pointer">
                <summary class="font-bold text-gray-900 flex justify-between items-center text-sm sm:text-base select-none">
                    <span>What is your return policy?</span>
                    <span class="text-indigo-600 text-lg group-open:rotate-180 transition-transform">▾</span>
                </summary>
                <p class="mt-3 text-sm text-gray-600 leading-relaxed">
                    We offer a 30-day money-back guarantee on all eligible items. Items must be in their original packaging and unused condition.
                </p>
            </details>

            <details class="group p-5 rounded-2xl border border-gray-200 bg-gray-50/50 open:bg-white open:border-indigo-200 transition-all cursor-pointer">
                <summary class="font-bold text-gray-900 flex justify-between items-center text-sm sm:text-base select-none">
                    <span>What payment methods are supported?</span>
                    <span class="text-indigo-600 text-lg group-open:rotate-180 transition-transform">▾</span>
                </summary>
                <p class="mt-3 text-sm text-gray-600 leading-relaxed">
                    We accept Cash on Delivery (COD), Credit/Debit Cards, PayPal, and digital wallets. All transactions are SSL encrypted.
                </p>
            </details>
        </div>
    </div>
</div>
@endsection
