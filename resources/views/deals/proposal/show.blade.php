@extends('layouts.app')
@section('title', ($readOnlyPreview ?? false) ? 'View Proposal' : 'Create Proposal')

@section('content')
<style>
    .proposal-shell { height: calc(100vh - 7.5rem); min-height: 700px; }
    .proposal-workspace-card { height: 100%; min-height: 0; display: flex; flex-direction: column; }
    .proposal-form-scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
    .proposal-preview-shell { height: 100%; min-height: 0; display: flex; flex-direction: column; }
    .proposal-preview-pane { flex: 1 1 auto; min-height: 0; padding: 1.25rem; display: flex; }
    .proposal-preview-scroll { flex: 1 1 auto; min-height: 0; overflow: auto; background: #eef2f7; padding: 12px; }
    .proposal-editor-toolbar { display: flex; flex-wrap: wrap; gap: 8px; padding: 10px 14px; border-bottom: 1px solid #e5e7eb; background: #fff; }
    .proposal-editor-toolbar button { border: 1px solid #d1d5db; background: #fff; border-radius: 8px; padding: 6px 10px; font-size: 12px; font-weight: 600; color: #334155; }
    .proposal-editor-toolbar button:hover { background: #f8fafc; }
    .proposal-editor-toolbar button.is-primary { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .proposal-summary-table { width: 100%; border-collapse: collapse; }
    .proposal-summary-table th, .proposal-summary-table td { border: 1px solid #e5e7eb; padding: 8px 10px; font-size: 12px; vertical-align: top; }
    .proposal-summary-table th { background: #f8fafc; text-align: left; color: #475569; font-weight: 600; }
    .proposal-summary-table tfoot td { font-weight: 700; background: #f8fafc; }
    .proposal-doc { font-family: Georgia, "Times New Roman", serif; color: #111827; font-size: 12px; line-height: 1.58; }
    .proposal-preview-html-wrap { width: fit-content; min-width: 100%; margin: 0 auto; }
    .proposal-page {
        box-sizing: border-box;
        width: 210mm;
        max-width: 210mm;
        height: 297mm;
        margin: 0 auto 16px;
        background: #fff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        border: 1px solid #dbe2ea;
        padding: 44px 52px 70px;
        position: relative;
        overflow: hidden;
    }
    .proposal-inner-page { height: 297mm; padding-top: 52px; overflow: hidden; }
    .proposal-page-body { width: 100%; max-height: calc(297mm - 44px - 70px - 52px - 44px); overflow: hidden; padding-bottom: 8px; box-sizing: border-box; }
    .proposal-cover { height: 297mm; min-height: 297mm; position: relative; overflow: hidden; }
    .proposal-cover-logo-wrap { width: 100%; }
    .proposal-brand-logo { width: 470px; max-width: 100%; height: auto; object-fit: contain; }
    .proposal-cover-body { margin-top: 165px; color: #0031af; }
    .proposal-cover-year { font-size: 86px; line-height: 1; font-style: italic; font-weight: 700; }
    .proposal-cover-title { margin-top: 14px; font-size: 32px; line-height: 1.22; font-style: italic; }
    .proposal-cover-date { margin-top: 60px; font-size: 14px; color: #111827; font-style: italic; }
    .proposal-presented-label { margin-top: 70px; font-size: 15px; font-style: italic; }
    .proposal-presented-name, .proposal-presented-location { margin-top: 10px; font-size: 16px; font-weight: 700; font-style: italic; }
    .proposal-cover-footer { position: absolute; left: 58px; right: 58px; bottom: 54px; }
    .proposal-contact-strip { margin: 0; text-align: center; color: #0031af; font-style: italic; }
    .proposal-contact-inline { display: flex; justify-content: center; flex-wrap: nowrap; gap: 26px; font-size: 12px; }
    .proposal-contact-address { margin-top: 4px; text-align: center; color: #0031af; font-size: 13px; font-style: italic; }
    .proposal-page-footer { position: absolute; left: 64px; right: 64px; bottom: 22px; z-index: 2; background:#fff; font-size: 10px; line-height: 1.2; color: #111827; }
    .proposal-page-number { position: absolute; top: 24px; right: 64px; font-size: 11px; font-weight: 700; color: #111827; }
    .proposal-page-footer div { margin: 0; }
    .proposal-section-heading { margin: 10px 0 18px; font-size: 18px; line-height: 1.22; color: #0031af; font-style: italic; font-weight: 700; letter-spacing: 0.01em; }
    .proposal-section-number { display: inline-block; min-width: 34px; margin-right: 8px; }
.proposal-subheading { margin: 18px 0 8px; font-size: 13px; line-height: 1.35; color: #111827; font-weight: 700; }
.proposal-subheading-blue { color: #0031af; font-style: italic; font-weight: 700; }
.proposal-subheading-tight { margin-top: 16px; }
.proposal-block-spaced { margin-top: 42px; }
.proposal-need-heading { margin-top: 76px; }
    .proposal-term-number { display: inline-block; min-width: 18px; }
    .proposal-paragraph, .proposal-note, .proposal-system-note { margin: 0 0 10px; font-size: 11px; line-height: 1.58; text-align: justify; }
    .proposal-highlights-intro { width: 74%; margin: 36px auto 48px; font-size: 14px; line-height: 1.35; text-align: justify; }
    .proposal-highlights-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 60px 80px; width: 74%; margin: 0 auto; }
    .proposal-highlight-item h3 { margin: 0 0 14px; font-size: 15px; line-height: 1.25; font-weight: 700; color: #111827; }
    .proposal-highlight-item p { margin: 0; font-size: 14px; line-height: 1.25; text-align: justify; }
    .proposal-commitment-lead strong { font-style: italic; }
    .proposal-note { color: #475569; font-style: italic; }
    .proposal-system-note { margin-top: 18px; font-size: 10px; color: #475569; }
    .proposal-bullet-list, .proposal-numbered-list { margin: 0 0 8px 18px; padding: 0; font-size: 11px; line-height: 1.55; }
    .proposal-bullet-list li { margin-bottom: 4px; }
    .proposal-numbered-list li { margin-bottom: 4px; }
    .proposal-requirement-group { margin-bottom: 12px; }
    .proposal-requirement-label { margin-bottom: 6px; font-size: 12px; font-weight: 700; color: #0031af; }
    .proposal-term-block { margin-bottom: 16px; }
    .proposal-service-table, .proposal-pricing-table, .proposal-data-table { width: 100%; border: 1px solid #111827; border-collapse: separate; border-spacing: 0; margin-top: 12px; table-layout: fixed; box-shadow: inset 0 -1px 0 #111827; }
    .proposal-service-table th, .proposal-service-table td, .proposal-pricing-table th, .proposal-pricing-table td, .proposal-data-table th, .proposal-data-table td {
        border: 0;
        border-right: 1px solid #111827;
        border-bottom: 1px solid #111827;
        padding: 8px 10px;
        font-size: 10.5px;
        vertical-align: top;
    }
    .proposal-service-table th:last-child, .proposal-service-table td:last-child, .proposal-pricing-table th:last-child, .proposal-pricing-table td:last-child, .proposal-data-table th:last-child, .proposal-data-table td:last-child { border-right: 0; }
    .proposal-service-table tbody tr:last-child td, .proposal-pricing-table tbody tr:last-child td, .proposal-data-table tbody tr:last-child td { border-bottom: 0; }
.proposal-service-table th, .proposal-pricing-table th, .proposal-data-table th { text-align: left; font-weight: 400; background: transparent; }
    .proposal-service-no { width: 7%; }
    .proposal-service-area { width: 24%; }
    .proposal-service-scope { width: 69%; }
    .proposal-service-area-title { font-style: italic; font-size: 12px; }
    .proposal-service-scope-list ol { margin: 0; padding-left: 18px; }
    .proposal-service-scope-list li { margin: 0 0 4px; }
    .proposal-service-scope-list ol[type="a"] { list-style-type: lower-alpha; }
    .proposal-service-table { margin-top: 22px; }
.proposal-product-offerings-heading { margin-top: 24px; }
.proposal-product-table { margin-top: 10px; margin-bottom: 10px; }
.proposal-pricing-table, .proposal-data-table { margin-top: 16px; }
.proposal-availed-table { margin: 36px 0 14px; table-layout: fixed; }
.proposal-availed-table th, .proposal-availed-table td { padding: 6px 7px; font-size: 10.5px; line-height: 1.35; }
.proposal-availed-table th:first-child, .proposal-availed-table td:first-child { width: 42px; text-align: center; }
.proposal-requirements-table { margin: 30px 0 48px; table-layout: fixed; }
.proposal-requirements-table th, .proposal-requirements-table td { height: 26px; padding: 6px 7px; font-size: 10px; line-height: 1.2; }
.proposal-requirements-table th { font-style: italic; font-weight: 700; }
.proposal-requirements-table th:first-child, .proposal-requirements-table td:first-child { width: 15%; }
.proposal-requirements-table th:nth-child(2), .proposal-requirements-table td:nth-child(2) { width: 14%; }
.proposal-requirements-table th:nth-child(3), .proposal-requirements-table td:nth-child(3),
.proposal-requirements-table th:nth-child(4), .proposal-requirements-table td:nth-child(4),
.proposal-requirements-table th:nth-child(5), .proposal-requirements-table td:nth-child(5) { width: 20%; }
.proposal-requirements-table th:nth-child(6), .proposal-requirements-table td:nth-child(6) { width: 11%; }
.proposal-fee-detail-table { margin: 0 0 16px; }
.proposal-fee-detail-table th, .proposal-fee-detail-table td { padding: 6px 7px; font-size: 10.5px; line-height: 1.35; }
.proposal-pricing-table { width: 98%; margin: 60px auto 0; }
.proposal-pricing-table th:last-child, .proposal-pricing-table td:last-child { text-align: center; width: 34%; }
    .proposal-pricing-table .is-total td { font-weight: 700; color: #0031af; }
    .proposal-term-block + .proposal-term-block { margin-top: 8px; }
    .proposal-end-note { margin: 18px 0 10px; font-size: 11px; font-style: italic; }
    .proposal-signature-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 32px; margin-top: 30px; }
    .proposal-signature-label { font-size: 12px; font-weight: 700; margin-bottom: 50px; }
    .proposal-signature-line { border-bottom: 1px solid #111827; padding-bottom: 6px; font-size: 12px; }
    .proposal-signature-subline { margin-top: 8px; font-size: 12px; }
    .proposal-footer-note { margin-top: 90px; font-size: 10px; color: #5a6470; }
    .proposal-footer-note div { margin-bottom: 2px; }
    .proposal-preview-editable { min-height: 100%; outline: none; }
    .proposal-preview-editable:focus { box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.18); }
    @media (min-width: 1440px) {
        .proposal-page {
            transform: scale(0.94);
            transform-origin: top center;
            margin-bottom: -2px;
        }
    }
    @media (max-width: 1279px) {
        .proposal-shell { height: auto; min-height: 0; }
        .proposal-workspace-card { height: auto; }
        .proposal-form-scroll, .proposal-preview-scroll { max-height: calc(100vh - 15rem); }
    }
    @media (max-width: 768px) {
        .proposal-shell { height: auto; min-height: 0; }
        .proposal-page { padding: 32px 20px; }
        .proposal-cover { min-height: auto; }
        .proposal-cover-body { margin-top: 72px; }
        .proposal-cover-year { font-size: 56px; }
        .proposal-cover-title { font-size: 26px; }
        .proposal-cover-footer { position: static; margin-top: 72px; }
        .proposal-page-footer { left: 20px; right: 20px; bottom: 18px; }
        .proposal-page-number { top: 16px; right: 20px; font-size: 10px; }
        .proposal-contact-inline { flex-wrap: wrap; gap: 12px 18px; }
        .proposal-signature-grid { grid-template-columns: 1fr; }
        .proposal-preview-scroll, .proposal-form-scroll { max-height: none; }
    }
</style>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
            <a href="{{ route('deals.show', $deal->id) }}" class="text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div class="text-lg font-semibold">{{ ($readOnlyPreview ?? false) ? 'View Proposal' : 'Create Proposal' }}</div>
                <div class="text-xs text-gray-500">{{ ($readOnlyPreview ?? false) ? ($proposal->reference_id ?: 'Saved proposal preview') : $deal->deal_code }}</div>
            </div>
            <div class="flex-1"></div>
            <span id="proposal-preview-badge" class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                {{ $generatedPdfUrl ? (($readOnlyPreview ?? false) ? 'Proposal preview ready' : 'Exact preview ready') : 'Generating preview' }}
            </span>
            <a
                id="proposal-pdf-download"
                href="{{ $generatedPdfDownloadUrl ?: '#' }}"
                class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 {{ $generatedPdfDownloadUrl ? '' : 'pointer-events-none opacity-50' }}"
            >
                Download PDF
            </a>
            @unless($readOnlyPreview ?? false)
            <form method="POST" action="{{ route('deals.proposal.send', $deal) }}" class="flex items-center gap-2">
                @csrf
                <input
                    type="email"
                    name="recipient_email"
                    value="{{ old('recipient_email', $deal->email ?: $deal->contact?->email) }}"
                    placeholder="client@email.com"
                    class="h-9 w-56 rounded-lg border border-gray-300 px-3 text-sm"
                    required
                >
                <button type="submit" class="inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Send Proposal
                </button>
            </form>
            @endunless
        </div>
        @if (session('success'))
            <div class="border-b border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
                @if (session('proposal_client_link'))
                    <a href="{{ session('proposal_client_link') }}" target="_blank" class="ml-2 underline">Open client link</a>
                @endif
            </div>
        @endif
        @if (session('error'))
            <div class="border-b border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif
    </div>
</div>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="proposal-shell grid grid-cols-1 xl:grid-cols-[minmax(420px,0.9fr)_minmax(0,1.6fr)] gap-4 p-4 lg:p-5">
            <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden proposal-workspace-card">
                <div class="border-b border-gray-100 px-5 py-4">
                    <div class="text-sm font-semibold text-gray-900">{{ ($readOnlyPreview ?? false) ? 'Saved Proposal Details' : 'Create Proposal Form' }}</div>
                    <div class="mt-1 text-xs text-gray-500">
                        {{ ($readOnlyPreview ?? false)
                            ? 'This proposal has already been prepared and is shown here as a read-only record.'
                            : 'The form is auto-filled from the deal. Edit any field and the right-side preview regenerates for the final PDF output.' }}
                    </div>
                    @unless($readOnlyPreview ?? false)
                    <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                        You can now edit key proposal content directly in the preview on the right. Click the blue-highlighted text blocks to edit them, and the saved proposal fields will stay in sync.
                    </div>
                    @endunless
                </div>

                <div class="proposal-form-scroll px-5 py-5">
                    @if ($readOnlyPreview ?? false)
                    <div class="space-y-5 text-sm text-gray-700">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Deal Code</div>
                                <div class="mt-1 font-semibold text-gray-900">{{ $deal->deal_code }}</div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Reference ID</div>
                                <div class="mt-1 font-semibold text-gray-900">{{ $proposal->reference_id ?: '-' }}</div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Prepared Date</div>
                                <div class="mt-1 font-semibold text-gray-900">{{ optional($proposal->proposal_date)->format('F d, Y') ?: '-' }}</div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Prepared By</div>
                                <div class="mt-1 font-semibold text-gray-900">{{ $proposal->prepared_by_name ?: '-' }}</div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 md:col-span-2">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Location</div>
                                <div class="mt-1 font-semibold text-gray-900">{{ $proposal->location ?: '-' }}</div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 md:col-span-2">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Service Area</div>
                                <div class="mt-1 font-semibold text-gray-900">{{ $documentData['service_type'] ?? '-' }}</div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Services Availed</div>
                            <table class="proposal-summary-table">
                                <thead>
                                    <tr>
                                        <th>Service ID</th>
                                        <th>Name</th>
                                        <th class="text-right">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($documentData['service_fee_rows'] ?? []) as $row)
                                        <tr>
                                            <td>{{ $row['service_id'] ?? '-' }}</td>
                                            <td>{{ $row['name'] ?? '-' }}</td>
                                            <td class="text-right">P{{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-gray-500">No services selected.</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">Total Services</td>
                                        <td class="text-right">P{{ number_format((float) ($documentData['price_regular'] ?? 0), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Products Availed</div>
                            <table class="proposal-summary-table">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Name</th>
                                        <th class="text-right">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($documentData['product_fee_rows'] ?? []) as $row)
                                        <tr>
                                            <td>{{ $row['service_id'] ?? '-' }}</td>
                                            <td>{{ $row['name'] ?? '-' }}</td>
                                            <td class="text-right">P{{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-gray-500">No products selected.</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">Total Products</td>
                                        <td class="text-right">P{{ number_format((float) ($documentData['price_products'] ?? 0), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Computed Totals</div>
                            <table class="proposal-summary-table">
                                <tbody>
                                    <tr><th>Total Services</th><td class="text-right">P{{ number_format((float) ($documentData['price_regular'] ?? 0), 2) }}</td></tr>
                                    <tr><th>Total Products</th><td class="text-right">P{{ number_format((float) ($documentData['price_products'] ?? 0), 2) }}</td></tr>
                                    <tr><th>Discount</th><td class="text-right">P{{ number_format((float) $proposal->price_discount, 2) }}</td></tr>
                                    <tr><th>Tax</th><td class="text-right">P{{ number_format((float) $proposal->price_tax, 2) }}</td></tr>
                                    <tr><th>Subtotal</th><td class="text-right">P{{ number_format((float) $proposal->price_subtotal, 2) }}</td></tr>
                                    <tr><th>Total</th><td class="text-right font-semibold text-blue-700">P{{ number_format((float) $proposal->price_total, 2) }}</td></tr>
                                    <tr><th>Downpayment</th><td class="text-right">P{{ number_format((float) $proposal->price_down, 2) }}</td></tr>
                                    <tr><th>Balance</th><td class="text-right">P{{ number_format((float) $proposal->price_balance, 2) }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @else
                    <form method="POST" action="{{ route('deals.proposal.update', $deal) }}" id="proposal-live-form" class="space-y-5" data-price-products="{{ number_format((float) ($documentData['price_products'] ?? 0), 2, '.', '') }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label class="text-xs text-gray-600">Reference ID</label>
                                <input type="text" name="reference_id" value="{{ old('reference_id', $proposal->reference_id) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="text-xs text-gray-600">Prepared Date</label>
                                <input type="date" name="proposal_date" value="{{ old('proposal_date', optional($proposal->proposal_date)->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="text-xs text-gray-600">Service Area</label>
                                <input type="text" name="service_type" value="{{ old('service_type', $documentData['service_type'] ?? $proposal->service_type) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="text-xs text-gray-600">Prepared By Name</label>
                                <input type="text" name="prepared_by_name" value="{{ old('prepared_by_name', $proposal->prepared_by_name) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-600">Location</label>
                                <input type="text" name="location" value="{{ old('location', $proposal->location) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Services Availed</div>
                            <table class="proposal-summary-table">
                                <thead>
                                    <tr>
                                        <th>Service ID</th>
                                        <th>Name</th>
                                        <th class="text-right">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($documentData['service_fee_rows'] ?? []) as $row)
                                        <tr>
                                            <td>{{ $row['service_id'] ?? '-' }}</td>
                                            <td>{{ $row['name'] ?? '-' }}</td>
                                            <td class="text-right">P{{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-gray-500">No services selected.</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">Total Services</td>
                                        <td class="text-right">P<span data-summary-display="price_regular">{{ number_format((float) ($documentData['price_regular'] ?? 0), 2) }}</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Products Availed</div>
                            <table class="proposal-summary-table">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Name</th>
                                        <th class="text-right">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($documentData['product_fee_rows'] ?? []) as $row)
                                        <tr>
                                            <td>{{ $row['service_id'] ?? '-' }}</td>
                                            <td>{{ $row['name'] ?? '-' }}</td>
                                            <td class="text-right">P{{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-gray-500">No products selected.</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">Total Products</td>
                                        <td class="text-right">P<span data-summary-display="price_products">{{ number_format((float) ($documentData['price_products'] ?? 0), 2) }}</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <div class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">Computed Totals</div>
                            <table class="proposal-summary-table">
                                <tbody>
                                    <tr><th>Total Services</th><td class="text-right">P<span data-summary-display="price_regular">{{ number_format((float) ($documentData['price_regular'] ?? 0), 2) }}</span></td></tr>
                                    <tr><th>Total Products</th><td class="text-right">P<span data-summary-display="price_products">{{ number_format((float) ($documentData['price_products'] ?? 0), 2) }}</span></td></tr>
                                    <tr><th>Discount</th><td class="text-right">P<span data-summary-display="price_discount">{{ number_format((float) ($documentData['price_discount'] ?? 0), 2) }}</span></td></tr>
                                    <tr><th>Tax</th><td class="text-right">P<span data-summary-display="price_tax">{{ number_format((float) ($documentData['price_tax'] ?? 0), 2) }}</span></td></tr>
                                    <tr><th>Subtotal</th><td class="text-right">P<span data-summary-display="price_subtotal">{{ number_format((float) ($documentData['price_subtotal'] ?? 0), 2) }}</span></td></tr>
                                    <tr><th>Total</th><td class="text-right font-semibold text-blue-700">P<span data-summary-display="price_total">{{ number_format((float) ($documentData['price_total'] ?? 0), 2) }}</span></td></tr>
                                    <tr><th>Downpayment</th><td class="text-right">P<span data-summary-display="price_down">{{ number_format((float) ($documentData['price_down'] ?? 0), 2) }}</span></td></tr>
                                    <tr><th>Balance</th><td class="text-right">P<span data-summary-display="price_balance">{{ number_format((float) ($documentData['price_balance'] ?? 0), 2) }}</span></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <input type="hidden" name="price_regular" value="{{ old('price_regular', $proposal->price_regular) }}">
                        <input type="hidden" name="price_discount" value="{{ old('price_discount', $proposal->price_discount) }}">
                        <input type="hidden" name="price_subtotal" value="{{ old('price_subtotal', $proposal->price_subtotal) }}">
                        <input type="hidden" name="price_tax" value="{{ old('price_tax', $proposal->price_tax) }}">
                        <input type="hidden" name="price_total" value="{{ old('price_total', $proposal->price_total) }}">
                        <input type="hidden" name="price_down" value="{{ old('price_down', $proposal->price_down) }}">
                        <input type="hidden" name="price_balance" value="{{ old('price_balance', $proposal->price_balance) }}">
                        <textarea name="our_proposal_text" rows="6" class="hidden">{{ old('our_proposal_text', $proposal->our_proposal_text) }}</textarea>
                        <textarea name="document_html" id="proposal-document-html" class="hidden">{{ old('document_html', $proposal->document_html) }}</textarea>
                        <textarea name="scope_of_service" rows="5" class="hidden">{{ old('scope_of_service', $proposal->scope_of_service) }}</textarea>
                        <textarea name="what_you_will_receive" rows="5" class="hidden">{{ old('what_you_will_receive', $proposal->what_you_will_receive) }}</textarea>
                        @if ($requirementGroup === 'sole')
                            <textarea name="requirements_sole" rows="5" class="hidden">{{ old('requirements_sole', $proposal->requirements_sole) }}</textarea>
                        @else
                            <textarea name="requirements_juridical" rows="5" class="hidden">{{ old('requirements_juridical', $proposal->requirements_juridical) }}</textarea>
                        @endif
                        <textarea name="requirements_optional" rows="4" class="hidden">{{ old('requirements_optional', $proposal->requirements_optional) }}</textarea>

                        <div class="flex flex-wrap gap-2 pt-2">
                            <button type="submit" class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Save Proposal</button>
                            <button type="button" id="proposal-refresh-button" class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Refresh Preview</button>
                        </div>
                        @if ($pendingGlobalTemplate ?? false)
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                                A global proposal template update is already waiting for admin approval. New deals will keep using the last approved template until an admin approves the pending one.
                                @if (in_array((string) (auth()->user()?->role ?? ''), ['Admin', 'SuperAdmin'], true))
                                    <a href="{{ route('admin.deal-proposal-templates.index') }}" class="ml-1 font-semibold underline">Review templates</a>
                                @endif
                            </div>
                        @endif
                    </form>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-[#f8fafc] overflow-hidden flex flex-col proposal-workspace-card">
                <div class="border-b border-gray-100 bg-white px-5 py-4">
                    <div class="flex items-start gap-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">{{ ($readOnlyPreview ?? false) ? $deal->deal_code.' Preview' : 'Proposal Preview' }}</div>
                            <div id="proposal-preview-status" class="mt-1 text-xs text-gray-500">
                                {{ $generatedPdfUrl
                                    ? (($readOnlyPreview ?? false) ? 'This is the saved proposal preview aligned with the downloadable PDF output.' : 'This preview is aligned with the downloadable PDF output.')
                                    : (($readOnlyPreview ?? false) ? 'This saved proposal preview is aligned with the PDF output while the downloadable file is generated in the background.' : 'This preview is aligned with the PDF output while the downloadable file is generated in the background.') }}
                            </div>
                        </div>
                    </div>
                    <div id="proposal-preview-error" class="mt-3 {{ $previewError ? '' : 'hidden' }} rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                        {{ $previewError ?: '' }}
                    </div>
                </div>

                <div class="proposal-preview-pane">
                    <div id="proposal-preview-panel" class="proposal-preview-shell w-full rounded-2xl border border-gray-200 bg-white overflow-hidden">
                        @unless($readOnlyPreview ?? false)
                        <div class="proposal-editor-toolbar">
                            <button type="button" data-editor-cmd="bold">Bold</button>
                            <button type="button" data-editor-cmd="italic">Italic</button>
                            <button type="button" data-editor-cmd="underline">Underline</button>
                            <button type="button" data-editor-cmd="insertUnorderedList">Bullets</button>
                            <button type="button" data-editor-cmd="insertOrderedList">Numbering</button>
                            <button type="button" data-editor-cmd="justifyLeft">Left</button>
                            <button type="button" data-editor-cmd="justifyCenter">Center</button>
                            <button type="button" data-editor-cmd="justifyRight">Right</button>
                            <button type="button" data-editor-action="clear-format">Clear Format</button>
                            <button type="button" data-editor-action="save-global-template" class="is-primary">Save Global Template</button>
                        </div>
                        @endunless
                        <div id="proposal-preview-scroll" class="proposal-preview-scroll">
                            <div class="proposal-preview-html-wrap">
                                <div id="proposal-preview-html" @unless($readOnlyPreview ?? false) contenteditable="true" class="proposal-preview-editable" @endunless>
                                    {!! $proposalDocumentHtml !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@unless ($readOnlyPreview ?? false)
<script type="application/json" id="dealProposalPreviewData">@json([
    'previewUrl' => route('deals.proposal.preview', $deal),
])</script>

<script>
(() => {
    const previewDataElement = document.getElementById('dealProposalPreviewData');
    const previewData = previewDataElement ? JSON.parse(previewDataElement.textContent || '{}') : {};
    const previewUrl = previewData.previewUrl || '';
    const form = document.getElementById('proposal-live-form');
    const refreshButton = document.getElementById('proposal-refresh-button');
    const htmlPreview = document.getElementById('proposal-preview-html');
    const status = document.getElementById('proposal-preview-status');
    const errorBox = document.getElementById('proposal-preview-error');
    const badge = document.getElementById('proposal-preview-badge');
    const pdfDownload = document.getElementById('proposal-pdf-download');
    const documentHtmlInput = document.getElementById('proposal-document-html');
    const editorToolbar = document.querySelector('.proposal-editor-toolbar');

    if (!form || !refreshButton || !htmlPreview || !status || !errorBox || !badge || !pdfDownload || !documentHtmlInput) {
        return;
    }

    let previewTimer = null;
    let activeController = null;
    const productTotal = Number.parseFloat(form.dataset.priceProducts || '0') || 0;

    const inputByName = (name) => form.querySelector(`[name="${name}"]`);
    const parseNumber = (value) => {
        const normalized = String(value ?? '').replace(/,/g, '').replace(/[^\d.-]/g, '').trim();
        const parsed = Number.parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    };
    const formatMoney = (value) => Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const normalizeMultilineText = (element) => element.innerText
        .replace(/\r/g, '')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
    const setPreviewDisplay = (field, value) => {
        htmlPreview.querySelectorAll(`[data-proposal-display="${field}"]`).forEach((node) => {
            node.textContent = formatMoney(value);
        });
        document.querySelectorAll(`[data-summary-display="${field}"]`).forEach((node) => {
            node.textContent = formatMoney(value);
        });
    };
    const recalculateFeeFields = () => {
        const regular = parseNumber(inputByName('price_regular')?.value || 0);
        const discount = parseNumber(inputByName('price_discount')?.value || 0);
        const tax = parseNumber(inputByName('price_tax')?.value || 0);
        const subtotal = Math.max((regular + productTotal) - discount, 0);
        const total = subtotal + tax;
        const down = Number((total * 0.5).toFixed(2));
        const balance = Number((total - down).toFixed(2));

        if (inputByName('price_subtotal')) inputByName('price_subtotal').value = subtotal.toFixed(2);
        if (inputByName('price_total')) inputByName('price_total').value = total.toFixed(2);
        if (inputByName('price_down')) inputByName('price_down').value = down.toFixed(2);
        if (inputByName('price_balance')) inputByName('price_balance').value = balance.toFixed(2);

        setPreviewDisplay('price_regular', regular);
        setPreviewDisplay('price_products', productTotal);
        setPreviewDisplay('price_discount', discount);
        setPreviewDisplay('price_tax', tax);
        setPreviewDisplay('price_subtotal', subtotal);
        setPreviewDisplay('price_total', total);
        setPreviewDisplay('price_down', down);
        setPreviewDisplay('price_balance', balance);
    };

    const setButtonState = (link, href) => {
        if (href) {
            link.href = href;
            link.classList.remove('pointer-events-none', 'opacity-50');
            return;
        }

        link.href = '#';
        link.classList.add('pointer-events-none', 'opacity-50');
    };

    const setBusy = (message) => {
        badge.textContent = 'Generating preview';
        badge.classList.remove('bg-emerald-50', 'text-emerald-700', 'bg-amber-50', 'text-amber-700');
        badge.classList.add('bg-blue-50', 'text-blue-700');
        status.textContent = message;
        errorBox.classList.add('hidden');
    };

    const setReady = (payload) => {
        badge.textContent = payload.pdf_url ? 'Preview ready' : 'HTML preview ready';
        badge.classList.remove('bg-blue-50', 'text-blue-700', 'bg-amber-50', 'text-amber-700');
        badge.classList.add('bg-emerald-50', 'text-emerald-700');
        status.textContent = payload.pdf_url
            ? 'Preview updated and downloadable PDF refreshed.'
            : 'Preview updated from the same proposal data used for the generated PDF.';
        errorBox.classList.add('hidden');
        htmlPreview.innerHTML = payload.html || htmlPreview.innerHTML;
        window.paginateProposalTables?.(htmlPreview);
        syncDocumentHtml();
        recalculateFeeFields();

        setButtonState(pdfDownload, payload.pdf_download_url || null);
    };

    const setError = (message) => {
        badge.textContent = 'Preview failed';
        badge.classList.remove('bg-blue-50', 'text-blue-700', 'bg-emerald-50', 'text-emerald-700');
        badge.classList.add('bg-amber-50', 'text-amber-700');
        status.textContent = 'The proposal preview could not be refreshed just yet.';
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    };

    const refreshPreview = async () => {
        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();
        setBusy('Regenerating the proposal preview for the PDF output.');

        try {
            const formData = new FormData(form);
            formData.delete('_method');

            const response = await fetch(previewUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                signal: activeController.signal,
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Preview generation failed.');
            }

            setReady(payload);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            setError(error.message || 'Preview generation failed.');
        }
    };

    const queuePreview = () => {
        window.clearTimeout(previewTimer);
        previewTimer = window.setTimeout(refreshPreview, 700);
    };
    const extractFieldText = (selector) => {
        const node = htmlPreview.querySelector(selector);
        return node ? (node.textContent || '').trim() : '';
    };
    const extractFieldMultiline = (selector) => {
        const node = htmlPreview.querySelector(selector);
        return node ? normalizeMultilineText(node) : '';
    };
    const syncStructuredFieldsFromDocument = () => {
        const serviceType = extractFieldText('.proposal-cover-title');
        const location = extractFieldText('.proposal-presented-location');
        const proposalText = extractFieldMultiline('.proposal-page:nth-of-type(7) .proposal-paragraph');
        const preparedByName = extractFieldText('.proposal-signature-line');

        if (inputByName('service_type') && serviceType) inputByName('service_type').value = serviceType;
        if (inputByName('location') && location) inputByName('location').value = location;
        if (inputByName('our_proposal_text') && proposalText) inputByName('our_proposal_text').value = proposalText;
        if (inputByName('prepared_by_name') && preparedByName) inputByName('prepared_by_name').value = preparedByName;

        const requirementCells = htmlPreview.querySelectorAll('.proposal-requirements-table tbody tr td');
        if (requirementCells.length >= 5) {
            if (inputByName('requirements_sole')) inputByName('requirements_sole').value = normalizeMultilineText(requirementCells[2]);
            if (inputByName('requirements_juridical')) inputByName('requirements_juridical').value = normalizeMultilineText(requirementCells[3]);
            if (inputByName('requirements_optional')) inputByName('requirements_optional').value = normalizeMultilineText(requirementCells[4]);
        }
    };
    const syncDocumentHtml = () => {
        documentHtmlInput.value = htmlPreview.innerHTML.trim();
        syncStructuredFieldsFromDocument();
    };
    const loadTemplateIntoEditor = async () => {
        const formData = new FormData(form);
        formData.delete('_method');
        formData.set('document_html', '');

        const response = await fetch(previewUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.message || 'Unable to reset proposal template.');
        }

        htmlPreview.innerHTML = payload.html || htmlPreview.innerHTML;
        window.paginateProposalTables?.(htmlPreview);
        syncDocumentHtml();
        recalculateFeeFields();
    };

    form.addEventListener('input', queuePreview);
    form.addEventListener('input', recalculateFeeFields);
    form.addEventListener('change', () => {
        recalculateFeeFields();
        queuePreview();
    });
    form.addEventListener('submit', () => {
        window.paginateProposalTables?.(htmlPreview);
        syncDocumentHtml();
    });
    refreshButton.addEventListener('click', refreshPreview);

    htmlPreview.addEventListener('input', () => {
        syncDocumentHtml();
    });
    htmlPreview.addEventListener('paste', () => {
        window.setTimeout(syncDocumentHtml, 0);
    });

    editorToolbar?.querySelectorAll('[data-editor-cmd]').forEach((button) => {
        button.addEventListener('click', () => {
            htmlPreview.focus();
            document.execCommand(button.dataset.editorCmd, false, null);
            syncDocumentHtml();
        });
    });

    editorToolbar?.querySelector('[data-editor-action="clear-format"]')?.addEventListener('click', () => {
        htmlPreview.focus();
        document.execCommand('removeFormat', false, null);
        syncDocumentHtml();
    });

    editorToolbar?.querySelector('[data-editor-action="save-global-template"]')?.addEventListener('click', () => {
        window.paginateProposalTables?.(htmlPreview);
        syncDocumentHtml();

        let globalInput = form.querySelector('[name="submit_global_template"]');
        if (!globalInput) {
            globalInput = document.createElement('input');
            globalInput.type = 'hidden';
            globalInput.name = 'submit_global_template';
            form.appendChild(globalInput);
        }

        globalInput.value = '1';
        form.requestSubmit();
    });

    syncDocumentHtml();
    recalculateFeeFields();
})();
</script>
@endunless

@include('deals.proposal.partials.pagination-script')
@endsection
