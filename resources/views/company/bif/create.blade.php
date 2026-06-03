@extends('layouts.app')
@section('title', 'New Business Client Information Form')

@section('content')
<div class="mt-4 w-full px-4 pb-8 sm:px-6 lg:px-8">
    <div class="overflow-hidden rounded-md border border-gray-100 bg-white">
        @include('company.partials.company-header', ['company' => $company])

        <div class="border-b border-gray-100 px-4 py-4">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <a href="{{ route('company.kyc', ['company' => $company->id, 'tab' => 'business-client-information']) }}" class="inline-flex items-center gap-1 text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Business Client Information Form</span>
                </a>
                <span>/</span>
                <span class="font-semibold text-gray-900">New Business Client Information Form</span>
            </div>
        </div>

        <section class="bg-gray-50 p-4">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 px-4 py-4">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Business Client Information Form</h1>
                        <p class="mt-1 text-sm text-gray-500">Fill out the business client information details for {{ $company->company_name }}.</p>
                    </div>
                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700">
                        JK&C Internal Form
                    </span>
                </div>

                @if ($errors->any())
                    <div class="mx-4 mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Please review the BIF fields and correct the highlighted errors.
                    </div>
                @endif

                <form method="POST" action="{{ route('company.bif.store', $company->id) }}">
                    @csrf
                    <div class="bg-gray-50 p-4 sm:p-6">
                        <div class="mx-auto max-w-[1120px]">
                            @include('company.bif.partials.form-fields', ['bif' => $bif])
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 px-4 py-4">
                        <a href="{{ route('company.kyc', ['company' => $company->id, 'tab' => 'business-client-information']) }}" class="inline-flex h-10 min-w-[100px] items-center justify-center rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" name="action" value="draft" class="h-10 min-w-[120px] rounded-full border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Save Draft
                        </button>
                        <button type="submit" name="action" value="submit" class="h-10 min-w-[140px] rounded-full bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
                            Submit for Approval
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
