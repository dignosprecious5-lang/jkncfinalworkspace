@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="w-full px-6 py-5">
    <div class="max-w-3xl mx-auto bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
            <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Account Settings</p>
            <h1 class="text-2xl font-semibold text-gray-900 mt-1">Change Password</h1>
            <p class="text-sm text-gray-500 mt-1">
                Use this page after receiving a temporary password from the administrator.
            </p>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="mb-5 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                    <p class="font-semibold mb-1">Please fix the following:</p>
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.change.update') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-1">
                        Current Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            name="current_password"
                            id="current_password"
                            required
                            autocomplete="current-password"
                            class="w-full px-4 py-3 pr-20 border border-gray-300 rounded-lg bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        >
                        <button type="button" onclick="togglePasswordField('current_password', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-blue-600 text-xs font-semibold hover:text-blue-800">
                            Show
                        </button>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">
                        New Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            autocomplete="new-password"
                            class="w-full px-4 py-3 pr-20 border border-gray-300 rounded-lg bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        >
                        <button type="button" onclick="togglePasswordField('password', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-blue-600 text-xs font-semibold hover:text-blue-800">
                            Show
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Minimum of 8 characters.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">
                        Confirm New Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            required
                            autocomplete="new-password"
                            class="w-full px-4 py-3 pr-20 border border-gray-300 rounded-lg bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        >
                        <button type="button" onclick="togglePasswordField('password_confirmation', this)" class="absolute right-4 top-1/2 -translate-y-1/2 text-blue-600 text-xs font-semibold hover:text-blue-800">
                            Show
                        </button>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                    <a href="{{ url()->previous() }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePasswordField(id, button) {
        const input = document.getElementById(id);
        if (!input) return;

        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        button.textContent = isHidden ? 'Hide' : 'Show';
    }
</script>
@endsection
