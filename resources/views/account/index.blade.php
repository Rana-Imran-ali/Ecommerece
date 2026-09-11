@extends('layouts.app')

@section('title', 'My Account – ' . config('app.name', 'EStore'))

@push('styles')
<style>
    :root {
        --accent: #6366f1;
        --accent-dark: #4f46e5;
        --accent-light: #eef2ff;
        --danger: #ef4444;
        --success: #10b981;
        --warn: #f59e0b;
    }

    /* ── Layout ─────────────────────────────────────────── */
    .account-wrap { display: flex; gap: 1.5rem; align-items: flex-start; }

    /* ── Sidebar ─────────────────────────────────────────── */
    .account-sidebar {
        width: 240px;
        flex-shrink: 0;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        position: sticky;
        top: 76px;
    }
    .sidebar-header {
        background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
        padding: 1.5rem 1.25rem;
        text-align: center;
    }
    .sidebar-avatar {
        width: 72px; height: 72px;
        border-radius: 50%;
        border: 3px solid rgba(255,255,255,.4);
        object-fit: cover;
        margin: 0 auto 0.75rem;
        display: block;
        background: rgba(255,255,255,.2);
    }
    .sidebar-name { color:#fff; font-weight:700; font-size:.95rem; margin-bottom:.15rem; }
    .sidebar-email { color:rgba(255,255,255,.75); font-size:.72rem; word-break:break-all; }
    .sidebar-nav { padding: .5rem 0; }
    .sidebar-nav-item {
        display: flex; align-items: center; gap: .65rem;
        padding: .65rem 1.25rem;
        font-size: .83rem; font-weight: 500; color: #4b5563;
        cursor: pointer; transition: all .15s;
        border-left: 3px solid transparent;
        text-decoration: none;
    }
    .sidebar-nav-item:hover { background: var(--accent-light); color: var(--accent); }
    .sidebar-nav-item.active { background: var(--accent-light); color: var(--accent); border-left-color: var(--accent); font-weight:600; }
    .sidebar-nav-item svg { width: 16px; height: 16px; flex-shrink: 0; }
    .sidebar-divider { height: 1px; background: #f3f4f6; margin: .4rem .75rem; }
    .sidebar-logout { color: #ef4444 !important; }
    .sidebar-logout:hover { background: #fef2f2 !important; }

    /* ── Main panel ─────────────────────────────────────── */
    .account-main { flex: 1; min-width: 0; }
    .account-section { display: none; }
    .account-section.active { display: block; }

    /* ── Cards ───────────────────────────────────────────── */
    .card {
        background: #fff; border: 1px solid #e5e7eb;
        border-radius: 14px; padding: 1.75rem;
        margin-bottom: 1.25rem;
    }
    .card-title {
        font-size: 1rem; font-weight: 700; color: #111827;
        padding-bottom: .85rem; margin-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex; align-items: center; gap: .5rem;
    }
    .card-title svg { width:18px; height:18px; color: var(--accent); }

    /* ── Overview stats ──────────────────────────────────── */
    .stat-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; }
    .stat-card {
        border-radius: 12px; padding: 1.25rem 1rem; text-align: center;
        background: var(--accent-light);
    }
    .stat-num { font-size: 1.75rem; font-weight: 800; color: var(--accent); }
    .stat-label { font-size: .72rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-top: .2rem; }

    /* ── Avatar upload ───────────────────────────────────── */
    .avatar-upload-area {
        display: flex; align-items: center; gap: 1.25rem;
        padding: 1rem; background: #f9fafb; border-radius: 10px;
        border: 1px dashed #d1d5db; margin-bottom: 1rem; cursor: pointer;
    }
    .avatar-preview {
        width: 72px; height: 72px; border-radius: 50%;
        object-fit: cover; border: 2px solid #e5e7eb; flex-shrink:0;
    }
    .avatar-upload-label { font-size: .8rem; color: #6b7280; }
    .avatar-upload-label strong { display:block; color: var(--accent); font-size: .85rem; }

    /* ── Form controls ───────────────────────────────────── */
    .form-group { margin-bottom: 1rem; }
    .form-label {
        display: block; font-size: .72rem; font-weight: 700;
        color: #374151; text-transform: uppercase; letter-spacing: .04em;
        margin-bottom: .35rem;
    }
    .form-control {
        width: 100%; padding: .6rem .85rem;
        border: 1px solid #d1d5db; border-radius: 8px;
        font-size: .875rem; color: #111827; outline: none;
        transition: border-color .15s, box-shadow .15s;
        background: #fff;
    }
    .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

    /* ── Buttons ─────────────────────────────────────────── */
    .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: .4rem; padding: .58rem 1.2rem;
        font-size: .83rem; font-weight: 600; border-radius: 8px;
        cursor: pointer; transition: all .15s; border: none; outline: none;
    }
    .btn-primary { background: var(--accent); color: #fff; }
    .btn-primary:hover { background: var(--accent-dark); }
    .btn-primary:disabled { opacity:.6; cursor:not-allowed; }
    .btn-outline { background: #fff; color: #374151; border: 1px solid #d1d5db; }
    .btn-outline:hover { background: #f9fafb; }
    .btn-danger { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
    .btn-danger:hover { background: #fee2e2; }
    .btn-danger-solid { background: var(--danger); color: #fff; }
    .btn-danger-solid:hover { background: #dc2626; }
    .btn-sm { padding: .38rem .85rem; font-size: .78rem; }
    .btn-full { width: 100%; }

    /* ── Alert ───────────────────────────────────────────── */
    .alert {
        padding: .7rem 1rem; border-radius: 8px; font-size: .83rem;
        font-weight: 500; margin-bottom: 1rem; display: flex; align-items:center; gap:.5rem;
    }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .alert-info    { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

    /* ── Badge ───────────────────────────────────────────── */
    .badge {
        display: inline-flex; align-items:center;
        padding: .2rem .6rem; border-radius: 999px;
        font-size: .7rem; font-weight: 700; text-transform: uppercase;
    }
    .badge-pending          { background:#fef9c3; color:#854d0e; }
    .badge-processing       { background:#dbeafe; color:#1e40af; }
    .badge-out-for-delivery { background:#f3e8ff; color:#6b21a8; }
    .badge-shipped          { background:#e0e7ff; color:#3730a3; }
    .badge-delivered        { background:#d1fae5; color:#065f46; }
    .badge-completed        { background:#dcfce7; color:#14532d; }
    .badge-cancelled        { background:#fee2e2; color:#991b1b; }

    /* ── Order card ──────────────────────────────────────── */
    .order-card {
        border: 1px solid #e5e7eb; border-radius: 10px;
        padding: 1rem 1.25rem; margin-bottom: .75rem;
        transition: border-color .15s;
    }
    .order-card:hover { border-color: var(--accent); }

    /* ── Wishlist grid ───────────────────────────────────── */
    .wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px,1fr)); gap: 1rem; }
    .wishlist-item {
        border: 1px solid #e5e7eb; border-radius: 10px;
        overflow: hidden; position: relative; transition: box-shadow .15s;
    }
    .wishlist-item:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
    .wishlist-item img { width:100%; height:140px; object-fit:cover; }
    .wishlist-item-body { padding: .65rem .75rem; }
    .wishlist-remove {
        position: absolute; top:.5rem; right:.5rem;
        background: rgba(255,255,255,.9); border-radius: 50%;
        width:26px; height:26px; display:flex; align-items:center;
        justify-content:center; cursor:pointer; border:none;
        color: #ef4444; transition: background .15s;
    }
    .wishlist-remove:hover { background: #fee2e2; }

    /* ── Address card ────────────────────────────────────── */
    .address-card {
        border: 1px solid #e5e7eb; border-radius: 10px;
        padding: 1rem 1.25rem; margin-bottom: .75rem;
        display:flex; align-items:flex-start; justify-content:space-between;
        gap: 1rem; transition: border-color .15s;
    }
    .address-card.default { border-color: var(--accent); background: var(--accent-light); }
    .address-card .address-actions { display:flex; gap:.4rem; flex-shrink:0; }

    /* ── Password strength ───────────────────────────────── */
    .strength-bar { height: 4px; border-radius: 4px; background: #e5e7eb; margin-top: .35rem; }
    .strength-bar-fill { height: 100%; border-radius: 4px; transition: width .3s, background .3s; width:0; }

    /* ── Modal ───────────────────────────────────────────── */
    .modal-backdrop {
        position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000;
        display:none; align-items:center; justify-content:center; padding:1rem;
    }
    .modal-backdrop.open { display:flex; }
    .modal-box {
        background:#fff; border-radius:14px; padding:2rem;
        max-width:440px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.2);
        animation: modalIn .2s ease;
    }
    @keyframes modalIn { from{transform:scale(.95);opacity:0} to{transform:scale(1);opacity:1} }
    .modal-title { font-size:1.1rem; font-weight:700; color:#111827; margin-bottom:.35rem; }
    .modal-desc  { font-size:.83rem; color:#6b7280; margin-bottom:1.25rem; }

    /* ── Tabs (mobile) ───────────────────────────────────── */
    .mobile-tabs {
        display:none; overflow-x:auto; gap:.5rem; padding:.75rem 0;
        border-bottom:1px solid #e5e7eb; margin-bottom:1rem; scrollbar-width:none;
    }
    .mobile-tabs::-webkit-scrollbar { display:none; }
    .mobile-tab {
        white-space:nowrap; padding:.45rem .9rem; border-radius:999px;
        font-size:.78rem; font-weight:600; cursor:pointer;
        background:#f3f4f6; color:#374151; border:none; transition:all .15s;
    }
    .mobile-tab.active { background: var(--accent); color:#fff; }

    /* ── Responsive ──────────────────────────────────────── */
    @media(max-width:768px) {
        .account-wrap { flex-direction:column; }
        .account-sidebar { width:100%; position:static; display:none; }
        .mobile-tabs { display:flex; }
        .stat-grid { grid-template-columns:1fr 1fr; }
        .form-grid-2 { grid-template-columns:1fr; }
    }
    @media(max-width:480px) {
        .stat-grid { grid-template-columns:1fr; }
    }

    /* ── Spinner ─────────────────────────────────────────── */
    .spinner {
        display:inline-block; width:18px; height:18px;
        border:2px solid rgba(255,255,255,.4); border-top-color:#fff;
        border-radius:50%; animation:spin .7s linear infinite;
    }
    @keyframes spin { to { transform:rotate(360deg); } }

    /* ── Empty state ─────────────────────────────────────── */
    .empty-state { text-align:center; padding:3rem 1rem; }
    .empty-state svg { width:48px; height:48px; color:#d1d5db; margin:0 auto .75rem; }
    .empty-state h3 { font-size:1rem; font-weight:700; color:#374151; margin-bottom:.35rem; }
    .empty-state p  { font-size:.83rem; color:#9ca3af; }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Page header --}}
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-gray-900">My Account</h1>
        <p class="text-sm text-gray-500">Manage your profile, orders, and preferences.</p>
    </div>

    {{-- Global alert --}}
    <div id="account-alert"></div>

    {{-- Mobile tabs --}}
    <div class="mobile-tabs" id="mobile-tabs">
        <button class="mobile-tab active" data-section="overview">Overview</button>
        <button class="mobile-tab" data-section="profile">Profile</button>
        <button class="mobile-tab" data-section="password">Password</button>
        <button class="mobile-tab" data-section="orders">Orders</button>
        <button class="mobile-tab" data-section="wishlist">Wishlist</button>
        <button class="mobile-tab" data-section="addresses">Addresses</button>
        <button class="mobile-tab" data-section="settings">Settings</button>
    </div>

    <div class="account-wrap">

        {{-- ── Sidebar ────────────────────────────────────────── --}}
        <aside class="account-sidebar" id="account-sidebar">
            <div class="sidebar-header">
                <img id="sb-avatar" src="" alt="Profile picture" class="sidebar-avatar">
                <div class="sidebar-name" id="sb-name">Loading…</div>
                <div class="sidebar-email" id="sb-email"></div>
            </div>
            <nav class="sidebar-nav">
                <a class="sidebar-nav-item active" data-section="overview">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Overview
                </a>
                <a class="sidebar-nav-item" data-section="profile">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    My Profile
                </a>
                <a class="sidebar-nav-item" data-section="password">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Change Password
                </a>
                <div class="sidebar-divider"></div>
                <a class="sidebar-nav-item" data-section="orders">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    My Orders
                    <span class="ml-auto text-xs font-bold bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full" id="sb-orders-count">0</span>
                </a>
                <a class="sidebar-nav-item" data-section="wishlist">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    Wishlist
                    <span class="ml-auto text-xs font-bold bg-red-100 text-red-600 px-2 py-0.5 rounded-full" id="sb-wish-count">0</span>
                </a>
                <a class="sidebar-nav-item" data-section="addresses">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Addresses
                </a>
                <div class="sidebar-divider"></div>
                <a class="sidebar-nav-item" data-section="settings">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                    Account Settings
                </a>
                <a class="sidebar-nav-item sidebar-logout" id="sidebar-logout-btn" style="cursor:pointer;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign Out
                </a>
            </nav>
        </aside>

        {{-- ── Main panel ───────────────────────────────────── --}}
        <main class="account-main">

            {{-- ══ OVERVIEW ════════════════════════════════════ --}}
            <div class="account-section active" id="section-overview">
                <div class="card">
                    <div class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Account Overview
                    </div>

                    <div class="flex items-center gap-4 mb-5 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl border border-indigo-100">
                        <img id="ov-avatar" src="" alt="" class="w-16 h-16 rounded-full object-cover border-2 border-indigo-200">
                        <div>
                            <div class="text-lg font-bold text-gray-900" id="ov-name">—</div>
                            <div class="text-sm text-gray-500" id="ov-email">—</div>
                            <div class="text-xs text-gray-400 mt-1" id="ov-since">Member since —</div>
                        </div>
                        <button class="ml-auto btn btn-outline btn-sm" onclick="switchSection('profile')">Edit Profile</button>
                    </div>

                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-num" id="ov-orders">—</div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card" style="background:#fdf4ff;">
                            <div class="stat-num" style="color:#9333ea;" id="ov-wishlist">—</div>
                            <div class="stat-label">Wishlist Items</div>
                        </div>
                        <div class="stat-card" style="background:#f0fdf4;">
                            <div class="stat-num" style="color:#16a34a;" id="ov-addresses">—</div>
                            <div class="stat-label">Saved Addresses</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Recent Orders
                    </div>
                    <div id="ov-recent-orders">
                        <div class="text-center py-8 text-gray-400 text-sm">Loading recent orders…</div>
                    </div>
                    <div class="text-center mt-2">
                        <button class="btn btn-outline btn-sm" onclick="switchSection('orders')">View All Orders →</button>
                    </div>
                </div>
            </div>

            {{-- ══ MY PROFILE ══════════════════════════════════ --}}
            <div class="account-section" id="section-profile">
                <div class="card">
                    <div class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Personal Information
                    </div>
                    <div id="profile-alert"></div>

                    {{-- Avatar Upload --}}
                    <input type="file" id="avatar-input" accept="image/*" class="hidden">
                    <div class="avatar-upload-area" onclick="document.getElementById('avatar-input').click()">
                        <img id="profile-avatar-preview" src="" alt="Your avatar" class="avatar-preview">
                        <div class="avatar-upload-label">
                            <strong>Click to change profile picture</strong>
                            JPG, PNG, GIF or WebP · Max 2 MB
                        </div>
                        <svg class="ml-auto w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>

                    <form id="profile-form" onsubmit="handleUpdateProfile(event)">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label" for="prof-name">Full Name</label>
                                <input type="text" id="prof-name" class="form-control" required placeholder="Your full name">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="prof-email">Email Address</label>
                                <input type="email" id="prof-email" class="form-control" required placeholder="you@example.com">
                            </div>
                        </div>
                        <div class="flex gap-3 mt-1">
                            <button type="submit" id="save-profile-btn" class="btn btn-primary">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ══ CHANGE PASSWORD ══════════════════════════════ --}}
            <div class="account-section" id="section-password">
                <div class="card">
                    <div class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Change Password
                    </div>
                    <div id="password-alert"></div>
                    <form id="password-form" onsubmit="handleUpdatePassword(event)" style="max-width:440px;">
                        <div class="form-group">
                            <label class="form-label" for="curr-pass">Current Password</label>
                            <input type="password" id="curr-pass" class="form-control" required placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="new-pass">New Password <span class="text-gray-400 normal-case font-normal">(min 6 chars)</span></label>
                            <input type="password" id="new-pass" class="form-control" required minlength="6" placeholder="••••••••" oninput="updateStrength(this.value)">
                            <div class="strength-bar mt-2"><div class="strength-bar-fill" id="strength-fill"></div></div>
                            <div class="text-xs mt-1 text-gray-400" id="strength-label"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="conf-pass">Confirm New Password</label>
                            <input type="password" id="conf-pass" class="form-control" required minlength="6" placeholder="••••••••">
                        </div>
                        <button type="submit" id="save-pass-btn" class="btn btn-primary">Update Password</button>
                    </form>
                </div>
            </div>

            {{-- ══ MY ORDERS ════════════════════════════════════ --}}
            <div class="account-section" id="section-orders">
                <div class="card">
                    <div class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Order History
                    </div>
                    <div id="orders-alert"></div>
                    <div id="orders-container">
                        <div class="text-center py-8 text-gray-400 text-sm">Loading orders…</div>
                    </div>
                    <div id="orders-pagination" class="flex justify-center gap-2 mt-4"></div>
                </div>
            </div>

            {{-- ══ WISHLIST ══════════════════════════════════════ --}}
            <div class="account-section" id="section-wishlist">
                <div class="card">
                    <div class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        My Wishlist
                        <button id="clear-wishlist-btn" class="ml-auto btn btn-outline btn-sm" onclick="clearWishlist()" style="display:none">Clear All</button>
                    </div>
                    <div id="wishlist-alert"></div>
                    <div id="wishlist-container" class="wishlist-grid">
                        <div class="col-span-full text-center py-8 text-gray-400 text-sm">Loading wishlist…</div>
                    </div>
                </div>
            </div>

            {{-- ══ ADDRESSES ════════════════════════════════════ --}}
            <div class="account-section" id="section-addresses">
                <div class="card">
                    <div class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Shipping & Billing Addresses
                        <button class="ml-auto btn btn-primary btn-sm" onclick="openAddressModal()">+ Add New</button>
                    </div>
                    <div id="addresses-alert"></div>
                    <div id="addresses-container">
                        <div class="text-center py-8 text-gray-400 text-sm">Loading addresses…</div>
                    </div>
                </div>
            </div>

            {{-- ══ ACCOUNT SETTINGS ══════════════════════════════ --}}
            <div class="account-section" id="section-settings">
                <div class="card">
                    <div class="card-title">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                        Account Settings
                    </div>

                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 mb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold text-sm text-gray-800">Secure Logout</div>
                                <div class="text-xs text-gray-500 mt-0.5">Log out and revoke your current session token.</div>
                            </div>
                            <button class="btn btn-outline btn-sm" onclick="handleSecureLogout()">Sign Out</button>
                        </div>
                    </div>

                    <div class="p-4 bg-red-50 rounded-xl border border-red-200">
                        <div class="font-semibold text-sm text-red-700 mb-1">⚠ Danger Zone</div>
                        <p class="text-xs text-red-500 mb-3">
                            Permanently delete your account and all associated data. This action is irreversible.
                        </p>
                        <button class="btn btn-danger btn-sm" onclick="openDeleteModal()">Delete My Account</button>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

{{-- ── Address Modal ─────────────────────────────────────────── --}}
<div class="modal-backdrop" id="address-modal">
    <div class="modal-box" style="max-width:520px;">
        <div class="modal-title" id="addr-modal-title">Add New Address</div>
        <div class="modal-desc" id="addr-modal-desc">Fill in the delivery address details below.</div>
        <form id="address-form" onsubmit="handleSaveAddress(event)">
            <input type="hidden" id="addr-id">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="addr-name">Full Name</label>
                    <input type="text" id="addr-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="addr-phone">Phone Number</label>
                    <input type="text" id="addr-phone" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="addr-line1">Address Line 1</label>
                <input type="text" id="addr-line1" class="form-control" required placeholder="Street, house/apartment number">
            </div>
            <div class="form-group">
                <label class="form-label" for="addr-line2">Address Line 2 <span class="text-gray-400 normal-case font-normal">(optional)</span></label>
                <input type="text" id="addr-line2" class="form-control" placeholder="Floor, suite, etc.">
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="addr-city">City</label>
                    <input type="text" id="addr-city" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="addr-state">State / Province</label>
                    <input type="text" id="addr-state" class="form-control" required>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="addr-postal">Postal Code</label>
                    <input type="text" id="addr-postal" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="addr-country">Country</label>
                    <input type="text" id="addr-country" class="form-control" required placeholder="e.g. Pakistan">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm mb-4 cursor-pointer">
                <input type="checkbox" id="addr-default" class="rounded">
                <span class="text-gray-700 font-medium">Set as default address</span>
            </label>
            <div class="flex gap-3">
                <button type="submit" id="save-addr-btn" class="btn btn-primary">Save Address</button>
                <button type="button" class="btn btn-outline" onclick="closeAddressModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Delete Account Modal ──────────────────────────────────── --}}
<div class="modal-backdrop" id="delete-modal">
    <div class="modal-box">
        <div class="modal-title" style="color:#ef4444;">🗑 Delete Account</div>
        <div class="modal-desc">
            This will permanently delete your account, orders, wishlist, and all personal data. This action <strong>cannot be undone</strong>.
        </div>
        <div id="delete-alert"></div>
        <div class="form-group">
            <label class="form-label" for="delete-pass">Confirm your password</label>
            <input type="password" id="delete-pass" class="form-control" placeholder="••••••••">
        </div>
        <div class="flex gap-3">
            <button class="btn btn-danger-solid" id="confirm-delete-btn" onclick="handleDeleteAccount()">Yes, Delete Everything</button>
            <button class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/* ═══════════════════════════════════════════════════════════════
   STATE
═══════════════════════════════════════════════════════════════ */
let currentUser = null;
let ordersPage = 1;
let avatarFile = null;

/* ═══════════════════════════════════════════════════════════════
   INIT – auth gate
═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {
    const isAuth = (typeof window !== 'undefined' && window.IS_AUTHENTICATED === true) || !!getAuthToken();
    if (!isAuth) {
        window.location.href = '/login?redirect=/account';
        return;
    }
    await loadUserData();

    // Wire up sidebar & mobile tab click handlers
    document.querySelectorAll('[data-section]').forEach(el => {
        el.addEventListener('click', () => switchSection(el.dataset.section));
    });
    document.getElementById('sidebar-logout-btn')?.addEventListener('click', handleSecureLogout);
    document.getElementById('avatar-input')?.addEventListener('change', onAvatarSelected);

    // Support direct navigation to tabs via URL hash or sessionStorage
    const hash = window.location.hash.replace('#', '');
    const initialTab = hash || sessionStorage.getItem('account_tab') || 'overview';
    sessionStorage.removeItem('account_tab');
    if (initialTab && initialTab !== 'overview') {
        switchSection(initialTab);
    }

    window.addEventListener('hashchange', () => {
        const h = window.location.hash.replace('#', '');
        if (h) switchSection(h);
    });
});

/* ═══════════════════════════════════════════════════════════════
   SECTION SWITCHING
═══════════════════════════════════════════════════════════════ */
function switchSection(name) {
    document.querySelectorAll('.account-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.sidebar-nav-item').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.mobile-tab').forEach(s => s.classList.remove('active'));

    document.getElementById('section-' + name)?.classList.add('active');
    document.querySelector(`.sidebar-nav-item[data-section="${name}"]`)?.classList.add('active');
    document.querySelector(`.mobile-tab[data-section="${name}"]`)?.classList.add('active');

    // Lazy-load section data
    if (name === 'orders')    loadOrders();
    if (name === 'wishlist')  loadWishlist();
    if (name === 'addresses') loadAddresses();
}

/* ═══════════════════════════════════════════════════════════════
   LOAD USER (overview + sidebar)
═══════════════════════════════════════════════════════════════ */
async function loadUserData() {
    const res = await apiFetch('/api/user');
    if (!res.ok) {
        showAlert('account-alert', 'Failed to load account data: ' + (res.data?.message || 'Server error'), 'danger');
        return;
    }

    currentUser = res.data.user;

    // Sidebar
    document.getElementById('sb-avatar').src = currentUser.avatar_url;
    document.getElementById('sb-name').textContent  = currentUser.name;
    document.getElementById('sb-email').textContent = currentUser.email;
    document.getElementById('sb-orders-count').textContent = currentUser.orders_count ?? 0;
    document.getElementById('sb-wish-count').textContent   = currentUser.wishlist_count ?? 0;

    // Overview section
    document.getElementById('ov-avatar').src = currentUser.avatar_url;
    document.getElementById('ov-name').textContent  = currentUser.name;
    document.getElementById('ov-email').textContent = currentUser.email;
    document.getElementById('ov-since').textContent = 'Member since ' + (currentUser.created_at ? new Date(currentUser.created_at).toLocaleDateString(undefined,{year:'numeric',month:'long'}) : '—');
    document.getElementById('ov-orders').textContent    = currentUser.orders_count ?? 0;
    document.getElementById('ov-wishlist').textContent  = currentUser.wishlist_count ?? 0;
    document.getElementById('ov-addresses').textContent = currentUser.addresses_count ?? 0;

    // Profile form
    document.getElementById('prof-name').value  = currentUser.name;
    document.getElementById('prof-email').value = currentUser.email;
    document.getElementById('profile-avatar-preview').src = currentUser.avatar_url;

    // Recent orders
    loadRecentOrders();
}

/* ═══════════════════════════════════════════════════════════════
   RECENT ORDERS (overview panel, max 3)
═══════════════════════════════════════════════════════════════ */
async function loadRecentOrders() {
    const el = document.getElementById('ov-recent-orders');
    const res = await apiFetch('/api/orders?page=1');
    if (!res.ok) { el.innerHTML = '<p class="text-sm text-red-500">Could not load recent orders.</p>'; return; }

    const orders = (res.data?.data || []).slice(0, 3);
    if (!orders.length) {
        el.innerHTML = '<div class="empty-state"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg><h3>No orders yet</h3><p>Your placed orders will show here.</p></div>';
        return;
    }
    el.innerHTML = orders.map(o => orderCardHtml(o, true)).join('');
}

/* ═══════════════════════════════════════════════════════════════
   ORDERS (full list, paginated)
═══════════════════════════════════════════════════════════════ */
async function loadOrders(page = 1) {
    ordersPage = page;
    const container   = document.getElementById('orders-container');
    const pagination  = document.getElementById('orders-pagination');
    container.innerHTML = '<div class="text-center py-8 text-gray-400 text-sm">Loading orders…</div>';

    const res = await apiFetch('/api/orders?page=' + page);
    if (!res.ok) {
        container.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Failed to load orders.</p>';
        return;
    }

    const orders = res.data?.data || [];
    const meta   = res.data?.meta || {};

    if (!orders.length) {
        container.innerHTML = `
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <h3>No orders yet</h3>
                <p>Place your first order from our <a href="/products" class="text-indigo-600 underline">product catalog</a>.</p>
            </div>`;
        pagination.innerHTML = '';
        return;
    }

    container.innerHTML = orders.map(o => orderCardHtml(o, false)).join('');

    // Pagination
    pagination.innerHTML = '';
    if (meta.last_page > 1) {
        for (let p = 1; p <= meta.last_page; p++) {
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm ' + (p === page ? 'btn-primary' : 'btn-outline');
            btn.textContent = p;
            btn.onclick = () => loadOrders(p);
            pagination.appendChild(btn);
        }
    }
}

function orderCardHtml(order, compact) {
    const statusColors = {
        pending: 'badge-pending',
        processing: 'badge-processing',
        out_for_delivery: 'badge-out-for-delivery',
        shipped: 'badge-shipped',
        delivered: 'badge-delivered',
        completed: 'badge-completed',
        cancelled: 'badge-cancelled',
    };
    const statusLabels = {
        pending: 'Pending',
        processing: 'Confirmed / Processing',
        out_for_delivery: 'Out for Delivery',
        shipped: 'Out for Delivery',
        delivered: 'Delivered',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };
    const badge = statusColors[order.status] || 'badge-processing';
    const label = statusLabels[order.status] || order.status;
    const date  = new Date(order.created_at).toLocaleDateString(undefined, {year:'numeric',month:'short',day:'numeric'});
    const expectedDelivery = order.expected_delivery_date
        ? new Date(order.expected_delivery_date).toLocaleDateString(undefined, {year:'numeric',month:'short',day:'numeric'})
        : null;
    const cancelBtn = (!compact && order.status === 'pending')
        ? `<button class="btn btn-danger btn-sm" onclick="cancelOrder(${order.id})">Cancel</button>` : '';
    return `
        <div class="order-card">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <span class="font-bold text-gray-900 text-sm">Order #${order.id}</span>
                <span class="badge ${badge}">${label}</span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
                <div>
                    <span>${order.items?.length || 0} item(s)</span>
                    ${order.address ? `<span class="ml-2 text-gray-400">· ${order.address.city}</span>` : ''}
                    ${expectedDelivery ? `<div class="text-xs text-indigo-600 font-semibold mt-1 flex items-center gap-1">🚚 Expected: ${expectedDelivery}</div>` : ''}
                </div>
                <div class="text-right">
                    <span class="font-bold text-gray-900">$${parseFloat(order.total_amount).toFixed(2)}</span>
                    <span class="block text-xs text-gray-400">${date}</span>
                </div>
            </div>
            <div class="flex gap-2 mt-3">
                <a href="/orders/${order.id}" class="btn btn-outline btn-sm">View Details</a>
                ${cancelBtn}
            </div>
        </div>`;
}

async function cancelOrder(orderId) {
    if (!confirm('Cancel order #' + orderId + '? Stock will be restored.')) return;
    const res = await apiFetch('/api/orders/' + orderId + '/cancel', {method:'PATCH'});
    if (res.ok) {
        showAlert('orders-alert', 'Order #' + orderId + ' cancelled and stock restored.', 'success');
        loadOrders(ordersPage);
        loadUserData();
    } else {
        showAlert('orders-alert', res.data?.message || 'Could not cancel order.', 'danger');
    }
}

/* ═══════════════════════════════════════════════════════════════
   WISHLIST
═══════════════════════════════════════════════════════════════ */
async function loadWishlist() {
    const el = document.getElementById('wishlist-container');
    el.innerHTML = '<div class="col-span-full text-center py-8 text-gray-400 text-sm">Loading wishlist…</div>';

    const res = await apiFetch('/api/wishlist');
    if (!res.ok) { el.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Failed to load wishlist.</p>'; return; }

    const items = res.data?.data?.items || [];
    document.getElementById('clear-wishlist-btn').style.display = items.length ? 'inline-flex' : 'none';
    document.getElementById('sb-wish-count').textContent = items.length;

    if (!items.length) {
        el.innerHTML = `<div class="col-span-full empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <h3>Your wishlist is empty</h3>
            <p>Save products you love by clicking the ♡ button.</p>
        </div>`;
        return;
    }

    el.innerHTML = items.map(item => {
        const p = item.product;
        const img = p?.primary_image?.image
            ? `/storage/${p.primary_image.image.replace(/^\/+/, '')}`
            : (p?.images?.[0]?.image ? `/storage/${p.images[0].image.replace(/^\/+/, '')}` : 'https://placehold.co/300x300?text=No+Image');
        return `
            <div class="wishlist-item">
                <img src="${img}" alt="${p?.name || ''}" onerror="this.src='https://placehold.co/300x200?text=No+Image'">
                <button class="wishlist-remove" onclick="removeWishlistItem(${item.id})" title="Remove">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div class="wishlist-item-body">
                    <div class="text-xs font-semibold text-gray-800 truncate">${p?.name || 'Product'}</div>
                    <div class="text-xs font-bold text-indigo-600 mt-0.5">$${parseFloat(p?.price || 0).toFixed(2)}</div>
                    <a href="/products/${p?.id}" class="mt-1.5 btn btn-outline btn-sm" style="width:100%">View</a>
                </div>
            </div>`;
    }).join('');
}

async function removeWishlistItem(itemId) {
    const res = await apiFetch('/api/wishlist/items/' + itemId, {method:'DELETE'});
    if (res.ok) { loadWishlist(); showAlert('wishlist-alert','Item removed from wishlist.','success'); }
    else showAlert('wishlist-alert', res.data?.message || 'Could not remove item.', 'danger');
}

async function clearWishlist() {
    if (!confirm('Clear your entire wishlist?')) return;
    const res = await apiFetch('/api/wishlist', {method:'DELETE'});
    if (res.ok) { loadWishlist(); showAlert('wishlist-alert','Wishlist cleared.','success'); }
    else showAlert('wishlist-alert', res.data?.message || 'Could not clear wishlist.', 'danger');
}

/* ═══════════════════════════════════════════════════════════════
   ADDRESSES
═══════════════════════════════════════════════════════════════ */
async function loadAddresses() {
    const el = document.getElementById('addresses-container');
    el.innerHTML = '<div class="text-center py-8 text-gray-400 text-sm">Loading addresses…</div>';
    const res = await apiFetch('/api/addresses');
    if (!res.ok) { el.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Failed to load addresses.</p>'; return; }

    const addrs = res.data?.data || [];
    if (!addrs.length) {
        el.innerHTML = `<div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
            <h3>No addresses saved</h3>
            <p>Add a delivery address for faster checkout.</p>
        </div>`;
        return;
    }

    el.innerHTML = addrs.map(a => `
        <div class="address-card ${a.is_default ? 'default' : ''}">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-semibold text-sm text-gray-900">${a.name}</span>
                    ${a.is_default ? '<span class="badge" style="background:#e0e7ff;color:#3730a3;">Default</span>' : ''}
                </div>
                <div class="text-sm text-gray-600">${a.address_line1}${a.address_line2 ? ', ' + a.address_line2 : ''}</div>
                <div class="text-sm text-gray-600">${a.city}, ${a.state} ${a.postal_code}, ${a.country}</div>
                <div class="text-xs text-gray-400 mt-1">📞 ${a.phone}</div>
            </div>
            <div class="address-actions">
                ${!a.is_default ? `<button class="btn btn-outline btn-sm" onclick="setDefaultAddress(${a.id})">Set Default</button>` : ''}
                <button class="btn btn-outline btn-sm" onclick="editAddress(${JSON.stringify(a).replace(/"/g,'&quot;')})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteAddress(${a.id})">Delete</button>
            </div>
        </div>`).join('');
}

function openAddressModal(addr = null) {
    document.getElementById('addr-modal-title').textContent = addr ? 'Edit Address' : 'Add New Address';
    document.getElementById('addr-modal-desc').textContent  = addr ? 'Update your delivery address.' : 'Fill in the delivery address details below.';
    document.getElementById('addr-id').value          = addr?.id ?? '';
    document.getElementById('addr-name').value        = addr?.name ?? '';
    document.getElementById('addr-phone').value       = addr?.phone ?? '';
    document.getElementById('addr-line1').value       = addr?.address_line1 ?? '';
    document.getElementById('addr-line2').value       = addr?.address_line2 ?? '';
    document.getElementById('addr-city').value        = addr?.city ?? '';
    document.getElementById('addr-state').value       = addr?.state ?? '';
    document.getElementById('addr-postal').value      = addr?.postal_code ?? '';
    document.getElementById('addr-country').value     = addr?.country ?? '';
    document.getElementById('addr-default').checked   = addr?.is_default ?? false;
    document.getElementById('address-modal').classList.add('open');
}

function closeAddressModal() {
    document.getElementById('address-modal').classList.remove('open');
    document.getElementById('address-form').reset();
}

function editAddress(addr) { openAddressModal(addr); }

async function handleSaveAddress(e) {
    e.preventDefault();
    const btn  = document.getElementById('save-addr-btn');
    const id   = document.getElementById('addr-id').value;
    const body = {
        name:          document.getElementById('addr-name').value.trim(),
        phone:         document.getElementById('addr-phone').value.trim(),
        address_line1: document.getElementById('addr-line1').value.trim(),
        address_line2: document.getElementById('addr-line2').value.trim(),
        city:          document.getElementById('addr-city').value.trim(),
        state:         document.getElementById('addr-state').value.trim(),
        postal_code:   document.getElementById('addr-postal').value.trim(),
        country:       document.getElementById('addr-country').value.trim(),
        is_default:    document.getElementById('addr-default').checked,
    };

    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving…';
    const res = id
        ? await apiFetch('/api/addresses/' + id, {method:'PUT', body:JSON.stringify(body)})
        : await apiFetch('/api/addresses',        {method:'POST', body:JSON.stringify(body)});
    btn.disabled = false; btn.textContent = 'Save Address';

    if (res.ok) {
        closeAddressModal();
        loadAddresses();
        showAlert('addresses-alert', id ? 'Address updated.' : 'Address added.', 'success');
    } else {
        showAlert('addresses-alert', res.data?.message || 'Could not save address.', 'danger');
        closeAddressModal();
    }
}

async function setDefaultAddress(id) {
    const res = await apiFetch('/api/addresses/' + id + '/default', {method:'PATCH'});
    if (res.ok) { loadAddresses(); showAlert('addresses-alert','Default address updated.','success'); }
    else showAlert('addresses-alert', res.data?.message || 'Error.', 'danger');
}

async function deleteAddress(id) {
    if (!confirm('Delete this address?')) return;
    const res = await apiFetch('/api/addresses/' + id, {method:'DELETE'});
    if (res.ok) { loadAddresses(); showAlert('addresses-alert','Address deleted.','success'); }
    else showAlert('addresses-alert', res.data?.message || 'Could not delete address.', 'danger');
}

/* ═══════════════════════════════════════════════════════════════
   PROFILE UPDATE (with avatar)
═══════════════════════════════════════════════════════════════ */
function onAvatarSelected(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        showAlert('profile-alert', 'Avatar must be under 2 MB.', 'danger'); return;
    }
    avatarFile = file;
    const reader = new FileReader();
    reader.onload = ev => {
        document.getElementById('profile-avatar-preview').src = ev.target.result;
        document.getElementById('sb-avatar').src = ev.target.result;
        document.getElementById('ov-avatar').src = ev.target.result;
    };
    reader.readAsDataURL(file);
}

async function handleUpdateProfile(e) {
    e.preventDefault();
    const btn = document.getElementById('save-profile-btn');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Saving…';

    const fd = new FormData();
    fd.append('name',  document.getElementById('prof-name').value.trim());
    fd.append('email', document.getElementById('prof-email').value.trim());
    if (avatarFile) fd.append('avatar', avatarFile);

    const token = getAuthToken();
    const res = await fetch('/api/profile', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
        body: fd,
    });
    const data = await res.json();

    btn.disabled = false; btn.textContent = 'Save Changes';

    if (res.ok) {
        avatarFile = null;
        showAlert('profile-alert', 'Profile updated successfully!', 'success');
        if (data.user) setAuthData(token, data.user);
        await loadUserData();
    } else {
        showAlert('profile-alert', data?.message || 'Failed to update profile.', 'danger');
    }
}

/* ═══════════════════════════════════════════════════════════════
   PASSWORD UPDATE
═══════════════════════════════════════════════════════════════ */
function updateStrength(val) {
    const fill  = document.getElementById('strength-fill');
    const label = document.getElementById('strength-label');
    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;
    const levels = [{w:'20%',c:'#ef4444',t:'Very weak'},{w:'40%',c:'#f59e0b',t:'Weak'},{w:'60%',c:'#eab308',t:'Fair'},{w:'80%',c:'#22c55e',t:'Strong'},{w:'100%',c:'#16a34a',t:'Very strong'}];
    const lv = levels[Math.min(score, 4)];
    fill.style.width = lv.w; fill.style.background = lv.c;
    label.textContent = lv.t; label.style.color = lv.c;
}

async function handleUpdatePassword(e) {
    e.preventDefault();
    const btn = document.getElementById('save-pass-btn');
    const current = document.getElementById('curr-pass').value;
    const newPass = document.getElementById('new-pass').value;
    const conf    = document.getElementById('conf-pass').value;

    if (newPass !== conf) {
        showAlert('password-alert','New passwords do not match.','danger'); return;
    }

    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Updating…';

    const res = await apiFetch('/api/password', {
        method:'PUT',
        body: JSON.stringify({current_password: current, password: newPass, password_confirmation: conf})
    });

    btn.disabled = false; btn.textContent = 'Update Password';

    if (res.ok) {
        showAlert('password-alert','Password updated successfully!','success');
        document.getElementById('password-form').reset();
        document.getElementById('strength-fill').style.width = '0';
        document.getElementById('strength-label').textContent = '';
    } else {
        showAlert('password-alert', res.data?.message || 'Failed to update password.','danger');
    }
}

/* ═══════════════════════════════════════════════════════════════
   DELETE ACCOUNT
═══════════════════════════════════════════════════════════════ */
function openDeleteModal()  { document.getElementById('delete-modal').classList.add('open'); }
function closeDeleteModal() {
    document.getElementById('delete-modal').classList.remove('open');
    document.getElementById('delete-pass').value = '';
    document.getElementById('delete-alert').innerHTML = '';
}

async function handleDeleteAccount() {
    const password = document.getElementById('delete-pass').value;
    if (!password) { showAlert('delete-alert','Please enter your password.','danger'); return; }

    const btn = document.getElementById('confirm-delete-btn');
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Deleting…';

    const res = await apiFetch('/api/account', {
        method:'DELETE', body: JSON.stringify({password})
    });

    btn.disabled = false; btn.textContent = 'Yes, Delete Everything';

    if (res.ok) {
        localStorage.removeItem('ecommerce_auth_token');
        localStorage.removeItem('ecommerce_auth_user');
        sessionStorage.setItem('logged_out','1');
        window.location.href = '/?account_deleted=1';
    } else {
        showAlert('delete-alert', res.data?.message || 'Could not delete account.', 'danger');
    }
}

/* ═══════════════════════════════════════════════════════════════
   SECURE LOGOUT
═══════════════════════════════════════════════════════════════ */
async function handleSecureLogout() {
    await apiFetch('/api/logout', {method:'POST'});
    localStorage.removeItem('ecommerce_auth_token');
    localStorage.removeItem('ecommerce_auth_user');
    sessionStorage.setItem('logged_out','1');
    window.location.href = '/login';
}

/* ═══════════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════════ */
function showAlert(containerId, msg, type) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const icons = {success:'✔', danger:'✖', info:'ℹ'};
    el.innerHTML = `<div class="alert alert-${type}"><span>${icons[type]||''}</span>${msg}</div>`;
    setTimeout(() => { if(el) el.innerHTML=''; }, 5000);
}
</script>
@endpush
