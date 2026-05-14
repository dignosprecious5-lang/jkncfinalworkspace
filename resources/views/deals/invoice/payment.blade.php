@extends('layouts.app')
@section('title', 'Payment Invoice')

@section('content')
@php
    $formatCurrency = static fn ($amount): string => 'P'.number_format((float) $amount, 2);
    $invoiceStatus = $proposal->invoice_status ?: 'not_started';
    $isPaymentConfirmed = filled($proposal->payment_confirmed_at) || in_array($invoiceStatus, ['approved', 'payment_confirmed'], true);
@endphp

<div class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('deals.show', $deal) }}" class="font-semibold text-blue-700 hover:underline">Deal</a>
                <span>/</span>
                <span>{{ $deal->deal_code ?: 'Payment' }}</span>
            </div>
            <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Payment / Invoice</p>
                    <h1 class="mt-1 text-2xl font-semibold text-slate-950">{{ $deal->company_name ?: $deal->deal_name ?: $deal->deal_code }}</h1>
                    <p class="mt-1 text-sm text-slate-600">Upload the invoice, then finance can approve it and submit START for approval.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">
                    {{ str_replace('_', ' ', $invoiceStatus) }}
                </span>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Payment Details</h2>
                        <p class="mt-1 text-sm text-slate-500">Invoice uses the approved quotation/proposal amount for this deal.</p>
                    </div>
                    @if ($proposal->quotation_finance_file_path)
                        <a href="{{ route('uploads.show', ['path' => $proposal->quotation_finance_file_path]) }}" target="_blank" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="fas fa-file-invoice-dollar mr-2"></i>View Quotation
                        </a>
                    @endif
                </div>

                <div class="mt-5 grid gap-3 text-sm md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Condeal Reference No.</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $deal->deal_code ?: '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Client Name</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $clientName ?: '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Business Name</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $deal->company_name ?: '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Payment Terms</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $deal->payment_terms ?: '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Service Type</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $documentData['service_type'] ?? '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Invoice Amount</div>
                        <div class="mt-1 font-semibold text-slate-900">{{ $formatCurrency($proposal->price_total ?? $deal->total_estimated_engagement_value ?? 0) }}</div>
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 bg-white px-4 py-4">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Invoice Timeline</div>
                    <div class="mt-3 space-y-2 text-sm text-slate-600">
                        <div>Uploaded: <span class="font-semibold text-slate-900">{{ optional($proposal->invoice_uploaded_at)->format('F j, Y g:i A') ?: '-' }}</span></div>
                        <div>Payment Confirmed: <span class="font-semibold text-slate-900">{{ optional($proposal->payment_confirmed_at)->format('F j, Y g:i A') ?: '-' }}</span></div>
                        <div>Confirmed By: <span class="font-semibold text-slate-900">{{ $proposal->payment_confirmed_by_name ?: '-' }}</span></div>
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-950">Approval</h2>
                    <div class="mt-4 grid gap-2">
                        @if ($isPaymentConfirmed)
                            <button type="button" disabled class="inline-flex h-11 w-full cursor-not-allowed items-center justify-center rounded-lg bg-emerald-100 px-4 text-sm font-semibold text-emerald-700">
                                <i class="fas fa-circle-check mr-2"></i>Approved by Finance
                            </button>
                        @else
                            <form method="POST" action="{{ route('deals.payment.confirm', $deal) }}">
                                @csrf
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                                    <i class="fas fa-circle-check mr-2"></i>Approve Payment / Invoice
                                </button>
                            </form>
                        @endif
                    </div>
                </section>

                <form method="POST" action="{{ route('deals.invoice.upload', $deal) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    @csrf
                    <h2 class="text-base font-semibold text-slate-950">Upload Invoice</h2>
                    @if ($proposal->invoice_file_path)
                        <a href="{{ route('uploads.show', ['path' => $proposal->invoice_file_path]) }}" target="_blank" class="mt-3 inline-flex text-sm font-semibold text-blue-700 underline">
                            View uploaded invoice
                        </a>
                    @endif
                    <input type="file" name="invoice_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="mt-4 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 disabled:cursor-not-allowed disabled:opacity-60" required @disabled($isPaymentConfirmed)>
                    <button type="submit" class="mt-3 inline-flex h-11 w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" @disabled($isPaymentConfirmed)>
                        <i class="fas fa-upload mr-2"></i>Upload Invoice
                    </button>
                </form>
            </aside>
        </div>
    </div>
</div>
@endsection
