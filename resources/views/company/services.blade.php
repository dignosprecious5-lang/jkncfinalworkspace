@extends('layouts.app')
@section('title', 'Company Services')

@section('content')
@php
    $completedServices = $services->filter(fn ($service) => (string) $service->status === 'Completed')->count();
    $statusClasses = [
        'Pending Approval' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Draft' => 'border-slate-200 bg-slate-50 text-slate-700',
        'Active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'Inactive' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Archived' => 'border-rose-200 bg-rose-50 text-rose-700',
    ];
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">Services Availed</h2>
                    <p class="mt-1 text-sm text-gray-500">Services associated with this company through related company work.</p>
                </div>
                <div class="text-sm text-gray-500">
                    {{ $summary['total_count'] }} {{ \Illuminate\Support\Str::plural('service', $summary['total_count']) }}
                    <span class="mx-2 text-gray-300">|</span>
                    Total value P{{ number_format($summary['total_value'], 2) }}
                </div>
            </div>

            @if (session('services_success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('services_success') }}
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto overflow-y-visible">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-700">
                            <tr>
                                <th class="px-3 py-3 text-left font-medium">Service Name</th>
                                <th class="px-3 py-3 text-left font-medium">Category</th>
                                <th class="px-3 py-3 text-left font-medium">Frequency</th>
                                <th class="px-3 py-3 text-left font-medium">Engagement Type</th>
                                <th class="px-3 py-3 text-left font-medium">Price / Rate</th>
                                <th class="px-3 py-3 text-left font-medium">Assigned Unit</th>
                                <th class="px-3 py-3 text-left font-medium">Status</th>
                                <th class="px-3 py-3 text-left font-medium">Service Owner</th>
                                <th class="px-3 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($services as $service)
                                <tr class="text-gray-700 hover:bg-gray-50">
                                    <td class="px-3 py-3">
                                        <a href="{{ route('company.services.show', [$company->id, $service->id]) }}" class="font-medium text-gray-900 hover:text-blue-700">{{ $service->service_name }}</a>
                                        <div class="mt-1 text-xs text-gray-500">ID {{ $service->service_id }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-gray-600">{{ $service->category ?: '-' }}</td>
                                    <td class="px-3 py-3 text-gray-600">{{ $service->frequency ?: '-' }}</td>
                                    <td class="px-3 py-3 text-gray-600">{{ implode(', ', $service->engagement_structure ?? []) ?: '-' }}</td>
                                    <td class="px-3 py-3 text-gray-600">
                                        @if ($service->rate_per_unit)
                                            {{ number_format((float) $service->rate_per_unit, 2) }} / {{ $service->unit }}
                                        @elseif ($service->price_fee)
                                            {{ number_format((float) $service->price_fee, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-gray-600">{{ $service->assigned_unit ?: '-' }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClasses[$service->status] ?? 'border-gray-200 bg-gray-50 text-gray-700' }}">{{ $service->status }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-gray-600">{{ $service->creator?->name ?: '-' }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center justify-start gap-2">
                                            <a href="{{ route('services.show', $service->id) }}" class="rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">View</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-12 text-center text-sm text-gray-500">No availed services found for this company yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3 text-sm text-gray-500">
                    Completed: <span class="font-semibold text-gray-900">{{ $completedServices }}</span>
                </div>
            </div>
        </section>
    </div>
</div>

@include('services.partials.service-form-modal', [
    'fieldPrefix' => 'companyService',
    'modalId' => 'companyServiceModal',
    'title' => 'Assign Service',
    'subtitle' => 'This service will be linked to ' . $company->company_name . '.',
    'action' => route('company.services.store', $company->id),
    'companyLocked' => true,
    'lockedCompany' => $company,
])

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('companyServiceModal');
    const form = document.getElementById('companyServiceForm');
    const openButton = document.getElementById('openCompanyServiceModalCreate');
    const closeButtons = modal.querySelectorAll('[data-close-service-modal]');
    const editButtons = document.querySelectorAll('[data-company-service-edit]');
    const methodInput = document.getElementById('companyServiceFormMethod');
    const title = document.getElementById('companyServiceModalTitle');
    const submit = document.getElementById('companyServiceFormSubmit');
    const updateUrlTemplate = @json(route('company.services.update', [$company->id, '__SERVICE__']));
    const createUrl = @json(route('company.services.store', $company->id));

    const openModal = () => window.jkncSlideOver.open(modal);
    const closeModal = () => window.jkncSlideOver.close(modal);

    const formatDateTimeLocal = (value) => {
        if (!value) return '';
        return String(value).replace(' ', 'T').slice(0, 16);
    };

    const setMultiSelect = (id, values) => {
        const select = document.getElementById(id);
        const selected = Array.isArray(values) ? values : [];
        Array.from(select.options).forEach((option) => {
            option.selected = selected.includes(option.value);
        });
        select.dispatchEvent(new Event('change'));
    };

    const resetForm = () => {
        form.reset();
        form.action = createUrl;
        methodInput.value = 'POST';
        title.textContent = 'Assign Service';
        submit.textContent = 'Save';
        const statusField = document.getElementById('companyServiceFormStatus');
        if (statusField) {
            statusField.value = 'Pending Approval';
            statusField.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };

    const fillForm = (service) => {
        document.getElementById('companyServiceFormServiceName').value = service.service_name ?? '';
        document.getElementById('companyServiceFormServiceDescription').value = service.service_description ?? '';
        document.getElementById('companyServiceFormServiceOutput').value = service.service_activity_output ?? '';
        setMultiSelect('companyServiceFormServiceArea', service.service_area ?? []);
        document.getElementById('companyServiceFormServiceAreaOther').value = service.service_area_other ?? '';
        document.getElementById('companyServiceFormCategory').value = service.category ?? '';
        document.getElementById('companyServiceFormFrequency').value = service.frequency ?? '';
        document.getElementById('companyServiceFormFrequency').dispatchEvent(new Event('change'));
        document.getElementById('companyServiceFormScheduleRule').value = service.schedule_rule ?? '';
        document.getElementById('companyServiceFormDeadline').value = formatDateTimeLocal(service.deadline ?? '');
        document.getElementById('companyServiceFormReminder').value = service.reminder_lead_time ?? '';
        const requirementGroups = service.requirements?.groups ?? {};
        document.getElementById('companyServiceFormRequirementCategory').value = service.requirement_category ?? service.requirements?.category ?? '';
        document.getElementById('companyServiceFormRequirements').value = Array.isArray(service.requirements?.items) ? service.requirements.items.join('\n') : '';
        document.getElementById('companyServiceFormRequirementsIndividual').value = Array.isArray(requirementGroups.individual) ? requirementGroups.individual.join('\n') : '';
        document.getElementById('companyServiceFormRequirementsJuridical').value = Array.isArray(requirementGroups.juridical) ? requirementGroups.juridical.join('\n') : '';
        document.getElementById('companyServiceFormRequirementsOther').value = Array.isArray(requirementGroups.other) ? requirementGroups.other.join('\n') : '';

        if (service.requirements?.category && Array.isArray(service.requirements?.items)) {
            if (service.requirements.category === 'SOLE / NATURAL PERSON / INDIVIDUAL') {
                document.getElementById('companyServiceFormRequirementsIndividual').value = service.requirements.items.join('\n');
            } else if (service.requirements.category === 'JURIDICAL ENTITY (Corporation / OPC / Partnership / Cooperative)') {
                document.getElementById('companyServiceFormRequirementsJuridical').value = service.requirements.items.join('\n');
            } else {
                document.getElementById('companyServiceFormRequirementsOther').value = service.requirements.items.join('\n');
            }
        }
        ['companyServiceFormRequirementsIndividual', 'companyServiceFormRequirementsJuridical', 'companyServiceFormRequirementsOther'].forEach((id) => {
            document.getElementById(id)?.dispatchEvent(new Event('input', { bubbles: true }));
        });
        setMultiSelect('companyServiceFormEngagement', service.engagement_structure ?? []);
        document.getElementById('companyServiceFormUnit').value = service.unit ?? '';
        document.getElementById('companyServiceFormRatePerUnit').value = service.rate_per_unit ?? '';
        document.getElementById('companyServiceFormMinUnits').value = service.min_units ?? '';
        document.getElementById('companyServiceFormMaxCap').value = service.max_cap ?? '';
        document.getElementById('companyServiceFormPriceFee').value = service.price_fee ?? '';
        document.getElementById('companyServiceFormCost').value = service.cost_of_service ?? '';
        const companyTaxType = service.tax_type ?? 'Tax Exclusive';
        document.querySelectorAll('#companyServiceModal input[name="tax_type"]').forEach((input) => {
            input.checked = input.value === companyTaxType;
        });
        document.getElementById('companyServiceFormAssignedUnit').value = service.assigned_unit ?? '';
        document.getElementById('companyServiceFormAssignedUnit').dispatchEvent(new Event('change', { bubbles: true }));
        const statusField = document.getElementById('companyServiceFormStatus');
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
            const service = JSON.parse(this.dataset.companyServiceEdit);
            resetForm();
            form.action = updateUrlTemplate.replace('__SERVICE__', service.id);
            methodInput.value = 'PUT';
            title.textContent = 'Edit Service';
            submit.textContent = 'Update';
            fillForm(service);
            openModal();
        });
    });
});
</script>
@endsection
