@extends('layouts.app')

@section('title', 'Address Book - ' . config('app.name', 'EStore'))
@section('meta_description', 'Manage shipping addresses and designate your default delivery location.')

@section('content')
<div class="space-y-8 max-w-4xl mx-auto">
    <!-- Header Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-navy-975 via-navy-900 to-navy-950 p-8 sm:p-10 text-white shadow-xl shadow-navy-950/10">
        <div class="absolute inset-0 bg-radial-at-t from-blue-600/15 via-transparent to-transparent pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-blue-200 border border-white/10 backdrop-blur-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Delivery Destination Hub
                </span>
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white">Saved Addresses</h1>
                <p class="text-slate-300 text-sm">Store multiple shipping locations for fast, seamless one-click checkouts.</p>
            </div>
            <button type="button" onclick="openAddressModal()"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-xs font-bold text-navy-950 bg-white hover:bg-slate-100 shadow-md transition-all self-start md:self-auto active:scale-[0.98]">
                <svg class="w-4 h-4 text-navy-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add New Address
            </button>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="address-alert"></div>

    <!-- Address List -->
    <div id="addresses-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="col-span-full py-16 text-center text-slate-500 bg-white rounded-3xl border border-slate-200/80 shadow-xs">
            <div class="inline-block animate-spin w-8 h-8 border-3 border-navy-900 border-t-transparent rounded-full mb-3"></div>
            <div class="text-sm font-semibold text-slate-700">Loading addresses...</div>
        </div>
    </div>
</div>

<!-- Modal: Add/Edit Address -->
<div id="address-modal" class="hidden fixed inset-0 bg-navy-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl border border-navy-100 max-w-lg w-full p-6 sm:p-8 space-y-5 max-h-[90vh] overflow-y-auto shadow-2xl animate-[fadeIn_0.2s_ease-out]">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-navy-50 flex items-center justify-center text-navy-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                </div>
                <h3 id="modal-title" class="text-base font-bold text-navy-950">Add New Address</h3>
            </div>
            <button type="button" onclick="closeAddressModal()" class="w-8 h-8 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors font-bold">&times;</button>
        </div>

        <form id="address-form" onsubmit="handleAddressSubmit(event)" class="space-y-4">
            <input type="hidden" id="addr-id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Name</label>
                    <input type="text" id="addr-name" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm outline-none focus:ring-2 focus:ring-navy-600 focus:border-navy-600 transition-all placeholder:text-slate-400"
                           placeholder="Recipient name">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number</label>
                    <input type="text" id="addr-phone" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm outline-none focus:ring-2 focus:ring-navy-600 focus:border-navy-600 transition-all placeholder:text-slate-400"
                           placeholder="+1 (555) 000-0000">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Address Line 1</label>
                <input type="text" id="addr-line1" required
                       class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm outline-none focus:ring-2 focus:ring-navy-600 focus:border-navy-600 transition-all placeholder:text-slate-400"
                       placeholder="Street address, P.O. box">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Address Line 2 (Optional)</label>
                <input type="text" id="addr-line2"
                       class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm outline-none focus:ring-2 focus:ring-navy-600 focus:border-navy-600 transition-all placeholder:text-slate-400"
                       placeholder="Apt, suite, unit, building">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">City</label>
                    <input type="text" id="addr-city" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm outline-none focus:ring-2 focus:ring-navy-600 focus:border-navy-600 transition-all placeholder:text-slate-400"
                           placeholder="City">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">State / Region</label>
                    <input type="text" id="addr-state" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm outline-none focus:ring-2 focus:ring-navy-600 focus:border-navy-600 transition-all placeholder:text-slate-400"
                           placeholder="State">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Postal Code</label>
                    <input type="text" id="addr-postal" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm outline-none focus:ring-2 focus:ring-navy-600 focus:border-navy-600 transition-all placeholder:text-slate-400"
                           placeholder="Zip / Postal code">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Country</label>
                    <input type="text" id="addr-country" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs sm:text-sm outline-none focus:ring-2 focus:ring-navy-600 focus:border-navy-600 transition-all placeholder:text-slate-400"
                           placeholder="Country">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="addr-default" class="w-4 h-4 rounded border-slate-300 text-navy-900 focus:ring-navy-600">
                <label for="addr-default" class="text-xs font-semibold text-slate-700 cursor-pointer">Set as default shipping destination</label>
            </div>

            <div class="flex justify-end items-center gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeAddressModal()"
                        class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" id="save-addr-btn"
                        class="px-5 py-2.5 bg-navy-950 hover:bg-navy-900 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-navy-950/20">
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
                <div class="col-span-full p-16 text-center bg-white rounded-3xl border border-slate-200/80 space-y-4 shadow-sm">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-navy-50 flex items-center justify-center text-navy-800">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <p class="text-base font-bold text-navy-950">Please sign in to manage your addresses</p>
                    <a href="/login?redirect=/addresses" class="inline-block px-5 py-2.5 bg-navy-950 hover:bg-navy-900 text-white text-xs font-bold rounded-xl shadow-md shadow-navy-950/20">
                        Sign In Now &rarr;
                    </a>
                </div>
            `;
            return;
        }

        const res = await apiFetch('/api/addresses');
        const container = document.getElementById('addresses-container');

        if (!res.ok) {
            container.innerHTML = `
                <div class="col-span-full p-8 text-center bg-white rounded-2xl border border-red-200 text-red-600 shadow-sm">
                    Failed to fetch addresses: ${res.data?.message || 'Server error'}
                </div>
            `;
            return;
        }

        addressList = res.data?.data || [];

        if (addressList.length === 0) {
            container.innerHTML = `
                <div class="col-span-full p-16 text-center bg-white rounded-3xl border border-slate-200/80 space-y-4 shadow-sm">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-navy-50 flex items-center justify-center text-navy-800">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-navy-950">No saved addresses yet</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Add a delivery address to complete your checkout smoothly with one click.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = addressList.map(addr => {
            const isDefault = Boolean(addr.is_default);

            return `
                <div class="bg-white rounded-2xl border ${isDefault ? 'border-navy-900 ring-2 ring-navy-900/10' : 'border-slate-200/80'} p-6 flex flex-col justify-between hover:shadow-lg hover:shadow-navy-950/5 transition-all">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-base font-black text-navy-950">${addr.name}</h3>
                            ${isDefault 
                                ? `<span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-navy-950 text-white tracking-wider">DEFAULT</span>`
                                : `<button type="button" onclick="setDefaultAddress(${addr.id})" class="text-xs font-bold text-navy-900 hover:text-blue-600 transition-colors">Set Default</button>`}
                        </div>
                        <div class="text-xs text-slate-600 space-y-1">
                            <p class="font-semibold text-slate-800">${addr.address_line1}</p>
                            ${addr.address_line2 ? `<p>${addr.address_line2}</p>` : ''}
                            <p>${addr.city}, ${addr.state} ${addr.postal_code}</p>
                            <p class="font-semibold text-slate-900">${addr.country}</p>
                            <p class="text-[11px] text-slate-400 font-mono pt-1">Phone: ${addr.phone}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 mt-5 border-t border-slate-100">
                        <button type="button" onclick="editAddress(${addr.id})" class="text-xs font-bold text-slate-600 hover:text-navy-950 transition-colors">
                            Edit Address
                        </button>
                        <span class="text-slate-200">|</span>
                        <button type="button" onclick="deleteAddress(${addr.id})" class="text-xs font-bold text-rose-600 hover:text-rose-800 transition-colors">
                            Remove
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
