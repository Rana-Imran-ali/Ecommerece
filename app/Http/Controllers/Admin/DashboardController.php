<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Revenue stats
        $totalRevenue = Order::where('status', 'delivered')->sum('total_amount');
        $monthRevenue = Order::where('status', 'delivered')
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        // Order stats
        $totalOrders    = Order::count();
        $pendingOrders  = Order::where('status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $deliveredOrders = Order::where('status', 'delivered')->count();
        $cancelledOrders = Order::where('status', 'cancelled')->count();

        // Customer stats
        $totalCustomers = User::where('role', 'customer')->orWhereNull('role')->count();
        $newCustomers   = User::where(function ($q) {
            $q->where('role', 'customer')->orWhereNull('role');
        })->whereMonth('created_at', now()->month)->count();

        // Product / stock stats
        $totalProducts   = Product::count();
        $activeProducts  = Product::where('status', 'active')->count();
        $lowStock        = Product::where('status', 'active')->where('stock', '>', 0)->where('stock', '<=', 10)->count();
        $outOfStock      = Product::where('stock', 0)->count();

        // Revenue chart (last 6 months)
        $revenueChart = Order::where('status', 'delivered')
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('SUM(total_amount) as total'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // Recent orders
        $recentOrders = Order::with(['user', 'items'])
            ->latest()
            ->take(8)
            ->get();

        // Low-stock products
        $lowStockProducts = Product::with('primaryImage')
            ->where('stock', '<=', 10)
            ->orderBy('stock')
            ->take(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue', 'monthRevenue',
            'totalOrders', 'pendingOrders', 'processingOrders', 'deliveredOrders', 'cancelledOrders',
            'totalCustomers', 'newCustomers',
            'totalProducts', 'activeProducts', 'lowStock', 'outOfStock',
            'revenueChart', 'recentOrders', 'lowStockProducts'
        ));
    }
}
