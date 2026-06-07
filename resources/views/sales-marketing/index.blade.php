@extends('layouts.app')

@section('title', 'Incentive Management')

@section('content')
<div class="flex-1 overflow-y-auto p-6">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Incentive Management</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Manage commission earners, IDA records, and future Incentive Management workflows.
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="{{ route('sales-marketing.earners.index') }}"
               class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-blue-300 hover:shadow-sm transition">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Incentive Earners</h2>
                        <p class="text-sm text-gray-500">Master list of incentive earners and profiles.</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('sales-marketing.ida.index') }}"
               class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-blue-300 hover:shadow-sm transition">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">IDA Records</h2>
                        <p class="text-sm text-gray-500">Incentive Distribution & Allocation records.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
