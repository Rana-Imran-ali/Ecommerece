<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages');

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $coupons = $query->latest()->paginate(20)->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code',
            'discount_type'    => 'nullable|in:percent,fixed',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount'  => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date',
            'is_active'        => 'boolean',
        ]);

        $validated['discount_type'] = $validated['discount_type'] ?? 'percent';

        // Ensure required field for each type is present & null opposite fields
        if ($validated['discount_type'] === 'percent') {
            $request->validate(['discount_percent' => 'required|numeric|min:0.01|max:100']);
            $validated['discount_amount'] = null;
        } else {
            $request->validate(['discount_amount' => 'required|numeric|min:0.01']);
            $validated['discount_percent'] = 0;
            $validated['max_discount'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active');
        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon \"{$validated['code']}\" created.");
    }

    public function edit(Coupon $coupon)
    {
        $coupon->load('usages');
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'discount_type'    => 'nullable|in:percent,fixed',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount'  => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses'         => 'nullable|integer|min:1',
            'expires_at'       => 'nullable|date',
            'is_active'        => 'boolean',
        ]);

        $validated['discount_type'] = $validated['discount_type'] ?? $coupon->discount_type ?? 'percent';

        if ($validated['discount_type'] === 'percent') {
            $request->validate(['discount_percent' => 'required|numeric|min:0.01|max:100']);
            $validated['discount_amount'] = null;
        } else {
            $request->validate(['discount_amount' => 'required|numeric|min:0.01']);
            $validated['discount_percent'] = 0;
            $validated['max_discount'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active');
        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon updated.");
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Coupon deleted.');
    }
}
