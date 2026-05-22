@extends('layouts.app')
@section('title', $service->service_name)

@section('content')
@php
    $priceLabel = $service->rate_per_unit
        ? number_format((float) $service->rate_per_unit, 2).($service->unit ? ' / '.$service->unit : '')
        : ($service->price_fee ? number_format((float) $service->price_fee, 2) : null);
    $customFieldValues = $service->custom_field_values ?? [];
    $backRoute = $backRoute ?? route('services.index');
    $backLabel = $backLabel ?? 'Back to Services';
    $lastModifiedLabel = 'Last Modified on '.($service->updated_at?->format('M d, h:i A') ?? '-');
@endphp

<div class="px-6 py-6 lg:px-8">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ $backRoute }}" class="mb-2 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-600">
                <i class="fas fa-arrow-left text-xs"></i>
                <span>{{ $backLabel }}</span>
            </a>
            <h1 class="text-3xl font-semibold text-gray-900">
                {{ $service->service_name }}
                @if ($priceLabel)
                    <span class="text-2xl font-semibold text-gray-700"> &middot; {{ $priceLabel }}</span>
                @endif
            </h1>
            <p class="mt-1 text-sm text-gray-500">{{ $service->assigned_unit ?: 'Unassigned' }}</p>
        </div>

        <div class="flex items-center gap-2">
            @if ($company)
                <a href="{{ route('company.services.show', [$company->id, $service->id]) }}" class="h-9 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 hover:bg-gray-50">
                    Company View
                </a>
            @endif
            <button type="button" class="h-9 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 hover:bg-gray-50">
                Edit Service
            </button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
        <aside class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-2xl font-semibold text-gray-900">Basic Info</h2>
            <dl class="space-y-3 text-sm">
                <div class="grid grid-cols-[120px_1fr] gap-2">
                    <dt class="text-gray-500">Service ID</dt>
                    <dd class="text-gray-800">{{ $service->service_id }}</dd>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-2">
                    <dt class="text-gray-500">Category</dt>
                    <dd class="text-gray-800">{{ $service->category ?: '-' }}</dd>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-2">
                    <dt class="text-gray-500">Status</dt>
                    <dd class="text-gray-800">{{ $service->status ?: '-' }}</dd>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-2">
                    <dt class="text-gray-500">Service Areas</dt>
                    <dd class="text-gray-800">{{ collect($service->service_area ?? [])->filter()->implode(', ') ?: '-' }}</dd>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-2">
                    <dt class="text-gray-500">Structure</dt>
                    <dd class="text-gray-800">{{ collect($service->engagement_structure ?? [])->filter()->implode(', ') ?: '-' }}</dd>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-2">
                    <dt class="text-gray-500">Frequency</dt>
                    <dd class="text-gray-800">{{ $service->frequency ?: '-' }}</dd>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-2">
                    <dt class="text-gray-500">Unit</dt>
                    <dd class="text-gray-800">{{ $service->unit ?: '-' }}</dd>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-2">
                    <dt class="text-gray-500">Deadline</dt>
                    <dd class="text-gray-800">{{ $service->deadline ? $service->deadline->format('M d, Y h:i A') : '-' }}</dd>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-2">
                    <dt class="text-gray-500">Tax Type</dt>
                    <dd class="text-gray-800">{{ $service->tax_type ?: '-' }}</dd>
                </div>
            </dl>

            <div class="mt-6">
                <h3 class="mb-2 text-lg font-semibold text-gray-900">Description</h3>
                <p class="text-sm text-gray-600">{{ $service->service_description ?: '-' }}</p>
            </div>

            <div class="mt-6">
                <h3 class="mb-2 text-lg font-semibold text-gray-900">Activity / Output</h3>
                <p class="text-sm text-gray-600">{{ $service->service_activity_output ?: '-' }}</p>
            </div>

            <div class="mt-6">
                <h3 class="mb-2 text-lg font-semibold text-gray-900">Requirements</h3>
                @php
                    $requirementGroups = collect($service->requirements['groups'] ?? []);
                    $requirementLabels = [
                        'individual' => 'Individual',
                        'juridical' => 'Juridical',
                        'other' => 'Other',
                    ];
                @endphp
                @if ($requirementGroups->isNotEmpty())
                    <div class="space-y-3 text-sm text-gray-600">
                        @foreach ($requirementLabels as $groupKey => $groupLabel)
                            @if (!empty($service->requirements['groups'][$groupKey]))
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $groupLabel }}</p>
                                    <ul class="mt-1 space-y-1">
                                        @foreach ($service->requirements['groups'][$groupKey] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-600">-</p>
                @endif
            </div>

            <div class="mt-6 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm text-gray-600">
                {{ $lastModifiedLabel }}
            </div>
        </aside>

        <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-900">History</h3>
                </div>
                <div class="space-y-4">
                    @forelse ($historyItems as $item)
                        <article class="flex gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                                <i class="fas {{ $item['icon'] }} text-xs"></i>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $item['title'] }} by {{ $item['user_name'] }}</p>
                                <p class="text-sm text-gray-600">{{ $item['description'] }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $item['created_at'] ?: '-' }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 px-5 py-8 text-center text-sm text-gray-500">
                            No service history recorded yet.
                        </div>
                    @endforelse
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-semibold text-gray-900">Pricing Snapshot</h4>
                        <div class="mt-3 space-y-2 text-sm text-gray-600">
                            <p>Rate Per Unit: <span class="font-medium text-gray-800">{{ $service->rate_per_unit ? number_format((float) $service->rate_per_unit, 2) : '-' }}</span></p>
                            <p>Price Fee: <span class="font-medium text-gray-800">{{ $service->price_fee ? number_format((float) $service->price_fee, 2) : '-' }}</span></p>
                            <p>Cost of Service: <span class="font-medium text-gray-800">{{ $service->cost_of_service ? number_format((float) $service->cost_of_service, 2) : '-' }}</span></p>
                            <p>Schedule Rule: <span class="font-medium text-gray-800">{{ $service->schedule_rule ?: '-' }}</span></p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-semibold text-gray-900">System Info</h4>
                        <div class="mt-3 space-y-2 text-sm text-gray-600">
                            <p>Created By: <span class="font-medium text-gray-800">{{ $service->creator?->name ?: (is_string($service->created_by) ? $service->created_by : '-') }}</span></p>
                            <p>Created At: <span class="font-medium text-gray-800">{{ $service->created_at?->format('M d, Y h:i A') ?: '-' }}</span></p>
                            <p>Reviewed By: <span class="font-medium text-gray-800">{{ $service->reviewer?->name ?: (is_string($service->reviewed_by) ? $service->reviewed_by : '-') }}</span></p>
                            <p>Reviewed At: <span class="font-medium text-gray-800">{{ $service->reviewed_at?->format('M d, Y') ?: '-' }}</span></p>
                            <p>Approved By: <span class="font-medium text-gray-800">{{ $service->approver?->name ?: (is_string($service->approved_by) ? $service->approved_by : '-') }}</span></p>
                            <p>Approved At: <span class="font-medium text-gray-800">{{ $service->approved_at?->format('M d, Y') ?: '-' }}</span></p>
                        </div>
                    </div>
                </div>

                @if ($customFields->count() > 0)
                    <div class="mt-6 rounded-lg border border-gray-200">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <h4 class="text-sm font-semibold text-gray-900">Custom Fields</h4>
                        </div>
                        <div class="grid gap-4 px-4 py-4 sm:grid-cols-2">
                            @foreach ($customFields as $field)
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $field->field_name }}</p>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ data_get($customFieldValues, $field->field_key, '-') ?: '-' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
