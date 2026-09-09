<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — ShopAdmin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 250px;
            --header-h: 60px;
            --primary: #4f46e5;
            --primary-light: #eef2ff;
            --danger: #ef4444;
            --success: #22c55e;
            --warning: #f59e0b;
            --info: #3b82f6;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
            --muted: #64748b;
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-active: #4f46e5;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-logo {
            padding: 20px 20px 16px;
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-logo span { color: var(--sidebar-active); }
        .sidebar-nav { flex: 1; padding: 12px 0; }
        .nav-section { padding: 8px 20px 4px; font-size: .68rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: .06em; }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 20px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: .875rem;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(79,70,229,.15);
            color: #fff;
            border-left-color: var(--sidebar-active);
        }
        .nav-icon { width: 18px; text-align: center; opacity: .7; }
        .nav-link.active .nav-icon, .nav-link:hover .nav-icon { opacity: 1; }
        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.08); }
        .sidebar-user { font-size: .8rem; color: var(--sidebar-text); margin-bottom: 10px; }
        .btn-logout {
            display: block; width: 100%; text-align: center;
            padding: 7px; background: rgba(239,68,68,.15);
            color: #fca5a5; border: 1px solid rgba(239,68,68,.3);
            border-radius: 6px; font-size: .8rem; text-decoration: none;
            transition: background .15s;
        }
        .btn-logout:hover { background: rgba(239,68,68,.3); }

        /* ── Main ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar {
            height: var(--header-h); background: var(--card);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; position: sticky; top: 0; z-index: 50;
        }
        .page-title { font-size: 1rem; font-weight: 600; color: var(--text); }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .badge-admin {
            background: var(--primary-light); color: var(--primary);
            font-size: .75rem; font-weight: 600; padding: 3px 10px; border-radius: 20px;
        }
        .content { flex: 1; padding: 28px 28px; }

        /* ── Cards ── */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 20px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .card-title { font-size: .95rem; font-weight: 600; color: var(--text); }

        /* ── Stats Grid ── */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 18px; }
        .stat-label { font-size: .78rem; color: var(--muted); font-weight: 500; margin-bottom: 6px; }
        .stat-value { font-size: 1.6rem; font-weight: 700; color: var(--text); }
        .stat-sub { font-size: .75rem; color: var(--muted); margin-top: 4px; }
        .stat-icon { float: right; font-size: 1.4rem; opacity: .15; }

        /* ── Tables ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        thead th { background: #f1f5f9; padding: 10px 14px; text-align: left; font-weight: 600; font-size: .78rem; color: var(--muted); border-bottom: 1px solid var(--border); }
        tbody td { padding: 11px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        /* ── Badges ── */
        .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .72rem; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #16a34a; }
        .badge-red   { background: #fee2e2; color: #b91c1c; }
        .badge-yellow{ background: #fef9c3; color: #92400e; }
        .badge-blue  { background: #dbeafe; color: #1d4ed8; }
        .badge-gray  { background: #f1f5f9; color: #475569; }
        .badge-purple{ background: #ede9fe; color: #6d28d9; }

        /* ── Buttons ── */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 7px; font-size: .83rem; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: opacity .15s, box-shadow .15s; }
        .btn:hover { opacity: .88; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-danger  { background: var(--danger); color: #fff; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-outline { background: transparent; color: var(--text); border: 1px solid var(--border); }
        .btn-sm { padding: 5px 10px; font-size: .78rem; }
        .btn-xs { padding: 3px 8px; font-size: .72rem; }

        /* ── Forms ── */
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: .8rem; font-weight: 500; margin-bottom: 5px; color: var(--text); }
        .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 7px; font-size: .875rem; color: var(--text); background: #fff; outline: none; transition: border .15s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
        select.form-control { cursor: pointer; }
        textarea.form-control { resize: vertical; min-height: 90px; }
        .form-error { color: var(--danger); font-size: .78rem; margin-top: 4px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media(max-width:640px){ .form-row { grid-template-columns: 1fr; } }

        /* ── Alerts ── */
        .alert { padding: 12px 16px; border-radius: 8px; font-size: .875rem; margin-bottom: 16px; }
        .alert-success { background: #dcfce7; color: #16a34a; border-left: 4px solid #22c55e; }
        .alert-error   { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }
        .alert-warning { background: #fef9c3; color: #854d0e; border-left: 4px solid #f59e0b; }
        .alert-info    { background: #dbeafe; color: #1d4ed8; border-left: 4px solid #3b82f6; }

        /* ── Pagination ── */
        .pagination { display: flex; align-items: center; gap: 4px; margin-top: 16px; }
        .pagination a, .pagination span { padding: 6px 11px; border: 1px solid var(--border); border-radius: 6px; font-size: .8rem; color: var(--muted); text-decoration: none; }
        .pagination a:hover { background: var(--primary-light); color: var(--primary); }
        .pagination .active span { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* ── Utility ── */
        .flex { display: flex; } .items-center { align-items: center; } .justify-between { justify-content: space-between; }
        .gap-8 { gap: 8px; } .gap-16 { gap: 16px; } .gap-24 { gap: 24px; }
        .mb-16 { margin-bottom: 16px; } .mb-24 { margin-bottom: 24px; } .mt-16 { margin-top: 16px; }
        .text-muted { color: var(--muted); } .text-sm { font-size: .8rem; } .text-right { text-align: right; }
        .w-full { width: 100%; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; }
        @media(max-width:900px){ .grid-2, .grid-3 { grid-template-columns: 1fr; } }

        /* image thumbnail */
        .img-thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); }

        /* ── Search row ── */
        .filter-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .filter-bar .form-control { width: auto; }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<aside class="sidebar">
    <div class="sidebar-logo">Shop<span>Admin</span></div>

    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="nav-icon">📊</span> Dashboard
        </a>

        <div class="nav-section">Catalog</div>
        <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
            <span class="nav-icon">📦</span> Products
        </a>
        <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
            <span class="nav-icon">🗂️</span> Categories
        </a>

        <div class="nav-section">Operations</div>
        <a href="{{ route('admin.inventory.index') }}" class="nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
            <span class="nav-icon">🏭</span> Inventory
        </a>
        <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
            <span class="nav-icon">🛒</span> Orders
        </a>
        <a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
            <span class="nav-icon">💳</span> Payments
        </a>
        <a href="{{ route('admin.customers.index') }}" class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
            <span class="nav-icon">👥</span> Customers
        </a>
        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <span class="nav-icon">🛡️</span> Users
        </a>

        <div class="nav-section">Marketing</div>
        <a href="{{ route('admin.coupons.index') }}" class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
            <span class="nav-icon">🎟️</span> Coupons
        </a>

        <div class="nav-section">Moderation</div>
        <a href="{{ route('admin.reviews.index') }}" class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
            <span class="nav-icon">⭐</span> Reviews
        </a>

        <div class="nav-section">Reports</div>
        <a href="{{ route('admin.reports.sales') }}" class="nav-link {{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}">
            <span class="nav-icon">📈</span> Sales Report
        </a>
        <a href="{{ route('admin.reports.top-products') }}" class="nav-link {{ request()->routeIs('admin.reports.top-products') ? 'active' : '' }}">
            <span class="nav-icon">🏆</span> Top Products
        </a>

        <div class="nav-section">Store</div>
        <a href="{{ route('home') }}" class="nav-link" target="_blank">
            <span class="nav-icon">🏪</span> View Store
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">👤 {{ auth()->user()?->name ?? 'Admin' }}</div>
        <a href="{{ route('login') }}" class="btn-logout">Logout</a>
    </div>
</aside>

{{-- Main content --}}
<div class="main">
    <header class="topbar">
        <span class="page-title">@yield('page-title', 'Dashboard')</span>
        <div class="topbar-right">
            <span class="badge-admin">Admin Panel</span>
        </div>
    </header>

    <main class="content">
        {{-- Flash messages --}}
        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">❌ {{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">⚠️ {{ session('warning') }}</div>
        @endif

        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
