@extends('layouts.app')

@section('title', 'AI Shopping Assistant - ' . config('app.name', 'EStore'))
@section('meta_description', 'Chat with our intelligent AI shopping concierge for product recommendations, orders, and instant help.')

@push('styles')
<style>
    /* Clean, Modern AI Chat Workspace */
    .ai-chat-root {
        --chat-primary: #4f46e5;
        --chat-primary-hover: #4338ca;
        --chat-bg-soft: #f8fafc;
        --chat-border: #e2e8f0;
        --chat-text-dark: #0f172a;
        --chat-text-muted: #64748b;
    }

    .ai-container {
        max-width: 1200px;
        margin: 0 auto;
        height: calc(100vh - 170px);
        min-height: 580px;
        max-height: 820px;
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }

    @media (min-width: 900px) {
        .ai-container {
            grid-template-columns: 1fr 310px;
        }
    }

    /* Main Chat Window Card */
    .ai-main-card {
        background: #ffffff;
        border: 1px solid var(--chat-border);
        border-radius: 1.25rem;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Top Bar */
    .ai-header {
        padding: 1rem 1.5rem;
        background: #ffffff;
        border-bottom: 1px solid var(--chat-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .ai-bot-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .ai-bot-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        position: relative;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
    }

    .ai-bot-avatar svg {
        width: 22px;
        height: 22px;
    }

    .ai-online-badge {
        position: absolute;
        bottom: 1px;
        right: 1px;
        width: 11px;
        height: 11px;
        background: #10b981;
        border: 2px solid #ffffff;
        border-radius: 50%;
    }

    .ai-bot-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--chat-text-dark);
        margin: 0;
        line-height: 1.2;
    }

    .ai-bot-subtitle {
        font-size: 0.75rem;
        color: #10b981;
        font-weight: 600;
        margin: 0.15rem 0 0;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .ai-header-btn {
        padding: 0.45rem 0.85rem;
        border-radius: 0.6rem;
        border: 1px solid var(--chat-border);
        background: #f8fafc;
        color: var(--chat-text-muted);
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s ease;
    }

    .ai-header-btn:hover {
        background: #f1f5f9;
        color: var(--chat-text-dark);
        border-color: #cbd5e1;
    }

    /* Messages Stream */
    .ai-stream {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        background: #f8fafc;
        scroll-behavior: smooth;
    }

    .ai-stream::-webkit-scrollbar {
        width: 6px;
    }

    .ai-stream::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .ai-row {
        display: flex;
        gap: 0.75rem;
        max-width: 82%;
        animation: chat-fade 0.2s ease-out;
    }

    @keyframes chat-fade {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .ai-row.bot {
        align-self: flex-start;
    }

    .ai-row.user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .ai-msg-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.75rem;
        font-weight: 700;
        color: #ffffff;
    }

    .ai-row.bot .ai-msg-avatar {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
    }

    .ai-row.user .ai-msg-avatar {
        background: linear-gradient(135deg, #3b82f6, #06b6d4);
    }

    .ai-bubble-box {
        display: flex;
        flex-direction: column;
    }

    .ai-row.user .ai-bubble-box {
        align-items: flex-end;
    }

    .ai-bubble {
        padding: 0.85rem 1.15rem;
        border-radius: 1.15rem;
        font-size: 0.92rem;
        line-height: 1.6;
        word-break: break-word;
    }

    .ai-row.bot .ai-bubble {
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        border-top-left-radius: 0.25rem;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }

    .ai-row.bot .ai-bubble p {
        margin: 0 0 0.5rem;
    }

    .ai-row.bot .ai-bubble p:last-child {
        margin-bottom: 0;
    }

    .ai-row.bot .ai-bubble ul {
        margin: 0.4rem 0 0.4rem 1.25rem;
        padding: 0;
        list-style-type: disc;
    }

    .ai-row.bot .ai-bubble li {
        margin-bottom: 0.35rem;
    }

    .ai-row.bot .ai-bubble a {
        color: #4f46e5;
        font-weight: 600;
        text-decoration: underline;
    }

    .ai-row.user .ai-bubble {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        color: #ffffff;
        border-top-right-radius: 0.25rem;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }

    .ai-time {
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 0.35rem;
        padding: 0 0.25rem;
    }

    /* Product Cards in Chat */
    .ai-product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 0.75rem;
        margin-top: 0.85rem;
        width: 100%;
    }

    .ai-prod-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.65rem;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .ai-prod-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }

    .ai-prod-img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-radius: 0.5rem;
        background: #f1f5f9;
    }

    .ai-prod-title {
        font-size: 0.8rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ai-prod-price {
        font-size: 0.85rem;
        font-weight: 700;
        color: #4f46e5;
    }

    .ai-prod-btn {
        display: block;
        text-align: center;
        padding: 0.35rem 0.5rem;
        background: #f1f5f9;
        color: #334155;
        border-radius: 0.45rem;
        font-size: 0.72rem;
        font-weight: 600;
        text-decoration: none !important;
        transition: background 0.15s, color 0.15s;
        margin-top: auto;
    }

    .ai-prod-btn:hover {
        background: #4f46e5;
        color: #ffffff;
    }

    /* Typing Dots */
    .ai-typing-row {
        display: none;
        align-self: flex-start;
        align-items: flex-end;
        gap: 0.75rem;
    }

    .ai-typing-row.active {
        display: flex;
    }

    .ai-typing-bubble {
        padding: 0.75rem 1.1rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1.15rem;
        border-top-left-radius: 0.25rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .ai-dot {
        width: 6px;
        height: 6px;
        background: #818cf8;
        border-radius: 50%;
        animation: typing-jump 1.2s infinite ease-in-out;
    }

    .ai-dot:nth-child(2) { animation-delay: 0.2s; }
    .ai-dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typing-jump {
        0%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-6px); }
    }

    /* Bottom Input Bar */
    .ai-input-bar {
        padding: 0.85rem 1.25rem;
        background: #ffffff;
        border-top: 1px solid var(--chat-border);
    }

    .ai-input-wrap {
        display: flex;
        align-items: flex-end;
        gap: 0.75rem;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 1rem;
        padding: 0.5rem 0.75rem 0.5rem 1rem;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .ai-input-wrap:focus-within {
        border-color: #4f46e5;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }

    .ai-textarea {
        flex: 1;
        border: none;
        background: transparent;
        outline: none;
        font-size: 0.92rem;
        line-height: 1.45;
        resize: none;
        max-height: 100px;
        color: var(--chat-text-dark);
        font-family: inherit;
        padding: 0.25rem 0;
    }

    .ai-textarea::placeholder {
        color: #94a3b8;
    }

    .ai-send-btn {
        width: 38px;
        height: 38px;
        border-radius: 0.75rem;
        background: #4f46e5;
        border: none;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: background 0.15s, transform 0.15s;
    }

    .ai-send-btn:hover:not(:disabled) {
        background: #4338ca;
        transform: translateY(-1px);
    }

    .ai-send-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .ai-send-btn svg {
        width: 17px;
        height: 17px;
    }

    .ai-input-hint {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.4rem;
        font-size: 0.72rem;
        color: #94a3b8;
        padding: 0 0.25rem;
    }

    /* RIGHT COLUMN: Chat History Panel */
    .ai-history-card {
        background: #ffffff;
        border: 1px solid var(--chat-border);
        border-radius: 1.25rem;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .ai-history-header {
        padding: 1rem 1.15rem;
        border-bottom: 1px solid var(--chat-border);
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .ai-history-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--chat-text-dark);
    }

    .ai-history-title svg {
        width: 17px;
        height: 17px;
        color: #4f46e5;
    }

    .ai-new-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 0.4rem 0.75rem;
        border-radius: 0.55rem;
        font-size: 0.76rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s;
    }

    .ai-new-btn:hover {
        background: #4338ca;
    }

    .ai-new-btn svg {
        width: 13px;
        height: 13px;
    }

    /* History List Items */
    .ai-history-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .ai-history-list::-webkit-scrollbar {
        width: 5px;
    }

    .ai-history-list::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 999px;
    }

    .ai-history-item {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.15s ease;
        text-align: left;
    }

    .ai-history-item:hover {
        background: #f8fafc;
        border-color: #e2e8f0;
    }

    .ai-history-item.active {
        background: #eef2ff;
        border-color: rgba(79, 70, 229, 0.3);
    }

    .ai-item-icon {
        width: 28px;
        height: 28px;
        border-radius: 0.45rem;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: #64748b;
    }

    .ai-history-item.active .ai-item-icon {
        background: #4f46e5;
        color: #ffffff;
    }

    .ai-item-icon svg {
        width: 14px;
        height: 14px;
    }

    .ai-item-info {
        flex: 1;
        min-width: 0;
    }

    .ai-item-title {
        font-size: 0.8rem;
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }

    .ai-history-item.active .ai-item-title {
        color: #3730a3;
        font-weight: 700;
    }

    .ai-item-meta {
        font-size: 0.68rem;
        color: #94a3b8;
        margin-top: 0.1rem;
    }

    .ai-del-btn {
        width: 24px;
        height: 24px;
        border-radius: 0.4rem;
        border: none;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.15s, color 0.15s, background 0.15s;
        flex-shrink: 0;
    }

    .ai-history-item:hover .ai-del-btn,
    .ai-history-item.active .ai-del-btn {
        opacity: 1;
    }

    .ai-del-btn:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    .ai-del-btn svg {
        width: 13px;
        height: 13px;
    }

    /* History Empty */
    .ai-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1rem;
        text-align: center;
        color: #94a3b8;
        height: 100%;
    }

    .ai-empty-state svg {
        width: 36px;
        height: 36px;
        color: #cbd5e1;
        margin-bottom: 0.6rem;
    }

    .ai-empty-state p {
        font-size: 0.8rem;
        margin: 0;
        line-height: 1.4;
    }

    /* History Footer */
    .ai-history-footer {
        padding: 0.75rem 1rem;
        border-top: 1px solid var(--chat-border);
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .ai-total-count {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 500;
    }

    .ai-clear-btn {
        border: none;
        background: transparent;
        color: #ef4444;
        font-size: 0.72rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.35rem;
        border-radius: 0.35rem;
        transition: background 0.15s;
    }

    .ai-clear-btn:hover {
        background: #fee2e2;
    }
</style>
@endpush

@section('content')
<div class="ai-chat-root">
    <div class="ai-container">
        
        <!-- LEFT: Primary Chat Card -->
        <div class="ai-main-card">
            
            <!-- Chat Topbar -->
            <div class="ai-header">
                <div class="ai-bot-info">
                    <div class="ai-bot-avatar">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="ai-online-badge"></span>
                    </div>
                    <div>
                        <h1 class="ai-bot-title">AI Shopping Concierge</h1>
                        <p class="ai-bot-subtitle">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                            Online • Instant product assistance
                        </p>
                    </div>
                </div>

                <button type="button" class="ai-header-btn" id="ai-reset-view-btn" title="Reset current conversation view">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Reset</span>
                </button>
            </div>

            <!-- Messages Stream -->
            <div class="ai-stream" id="ai-stream">
                <!-- Dynamically rendered messages -->
            </div>

            <!-- Typing indicator -->
            <div class="ai-stream px-6 py-2" style="flex:0; padding-top:0;" id="ai-typing-container">
                <div class="ai-typing-row" id="ai-typing">
                    <div class="ai-msg-avatar" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="ai-typing-bubble">
                        <div class="ai-dot"></div>
                        <div class="ai-dot"></div>
                        <div class="ai-dot"></div>
                    </div>
                </div>
            </div>

            <!-- Input area (Clean, prompt chips removed) -->
            <div class="ai-input-bar">
                <form id="ai-chat-form" onsubmit="return false;">
                    <div class="ai-input-wrap">
                        <textarea 
                            id="ai-user-input" 
                            class="ai-textarea" 
                            rows="1" 
                            placeholder="Ask about products, orders, returns, or discounts..." 
                            autocomplete="off"
                        ></textarea>
                        <button type="submit" class="ai-send-btn" id="ai-submit-btn" disabled aria-label="Send">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="ai-input-hint">
                        <span>Press <kbd class="px-1 py-0.5 bg-gray-100 border border-gray-200 rounded text-gray-500 font-mono text-[10px]">Enter</kbd> to send, <kbd class="px-1 py-0.5 bg-gray-100 border border-gray-200 rounded text-gray-500 font-mono text-[10px]">Shift+Enter</kbd> for new line</span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                            Gemini AI
                        </span>
                    </div>
                </form>
            </div>

        </div>

        <!-- RIGHT: Chat History Panel -->
        <div class="ai-history-card">
            
            <div class="ai-history-header">
                <div class="ai-history-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Chat History</span>
                </div>

                <button type="button" class="ai-new-btn" id="ai-new-chat-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>New Chat</span>
                </button>
            </div>

            <div class="ai-history-list" id="ai-history-list">
                <!-- Dynamically populated history -->
            </div>

            <div class="ai-history-footer">
                <span class="ai-total-count" id="ai-total-count">0 conversations</span>
                <button type="button" class="ai-clear-btn" id="ai-clear-history-btn">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Clear All</span>
                </button>
            </div>

        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY = 'estore_ai_sessions_v2';
    const ACTIVE_KEY  = 'estore_ai_active_session_v2';

    const streamContainer = document.getElementById('ai-stream');
    const typingRow       = document.getElementById('ai-typing');
    const userInput       = document.getElementById('ai-user-input');
    const submitBtn       = document.getElementById('ai-submit-btn');
    const historyList     = document.getElementById('ai-history-list');
    const totalCountLabel = document.getElementById('ai-total-count');
    const newChatBtn      = document.getElementById('ai-new-chat-btn');
    const resetViewBtn    = document.getElementById('ai-reset-view-btn');
    const clearHistoryBtn = document.getElementById('ai-clear-history-btn');

    const DEFAULT_GREETING = "Hello! Welcome to our store. 🛍️\n\nI'm your AI shopping concierge, and I'm here to help you find the perfect items, answer any questions, or assist with shipping and returns.\n\nJust to help you get started:\n* 🚚 **Shipping:** Fast 2-4 business days.\n* 🔄 **Returns:** 30-day money-back guarantee.\n* 🏷️ **Special Offer:** Use coupon code **WELCOME10** at checkout for 10% off your order!\n\nHow can I help you today?";

    // State
    let sessions = loadSessions();
    let currentSessionId = localStorage.getItem(ACTIVE_KEY);

    if (!sessions || sessions.length === 0) {
        const initial = createSessionObject("Welcome Chat", [
            { sender: 'bot', text: DEFAULT_GREETING, time: formatTime(new Date()) }
        ]);
        sessions = [initial];
        currentSessionId = initial.id;
        saveSessions();
    } else if (!sessions.find(s => s.id === currentSessionId)) {
        currentSessionId = sessions[0].id;
    }

    localStorage.setItem(ACTIVE_KEY, currentSessionId);

    // Initial Render
    renderHistoryList();
    renderCurrentMessages();

    // Textarea input auto-grow
    userInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        submitBtn.disabled = !this.value.trim();
    });

    // Enter sends, Shift+Enter makes newline
    userInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    submitBtn.addEventListener('click', sendMessage);

    newChatBtn.addEventListener('click', startNewChat);

    resetViewBtn.addEventListener('click', function () {
        const session = getCurrentSession();
        if (!session) return;
        session.messages = [
            { sender: 'bot', text: DEFAULT_GREETING, time: formatTime(new Date()) }
        ];
        session.title = "New Conversation";
        session.updatedAt = Date.now();
        saveSessions();
        renderHistoryList();
        renderCurrentMessages();
    });

    clearHistoryBtn.addEventListener('click', function () {
        if (confirm("Delete all conversations from history?")) {
            sessions = [];
            startNewChat();
        }
    });

    // -------------------------------------------------------------
    // Core Handlers
    // -------------------------------------------------------------

    function startNewChat() {
        const newSession = createSessionObject("New Conversation", [
            { sender: 'bot', text: DEFAULT_GREETING, time: formatTime(new Date()) }
        ]);
        sessions.unshift(newSession);
        currentSessionId = newSession.id;
        saveSessions();
        renderHistoryList();
        renderCurrentMessages();
        userInput.value = '';
        userInput.style.height = 'auto';
        submitBtn.disabled = true;
        userInput.focus();
    }

    async function sendMessage() {
        const text = userInput.value.trim();
        if (!text) return;

        const session = getCurrentSession();
        if (!session) return;

        // Auto-title if first user message
        const hasUserMsg = session.messages.some(m => m.sender === 'user');
        if (!hasUserMsg) {
            session.title = text.length > 26 ? text.substring(0, 26) + '...' : text;
        }

        const userMsg = {
            sender: 'user',
            text: text,
            time: formatTime(new Date())
        };

        session.messages.push(userMsg);
        session.updatedAt = Date.now();
        appendMessage(userMsg, true);

        userInput.value = '';
        userInput.style.height = 'auto';
        submitBtn.disabled = true;
        saveSessions();
        renderHistoryList();

        // Show typing indicator
        showTyping(true);

        try {
            const aiResult = await fetchAiReply(text);
            showTyping(false);

            const botMsg = {
                sender: 'bot',
                text: aiResult.text,
                products: aiResult.products || null,
                time: formatTime(new Date())
            };

            session.messages.push(botMsg);
            session.updatedAt = Date.now();
            appendMessage(botMsg, true);

            saveSessions();
            renderHistoryList();
        } catch (err) {
            console.error('Chat error:', err);
            showTyping(false);

            const fallbackMsg = {
                sender: 'bot',
                text: "I'm having a momentary connection delay, but you can explore our full product catalog anytime in our Shop section!",
                time: formatTime(new Date())
            };

            session.messages.push(fallbackMsg);
            appendMessage(fallbackMsg, true);
            saveSessions();
        }
    }

    // -------------------------------------------------------------
    // API Call & Product Matcher
    // -------------------------------------------------------------

    async function fetchAiReply(userText) {
        let matchedProducts = null;

        // Check if query is looking for items/products, fetch product cards in parallel
        const productKeywords = ['find', 'search', 'buy', 'product', 'laptop', 'shirt', 'shoe', 'shoes', 'phone', 'dress', 'watch', 'camera', 'headphone', 'look for', 'recommend', 'show me'];
        const isProductQuery = productKeywords.some(kw => userText.toLowerCase().includes(kw));

        if (isProductQuery) {
            try {
                let cleanSearch = userText.toLowerCase();
                productKeywords.forEach(kw => {
                    cleanSearch = cleanSearch.replace(new RegExp('\\b' + kw + '\\b', 'gi'), '');
                });
                cleanSearch = cleanSearch.replace(/(can you|please|i want to|where are|do you have)/gi, '').trim();

                const pRes = await fetch(`/api/products?search=${encodeURIComponent(cleanSearch || userText)}&per_page=3`);
                if (pRes.ok) {
                    const pData = await pRes.json();
                    const list = pData.data || (Array.isArray(pData) ? pData : []);
                    if (list.length > 0) {
                        matchedProducts = list.slice(0, 3).map(p => ({
                            id: p.id,
                            name: p.name,
                            price: p.price,
                            image: p.images && p.images.length > 0 ? (p.images[0].url || p.images[0]) : '/images/placeholder.svg'
                        }));
                    }
                }
            } catch (e) {
                console.warn('Product search failed:', e);
            }
        }

        // Send to Gemini Backend
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('/api/ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ message: userText })
            });

            if (res.ok) {
                const data = await res.json();
                if (data.success && data.message) {
                    return {
                        text: data.message,
                        products: matchedProducts
                    };
                }
            }
        } catch (e) {
            console.warn('Backend chat API failed:', e);
        }

        // Local Fallback if server is completely offline
        return {
            text: "Thank you for reaching out! You can browse our complete collection anytime on our [Shop Page](/shop), or let me know what categories you are exploring!",
            products: matchedProducts
        };
    }

    // -------------------------------------------------------------
    // UI Helpers & Message Rendering
    // -------------------------------------------------------------

    function renderCurrentMessages() {
        streamContainer.innerHTML = '';
        const session = getCurrentSession();
        if (!session || !session.messages) return;

        session.messages.forEach(msg => {
            appendMessage(msg, false);
        });

        scrollToBottom();
    }

    function appendMessage(msg, shouldScroll) {
        const isBot = msg.sender === 'bot';
        const row = document.createElement('div');
        row.className = `ai-row ${isBot ? 'bot' : 'user'}`;

        const avatar = isBot
            ? `<div class="ai-msg-avatar" title="AI Concierge">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
               </div>`
            : `<div class="ai-msg-avatar" title="You">You</div>`;

        // Format markdown safely
        const formatted = parseMarkdown(msg.text);

        // Product Cards HTML
        let productCardsHtml = '';
        if (msg.products && Array.isArray(msg.products) && msg.products.length > 0) {
            productCardsHtml = `
                <div class="ai-product-grid">
                    ${msg.products.map(p => `
                        <div class="ai-prod-card">
                            <img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" class="ai-prod-img" onerror="this.src='/images/placeholder.svg';">
                            <h4 class="ai-prod-title">${escapeHtml(p.name)}</h4>
                            <div class="ai-prod-price">$${parseFloat(p.price || 0).toFixed(2)}</div>
                            <a href="/products/${p.id}" class="ai-prod-btn">View Product →</a>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        row.innerHTML = `
            ${avatar}
            <div class="ai-bubble-box">
                <div class="ai-bubble">
                    ${formatted}
                    ${productCardsHtml}
                </div>
                <span class="ai-time">${escapeHtml(msg.time || '')}</span>
            </div>
        `;

        streamContainer.appendChild(row);

        if (shouldScroll) {
            scrollToBottom();
        }
    }

    function parseMarkdown(raw) {
        if (!raw) return '';
        let escaped = escapeHtml(raw);

        // Bold
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Links [text](url)
        escaped = escaped.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2">$1</a>');

        // Bullet points
        const lines = escaped.split('\n');
        let inList = false;
        let html = '';

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line.startsWith('* ') || line.startsWith('• ') || line.startsWith('- ')) {
                if (!inList) {
                    html += '<ul>';
                    inList = true;
                }
                html += `<li>${line.substring(2)}</li>`;
            } else {
                if (inList) {
                    html += '</ul>';
                    inList = false;
                }
                if (line.length > 0) {
                    html += `<p>${line}</p>`;
                }
            }
        }

        if (inList) {
            html += '</ul>';
        }

        return html;
    }

    function renderHistoryList() {
        historyList.innerHTML = '';
        totalCountLabel.textContent = `${sessions.length} conversation${sessions.length !== 1 ? 's' : ''}`;

        if (sessions.length === 0) {
            historyList.innerHTML = `
                <div class="ai-empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p>No chat history yet.<br>Your chats will be saved here.</p>
                </div>
            `;
            return;
        }

        sessions.forEach(session => {
            const isActive = session.id === currentSessionId;
            const item = document.createElement('div');
            item.className = `ai-history-item ${isActive ? 'active' : ''}`;
            item.dataset.id = session.id;

            const timeLabel = formatRelativeTime(session.updatedAt || Date.now());
            const count = (session.messages || []).length;

            item.innerHTML = `
                <div class="ai-item-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div class="ai-item-info">
                    <span class="ai-item-title" title="${escapeHtml(session.title)}">${escapeHtml(session.title)}</span>
                    <div class="ai-item-meta">${timeLabel} • ${count} msg${count !== 1 ? 's' : ''}</div>
                </div>
                <button type="button" class="ai-del-btn" title="Delete" data-action="delete">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            `;

            item.addEventListener('click', function (e) {
                if (e.target.closest('[data-action="delete"]')) {
                    e.stopPropagation();
                    deleteSession(session.id);
                    return;
                }
                switchSession(session.id);
            });

            historyList.appendChild(item);
        });
    }

    function switchSession(id) {
        if (currentSessionId === id) return;
        currentSessionId = id;
        localStorage.setItem(ACTIVE_KEY, id);
        renderHistoryList();
        renderCurrentMessages();
    }

    function deleteSession(id) {
        sessions = sessions.filter(s => s.id !== id);
        if (currentSessionId === id) {
            if (sessions.length > 0) {
                currentSessionId = sessions[0].id;
            } else {
                startNewChat();
                return;
            }
        }
        localStorage.setItem(ACTIVE_KEY, currentSessionId);
        saveSessions();
        renderHistoryList();
        renderCurrentMessages();
    }

    function getCurrentSession() {
        return sessions.find(s => s.id === currentSessionId);
    }

    function createSessionObject(title, initialMessages = []) {
        return {
            id: 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
            title: title || 'New Conversation',
            messages: initialMessages,
            createdAt: Date.now(),
            updatedAt: Date.now()
        };
    }

    function loadSessions() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function saveSessions() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(sessions));
        } catch (e) {
            console.error('Failed saving sessions', e);
        }
    }

    function showTyping(active) {
        if (active) {
            typingRow.classList.add('active');
            scrollToBottom();
        } else {
            typingRow.classList.remove('active');
        }
    }

    function scrollToBottom() {
        setTimeout(() => {
            streamContainer.scrollTop = streamContainer.scrollHeight;
        }, 30);
    }

    function formatTime(date) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function formatRelativeTime(ts) {
        const diff = Date.now() - ts;
        const mins = Math.floor(diff / 60000);
        if (mins < 1) return 'Just now';
        if (mins < 60) return `${mins}m ago`;
        const hours = Math.floor(mins / 60);
        if (hours < 24) return `${hours}h ago`;
        return new Date(ts).toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endpush
