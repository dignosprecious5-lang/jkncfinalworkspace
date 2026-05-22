@extends('layouts.app')
@section('title', 'Services')

@section('content')
@php
    $isAdminReviewer = false;
    $statusClasses = [
        'Pending Approval' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Draft' => 'border-slate-200 bg-slate-50 text-slate-700',
        'Active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'Inactive' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Rejected' => 'border-rose-200 bg-rose-50 text-rose-700',
        'Archived' => 'border-rose-200 bg-rose-50 text-rose-700',
    ];
@endphp

<div class="px-6 py-6 lg:px-8">
    <div class="mx-auto max-w-[1600px]">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight text-gray-900">{{ $isAdminReviewer ? 'Services Review' : 'Services' }}</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $isAdminReviewer
                    ? 'Review submitted services, then approve or reject them before they become active in the catalog.'
                    : 'Standardized service catalog with configurable fields, routing, scheduling, and pricing.' }}
            </p>
        </div>
        <button type="button" id="openGlobalServiceModalCreate" class="inline-flex h-11 items-center justify-center rounded-xl bg-blue-600 px-5 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
            <i class="fas fa-plus mr-2 text-xs"></i> Add Service
        </button>
    </div>

    @if (session('services_success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('services_success') }}
        </div>
    @endif

    <div class="mb-6 grid gap-3 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active Services</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['active'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pending Approval</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['pending'] }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rejected Services</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['rejected'] }}</p>
        </div>
    </div>

    @if ($isAdminReviewer)
        <div class="mb-4 flex flex-wrap gap-2">
            @php
                $reviewTabs = [
                    'pending_review' => 'Pending Review',
                    'active' => 'Active',
                    'rejected' => 'Rejected',
                    'all' => 'All Services',
                ];
            @endphp
            @foreach ($reviewTabs as $tabValue => $tabLabel)
                <a
                    href="{{ route('services.index', array_merge(request()->query(), ['tab' => $tabValue])) }}"
                    class="inline-flex h-10 items-center rounded-lg border px-4 text-sm font-medium {{ $filters['tab'] === $tabValue ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
                >
                    {{ $tabLabel }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($isAdminReviewer && $filters['tab'] === 'pending_review')
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50/60 p-4">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Approval Queue</h2>
                    <p class="text-sm text-gray-600">Review submitted services and queued edit or delete requests before anything changes in the live catalog.</p>
                </div>
                <div class="text-sm font-medium text-amber-800">
                    {{ $services->total() + $pendingChangeRequests->count() }} pending {{ \Illuminate\Support\Str::plural('item', $services->total() + $pendingChangeRequests->count()) }}
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @foreach ($pendingChangeRequests as $changeRequest)
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $changeRequest->record_name ?: 'Service' }}</h3>
                                <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                    Pending {{ ucfirst($changeRequest->action) }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                Service ID {{ $changeRequest->record_public_id ?: '-' }} • Requested by {{ $changeRequest->submitter?->name ?: 'Unknown user' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2 xl:justify-end">
                            <form method="POST" action="{{ route('catalog-change-requests.approve', $changeRequest) }}">
                                @csrf
                                <button type="submit" class="inline-flex h-10 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700 hover:bg-emerald-100">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('catalog-change-requests.reject', $changeRequest) }}">
                                @csrf
                                <button type="submit" class="inline-flex h-10 items-center rounded-lg border border-rose-200 bg-rose-50 px-4 text-sm font-medium text-rose-700 hover:bg-rose-100">Reject</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach

            @forelse ($services as $service)
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $service->service_name }}</h3>
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClasses[$service->status] ?? 'border-gray-200 bg-gray-50 text-gray-700' }}">{{ $service->status }}</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">ID {{ $service->service_id }} · {{ $service->company?->company_name ?: 'Global Catalog' }}</p>
                            <p class="mt-3 text-sm leading-6 text-gray-600">{{ $service->service_description }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 xl:justify-end">
                            <a href="{{ route('services.show', $service->id) }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">Review Details</a>
                            <form method="POST" action="{{ route('services.approve', $service->id) }}">
                                @csrf
                                <button type="submit" class="inline-flex h-10 items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700 hover:bg-emerald-100">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('services.reject', $service->id) }}">
                                @csrf
                                <button type="submit" class="inline-flex h-10 items-center rounded-lg border border-rose-200 bg-rose-50 px-4 text-sm font-medium text-rose-700 hover:bg-rose-100">Reject</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Service Area</p>
                            <p class="mt-2 text-sm font-medium text-gray-800">{{ implode(', ', $service->service_area ?? []) ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Category</p>
                            <p class="mt-2 text-sm font-medium text-gray-800">{{ $service->category ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Assigned Unit</p>
                            <p class="mt-2 text-sm font-medium text-gray-800">{{ $service->assigned_unit ?: '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Created By</p>
                            <p class="mt-2 text-sm font-medium text-gray-800">{{ $service->creator?->name ?: '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pricing Snapshot</p>
                            <dl class="mt-3 space-y-2 text-sm text-gray-700">
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Rate / Price</dt>
                                    <dd class="font-medium text-gray-900">
                                        @if ($service->rate_per_unit)
                                            {{ number_format((float) $service->rate_per_unit, 2) }} / {{ $service->unit }}
                                        @elseif ($service->price_fee)
                                            {{ number_format((float) $service->price_fee, 2) }}
                                        @else
                                            -
                                        @endif
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Cost of Service</dt>
                                    <dd class="font-medium text-gray-900">{{ $service->cost_of_service ? number_format((float) $service->cost_of_service, 2) : '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Tax Treatment</dt>
                                    <dd class="font-medium text-gray-900">{{ $service->tax_type ?: '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div class="rounded-xl border border-gray-200 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Engagement Snapshot</p>
                            <dl class="mt-3 space-y-2 text-sm text-gray-700">
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Frequency</dt>
                                    <dd class="font-medium text-gray-900">{{ $service->frequency ?: '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Engagement Type</dt>
                                    <dd class="font-medium text-right text-gray-900">{{ implode(', ', $service->engagement_structure ?? []) ?: '-' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt>Updated At</dt>
                                    <dd class="font-medium text-gray-900">{{ optional($service->updated_at)->format('M d, Y h:i A') ?: '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center text-sm text-gray-500">
                    No services waiting for review.
                </div>
            @endforelse
        </div>

        @if ($services->hasPages())
            <div class="mt-6">
                {{ $services->onEachSide(1)->links() }}
            </div>
        @endif
    @else
    <form method="GET" action="{{ route('services.index') }}" class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
        <div class="grid gap-3 xl:grid-cols-[minmax(260px,1.6fr)_repeat(5,minmax(170px,1fr))_auto]">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400"></i>
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search services..." class="h-11 w-full rounded-xl border border-gray-200 bg-white pl-9 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            </div>
            <select name="status" class="h-11 rounded-xl border border-gray-200 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                <option value="all">Status: All</option>
                @foreach ($statusOptions as $option)
                    <option value="{{ $option }}" @selected($filters['status'] === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <select name="category" class="h-11 rounded-xl border border-gray-200 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                <option value="all">Category: All</option>
                @foreach ($categories as $option)
                    <option value="{{ $option }}" @selected($filters['category'] === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <select name="assigned_unit" class="h-11 rounded-xl border border-gray-200 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                <option value="all">Assigned Unit: All</option>
                @foreach ($assignedUnitOptions as $option)
                    <option value="{{ $option }}" @selected($filters['assigned_unit'] === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <select name="frequency" class="h-11 rounded-xl border border-gray-200 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                <option value="all">Frequency: All</option>
                @foreach (['One-time', 'Daily', 'Weekly', 'Monthly', 'Quarterly', 'Annually', 'Custom'] as $option)
                    <option value="{{ $option }}" @selected($filters['frequency'] === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <select name="engagement_type" class="h-11 rounded-xl border border-gray-200 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                <option value="all">Engagement: All</option>
                <option value="Project Engagement" @selected($filters['engagement_type'] === 'Project Engagement')>Project Engagement</option>
                <option value="Regular (Retainer)" @selected($filters['engagement_type'] === 'Regular (Retainer)')>Regular (Retainer)</option>
                <option value="Hybrid" @selected($filters['engagement_type'] === 'Hybrid')>Hybrid Engagement</option>
            </select>
            <button class="h-11 rounded-xl border border-gray-200 bg-gray-900 px-5 text-sm font-medium text-white hover:bg-gray-800">Apply</button>
        </div>
    </form>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @php
                        $serviceAreaTabs = collect($serviceAreaOptions)
                            ->reject(fn ($option) => $option === 'Others')
                            ->values();
                    @endphp
                    <label for="serviceAreaQuickFilter" class="text-sm font-medium text-gray-600">Service Area</label>
                    <form method="GET" action="{{ route('services.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <select id="serviceAreaQuickFilter" name="service_area" onchange="this.form.submit()" class="h-10 min-w-[320px] rounded-xl border border-gray-200 bg-white px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="all" @selected($filters['service_area'] === 'all')>All Service Areas</option>
                            @foreach ($serviceAreaTabs as $serviceAreaTab)
                                <option value="{{ $serviceAreaTab }}" @selected($filters['service_area'] === $serviceAreaTab)>{{ $serviceAreaTab }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] }}">
                        <input type="hidden" name="category" value="{{ $filters['category'] }}">
                        <input type="hidden" name="assigned_unit" value="{{ $filters['assigned_unit'] }}">
                        <input type="hidden" name="frequency" value="{{ $filters['frequency'] }}">
                        <input type="hidden" name="engagement_type" value="{{ $filters['engagement_type'] }}">
                        <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                        <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    </form>
                </div>
                <button id="openCreateFieldDropdown" type="button" class="self-start text-sm font-medium text-blue-600 hover:text-blue-700 lg:self-auto">+ Create Field</button>
            </div>
        </div>
        <div class="overflow-x-auto overflow-y-visible">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-700">
                    <tr>
                        <th data-column-key="service_name" data-column-type="base" class="group px-3 py-3 text-left font-medium">
                            <div class="inline-flex items-center gap-1">
                                <span>Service Name</span>
                                <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="service_name" data-column-type="base">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </div>
                        </th>
                        <th data-column-key="category" data-column-type="base" class="group px-3 py-3 text-left font-medium">
                            <div class="inline-flex items-center gap-1">
                                <span>Category</span>
                                <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="category" data-column-type="base">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </div>
                        </th>
                        <th data-column-key="company" data-column-type="base" class="group px-3 py-3 text-left font-medium">
                            <div class="inline-flex items-center gap-1">
                                <span>Company</span>
                                <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="company" data-column-type="base">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </div>
                        </th>
                        <th data-column-key="frequency" data-column-type="base" class="group px-3 py-3 text-left font-medium">
                            <div class="inline-flex items-center gap-1">
                                <span>Frequency</span>
                                <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="frequency" data-column-type="base">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </div>
                        </th>
                        <th data-column-key="engagement_type" data-column-type="base" class="group px-3 py-3 text-left font-medium">
                            <div class="inline-flex items-center gap-1">
                                <span>Engagement Type</span>
                                <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="engagement_type" data-column-type="base">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </div>
                        </th>
                        <th data-column-key="price_rate" data-column-type="base" class="group px-3 py-3 text-left font-medium">
                            <div class="inline-flex items-center gap-1">
                                <span>Price / Rate</span>
                                <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="price_rate" data-column-type="base">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </div>
                        </th>
                        <th data-column-key="assigned_unit" data-column-type="base" class="group px-3 py-3 text-left font-medium">
                            <div class="inline-flex items-center gap-1">
                                <span>Assigned Unit</span>
                                <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="assigned_unit" data-column-type="base">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </div>
                        </th>
                        <th data-column-key="status" data-column-type="base" class="group px-3 py-3 text-left font-medium">
                            <div class="inline-flex items-center gap-1">
                                <span>Status</span>
                                <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="status" data-column-type="base">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </div>
                        </th>
                        <th data-column-key="service_owner" data-column-type="base" class="group px-3 py-3 text-left font-medium">
                            <div class="inline-flex items-center gap-1">
                                <span>Service Owner</span>
                                <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="service_owner" data-column-type="base">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </div>
                        </th>
                        @foreach ($customFields as $field)
                            <th data-column-key="{{ $field->field_key }}" data-column-type="custom" class="group px-3 py-3 text-left font-medium">
                                <div class="inline-flex items-center gap-1">
                                    <span>{{ $field->field_name }}</span>
                                    <button type="button" class="field-header-trigger invisible inline-flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition group-hover:visible hover:bg-gray-100 hover:text-gray-600" data-column-key="{{ $field->field_key }}" data-column-type="custom">
                                        <i class="fas fa-ellipsis-v text-[10px]"></i>
                                    </button>
                                </div>
                            </th>
                        @endforeach
                        <th class="w-[180px] px-3 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($services as $service)
                        @php
                            $pendingRequest = $pendingRequestMap->get($service->id);
                        @endphp
                        <tr class="service-row text-gray-700 hover:bg-gray-50">
                            <td data-column-key="service_name" class="px-3 py-3">
                                <a href="{{ route('services.show', $service->id) }}" class="font-medium text-gray-900 hover:text-blue-700">{{ $service->service_name }}</a>
                                <div class="mt-1 text-xs text-gray-500">ID {{ $service->service_id }} @if($service->company)<span class="mx-1">|</span>{{ $service->company->company_name }}@endif</div>
                            </td>
                            <td data-column-key="category" class="px-3 py-3 text-gray-600">{{ $service->category ?: '-' }}</td>
                            <td data-column-key="company" class="px-3 py-3 text-gray-600">{{ $service->company?->company_name ?: 'Global Catalog' }}</td>
                            <td data-column-key="frequency" class="px-3 py-3 text-gray-600">{{ $service->frequency ?: '-' }}</td>
                            <td data-column-key="engagement_type" class="px-3 py-3 text-gray-600">{{ implode(', ', $service->engagement_structure ?? []) ?: '-' }}</td>
                            <td data-column-key="price_rate" class="px-3 py-3 text-gray-600">
                                @if ($service->rate_per_unit)
                                    {{ number_format((float) $service->rate_per_unit, 2) }} / {{ $service->unit }}
                                @elseif ($service->price_fee)
                                    {{ number_format((float) $service->price_fee, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td data-column-key="assigned_unit" class="px-3 py-3 text-gray-600">{{ $service->assigned_unit ?: '-' }}</td>
                            <td data-column-key="status" class="px-3 py-3">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex w-fit rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClasses[$service->status] ?? 'border-gray-200 bg-gray-50 text-gray-700' }}">{{ $service->status }}</span>
                                    @if ($pendingRequest)
                                        <span class="inline-flex w-fit rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Pending {{ ucfirst($pendingRequest->action) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td data-column-key="service_owner" class="px-3 py-3 text-gray-600">{{ $service->creator?->name ?: '-' }}</td>
                            @foreach ($customFields as $field)
                                <td data-column-key="{{ $field->field_key }}" class="px-3 py-3 text-gray-600">{{ data_get($service->custom_field_values, $field->field_key, '-') ?: '-' }}</td>
                            @endforeach
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-start gap-2 whitespace-nowrap">
                                    @if ($isAdminReviewer && $service->status === 'Pending Approval')
                                        <form method="POST" action="{{ route('services.approve', $service->id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full border border-emerald-200 px-3 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('services.reject', $service->id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full border border-rose-200 px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50">Reject</button>
                                        </form>
                                    @elseif ($pendingRequest)
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">Pending {{ ucfirst($pendingRequest->action) }}</span>
                                        @if ($isAdminReviewer)
                                            <form method="POST" action="{{ route('catalog-change-requests.approve', $pendingRequest) }}">
                                                @csrf
                                                <button type="submit" class="rounded-full border border-emerald-200 px-3 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('catalog-change-requests.reject', $pendingRequest) }}">
                                                @csrf
                                                <button type="submit" class="rounded-full border border-rose-200 px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50">Reject</button>
                                            </form>
                                        @endif
                                    @else
                                        <button type="button" class="rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50" data-global-service-edit='@json($service)'>Edit</button>
                                        <form method="POST" action="{{ route('services.destroy', $service->id) }}" onsubmit="return confirm('Submit a delete request for this service?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-full border border-red-200 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 9 + $customFields->count() }}" class="px-3 py-16 text-center text-sm text-gray-500">No services found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-5 py-4">
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-700">
                <span>Total Services: {{ $services->total() }}</span>
                <div class="ml-auto flex items-center gap-2 text-xs text-gray-600">
                    <form method="GET" action="{{ route('services.index') }}" class="flex items-center gap-2">
                        <span>Records per page</span>
                        <select name="per_page" class="h-9 rounded-lg border border-gray-200 px-3 text-xs" onchange="this.form.submit()">
                            @foreach ([5, 10, 25, 50] as $size)
                                <option value="{{ $size }}" {{ (int) $filters['per_page'] === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] }}">
                        <input type="hidden" name="category" value="{{ $filters['category'] }}">
                        <input type="hidden" name="assigned_unit" value="{{ $filters['assigned_unit'] }}">
                        <input type="hidden" name="frequency" value="{{ $filters['frequency'] }}">
                        <input type="hidden" name="engagement_type" value="{{ $filters['engagement_type'] }}">
                        <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                    </form>
                    <span>{{ $services->firstItem() ?? 0 }} to {{ $services->lastItem() ?? 0 }} | Page {{ $services->currentPage() }} of {{ $services->lastPage() }}</span>
                </div>
            </div>
            <div class="mt-4">
                {{ $services->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
    @endif
</div>
</div>

@include('services.partials.service-form-modal', [
    'fieldPrefix' => 'globalService',
    'modalId' => 'globalServiceModal',
    'title' => 'Add Service',
    'subtitle' => 'Create a configurable service entry with routing, requirements, and pricing.',
    'action' => route('services.store'),
    'companyLocked' => false,
])
@include('products.partials.create-field-dropdown', ['fieldTypes' => $fieldTypes])
@include('products.partials.field-actions-dropdown')
@include('products.partials.create-field-modal', [
    'createFieldActionRoute' => route('services.custom-fields.store'),
    'lookupModules' => $lookupModules,
])

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('globalServiceModal');
    const form = document.getElementById('globalServiceForm');
    const openButton = document.getElementById('openGlobalServiceModalCreate');
    const closeButtons = modal.querySelectorAll('[data-close-service-modal]');
    const editButtons = document.querySelectorAll('[data-global-service-edit]');
    const methodInput = document.getElementById('globalServiceFormMethod');
    const title = document.getElementById('globalServiceModalTitle');
    const submit = document.getElementById('globalServiceFormSubmit');
    const updateUrlTemplate = @json(route('services.update', '__SERVICE__'));
    const createUrl = @json(route('services.store'));

    const createFieldDropdownButton = document.getElementById('openCreateFieldDropdown');
    const createFieldDropdownMenu = document.getElementById('createFieldDropdownMenu');
    const fieldTypeButtons = Array.from(document.querySelectorAll('.create-field-type-option'));
    const createFieldModal = document.getElementById('createFieldModal');
    const createFieldPanel = document.getElementById('createFieldPanel');
    const createFieldModalOverlay = document.getElementById('createFieldModalOverlay');
    const closeCreateFieldModal = document.getElementById('closeCreateFieldModal');
    const cancelCreateFieldModal = document.getElementById('cancelCreateFieldModal');
    const createFieldTypeInput = document.getElementById('createFieldTypeInput');
    const createFieldTypeLabel = document.getElementById('createFieldTypeLabel');
    const picklistOptionsSection = document.getElementById('picklistOptionsSection');
    const picklistOptionsContainer = document.getElementById('picklistOptionsContainer');
    const addPicklistOption = document.getElementById('addPicklistOption');
    const defaultValueSection = document.getElementById('defaultValueSection');
    const lookupSection = document.getElementById('lookupSection');
    const defaultValueInput = document.getElementById('default_value');
    const headerActionTriggers = Array.from(document.querySelectorAll('.field-header-trigger'));
    const fieldActionsMenu = document.getElementById('fieldActionsMenu');
    const fieldActionButtons = Array.from(document.querySelectorAll('.field-action-item'));
    const tableHead = document.querySelector('table thead');
    const tableBody = document.querySelector('table tbody');
    let createFieldDropdownOpen = false;
    let fieldActionsMenuOpen = false;
    let activeFieldColumnKey = null;
    let activeFieldIsCustom = false;
    const columnSortState = {};
    const columnFilters = {};

    const openModal = () => window.jkncSlideOver.open(modal);
    const closeModal = () => window.jkncSlideOver.close(modal);

    const formatDateTimeLocal = (value) => {
        if (!value) return '';
        return String(value).replace(' ', 'T').slice(0, 16);
    };

    const resetForm = () => {
        form.reset();
        form.action = createUrl;
        methodInput.value = 'POST';
        title.textContent = 'Add Service';
        submit.textContent = 'Save';
        const statusField = document.getElementById('globalServiceFormStatus');
        if (statusField) {
            statusField.value = 'Pending Approval';
            statusField.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };

    const setMultiSelect = (id, values) => {
        const select = document.getElementById(id);
        const selected = Array.isArray(values) ? values : [];
        Array.from(select.options).forEach((option) => {
            option.selected = selected.includes(option.value);
        });
        select.dispatchEvent(new Event('change'));
    };

    const fillForm = (service) => {
        document.getElementById('globalServiceFormCompany').value = service.company_id ?? '';
        document.getElementById('globalServiceFormServiceName').value = service.service_name ?? '';
        document.getElementById('globalServiceFormServiceDescription').value = service.service_description ?? '';
        document.getElementById('globalServiceFormServiceOutput').value = service.service_activity_output ?? '';
        setMultiSelect('globalServiceFormServiceArea', service.service_area ?? []);
        document.getElementById('globalServiceFormServiceAreaOther').value = service.service_area_other ?? '';
        document.getElementById('globalServiceFormCategory').value = service.category ?? '';
        document.getElementById('globalServiceFormFrequency').value = service.frequency ?? '';
        document.getElementById('globalServiceFormFrequency').dispatchEvent(new Event('change'));
        document.getElementById('globalServiceFormScheduleRule').value = service.schedule_rule ?? '';
        document.getElementById('globalServiceFormDeadline').value = formatDateTimeLocal(service.deadline ?? '');
        document.getElementById('globalServiceFormReminder').value = service.reminder_lead_time ?? '';
        const requirementGroups = service.requirements?.groups ?? {};
        document.getElementById('globalServiceFormRequirementCategory').value = service.requirement_category ?? service.requirements?.category ?? '';
        document.getElementById('globalServiceFormRequirements').value = Array.isArray(service.requirements?.items) ? service.requirements.items.join('\n') : '';
        document.getElementById('globalServiceFormRequirementsIndividual').value = Array.isArray(requirementGroups.individual) ? requirementGroups.individual.join('\n') : '';
        document.getElementById('globalServiceFormRequirementsJuridical').value = Array.isArray(requirementGroups.juridical) ? requirementGroups.juridical.join('\n') : '';
        document.getElementById('globalServiceFormRequirementsOther').value = Array.isArray(requirementGroups.other) ? requirementGroups.other.join('\n') : '';

        if (service.requirements?.category && Array.isArray(service.requirements?.items)) {
            if (service.requirements.category === 'SOLE / NATURAL PERSON / INDIVIDUAL') {
                document.getElementById('globalServiceFormRequirementsIndividual').value = service.requirements.items.join('\n');
            } else if (service.requirements.category === 'JURIDICAL ENTITY (Corporation / OPC / Partnership / Cooperative)') {
                document.getElementById('globalServiceFormRequirementsJuridical').value = service.requirements.items.join('\n');
            } else {
                document.getElementById('globalServiceFormRequirementsOther').value = service.requirements.items.join('\n');
            }
        }
        ['globalServiceFormRequirementsIndividual', 'globalServiceFormRequirementsJuridical', 'globalServiceFormRequirementsOther'].forEach((id) => {
            document.getElementById(id)?.dispatchEvent(new Event('input', { bubbles: true }));
        });
        setMultiSelect('globalServiceFormEngagement', service.engagement_structure ?? []);
        document.getElementById('globalServiceFormUnit').value = service.unit ?? '';
        document.getElementById('globalServiceFormRatePerUnit').value = service.rate_per_unit ?? '';
        document.getElementById('globalServiceFormMinUnits').value = service.min_units ?? '';
        document.getElementById('globalServiceFormMaxCap').value = service.max_cap ?? '';
        document.getElementById('globalServiceFormPriceFee').value = service.price_fee ?? '';
        document.getElementById('globalServiceFormCost').value = service.cost_of_service ?? '';
        const globalTaxType = service.tax_type ?? 'Tax Exclusive';
        document.querySelectorAll('#globalServiceForm input[name="tax_type"]').forEach((input) => {
            input.checked = input.value === globalTaxType;
        });
        document.getElementById('globalServiceFormAssignedUnit').value = service.assigned_unit ?? '';
        document.getElementById('globalServiceFormAssignedUnit').dispatchEvent(new Event('change', { bubbles: true }));
        const statusField = document.getElementById('globalServiceFormStatus');
        if (statusField) {
            statusField.value = service.status ?? 'Pending Approval';
            statusField.dispatchEvent(new Event('input', { bubbles: true }));
        }
        Object.entries(service.custom_field_values ?? {}).forEach(([key, value]) => {
            const input = form.querySelector(`[name="custom_fields[${key}]"]`);
            if (!input) return;
            if (input.type === 'checkbox') {
                input.checked = value === '1' || value === 1 || value === true;
            } else {
                input.value = value ?? '';
            }
        });
    };

    openButton?.addEventListener('click', function () {
        resetForm();
        openModal();
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeModal));

    editButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const service = JSON.parse(this.dataset.globalServiceEdit);
            resetForm();
            form.action = updateUrlTemplate.replace('__SERVICE__', service.id);
            methodInput.value = 'PUT';
            title.textContent = 'Edit Service';
            submit.textContent = 'Update';
            fillForm(service);
            openModal();
        });
    });

    const buildPicklistOptionRow = (value = '') => {
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';
        row.innerHTML = `
            <input name="options[]" value="${value}" placeholder="Option value" class="h-10 flex-1 rounded-md border border-gray-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            <button type="button" class="remove-picklist-option h-8 w-8 rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50">
                <i class="fas fa-minus text-xs"></i>
            </button>
        `;
        return row;
    };

    const ensurePicklistOptionRows = () => {
        if (!picklistOptionsContainer) {
            return;
        }

        if (picklistOptionsContainer.querySelectorAll('input[name="options[]"]').length === 0) {
            picklistOptionsContainer.appendChild(buildPicklistOptionRow(''));
        }
    };

    const closeCreateFieldDropdown = () => {
        createFieldDropdownMenu?.classList.add('hidden');
        createFieldDropdownOpen = false;
    };

    const openCreateFieldDropdownMenu = () => {
        if (!createFieldDropdownMenu || !createFieldDropdownButton) {
            return;
        }

        const rect = createFieldDropdownButton.getBoundingClientRect();
        const menuWidth = createFieldDropdownMenu.offsetWidth || 256;
        const viewportWidth = window.innerWidth;
        const left = Math.min(
            Math.max(16, rect.right - menuWidth),
            Math.max(16, viewportWidth - menuWidth - 16)
        );

        createFieldDropdownMenu.style.left = `${left}px`;
        createFieldDropdownMenu.style.top = `${rect.bottom + 8}px`;
        createFieldDropdownMenu.classList.remove('hidden');
        createFieldDropdownOpen = true;
    };

    const getColumnHeader = (columnKey) => document.querySelector(`th[data-column-key="${columnKey}"]`);
    const getColumnCells = (columnKey) => Array.from(document.querySelectorAll(`td[data-column-key="${columnKey}"]`));

    const applyRowFilters = () => {
        const rows = Array.from(document.querySelectorAll('.service-row'));
        rows.forEach((row) => {
            let isVisible = true;

            Object.entries(columnFilters).forEach(([columnKey, filterText]) => {
                if (!isVisible || filterText === '') {
                    return;
                }

                const cellText = (row.querySelector(`td[data-column-key="${columnKey}"]`)?.textContent || '')
                    .trim()
                    .toLowerCase();

                if (!cellText.includes(filterText)) {
                    isVisible = false;
                }
            });

            row.classList.toggle('hidden', !isVisible);
        });
    };

    const sortByColumn = (columnKey) => {
        if (!tableBody) {
            return;
        }

        const rows = Array.from(tableBody.querySelectorAll('.service-row'));
        const nextDirection = columnSortState[columnKey] === 'asc' ? 'desc' : 'asc';
        columnSortState[columnKey] = nextDirection;

        rows.sort((left, right) => {
            const leftText = (left.querySelector(`td[data-column-key="${columnKey}"]`)?.textContent || '').trim().toLowerCase();
            const rightText = (right.querySelector(`td[data-column-key="${columnKey}"]`)?.textContent || '').trim().toLowerCase();
            return nextDirection === 'asc'
                ? leftText.localeCompare(rightText, undefined, { numeric: true })
                : rightText.localeCompare(leftText, undefined, { numeric: true });
        });

        rows.forEach((row) => tableBody.appendChild(row));
    };

    const collapseColumn = (columnKey) => {
        const header = getColumnHeader(columnKey);
        const cells = getColumnCells(columnKey);
        if (!header) {
            return;
        }

        const willHide = !header.classList.contains('hidden');
        header.classList.toggle('hidden', willHide);
        cells.forEach((cell) => cell.classList.toggle('hidden', willHide));
    };

    const moveColumnLeft = (columnKey) => {
        if (!tableHead || !tableBody) {
            return;
        }

        const header = getColumnHeader(columnKey);
        if (!header || !header.previousElementSibling || !header.previousElementSibling.hasAttribute('data-column-key')) {
            return;
        }

        const previousHeader = header.previousElementSibling;
        const previousKey = previousHeader.getAttribute('data-column-key');
        if (!previousKey) {
            return;
        }

        tableHead.querySelector('tr')?.insertBefore(header, previousHeader);
        Array.from(tableBody.querySelectorAll('.service-row')).forEach((row) => {
            const cell = row.querySelector(`td[data-column-key="${columnKey}"]`);
            const previousCell = row.querySelector(`td[data-column-key="${previousKey}"]`);
            if (cell && previousCell) {
                row.insertBefore(cell, previousCell);
            }
        });
    };

    const autoFitColumn = (columnKey) => {
        const header = getColumnHeader(columnKey);
        const cells = getColumnCells(columnKey);
        if (!header) {
            return;
        }

        header.style.width = '1%';
        header.classList.add('whitespace-nowrap');
        cells.forEach((cell) => cell.classList.add('whitespace-nowrap'));
    };

    const removeCustomColumn = (columnKey) => {
        if (!activeFieldIsCustom) {
            return;
        }

        getColumnHeader(columnKey)?.remove();
        getColumnCells(columnKey).forEach((cell) => cell.remove());
    };

    const positionFieldActionsMenu = (trigger) => {
        if (!fieldActionsMenu || !trigger) {
            return;
        }

        const rect = trigger.getBoundingClientRect();
        const menuWidth = fieldActionsMenu.offsetWidth || 224;
        const viewportPadding = 12;
        let left = rect.right - menuWidth;

        if (left < viewportPadding) {
            left = viewportPadding;
        }

        if (left + menuWidth > window.innerWidth - viewportPadding) {
            left = window.innerWidth - menuWidth - viewportPadding;
        }

        let top = rect.bottom + 6;
        const menuHeight = fieldActionsMenu.offsetHeight || 300;
        if (top + menuHeight > window.innerHeight - viewportPadding) {
            top = Math.max(viewportPadding, rect.top - menuHeight - 6);
        }

        fieldActionsMenu.style.left = `${left}px`;
        fieldActionsMenu.style.top = `${top}px`;
    };

    const closeFieldActionsMenu = () => {
        if (!fieldActionsMenu) {
            return;
        }

        fieldActionsMenu.classList.add('hidden');
        fieldActionsMenuOpen = false;
        activeFieldColumnKey = null;
        activeFieldIsCustom = false;
    };

    const openFieldActionsMenu = (trigger, columnKey, isCustom) => {
        if (!fieldActionsMenu) {
            return;
        }

        activeFieldColumnKey = columnKey;
        activeFieldIsCustom = isCustom;
        const removeBtn = fieldActionsMenu.querySelector('[data-action="remove-column"]');
        if (removeBtn) {
            removeBtn.classList.toggle('opacity-50', !isCustom);
            removeBtn.classList.toggle('cursor-not-allowed', !isCustom);
            removeBtn.classList.toggle('pointer-events-none', !isCustom);
            removeBtn.setAttribute('aria-disabled', !isCustom ? 'true' : 'false');
        }

        fieldActionsMenu.classList.remove('hidden');
        fieldActionsMenuOpen = true;
        positionFieldActionsMenu(trigger);
    };

    const applyCreateFieldTypeUI = (type, label) => {
        if (!createFieldTypeInput || !createFieldTypeLabel) {
            return;
        }

        createFieldTypeInput.value = type;
        createFieldTypeLabel.textContent = label;

        picklistOptionsSection?.classList.toggle('hidden', type !== 'picklist');
        defaultValueSection?.classList.toggle('hidden', false);
        lookupSection?.classList.toggle('hidden', type !== 'lookup');

        if (type === 'picklist') {
            ensurePicklistOptionRows();
        }
    };

    const openCreateField = () => {
        if (!createFieldModal) {
            return;
        }

        createFieldModal.classList.remove('hidden');
        createFieldModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');

        requestAnimationFrame(() => {
            createFieldModalOverlay?.classList.remove('opacity-0');
            createFieldPanel?.classList.remove('translate-x-full');
        });
    };

    createFieldDropdownButton?.addEventListener('click', function () {
        if (createFieldDropdownOpen) {
            closeCreateFieldDropdown();
            return;
        }

        closeFieldActionsMenu();
        openCreateFieldDropdownMenu();
    });

    fieldTypeButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const value = this.dataset.fieldType;
            const label = this.dataset.fieldLabel || 'Picklist';
            applyCreateFieldTypeUI(value, label);
            closeCreateFieldDropdown();
            openCreateField();
        });
    });

    const closeCreateField = () => {
        createFieldModalOverlay?.classList.add('opacity-0');
        createFieldPanel?.classList.add('translate-x-full');
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => {
            createFieldModal?.classList.add('hidden');
            createFieldModal?.setAttribute('aria-hidden', 'true');
        }, 200);
    };

    closeCreateFieldModal?.addEventListener('click', closeCreateField);
    cancelCreateFieldModal?.addEventListener('click', closeCreateField);
    createFieldModalOverlay?.addEventListener('click', closeCreateField);

    addPicklistOption?.addEventListener('click', function () {
        picklistOptionsContainer?.appendChild(buildPicklistOptionRow(''));
    });

    picklistOptionsContainer?.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-picklist-option');
        if (!removeButton) return;
        if (picklistOptionsContainer.children.length === 1) {
            const input = picklistOptionsContainer.querySelector('input');
            if (input) input.value = '';
            return;
        }
        removeButton.parentElement.remove();
    });

    document.addEventListener('click', function (event) {
        if (!createFieldDropdownButton?.contains(event.target) && !createFieldDropdownMenu?.contains(event.target)) {
            closeCreateFieldDropdown();
        }

        if (fieldActionsMenu && fieldActionsMenuOpen) {
            const clickedHeaderTrigger = headerActionTriggers.some((trigger) => trigger.contains(event.target));
            if (!fieldActionsMenu.contains(event.target) && !clickedHeaderTrigger) {
                closeFieldActionsMenu();
            }
        }
    });

    headerActionTriggers.forEach((trigger) => {
        trigger.addEventListener('click', function (event) {
            event.stopPropagation();
            const columnKey = trigger.dataset.columnKey || '';
            const columnType = trigger.dataset.columnType || 'base';
            const isCustom = columnType === 'custom';

            if (fieldActionsMenuOpen && activeFieldColumnKey === columnKey) {
                closeFieldActionsMenu();
                return;
            }

            closeCreateFieldDropdown();
            openFieldActionsMenu(trigger, columnKey, isCustom);
        });
    });

    fieldActionButtons.forEach((button) => {
        button.addEventListener('click', function () {
            if (!activeFieldColumnKey) {
                closeFieldActionsMenu();
                return;
            }

            const action = button.dataset.action || '';

            if (action === 'sort') {
                sortByColumn(activeFieldColumnKey);
                closeFieldActionsMenu();
                return;
            }

            if (action === 'filter') {
                const currentFilter = columnFilters[activeFieldColumnKey] || '';
                const input = window.prompt('Filter value', currentFilter);
                if (input !== null) {
                    columnFilters[activeFieldColumnKey] = input.trim().toLowerCase();
                    applyRowFilters();
                }
                closeFieldActionsMenu();
                return;
            }

            if (action === 'edit-field') {
                window.alert('Edit Field is not available yet.');
                closeFieldActionsMenu();
                return;
            }

            if (action === 'add-column') {
                closeFieldActionsMenu();
                openCreateFieldDropdownMenu();
                return;
            }

            if (action === 'collapse') {
                collapseColumn(activeFieldColumnKey);
                closeFieldActionsMenu();
                return;
            }

            if (action === 'move-left') {
                moveColumnLeft(activeFieldColumnKey);
                closeFieldActionsMenu();
                return;
            }

            if (action === 'autofit') {
                autoFitColumn(activeFieldColumnKey);
                closeFieldActionsMenu();
                return;
            }

            if (action === 'remove-column') {
                if (!activeFieldIsCustom) {
                    return;
                }
                removeCustomColumn(activeFieldColumnKey);
                closeFieldActionsMenu();
            }
        });
    });

    if (createFieldTypeInput && createFieldTypeLabel) {
        const initialButton = fieldTypeButtons.find((button) => button.dataset.fieldType === createFieldTypeInput.value)
            || fieldTypeButtons[0];
        applyCreateFieldTypeUI(
            initialButton?.dataset.fieldType || createFieldTypeInput.value || 'picklist',
            initialButton?.dataset.fieldLabel || createFieldTypeLabel.textContent || 'Picklist'
        );
    }

    if (defaultValueInput) {
        defaultValueInput.value = defaultValueInput.value ?? '';
    }

    window.addEventListener('resize', function () {
        if (createFieldDropdownOpen) {
            openCreateFieldDropdownMenu();
        }
        if (fieldActionsMenuOpen) {
            const activeTrigger = headerActionTriggers.find((trigger) => trigger.dataset.columnKey === activeFieldColumnKey) || null;
            positionFieldActionsMenu(activeTrigger);
        }
    });

    document.addEventListener('scroll', function () {
        if (createFieldDropdownOpen) {
            openCreateFieldDropdownMenu();
        }
        if (fieldActionsMenuOpen) {
            const activeTrigger = headerActionTriggers.find((trigger) => trigger.dataset.columnKey === activeFieldColumnKey) || null;
            positionFieldActionsMenu(activeTrigger);
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeCreateField();
            closeCreateFieldDropdown();
            closeFieldActionsMenu();
            closeModal();
        }
    });
});
</script>
@endsection
