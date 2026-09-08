<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $from = $request->date('from', now()->startOfMonth());
        $to   = $request->date('to',   now()->endOfMonth());

        $daily = Order::where('status', 'delivered')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as orders'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalRevenue = $daily->sum('total');
        $totalOrders  = $daily->sum('orders');

        return view('admin.reports.sales', compact('daily', 'totalRevenue', 'totalOrders', 'from', 'to'));
    }

    public function topProducts(Request $request)
    {
        $from = $request->date('from', now()->startOfMonth());
        $to   = $request->date('to',   now()->endOfMonth());

        $products = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', 'delivered')
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.quantity * order_items.price) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();

        return view('admin.reports.top_products', compact('products', 'from', 'to'));
    }
}
