@extends('layouts.app')

@section('title', 'My Profile - ' . config('app.name', 'EStore'))

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">User Profile & Account</h1>
            <p class="text-sm text-gray-500">Manage account information and security settings.</p>
        </div>
        <button type="button" onclick="handleLogout()"
                class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 bg-white hover:bg-red-50 text-sm font-semibold rounded-md transition-colors">
            Sign Out
        </button>
    </div>

    <!-- Alert Container -->
    <div id="profile-alert"></div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Profile Info & Edit -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
            <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3">Personal Information</h2>

            <form id="profile-form" onsubmit="handleUpdateProfile(event)" class="space-y-4">
                <div>
                    <label for="prof-name" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Full Name</label>
                    <input type="text" id="prof-name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="prof-email" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Email Address</label>
                    <input type="email" id="prof-email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="text-xs text-gray-500" id="member-since">
                    Member since: Loading...
                </div>

                <button type="submit" id="save-profile-btn"
                        class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-semibold transition-colors">
                    Save Changes
                </button>
            </form>
        </div>

        <!-- Security / Password Change -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
            <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3">Update Password</h2>

            <form id="password-form" onsubmit="handleUpdatePassword(event)" class="space-y-4">
                <div>
                    <label for="curr-pass" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Current Password</label>
                    <input type="password" id="curr-pass" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="••••••••">
                </div>

                <div>
                    <label for="new-pass" class="block text-xs font-semibold text-gray-700 uppercase mb-1">New Password</label>
                    <input type="password" id="new-pass" required minlength="6"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="At least 6 characters">
                </div>

                <div>
                    <label for="conf-pass" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Confirm New Password</label>
                    <input type="password" id="conf-pass" required minlength="6"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="Re-type new password">
                </div>

                <button type="submit" id="save-pass-btn"
                        class="w-full py-2 px-4 bg-gray-800 hover:bg-gray-900 text-white rounded-md text-sm font-semibold transition-colors">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function loadProfile() {
        if (!getAuthToken()) {
            window.location.href = '/login?redirect=/profile';
            return;
        }

        const res = await apiFetch('/api/user');

        if (!res.ok) {
            showAlert('profile-alert', 'Failed to retrieve profile: ' + (res.data?.message || 'Server error'), 'danger');
            return;
        }

        const user = res.data?.user;
        if (user) {
            document.getElementById('prof-name').value = user.name || '';
            document.getElementById('prof-email').value = user.email || '';
            document.getElementById('member-since').textContent = `Member since: ${user.created_at || 'Recently'}`;

            // Sync with localStorage
            setAuthData(getAuthToken(), user);
        }
    }

    async function handleUpdateProfile(event) {
        event.preventDefault();
        const btn = document.getElementById('save-profile-btn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        const name = document.getElementById('prof-name').value.trim();
        const email = document.getElementById('prof-email').value.trim();

        const res = await apiFetch('/api/profile', {
            method: 'PUT',
            body: JSON.stringify({ name, email })
        });

        btn.disabled = false;
        btn.textContent = 'Save Changes';

        if (res.ok) {
            showAlert('profile-alert', 'Profile updated successfully!', 'success');
            setAuthData(getAuthToken(), res.data.user);
        } else {
            showAlert('profile-alert', res.data?.message || 'Failed to update profile.', 'danger');
        }
    }

    async function handleUpdatePassword(event) {
        event.preventDefault();
        const btn = document.getElementById('save-pass-btn');

        const current_password = document.getElementById('curr-pass').value;
        const password = document.getElementById('new-pass').value;
        const password_confirmation = document.getElementById('conf-pass').value;

        if (password !== password_confirmation) {
            showAlert('profile-alert', 'New passwords do not match.', 'danger');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Updating...';

        const res = await apiFetch('/api/password', {
            method: 'PUT',
            body: JSON.stringify({
                current_password,
                password,
                password_confirmation
            })
        });

        btn.disabled = false;
        btn.textContent = 'Update Password';

        if (res.ok) {
            showAlert('profile-alert', 'Password updated successfully!', 'success');
            document.getElementById('password-form').reset();
        } else {
            showAlert('profile-alert', res.data?.message || 'Failed to update password.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', loadProfile);
</script>
@endpush
