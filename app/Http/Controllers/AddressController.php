<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AddressController extends Controller
{
    /**
     * Display a listing of the user's addresses.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created address.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $userId = $request->user()->id;
        $isDefault = !empty($validated['is_default']) || !Address::where('user_id', $userId)->exists();

        $address = DB::transaction(function () use ($userId, $validated, $isDefault) {
            if ($isDefault) {
                Address::where('user_id', $userId)->update(['is_default' => false]);
            }

            return Address::create([
                'user_id' => $userId,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'address_line1' => $validated['address_line1'],
                'address_line2' => $validated['address_line2'] ?? null,
                'city' => $validated['city'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
                'is_default' => $isDefault,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Address saved successfully.',
            'data' => $address,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update the specified address.
     */
    public function update(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:50'],
            'address_line1' => ['sometimes', 'required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['sometimes', 'required', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:20'],
            'country' => ['sometimes', 'required', 'string', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $userId = $request->user()->id;

        DB::transaction(function () use ($address, $userId, $validated) {
            if (!empty($validated['is_default'])) {
                Address::where('user_id', $userId)->update(['is_default' => false]);
            }

            $address->update($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data' => $address,
        ], Response::HTTP_OK);
    }

    /**
     * Delete the specified address.
     */
    public function destroy(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $userId = $request->user()->id;
        $wasDefault = $address->is_default;

        DB::transaction(function () use ($address, $userId, $wasDefault) {
            $address->delete();

            if ($wasDefault) {
                $nextDefault = Address::where('user_id', $userId)->first();
                if ($nextDefault) {
                    $nextDefault->update(['is_default' => true]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ], Response::HTTP_OK);
    }

    /**
     * Set a specific address as the default.
     */
    public function setDefault(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $userId = $request->user()->id;

        DB::transaction(function () use ($address, $userId) {
            Address::where('user_id', $userId)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Default address updated.',
            'data' => $address,
        ], Response::HTTP_OK);
    }
}
