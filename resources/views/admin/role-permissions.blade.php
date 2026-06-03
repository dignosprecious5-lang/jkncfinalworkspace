@extends('layouts.app')
@section('title', 'Role Permissions')

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
        'Finance' => [
            ['name' => 'access_finance', 'label' => 'Access Finance', 'description' => 'Open the Finance module shell.'],
            ['name' => 'create_finance', 'label' => 'Create Finance Records', 'description' => 'Create and submit finance records for permitted tabs.'],
            ['name' => 'approve_finance', 'label' => 'Approve Finance Records', 'description' => 'Review finance records and see submitted finance data.'],
            ['name' => 'access_finance_supplier', 'label' => 'Finance: Supplier', 'description' => 'Show the Supplier tab.'],
            ['name' => 'access_finance_service', 'label' => 'Finance: Service', 'description' => 'Show the Service tab.'],
            ['name' => 'access_finance_product', 'label' => 'Finance: Product', 'description' => 'Show the Product tab.'],
            ['name' => 'access_finance_chart_account', 'label' => 'Finance: Chart of Accounts', 'description' => 'Show the Chart of Accounts tab.'],
            ['name' => 'access_finance_bank_account', 'label' => 'Finance: Bank Accounts', 'description' => 'Show the Bank Accounts tab.'],
            ['name' => 'access_finance_pr', 'label' => 'Finance: Purchase Request', 'description' => 'Show the Purchase Request tab.'],
            ['name' => 'access_finance_po', 'label' => 'Finance: Purchase Order', 'description' => 'Show the Purchase Order tab.'],
            ['name' => 'access_finance_ca', 'label' => 'Finance: Cash Advance', 'description' => 'Show the Cash Advance tab.'],
            ['name' => 'access_finance_lr', 'label' => 'Finance: Liquidation Report', 'description' => 'Show the Liquidation Report tab.'],
            ['name' => 'access_finance_err', 'label' => 'Finance: Expense Reimbursement', 'description' => 'Show the Expense Reimbursement Request tab.'],
            ['name' => 'access_finance_dv', 'label' => 'Finance: Disbursement Voucher', 'description' => 'Show the Disbursement Voucher tab.'],
            ['name' => 'access_finance_pda', 'label' => 'Finance: Payroll Disbursement', 'description' => 'Show the Payroll Disbursement Authorization tab.'],
            ['name' => 'access_finance_crf', 'label' => 'Finance: Cash Return Form', 'description' => 'Show the Cash Return Form tab.'],
            ['name' => 'access_finance_ibtf', 'label' => 'Finance: Interbank Transfer', 'description' => 'Show the Interbank Fund Transfer Form tab.'],
            ['name' => 'access_finance_arf', 'label' => 'Finance: Asset Registration', 'description' => 'Show the Asset Registration Form tab.'],
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

    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative px-6 py-6">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-50 via-white to-purple-50"></div>

                <div class="relative">
                    <div class="inline-flex items-center gap-2 rounded-full border border-purple-100 bg-purple-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-purple-700">
                        <i class="fas fa-shield-halved text-[10px]"></i>
                        Role Access Matrix
                    </div>

                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Role Permissions</h1>
                    <p class="mt-1 text-sm text-slate-500">Manage default access rights assigned to each system role.</p>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            @foreach($permissions as $permission)
                @php
                    $isProtected = $permission->role === 'SuperAdmin';
                    $enabledCount = $isProtected
                        ? collect($permissionGroups)->flatten(1)->count()
                        : collect($permissionGroups)
                            ->flatten(1)
                            ->filter(fn ($item) => (bool) ($permission->{$item['name']} ?? false))
                            ->count();
                    $totalCount = collect($permissionGroups)->flatten(1)->count();
                @endphp

                <form action="{{ route('admin.role-permissions.update', $permission->id) }}" method="POST" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @csrf

                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $isProtected ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                                    <i class="fas {{ $isProtected ? 'fa-crown' : 'fa-user-shield' }}"></i>
                                </div>

                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-xl font-bold text-slate-900">{{ $permission->role }}</h2>

                                        @if($isProtected)
                                            <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-700 ring-1 ring-purple-100">
                                                Protected
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $enabledCount }} of {{ $totalCount }} permissions enabled
                                        @if($isProtected)
                                            • SuperAdmin has full access and cannot be modified.
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($isProtected)
                                <button
                                    type="button"
                                    disabled
                                    class="rounded-xl bg-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-500 cursor-not-allowed"
                                >
                                    Protected
                                </button>
                            @else
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                                >
                                    <i class="fas fa-save text-xs"></i>
                                    Save Changes
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 space-y-5">
                        @foreach($permissionGroups as $groupName => $items)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-600">{{ $groupName }}</h3>
                                    <span class="text-xs text-slate-400">
                                        {{ $isProtected ? count($items) : collect($items)->filter(fn ($item) => (bool) ($permission->{$item['name']} ?? false))->count() }} / {{ count($items) }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach($items as $item)
                                        <label class="group flex cursor-pointer items-start gap-3 rounded-xl border bg-white p-4 transition {{ $isProtected ? 'border-slate-200 opacity-75' : 'border-slate-200 hover:border-blue-200 hover:shadow-sm' }}">
                                            <input
                                                type="checkbox"
                                                name="{{ $item['name'] }}"
                                                class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                {{ $isProtected || ($permission->{$item['name']} ?? false) ? 'checked' : '' }}
                                                {{ $isProtected ? 'disabled' : '' }}
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
                </form>
            @endforeach
        </div>
    </div>
</div>
@endsection
