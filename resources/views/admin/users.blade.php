@extends('layouts.app')
@section('title', 'Users')

@section('content')
@php
    $authUser = auth()->user();

    $employeeAccountOptions = collect($employeeOptions ?? [])->filter(function ($employee) {
        return blank($employee->user_id);
    })->map(function ($employee) {
        $name = trim(($employee->first_name ?? '').' '.($employee->last_name ?? ''));
        $name = $name !== '' ? $name : ($employee->full_name ?? 'Employee #'.$employee->id);
        $email = $employee->work_email ?: ($employee->email ?? '');

        return [
            'id' => (string) $employee->id,
            'employee_code' => $employee->employee_code,
            'name' => $name,
            'email' => $email,
            'label' => trim(($employee->employee_code ? $employee->employee_code.' - ' : '').$name.($email ? ' ('.$email.')' : '')),
            'linked' => (bool) $employee->user_id,
        ];
    })->values();

    $contactAccountOptions = collect($contactOptions ?? [])->filter(function ($contact) {
        return blank($contact->user_id);
    })->map(function ($contact) {
        $name = trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''));
        $name = $name !== '' ? $name : 'Contact #'.$contact->id;
        $email = $contact->email ?? '';

        return [
            'id' => (string) $contact->id,
            'name' => $name,
            'email' => $email,
            'label' => trim($name.($email ? ' ('.$email.')' : '')),
            'linked' => (bool) $contact->user_id,
        ];
    })->values();

    $adminCount = collect($users->items())->filter(fn ($u) => strtolower((string) $u->role) === 'admin')->count();
    $employeeCount = collect($users->items())->filter(fn ($u) => strtolower((string) $u->role) === 'employee')->count();
    $clientCount = collect($users->items())->filter(fn ($u) => strtolower((string) $u->role) === 'client')->count();
@endphp

<div
    class="w-full min-h-screen bg-slate-50 px-6 py-5"
    x-data="usersPage({
        showCreateUser: {{ $errors->getBag('default')->any() ? 'true' : 'false' }},
        accountSource: @js(old('account_source', 'manual')),
        selectedEmployeeId: @js((string) old('employee_id', '')),
        selectedContactId: @js((string) old('contact_id', '')),
        name: @js(old('name', '')),
        email: @js(old('email', '')),
        role: @js(old('role', '')),
        employees: @js($employeeAccountOptions),
        contacts: @js($contactAccountOptions),
    })"
