@extends('layouts.app')
@section('title', 'Special Power of Attorney')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
        <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-6 py-4">
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Special Power of Attorney</h1>
                <p class="text-sm text-gray-500">Review, edit, and export the SPA as PDF using the saved company information as a starting point.</p>
            </div>
            <a href="{{ $backUrl }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">Back to KYC</a>
        </div>

        <div class="grid grid-cols-1 gap-6 p-6 xl:grid-cols-[minmax(0,1.45fr)_380px]">
            <div class="rounded-2xl border border-slate-200 bg-[#f8fafc] p-6">
                <div class="mx-auto max-w-[860px] rounded-sm bg-white px-14 py-12 shadow-[0_18px_50px_rgba(15,23,42,0.08)]">
                    @include('company.requirements.partials.spa-document', ['doc' => $doc])
                </div>
            </div>

            <div id="template-form" class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4">
                <div>
                    <div class="text-sm font-semibold text-gray-900">Editable Fields</div>
                    <div class="mt-1 text-xs text-gray-500">Adjust any blank or autofilled field here. The preview updates as you type.</div>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    @foreach ([
                        ['principal_name', 'Full Name'],
                        ['principal_nationality', 'Nationality'],
                        ['principal_civil_status', 'Civil Status'],
                        ['principal_address', 'Address'],
                        ['attorney_name', 'Attorney-in-Fact Name'],
                        ['attorney_nationality', 'Attorney-in-Fact Nationality'],
                        ['attorney_address', 'Attorney-in-Fact Address'],
                        ['principal_id_no', 'Guarantor ID No.'],
                        ['attorney_id_no', 'Attorney-in-Fact ID No.'],
                        ['signed_place', 'Place of Signing'],
                        ['signed_day', 'Signed Day'],
                        ['signed_month', 'Signed Month'],
                        ['signed_year', 'Signed Year'],
                    ] as [$name, $label])
                        <div>
                            <label class="text-xs font-medium text-gray-600">{{ $label }}</label>
                            <input type="text" name="{{ $name }}" value="{{ $doc[$name] }}" data-sync="{{ $name }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="button" id="spa-download-button" data-download-url="{{ $downloadUrl }}" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">Download PDF</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Build current form values from the rendered inputs
    var formValues = {};
    document.querySelectorAll('#template-form [data-sync]').forEach(function (input) {
        formValues[input.dataset.sync] = input.value;
    });

    // Sync a single field to all matching [data-field] targets in the document preview
    function syncField(fieldName, value) {
        document.querySelectorAll('[data-field="' + fieldName + '"]').forEach(function (target) {
            target.textContent = value;
        });
    }

    // Wire up live input listeners
    document.querySelectorAll('#template-form [data-sync]').forEach(function (input) {
        input.addEventListener('input', function () {
            formValues[input.dataset.sync] = input.value;
            syncField(input.dataset.sync, input.value);
        });
    });

    // Download PDF: pass all current form values as query params
    var spaDownloadButton = document.getElementById('spa-download-button');
    if (spaDownloadButton) {
        spaDownloadButton.addEventListener('click', function () {
            var params = new URLSearchParams();
            Object.keys(formValues).forEach(function (key) {
                params.set(key, formValues[key]);
            });
            params.set('autoprint', '1');
            window.location.href = spaDownloadButton.dataset.downloadUrl + '?' + params.toString();
        });
    }
})();
</script>
@endsection
