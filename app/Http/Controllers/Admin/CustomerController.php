<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where(function ($q) {
            $q->where('role', 'customer')->orWhereNull('role');
        })->withCount('orders');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $customers = $query->latest()->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        // Guard: admins cannot be viewed as customers
        if ($customer->isAdmin()) {
            abort(404);
        }

        $customer->load([
            'orders' => fn($q) => $q->with('items')->latest()->take(10),
            'addresses',
            'reviews' => fn($q) => $q->with('product')->latest()->take(5),
        ]);

        return view('admin.customers.show', compact('customer'));
    }
}