>
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            <i class="fas fa-circle-check mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <i class="fas fa-circle-exclamation mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- CREATE USER SLIDE OVER --}}
    <div x-show="showCreateUser" x-cloak class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div
                x-show="showCreateUser"
                @click="showCreateUser = false"
                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
            ></div>

            <div class="absolute inset-y-0 right-0 flex max-w-full">
                <div
                    x-show="showCreateUser"
                    x-transition:enter="transform transition ease-in-out duration-300"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-300"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="w-screen max-w-xl bg-white shadow-2xl h-full flex flex-col"
                >
                    <div class="px-6 py-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Create User</h2>
                            <p class="text-sm text-slate-500">Create login access from employee, client, or manual entry.</p>
                        </div>
                        <button
                            type="button"
                            @click="showCreateUser = false"
                            class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-white hover:text-slate-600 transition"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form action="{{ route('admin.users.store') }}" method="POST" class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                        @csrf

                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Account Source</label>
                            <select
                                name="account_source"
                                x-model="accountSource"
                                @change="changeAccountSource()"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                                <option value="manual">Manual Account</option>
                                <option value="employee">Link to Employee Profile</option>
                                <option value="client">Link to Client Contact</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-2">
                                Employee accounts use the employee work email. Client accounts use the contact email.
                            </p>
                        </div>

                        <div x-show="accountSource === 'employee'" x-cloak>
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Employee Profile</label>
                            <select
                                name="employee_id"
                                x-model="selectedEmployeeId"
                                @change="applySelectedProfile()"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                                <option value="">Select employee profile</option>
                                <template x-for="employee in employees" :key="employee.id">
                                    <option :value="employee.id" x-text="employee.label"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="accountSource === 'client'" x-cloak>
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Client Contact</label>
                            <select
                                name="contact_id"
                                x-model="selectedContactId"
                                @change="applySelectedProfile()"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                                <option value="">Select client contact</option>
                                <template x-for="contact in contacts" :key="contact.id">
                                    <option :value="contact.id" x-text="contact.label"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Full Name</label>
                                <input
                                    type="text"
                                    name="name"
                                    x-model="name"
                                    placeholder="Full name"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Email</label>
                                <input
                                    type="email"
                                    name="email"
                                    x-model="email"
                                    placeholder="Email address"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Role</label>
                            <select
                                name="role"
                                x-model="role"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                :disabled="accountSource === 'client'"
                            >
                                <option value="">Select role</option>
                                <option value="Admin">Admin</option>
                                <option value="Employee">Employee</option>
                                <option value="Client">Client</option>
                            </select>
                            <input
                                type="hidden"
                                name="role"
                                value="Client"
                                :disabled="accountSource !== 'client'"
                            >
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Password</label>
                                <input
                                    type="password"
                                    name="password"
                                    placeholder="Enter password"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wide">Confirm Password</label>
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>
                        </div>

                        <div class="sticky bottom-0 -mx-6 border-t border-slate-200 bg-white px-6 py-4 flex gap-3">
                            <button
                                type="button"
                                @click="showCreateUser = false"
                                class="flex-1 rounded-xl border border-slate-300 bg-white py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="flex-1 rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition"
                            >
                                Save User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-6">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50 via-white to-sky-50"></div>

                <div class="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                            <i class="fas fa-users-gear text-[10px]"></i>
                            Account Administration
                        </div>

                        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Users</h1>
                        <p class="mt-1 text-sm text-slate-500">Manage login credentials, linked profiles, roles, and account controls.</p>
                    </div>

                    <button
                        @click="openCreateModal()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    >
                        <i class="fas fa-plus text-xs"></i>
                        Create User
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Total Users</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $users->total() }}</p>
            </div>
            <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wide text-blue-500">Admins</p>
                <p class="mt-2 text-2xl font-bold text-blue-700">{{ $adminCount }}</p>
            </div>
            <div class="rounded-2xl border border-green-100 bg-green-50 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wide text-green-500">Employees</p>
                <p class="mt-2 text-2xl font-bold text-green-700">{{ $employeeCount }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wide text-amber-500">Clients</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ $clientCount }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">User Directory</h2>
                    <p class="mt-1 text-sm text-slate-500">Update roles, review linked profiles, and manage user actions.</p>
                </div>

                <div class="relative w-full lg:w-96">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input
                        type="text"
                        placeholder="Search name, email, role..."
                        class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-11 pr-4 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        oninput="filterUsersTable(this.value)"
                    >
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1250px] w-full border-collapse text-sm text-slate-700">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">User</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Role</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Linked Profile</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Account Status</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Admin Controls</th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Created</th>
                            <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($users as $user)
                            @php
                                $roleClass = match(strtolower((string) $user->role)) {
                                    'superadmin', 'super admin', 'system super admin' => 'bg-purple-50 text-purple-700 ring-purple-100',
                                    'admin' => 'bg-blue-50 text-blue-700 ring-blue-100',
                                    'employee' => 'bg-green-50 text-green-700 ring-green-100',
                                    'client' => 'bg-amber-50 text-amber-700 ring-amber-100',
                                    default => 'bg-slate-100 text-slate-700 ring-slate-200',
                                };
                            @endphp

                            <tr class="user-row align-top transition hover:bg-slate-50"
                                data-search="{{ strtolower($user->name.' '.$user->email.' '.$user->role.' '.$user->id) }}">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-bold text-slate-600">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                            <p class="mt-1 font-mono text-[11px] text-slate-400">ID #{{ $user->id }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    @if($authUser->canManageRoles() && !$user->isSuperAdmin())
                                        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="space-y-3">
                                            @csrf

                                            <select
                                                name="role"
                                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                            >
                                                <option value="Admin" {{ $user->role === 'Admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="Employee" {{ $user->role === 'Employee' ? 'selected' : '' }}>Employee</option>
                                                <option value="Client" {{ $user->role === 'Client' ? 'selected' : '' }}>Client</option>
                                            </select>

                                            @if($authUser->isSuperAdmin())
                                                <div class="space-y-2 rounded-xl bg-slate-50 p-3 text-xs">
                                                    <label class="flex items-center gap-2">
                                                        <input type="checkbox" name="can_edit_user_roles" value="1" {{ $user->can_edit_user_roles ? 'checked' : '' }}>
                                                        <span>Can edit user roles</span>
                                                    </label>

                                                    <label class="flex items-center gap-2">
                                                        <input type="checkbox" name="can_delete_users" value="1" {{ $user->can_delete_users ? 'checked' : '' }}>
                                                        <span>Can delete users</span>
                                                    </label>
                                                </div>
                                            @endif

                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition"
                                            >
                                                <i class="fas fa-save text-[10px]"></i>
                                                Update
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $roleClass }}">
                                            {{ $user->role }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-xs text-slate-700">
                                    @if($user->employeeProfile)
                                        <div class="rounded-xl bg-green-50 px-3 py-2 text-green-800">
                                            <div class="font-semibold">{{ $user->employeeProfile->full_name }}</div>
                                            <div class="text-green-600">Employee • {{ $user->employeeProfile->employee_code }}</div>
                                        </div>
                                    @elseif($user->contactProfile)
                                        <div class="rounded-xl bg-amber-50 px-3 py-2 text-amber-800">
                                            <div class="font-semibold">{{ $user->contactProfile->full_name ?: trim(($user->contactProfile->first_name ?? '').' '.($user->contactProfile->last_name ?? '')) }}</div>
                                            <div class="text-amber-600">Client Contact</div>
                                        </div>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">Manual account</span>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    @php
                                        $isActiveAccount = !isset($user->is_active) || (bool) $user->is_active;
                                    @endphp

                                    <div class="space-y-2">
                                        @if($isActiveAccount)
                                            <span class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700 ring-1 ring-green-100">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                                Enabled
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-100">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                Disabled
                                            </span>

                                            @if(!empty($user->disabled_at))
                                                <p class="text-[11px] text-slate-400">
                                                    {{ \Carbon\Carbon::parse($user->disabled_at)->format('M d, Y h:i A') }}
                                                </p>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-xs">
                                    <div class="space-y-2">
                                        <span class="inline-flex rounded-full px-3 py-1 font-semibold {{ $user->can_edit_user_roles ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                            Edit Roles: {{ $user->can_edit_user_roles ? 'Yes' : 'No' }}
                                        </span>
                                        <span class="inline-flex rounded-full px-3 py-1 font-semibold {{ $user->can_delete_users ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-500' }}">
                                            Delete Users: {{ $user->can_delete_users ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-slate-600">
                                    {{ $user->created_at?->format('M d, Y') }}
                                </td>

                                <td class="px-5 py-4 text-right">
                                    <div class="flex flex-col items-end gap-2">
                                        @if($authUser->isSuperAdmin() && !$user->isSuperAdmin())
                                            <div x-data="{ accountOpen: @js((int) session('edit_account_user_id') === (int) $user->id || (int) session('edit_account_success_user_id') === (int) $user->id), showPassword: false }" class="w-full">
                                                <button
                                                    type="button"
                                                    @click="accountOpen = !accountOpen"
                                                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700"
                                                >
                                                    <i class="fas fa-user-pen text-[10px]"></i>
                                                    Edit Account
                                                </button>

                                                <div
                                                    x-cloak
                                                    x-show="accountOpen"
                                                    x-transition
                                                    class="mt-3 w-[360px] rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-left shadow-sm"
                                                >
                                                    <div class="mb-3">
                                                        <p class="text-sm font-bold text-slate-900">Edit Account</p>
                                                        <p class="mt-1 text-xs text-slate-600">
                                                            Update login account details for <span class="font-semibold">{{ $user->name }}</span>.
                                                        </p>
                                                    </div>

                                                    @if((int) session('edit_account_user_id') === (int) $user->id && $errors->editAccount->any())
                                                        <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                                                            <ul class="list-disc pl-4">
                                                                @foreach($errors->editAccount->all() as $error)
                                                                    <li>{{ $error }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    @if((int) session('edit_account_success_user_id') === (int) $user->id)
                                                        <div class="mb-3 rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700">
                                                            <i class="fas fa-circle-check mr-1"></i>
                                                            Account updated successfully for {{ $user->name }}.
                                                        </div>
                                                    @endif

                                                    <form
                                                        action="{{ route('admin.users.account.update', $user->id) }}"
                                                        method="POST"
                                                        class="space-y-3"
                                                        onsubmit="return confirm('Save account changes for {{ addslashes($user->name) }}?')"
                                                    >
                                                        @csrf

                                                        <div>
                                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                                                Full Name
                                                            </label>
                                                            <input
                                                                type="text"
                                                                name="name"
                                                                required
                                                                value="{{ (int) session('edit_account_user_id') === (int) $user->id ? old('name', $user->name) : $user->name }}"
                                                                autocomplete="off"
                                                                class="w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                                            >
                                                        </div>

                                                        <div>
                                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                                                Email
                                                            </label>
                                                            <input
                                                                type="email"
                                                                name="email"
                                                                required
                                                                value="{{ (int) session('edit_account_user_id') === (int) $user->id ? old('email', $user->email) : $user->email }}"
                                                                autocomplete="off"
                                                                class="w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                                            >
                                                        </div>

                                                        <div>
                                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                                                Role
                                                            </label>
                                                            <select
                                                                name="role"
                                                                class="w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"
                                                            >
                                                                <option value="Admin" {{ old('role', $user->role) === 'Admin' ? 'selected' : '' }}>Admin</option>
                                                                <option value="Employee" {{ old('role', $user->role) === 'Employee' ? 'selected' : '' }}>Employee</option>
                                                                <option value="Client" {{ old('role', $user->role) === 'Client' ? 'selected' : '' }}>Client</option>
                                                            </select>
                                                        </div>

                                                        <div class="rounded-xl border border-indigo-100 bg-white/70 p-3">
                                                            <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                                                Account Controls
                                                            </p>

                                                            <label class="flex items-center gap-2 text-xs text-slate-700">
                                                                <input
                                                                    type="checkbox"
                                                                    name="can_edit_user_roles"
                                                                    value="1"
                                                                    {{ old('can_edit_user_roles', $user->can_edit_user_roles) ? 'checked' : '' }}
                                                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                                >
                                                                Can edit user roles
                                                            </label>

                                                            <label class="mt-2 flex items-center gap-2 text-xs text-slate-700">
                                                                <input
                                                                    type="checkbox"
                                                                    name="can_delete_users"
                                                                    value="1"
                                                                    {{ old('can_delete_users', $user->can_delete_users) ? 'checked' : '' }}
                                                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                                >
                                                                Can delete users
                                                            </label>
                                                        </div>

                                                        <div class="rounded-xl border border-amber-100 bg-amber-50 p-3">
                                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-amber-700">
                                                                Optional New Password
                                                            </label>

                                                            <div class="relative">
                                                                <input
                                                                    :type="showPassword ? 'text' : 'password'"
                                                                    name="password"
                                                                    autocomplete="new-password"
                                                                    placeholder="Leave blank to keep current password"
                                                                    class="w-full rounded-xl border border-amber-200 bg-white py-2 pl-3 pr-10 text-sm outline-none focus:border-amber-400 focus:ring-4 focus:ring-amber-100"
                                                                >
                                                                <button
                                                                    type="button"
                                                                    @click="showPassword = !showPassword"
                                                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                                                >
                                                                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                                                </button>
                                                            </div>

                                                            <input
                                                                :type="showPassword ? 'text' : 'password'"
                                                                name="password_confirmation"
                                                                autocomplete="new-password"
                                                                placeholder="Confirm new password"
                                                                class="mt-2 w-full rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-4 focus:ring-amber-100"
                                                            >

                                                            <p class="mt-2 text-[11px] text-amber-700">
                                                                Minimum 8 characters only. No uppercase/lowercase/number requirement.
                                                            </p>
                                                        </div>

                                                        <div class="flex items-center justify-end gap-2 pt-1">
                                                            <button
                                                                type="button"
                                                                @click="accountOpen = false"
                                                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                            >
                                                                Cancel
                                                            </button>

                                                            <button
                                                                type="submit"
                                                                class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                                            >
                                                                Save Account
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif

                                        @if(
                                            ($authUser->isSuperAdmin() || $authUser->isAdmin() || $authUser->hasPermission('manage_users'))
                                            && $authUser->id !== $user->id
                                            && !$user->isSuperAdmin()
                                        )
                                            @if(!isset($user->is_active) || (bool) $user->is_active)
                                                <form
                                                    action="{{ route('admin.users.disable', $user->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Disable this account? The user will no longer be able to log in.')"
                                                >
                                                    @csrf

                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800"
                                                    >
                                                        <i class="fas fa-ban text-[10px]"></i>
                                                        Disable
                                                    </button>
                                                </form>
                                            @else
                                                <form
                                                    action="{{ route('admin.users.enable', $user->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Enable this account? The user will be allowed to log in again.')"
                                                >
                                                    @csrf

                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-green-700"
                                                    >
                                                        <i class="fas fa-circle-check text-[10px]"></i>
                                                        Enable
                                                    </button>
                                                </form>
                                            @endif
                                        @endif

                                        @if($authUser->canDeleteUsers() && $authUser->id !== $user->id && !$user->isSuperAdmin())
                                            <form
                                                action="{{ route('admin.users.destroy', $user->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Delete this user?')"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 transition"
                                                >
                                                    <i class="fas fa-trash text-[10px]"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        @elseif(!($authUser->isSuperAdmin() && !$user->isSuperAdmin()))
                                            <span class="text-xs text-slate-400">
                                                {{ $authUser->id === $user->id ? 'Current user' : 'No action' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-14 text-center text-slate-500">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<script>
    function filterUsersTable(query) {
        const search = String(query || '').toLowerCase().trim();
        document.querySelectorAll('.user-row').forEach(row => {
            row.style.display = row.dataset.search.includes(search) ? '' : 'none';
        });
    }

    function usersPage(config) {
        return {
            showCreateUser: config.showCreateUser || false,
            accountSource: config.accountSource || 'manual',
            selectedEmployeeId: config.selectedEmployeeId || '',
            selectedContactId: config.selectedContactId || '',
            name: config.name || '',
            email: config.email || '',
            role: config.role || '',
            employees: config.employees || [],
            contacts: config.contacts || [],

            init() {
                if ((this.accountSource === 'employee' && this.selectedEmployeeId) ||
                    (this.accountSource === 'client' && this.selectedContactId)) {
                    this.applySelectedProfile(false);
                }
            },

            openCreateModal() {
                this.showCreateUser = true;
            },

            changeAccountSource() {
                this.selectedEmployeeId = '';
                this.selectedContactId = '';
                this.name = '';
                this.email = '';
                this.role = this.accountSource === 'client' ? 'Client' : '';
            },

            selectedEmployee() {
                return this.employees.find(employee => String(employee.id) === String(this.selectedEmployeeId));
            },

            selectedContact() {
                return this.contacts.find(contact => String(contact.id) === String(this.selectedContactId));
            },

            applySelectedProfile(overwrite = true) {
                if (this.accountSource === 'employee') {
                    const employee = this.selectedEmployee();

                    if (!employee) {
                        if (overwrite) {
                            this.name = '';
                            this.email = '';
                        }
                        return;
                    }

                    this.name = employee.name || '';
                    this.email = employee.email || '';
                }

                if (this.accountSource === 'client') {
                    const contact = this.selectedContact();

                    if (!contact) {
                        if (overwrite) {
                            this.name = '';
                            this.email = '';
                        }
                        this.role = 'Client';
                        return;
                    }

                    this.name = contact.name || '';
                    this.email = contact.email || '';
                    this.role = 'Client';
                }
            },
        };
    }
</script>
@endsection
