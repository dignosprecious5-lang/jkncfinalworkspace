@extends('layouts.app')
@section('title', 'Edit User Permissions')

@section('content')
@php
    $permissionGroups = [
        'Administration' => [
            ['name' => 'manage_users', 'label' => 'Manage Users', 'description' => 'Create and manage user accounts.'],
            ['name' => 'access_admin_dashboard', 'label' => 'Access Admin Dashboard', 'description' => 'View approval and admin dashboards.'],
        ],
        'Town Hall' => [
            ['name' => 'access_townhall', 'label' => 'Access Town Hall', 'description' => 'View Town Hall communications.'],
            ['name' => 'create_townhall', 'label' => 'Create Town Hall', 'description' => 'Create and submit communications.'],
            ['name' => 'approve_townhall', 'label' => 'Approve Town Hall', 'description' => 'Review and approve communications.'],
        ],
        'Corporate' => [
            ['name' => 'access_corporate', 'label' => 'Access Corporate', 'description' => 'Open corporate module records.'],
            ['name' => 'create_corporate', 'label' => 'Create Corporate', 'description' => 'Create corporate documents.'],
            ['name' => 'approve_corporate', 'label' => 'Approve Corporate', 'description' => 'Approve corporate submissions.'],
        ],
        'Policies' => [
            ['name' => 'access_policies', 'label' => 'Access Policies', 'description' => 'View policy library.'],
            ['name' => 'approve_policies', 'label' => 'Approve Policies', 'description' => 'Review and approve policy submissions.'],
        ],
        'Human Capital' => [
            ['name' => 'access_human_capital', 'label' => 'Access Human Capital', 'description' => 'Open HR and employee modules.'],
        ],
        'CRM / Operations' => [
            ['name' => 'access_activities', 'label' => 'Access Activities', 'description' => 'View activity records.'],
            ['name' => 'access_contacts', 'label' => 'Access Contacts', 'description' => 'View contact records.'],
            ['name' => 'access_company', 'label' => 'Access Company', 'description' => 'View company records.'],
            ['name' => 'access_transmittal', 'label' => 'Access Transmittal', 'description' => 'View transmittal records.'],
            ['name' => 'access_deals', 'label' => 'Access Deals', 'description' => 'View sales deals.'],
            ['name' => 'access_services', 'label' => 'Access Services', 'description' => 'View service records.'],
            ['name' => 'access_project', 'label' => 'Access Project', 'description' => 'View project records.'],
            ['name' => 'access_regular', 'label' => 'Access Regular', 'description' => 'View regular client records.'],
            ['name' => 'access_product', 'label' => 'Access Product', 'description' => 'View product records.'],
        ],
        'Sales & Marketing' => [
            ['name' => 'access_sales_marketing', 'label' => 'Access Sales & Marketing', 'description' => 'Open sales and marketing module.'],
            ['name' => 'create_sales_marketing', 'label' => 'Create Sales & Marketing', 'description' => 'Create sales and marketing records.'],
            ['name' => 'approve_sales_marketing', 'label' => 'Approve Sales & Marketing', 'description' => 'Approve sales and marketing requests.'],
        ],
    ];
@endphp

<div class="w-full min-h-screen bg-slate-50 px-6 py-5">

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

    @php
        $enabledCount = collect($permissionGroups)
            ->flatten(1)
            ->filter(fn ($item) => (bool) ($permission->{$item['name']} ?? false))
            ->count();
        $totalCount = collect($permissionGroups)->flatten(1)->count();
        $roleClass = match(strtolower((string) $user->role)) {
            'superadmin', 'super admin', 'system super admin' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'admin' => 'bg-blue-50 text-blue-700 ring-blue-100',
            'employee' => 'bg-green-50 text-green-700 ring-green-100',
            'client' => 'bg-amber-50 text-amber-700 ring-amber-100',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    @endphp

    <form action="{{ route('admin.user-permissions.update', $user->id) }}" method="POST" class="space-y-5">
        @csrf

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-6">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50 via-white to-indigo-50"></div>

                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-xl font-bold text-blue-700">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Edit User Permissions</h1>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $roleClass }}">
                                    {{ $user->role }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $user->name }} • {{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.user-permissions') }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i class="fas fa-arrow-left text-xs"></i>
                            Back
                        </a>

                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            <i class="fas fa-save text-xs"></i>
                            Save Permissions
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Enabled</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $enabledCount }}</p>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wide text-blue-500">Total Permissions</p>
                <p class="mt-2 text-2xl font-bold text-blue-700">{{ $totalCount }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">User ID</p>
                <p class="mt-2 font-mono text-2xl font-bold text-slate-900">#{{ $user->id }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Permission Groups</h2>
                <p class="mt-1 text-sm text-slate-500">Select the permissions that should apply only to this user account.</p>
            </div>

            <div class="p-5 space-y-5">
                @foreach($permissionGroups as $groupName => $items)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-600">{{ $groupName }}</h3>
                            <span class="text-xs text-slate-400">
                                {{ collect($items)->filter(fn ($item) => (bool) ($permission->{$item['name']} ?? false))->count() }} / {{ count($items) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($items as $item)
                                <label class="group flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-blue-200 hover:shadow-sm">
                                    <input
                                        type="checkbox"
                                        name="{{ $item['name'] }}"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        {{ ($permission->{$item['name']} ?? false) ? 'checked' : '' }}
                                    >
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">{{ $item['label'] }}</span>
                                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $item['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="sticky bottom-0 border-t border-slate-200 bg-white px-5 py-4">
                <div class="flex justify-end gap-2">
                    <a href="{{ route('admin.user-permissions') }}"
                       class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Cancel
                    </a>

                    <button type="submit"
                            class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Save Permissions
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
