@extends('layouts.app')
@section('title', 'Company Deals')

@section('content')
@php
    $dealStageClasses = [
        'Inquiry' => 'bg-slate-100 text-slate-700 border border-slate-200',
        'Qualification' => 'bg-blue-100 text-blue-700 border border-blue-200',
        'Consultation' => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
        'Proposal' => 'bg-cyan-100 text-cyan-700 border border-cyan-200',
        'Negotiation' => 'bg-amber-100 text-amber-700 border border-amber-200',
        'Payment' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        'Activation' => 'bg-violet-100 text-violet-700 border border-violet-200',
        'Closed Lost' => 'bg-red-100 text-red-700 border border-red-200',
    ];
    $dealStatusClasses = [
        'Open' => 'bg-blue-100 text-blue-700 border border-blue-200',
        'Won' => 'bg-green-100 text-green-700 border border-green-200',
        'Lost' => 'bg-red-100 text-red-700 border border-red-200',
        'Pending' => 'bg-amber-100 text-amber-700 border border-amber-200',
    ];
@endphp
<div class="w-full px-4 sm:px-6 lg:px-8 mt-4 pb-8">
    <div class="bg-white border border-gray-100 rounded-md overflow-hidden">
        @include('company.partials.company-header', ['company' => $company])

        <section class="bg-gray-50 p-4 min-h-[760px]">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">Related Deals</h2>
                    <p class="text-sm text-gray-500">Track all deals associated with this company</p>
                </div>
                <a href="{{ route('deals.index') }}" class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">+ Open Deals</a>
            </div>

            @if (session('deals_warning'))
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ session('deals_warning') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                            <tr>
                                <th class="px-3 py-3 text-left">Deal Name</th>
                                <th class="px-3 py-3 text-left">Stage</th>
                                <th class="px-3 py-3 text-left">Closing Date</th>
                                <th class="px-3 py-3 text-left">Owner</th>
                                <th class="px-3 py-3 text-left">Status</th>
                                <th class="px-3 py-3 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($deals as $deal)
                                <tr>
                                    <td class="px-3 py-3 font-medium text-gray-900">{{ $deal['name'] }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $dealStageClasses[$deal['stage']] ?? 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                                            {{ $deal['stage'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-gray-700">{{ $deal['closing_date'] }}</td>
                                    <td class="px-3 py-3 text-gray-700">{{ $deal['owner'] }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $dealStatusClasses[$deal['status']] ?? 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                                            {{ $deal['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <a href="{{ $deal['show_url'] ?? route('deals.index') }}" class="text-blue-600 hover:text-blue-700"><i class="far fa-eye mr-1"></i>View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-12 text-center text-sm text-gray-500">No deals found for this company yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
