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
@endphp

<div
    class="w-full h-full px-6 py-5"
    x-data="usersPage({
        showCreateUser: {{ $errors->any() ? 'true' : 'false' }},
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
    {{-- CREATE USER SLIDE OVER --}}
    <div x-show="showCreateUser" x-cloak class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div
                x-show="showCreateUser"
                @click="showCreateUser = false"
                class="absolute inset-0 bg-black/40"
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
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">Create User</h2>
                        <button
                            type="button"
                            @click="showCreateUser = false"
                            class="text-gray-400 hover:text-gray-600 text-lg"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form action="{{ route('admin.users.store') }}" method="POST" class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Account Source</label>
                            <select
                                name="account_source"
                                x-model="accountSource"
                                @change="changeAccountSource()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="manual">Manual Account</option>
                                <option value="employee">Link to Employee Profile</option>
                                <option value="client">Link to Client Contact</option>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">
                                Employee accounts use the employee work email. Client accounts use the contact email.
                            </p>
                        </div>

                        <div x-show="accountSource === 'employee'" x-cloak>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Employee Profile</label>
                            <select
                                name="employee_id"
                                x-model="selectedEmployeeId"
                                @change="applySelectedProfile()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="">Select employee profile</option>
                                <template x-for="employee in employees" :key="employee.id">
                                    <option :value="employee.id" x-text="employee.label"></option>
                                </template>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">
                                Selecting a profile will auto-fill the name and email below. Choose Employee or Admin manually for the role.
                            </p>
                        </div>

                        <div x-show="accountSource === 'client'" x-cloak>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Client Contact</label>
                            <select
                                name="contact_id"
                                x-model="selectedContactId"
                                @change="applySelectedProfile()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="">Select client contact</option>
                                <template x-for="contact in contacts" :key="contact.id">
                                    <option :value="contact.id" x-text="contact.label"></option>
                                </template>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">
                                Only client contacts without linked accounts are shown. Selecting a contact will auto-fill the name and email below and set the role to Client.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Full Name</label>
                            <input
                                type="text"
                                name="name"
                                x-model="name"
                                placeholder="Auto-filled from selected employee/contact, or type manually"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Email</label>
                            <input
                                type="email"
                                name="email"
                                x-model="email"
                                placeholder="Auto-filled from employee work email or contact email"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Role</label>
                            <select
                                name="role"
                                x-model="role"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                :disabled="accountSource === 'client'"
                            >
                                <option value="">Select role</option>
                                <option value="Admin">Admin</option>
                                <option value="Employee">Employee</option>
                                <option value="Client">Client</option>
                            </select>
                            <input type="hidden" name="role" value="Client" x-show="accountSource === 'client'">
                            <p class="text-[11px] text-gray-400 mt-1">
                                Client source will always create a Client account. Employee source can be Employee or Admin; choose manually.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Password</label>
                            <input
                                type="password"
                                name="password"
                                placeholder="Enter password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Confirm Password</label>
                            <input
                                type="password"
                                name="password_confirmation"
                                placeholder="Confirm password"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            >
                        </div>

                        <div class="pt-4 border-t border-gray-200 flex gap-3">
                            <button
                                type="button"
                                @click="showCreateUser = false"
                                class="flex-1 border border-gray-300 text-gray-700 rounded-lg py-2.5 text-sm font-medium hover:bg-gray-50 transition"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="flex-1 bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-blue-700 transition"
                            >
                                Save User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- PAGE WRAPPER --}}
    <div class="bg-white border border-gray-200 rounded-xl min-h-[calc(100vh-7rem)] flex flex-col">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h1 class="text-[30px] font-semibold text-gray-800 leading-none">Users</h1>
                <p class="text-sm text-gray-500 mt-1">Manage login credentials, permissions, and roles</p>
            </div>

            <button
                @click="openCreateModal()"
                class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
                + Create User
            </button>
        </div>

        <div class="px-5 py-5 flex-1 flex flex-col">
            <div class="border border-gray-200 rounded-xl overflow-hidden flex-1">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-4 py-3 border-r border-gray-200 font-semibold">ID</th>
                            <th class="px-4 py-3 border-r border-gray-200 font-semibold">Name</th>
                            <th class="px-4 py-3 border-r border-gray-200 font-semibold">Email</th>
                            <th class="px-4 py-3 border-r border-gray-200 font-semibold">Role</th>
                            <th class="px-4 py-3 border-r border-gray-200 font-semibold">Linked Profile</th>
                            <th class="px-4 py-3 border-r border-gray-200 font-semibold">Permissions</th>
                            <th class="px-4 py-3 border-r border-gray-200 font-semibold">Created At</th>
                            <th class="px-4 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white text-gray-700">
                        @forelse($users as $user)
                            <tr class="border-t border-gray-200 hover:bg-gray-50 align-top">
                                <td class="px-4 py-3 border-r border-gray-200">{{ $user->id }}</td>
                                <td class="px-4 py-3 border-r border-gray-200">{{ $user->name }}</td>
                                <td class="px-4 py-3 border-r border-gray-200">{{ $user->email }}</td>

                                <td class="px-4 py-3 border-r border-gray-200">
                                    @if($authUser->canManageRoles() && !$user->isSuperAdmin())
                                        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="space-y-2">
                                            @csrf

                                            <select
                                                name="role"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                            >
                                                <option value="Admin" {{ $user->role === 'Admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="Employee" {{ $user->role === 'Employee' ? 'selected' : '' }}>Employee</option>
                                                <option value="Client" {{ $user->role === 'Client' ? 'selected' : '' }}>Client</option>
                                            </select>

                                            @if($authUser->isSuperAdmin())
                                                <div class="space-y-1 text-xs">
                                                    <label class="flex items-center gap-2">
                                                        <input
                                                            type="checkbox"
                                                            name="can_edit_user_roles"
                                                            value="1"
                                                            {{ $user->can_edit_user_roles ? 'checked' : '' }}
                                                        >
                                                        <span>Can edit user roles</span>
                                                    </label>

                                                    <label class="flex items-center gap-2">
                                                        <input
                                                            type="checkbox"
                                                            name="can_delete_users"
                                                            value="1"
                                                            {{ $user->can_delete_users ? 'checked' : '' }}
                                                        >
                                                        <span>Can delete users</span>
                                                    </label>
                                                </div>
                                            @endif

                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 transition"
                                            >
                                                Update
                                            </button>
                                        </form>
                                    @else
                                        @php
                                            $roleClasses = $user->role === 'Admin'
                                                ? 'bg-blue-50 text-blue-700'
                                                : ($user->role === 'Superadmin'
                                                    ? 'bg-purple-50 text-purple-700'
                                                    : 'bg-gray-100 text-gray-700');
                                        @endphp

                                        <span class="px-2 py-1 text-xs rounded-full font-medium {{ $roleClasses }}">
                                            {{ $user->role }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 border-r border-gray-200 text-xs text-gray-700">
                                    @if($user->employeeProfile)
                                        <div class="font-semibold text-gray-900">{{ $user->employeeProfile->full_name }}</div>
                                        <div class="text-gray-500">Employee • {{ $user->employeeProfile->employee_code }}</div>
                                    @elseif($user->contactProfile)
                                        <div class="font-semibold text-gray-900">{{ $user->contactProfile->full_name ?: trim(($user->contactProfile->first_name ?? '').' '.($user->contactProfile->last_name ?? '')) }}</div>
                                        <div class="text-gray-500">Client Contact</div>
                                    @else
                                        <span class="text-gray-400">Manual account</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 border-r border-gray-200 text-xs">
                                    <div class="space-y-1">
                                        <div>
                                            <span class="font-medium text-gray-600">Edit Roles:</span>
                                            <span class="{{ $user->can_edit_user_roles ? 'text-green-600' : 'text-gray-400' }}">
                                                {{ $user->can_edit_user_roles ? 'Yes' : 'No' }}
                                            </span>
                                        </div>

                                        <div>
                                            <span class="font-medium text-gray-600">Delete Users:</span>
                                            <span class="{{ $user->can_delete_users ? 'text-green-600' : 'text-gray-400' }}">
                                                {{ $user->can_delete_users ? 'Yes' : 'No' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 border-r border-gray-200">
                                    {{ $user->created_at?->format('Y-m-d') }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
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
                                                    class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 transition"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400">
                                                {{ $authUser->id === $user->id ? 'Current user' : 'No action' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 flex items-center justify-between text-[11px] text-gray-500 px-1">
                <div>
                    Total Users <span class="text-gray-800 font-semibold">{{ $users->total() }}</span>
                </div>

                <div class="flex items-center gap-4">
                    <span>{{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }}</span>
                </div>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<script>
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

                    // Do not auto-select a role for employee profiles.
                    // Employee Profile can belong to staff, admin, HR, president, etc.
                    // Admin must choose the correct role manually.
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
