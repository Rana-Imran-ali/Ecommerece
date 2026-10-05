<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — ShopAdmin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 260px;
            --header-h: 68px;
            --primary: #1d4ed8;
            --primary-hover: #1e40af;
            --primary-light: #eff6ff;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #0284c7;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --sidebar-bg: #0a1128;
            --sidebar-header: #070d1e;
            --sidebar-text: #94a3b8;
            --sidebar-active: #3b82f6;
            --sidebar-active-bg: rgba(59, 130, 246, 0.12);
        }

        body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; -webkit-font-smoothing: antialiased; }

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
            border-right: 1px solid rgba(255,255,255,.06);
        }
        .sidebar-logo {
            padding: 22px 24px 18px;
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            background: var(--sidebar-header);
            border-bottom: 1px solid rgba(255,255,255,.07);
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-logo-badge {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.4);
        }
        .sidebar-logo span { color: var(--sidebar-active); }
        .sidebar-nav { flex: 1; padding: 16px 12px; }
        .nav-section { padding: 12px 14px 6px; font-size: .68rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .08em; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: .875rem;
            font-weight: 600;
            border-radius: 10px;
            margin-bottom: 3px;
            transition: all .2s;
        }
        .nav-link:hover {
            background: rgba(255,255,255,.06);
            color: #fff;
        }
        .nav-link.active {
            background: var(--sidebar-active-bg);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.25);
        }
        .nav-icon { width: 20px; text-align: center; font-size: 1.05rem; }
        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.07); background: var(--sidebar-header); }
        .sidebar-user { font-size: .82rem; font-weight: 600; color: #e2e8f0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .btn-logout {
            display: block; width: 100%; text-align: center;
            padding: 9px; background: rgba(239,68,68,.12);
            color: #fca5a5; border: 1px solid rgba(239,68,68,.25);
            border-radius: 8px; font-size: .82rem; font-weight: 700; text-decoration: none;
            transition: all .2s;
        }
        .btn-logout:hover { background: rgba(239,68,68,.25); color: #fff; }

        /* ── Main ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar {
            height: var(--header-h); background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px; position: sticky; top: 0; z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .page-title { font-size: 1.15rem; font-weight: 800; color: #0a1128; letter-spacing: -0.01em; }
        .topbar-right { display: flex; align-items: center; gap: 14px; }
        .badge-admin {
            background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
            font-size: .75rem; font-weight: 700; padding: 4px 12px; border-radius: 9999px;
        }
        .content { flex: 1; padding: 32px 32px; }

        /* ── Cards ── */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .card-title { font-size: 1.05rem; font-weight: 700; color: #0a1128; }

        /* ── Stats Grid ── */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-bottom: 28px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 22px; position: relative; box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: transform .2s, box-shadow .2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(10, 17, 40, 0.08); border-color: #cbd5e1; }
        .stat-label { font-size: .78rem; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
        .stat-value { font-size: 1.8rem; font-weight: 800; color: #0a1128; }
        .stat-sub { font-size: .78rem; color: var(--muted); margin-top: 6px; }
        .stat-icon { position: absolute; right: 20px; top: 20px; font-size: 1.8rem; opacity: .12; }

        /* ── Tables ── */
        .table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: 14px; background: #fff; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        thead th { background: #f8fafc; padding: 12px 16px; text-align: left; font-weight: 700; font-size: .78rem; color: #475569; border-bottom: 1px solid var(--border); text-transform: uppercase; letter-spacing: .05em; }
        tbody td { padding: 14px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; color: #1e293b; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        /* ── Badges ── */
        .badge { display: inline-block; padding: 3px 12px; border-radius: 9999px; font-size: .72rem; font-weight: 700; }
        .badge-green { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-red   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge-yellow{ background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .badge-blue  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-gray  { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .badge-purple{ background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }

        /* ── Buttons ── */
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border-radius: 10px; font-size: .84rem; font-weight: 700; cursor: pointer; border: none; text-decoration: none; transition: all .2s; }
        .btn-primary { background: linear-gradient(135deg, #1d4ed8, #2563eb); color: #fff; box-shadow: 0 2px 6px rgba(29, 78, 216, 0.25); }
        .btn-primary:hover { background: #1e40af; box-shadow: 0 4px 12px rgba(29, 78, 216, 0.4); color: #fff; }
        .btn-danger  { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; color: #fff; }
        .btn-success { background: #10b981; color: #fff; }
        .btn-outline { background: #fff; color: #1e293b; border: 1px solid var(--border); }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-sm { padding: 6px 12px; font-size: .78rem; border-radius: 8px; }
        .btn-xs { padding: 4px 9px; font-size: .72rem; border-radius: 6px; }

        /* ── Forms ── */
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: .8rem; font-weight: 700; margin-bottom: 6px; color: #1e293b; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: .875rem; color: var(--text); background: #fff; outline: none; transition: border .2s, box-shadow .2s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12); }
        select.form-control { cursor: pointer; }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .form-error { color: var(--danger); font-size: .78rem; margin-top: 5px; font-weight: 600; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        @media(max-width:640px){ .form-row { grid-template-columns: 1fr; } }

        /* ── Alerts ── */
        .alert { padding: 14px 18px; border-radius: 12px; font-size: .875rem; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-warning { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .alert-info    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

        /* ── Pagination ── */
        .pagination { display: flex; align-items: center; gap: 6px; margin-top: 20px; }
        .pagination a, .pagination span { padding: 7px 13px; border: 1px solid var(--border); border-radius: 8px; font-size: .82rem; font-weight: 600; color: var(--muted); text-decoration: none; }
        .pagination a:hover { background: #eff6ff; color: var(--primary); border-color: #bfdbfe; }
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

        .img-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); }
        .filter-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
        .filter-bar .form-control { width: auto; }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-badge">⚡</div>
        <div>Shop<span>Admin</span></div>
    </div>

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

        <div class="nav-section">Storefront</div>
        <a href="{{ route('home') }}" class="nav-link" target="_blank">
            <span class="nav-icon">🏪</span> View Live Store
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">👤 {{ auth()->user()?->name ?? 'Admin User' }}</div>
        <form method="POST" action="{{ route('logout') }}" id="admin-logout-form">
            @csrf
            <button type="submit" class="btn-logout" style="cursor:pointer; background:rgba(239,68,68,.15); width:100%; border:1px solid rgba(239,68,68,.3);">
                Sign Out
            </button>
        </form>
    </div>
</aside>

{{-- Main content --}}
<div class="main">
    <header class="topbar">
        <span class="page-title">@yield('page-title', 'Dashboard')</span>
        <div class="topbar-right">
            <span class="badge-admin">Admin Control Panel</span>
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
