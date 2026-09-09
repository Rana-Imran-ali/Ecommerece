<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount(['orders', 'reviews', 'addresses'])->latest();

        // Search by Name or Email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by Role
        if ($request->filled('role')) {
            if ($request->role === 'admin') {
                $query->where('role', 'admin');
            } elseif ($request->role === 'customer') {
                $query->where(function ($q) {
                    $q->where('role', 'customer')->orWhereNull('role');
                });
            }
        }

        // Metrics
        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $customerCount = User::where(function ($q) {
            $q->where('role', 'customer')->orWhereNull('role');
        })->count();
        $newThisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', compact(
            'users',
            'totalUsers',
            'adminCount',
            'customerCount',
            'newThisMonth'
        ));
    }

    public function show(User $user)
    {
        $user->load([
            'orders' => fn($q) => $q->with('items')->latest()->take(10),
            'addresses',
            'reviews' => fn($q) => $q->with('product')->latest()->take(5),
        ]);

        return view('admin.users.show', compact('user'));
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,customer',
        ]);

        // Guard: Prevent current logged in user from revoking their own admin access
        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove admin privileges from your own account.');
        }

        $user->update([
            'role' => $validated['role'],
        ]);

        return back()->with('success', "Role for user '{$user->name}' updated to " . ucfirst($validated['role']) . '.');
    }
}
