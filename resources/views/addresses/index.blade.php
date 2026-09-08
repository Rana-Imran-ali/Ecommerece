@extends('layouts.app')

@section('title', 'Address Book - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Saved Addresses</h1>
            <p class="text-sm text-gray-500">Manage shipping addresses and designate your default delivery location.</p>
        </div>
        <button type="button" onclick="openAddressModal()"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md shadow-sm transition-colors">
            + Add New Address
        </button>
    </div>

    <!-- Alert Container -->
    <div id="address-alert"></div>

    <!-- Address List -->
    <div id="addresses-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="col-span-full py-16 text-center text-gray-500 bg-white rounded-lg border border-gray-200">
            <div class="inline-block animate-spin w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full mb-2"></div>
            <div>Loading addresses...</div>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Address -->
<div id="address-modal" class="hidden fixed inset-0 bg-gray-600/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg border border-gray-200 max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 id="modal-title" class="text-lg font-bold text-gray-900">Add New Address</h3>
            <button type="button" onclick="closeAddressModal()" class="text-gray-400 hover:text-gray-600 font-bold">&times;</button>
        </div>

        <form id="address-form" onsubmit="handleAddressSubmit(event)" class="space-y-4">
            <input type="hidden" id="addr-id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Full Name</label>
                    <input type="text" id="addr-name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="Recipient name">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Phone Number</label>
                    <input type="text" id="addr-phone" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="+1 (555) 000-0000">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Address Line 1</label>
                <input type="text" id="addr-line1" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                       placeholder="Street address, P.O. box">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Address Line 2 (Optional)</label>
                <input type="text" id="addr-line2"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                       placeholder="Apt, suite, unit, building">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">City</label>
                    <input type="text" id="addr-city" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="City">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">State / Region</label>
                    <input type="text" id="addr-state" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="State">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Postal Code</label>
                    <input type="text" id="addr-postal" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="Zip / Postal code">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Country</label>
                    <input type="text" id="addr-country" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="Country">
                </div>
            </div>

            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" id="addr-default" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="addr-default" class="text-sm text-gray-700">Set as default shipping address</label>
            </div>

            <div class="flex justify-end space-x-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeAddressModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="save-addr-btn"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-semibold">
                    Save Address
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let addressList = [];

    async function loadAddresses() {
        if (!getAuthToken()) {
            document.getElementById('addresses-container').innerHTML = `
                <div class="col-span-full p-12 text-center bg-white rounded-lg border border-gray-200 space-y-4">
                    <p class="text-lg font-semibold text-gray-800">Please sign in to manage your addresses</p>
                    <a href="/login?redirect=/addresses" class="inline-block px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-md">
                        Sign In Now
                    </a>
                </div>
            `;
            return;
        }

        const res = await apiFetch('/api/addresses');
        const container = document.getElementById('addresses-container');

        if (!res.ok) {
            container.innerHTML = `
                <div class="col-span-full p-8 text-center bg-white rounded-lg border border-red-200 text-red-600">
                    Failed to fetch addresses: ${res.data?.message || 'Server error'}
                </div>
            `;
            return;
        }

        addressList = res.data?.data || [];

        if (addressList.length === 0) {
            container.innerHTML = `
                <div class="col-span-full p-16 text-center bg-white rounded-lg border border-gray-200 space-y-4">
                    <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <h3 class="text-lg font-semibold text-gray-800">No saved addresses yet</h3>
                    <p class="text-sm text-gray-500">Add a delivery address to complete orders smoothly.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = addressList.map(addr => {
            const isDefault = Boolean(addr.is_default);

            return `
                <div class="bg-white rounded-lg border ${isDefault ? 'border-indigo-600 ring-1 ring-indigo-600' : 'border-gray-200'} p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-base font-bold text-gray-900">${addr.name}</h3>
                            ${isDefault 
                                ? `<span class="px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-800">DEFAULT</span>`
                                : `<button type="button" onclick="setDefaultAddress(${addr.id})" class="text-xs text-indigo-600 hover:underline">Set as Default</button>`}
                        </div>
                        <div class="text-sm text-gray-600 space-y-0.5">
                            <p>${addr.address_line1}</p>
                            ${addr.address_line2 ? `<p>${addr.address_line2}</p>` : ''}
                            <p>${addr.city}, ${addr.state} ${addr.postal_code}</p>
                            <p>${addr.country}</p>
                            <p class="text-xs text-gray-400 pt-1">Phone: ${addr.phone}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 mt-4 border-t border-gray-100">
                        <button type="button" onclick="editAddress(${addr.id})" class="text-xs font-semibold text-gray-600 hover:text-indigo-600">
                            Edit
                        </button>
                        <button type="button" onclick="deleteAddress(${addr.id})" class="text-xs font-semibold text-red-600 hover:text-red-800">
                            Delete
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    function openAddressModal(addr = null) {
        document.getElementById('modal-title').textContent = addr ? 'Edit Address' : 'Add New Address';
        document.getElementById('addr-id').value = addr ? addr.id : '';
        document.getElementById('addr-name').value = addr ? addr.name : '';
        document.getElementById('addr-phone').value = addr ? addr.phone : '';
        document.getElementById('addr-line1').value = addr ? addr.address_line1 : '';
        document.getElementById('addr-line2').value = addr ? (addr.address_line2 || '') : '';
        document.getElementById('addr-city').value = addr ? addr.city : '';
        document.getElementById('addr-state').value = addr ? addr.state : '';
        document.getElementById('addr-postal').value = addr ? addr.postal_code : '';
        document.getElementById('addr-country').value = addr ? addr.country : '';
        document.getElementById('addr-default').checked = addr ? Boolean(addr.is_default) : false;

        document.getElementById('address-modal').classList.remove('hidden');
    }

    function closeAddressModal() {
        document.getElementById('address-modal').classList.add('hidden');
        document.getElementById('address-form').reset();
    }

    function editAddress(id) {
        const addr = addressList.find(a => a.id === id);
        if (addr) openAddressModal(addr);
    }

    async function handleAddressSubmit(event) {
        event.preventDefault();
        const btn = document.getElementById('save-addr-btn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const id = document.getElementById('addr-id').value;
        const payload = {
            name: document.getElementById('addr-name').value.trim(),
            phone: document.getElementById('addr-phone').value.trim(),
            address_line1: document.getElementById('addr-line1').value.trim(),
            address_line2: document.getElementById('addr-line2').value.trim() || null,
            city: document.getElementById('addr-city').value.trim(),
            state: document.getElementById('addr-state').value.trim(),
            postal_code: document.getElementById('addr-postal').value.trim(),
            country: document.getElementById('addr-country').value.trim(),
            is_default: document.getElementById('addr-default').checked,
        };

        const isUpdate = Boolean(id);
        const endpoint = isUpdate ? `/api/addresses/${id}` : '/api/addresses';
        const method = isUpdate ? 'PUT' : 'POST';

        const res = await apiFetch(endpoint, {
            method,
            body: JSON.stringify(payload)
        });

        btn.disabled = false;
        btn.textContent = 'Save Address';

        if (res.ok) {
            closeAddressModal();
            showAlert('address-alert', `Address ${isUpdate ? 'updated' : 'created'} successfully!`, 'success');
            loadAddresses();
        } else {
            showAlert('address-alert', res.data?.message || 'Failed to save address.', 'danger');
        }
    }

    async function setDefaultAddress(id) {
        const res = await apiFetch(`/api/addresses/${id}/default`, {
            method: 'PATCH'
        });

        if (res.ok) {
            showAlert('address-alert', 'Default address updated.', 'success');
            loadAddresses();
        } else {
            showAlert('address-alert', res.data?.message || 'Failed to set default.', 'danger');
        }
    }

    async function deleteAddress(id) {
        if (!confirm('Are you sure you want to delete this address?')) return;

        const res = await apiFetch(`/api/addresses/${id}`, {
            method: 'DELETE'
        });

        if (res.ok) {
            showAlert('address-alert', 'Address deleted.', 'success');
            loadAddresses();
        } else {
            showAlert('address-alert', res.data?.message || 'Failed to delete address.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', loadAddresses);
</script>
@endpush
