<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Review</title>
    <style>
        body { margin: 0; background: #eef2f7; color: #0f172a; font-family: Arial, Helvetica, sans-serif; }
        .topbar { position: sticky; top: 0; z-index: 10; display: flex; gap: 12px; align-items: center; padding: 14px 18px; background: #fff; border-bottom: 1px solid #dbe2ea; }
        .topbar h1 { margin: 0; font-size: 18px; }
        .topbar p { margin: 3px 0 0; color: #64748b; font-size: 12px; }
        .spacer { flex: 1; }
        .button { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; padding: 10px 16px; font-size: 14px; font-weight: 700; text-decoration: none; cursor: pointer; transition: background 0.15s ease, transform 0.05s ease; }
        .button-blue { background: #2563eb; color: #fff; }
        .button-blue:hover { background: #1d4ed8; }
        .button-muted { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; }
        .shell { max-width: 1500px; margin: 22px auto; padding: 0 16px 32px; }
        .preview-shell { border: 1px solid #e5e7eb; background: #f8fafc; border-radius: 16px; overflow: hidden; }
        .preview-shell-header { border-bottom: 1px solid #f1f5f9; background: #fff; padding: 16px 20px; }
        .preview-shell-title { margin: 0; font-size: 14px; font-weight: 700; color: #111827; }
        .preview-shell-subtitle { margin: 4px 0 0; color: #6b7280; font-size: 12px; }
        .preview-pane { padding: 20px; }
        .notice { margin-bottom: 14px; border: 1px solid #bbf7d0; background: #ecfdf5; color: #047857; padding: 14px 16px; border-radius: 10px; font-size: 14px; font-weight: 600; line-height: 1.5; }
        .notice-warning { border-color: #fef08a; background: #fefce8; color: #854d0e; }
        .notice-danger { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
        .decision-workspace { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; align-items: stretch; }
        .decision-panel { min-width: 0; border: 1px solid #dbe2ea; background: #fff; border-radius: 12px; padding: 16px 18px; box-shadow: 0 4px 12px rgba(15,23,42,0.03); }
        .decision-panel h3 { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #0f172a; }
        .decision-panel label { display: block; margin-bottom: 6px; font-size: 12px; color: #475569; font-weight: 700; }
        .decision-panel input, .decision-panel textarea, .decision-panel select { width: 100%; min-width: 0; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px 10px; font-size: 13px; font-family: inherit; }
        .decision-panel input:focus, .decision-panel textarea:focus { outline: none; border-color: #2563eb; }
        .decision-panel select:focus { outline: none; border-color: #2563eb; }
        .decision-panel textarea { min-height: 64px; resize: vertical; }
        .decision-representative { margin: 0 0 14px; }
        .decision-representative label { margin-bottom: 5px; }
        .decision-workspace .preview-shell { min-width: 0; }
        .item-response-intro { margin: 0 0 10px; color: #64748b; font-size: 12px; line-height: 1.45; }
        .decision-table-scroll { width: 100%; overflow-x: auto; }
        .decision-items-table { width: 100%; min-width: 700px; border-collapse: collapse; table-layout: fixed; color: #334155; font-size: 11px; }
        .decision-items-table th, .decision-items-table td { padding: 9px 7px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        .decision-items-table th { background: #f8fafc; color: #475569; font-size: 10px; font-weight: 700; }
        .decision-items-table th:nth-child(1) { width: 12%; }
        .decision-items-table th:nth-child(2) { width: 20%; }
        .decision-items-table th:nth-child(3) { width: 9%; }
        .decision-items-table th:nth-child(4), .decision-items-table th:nth-child(5) { width: 14%; }
        .decision-items-table th:nth-child(6) { width: 31%; }
        .decision-item-type { display: inline-block; padding: 3px 6px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 9px; font-weight: 700; }
        .decision-item-name { color: #0f172a; font-weight: 700; }
        .decision-item-actions { min-width: 0; }
        .item-decision-buttons { display: flex; flex-wrap: nowrap; gap: 6px; width: 100%; }
        .item-decision-button { flex: 1 1 0; min-width: 0; border: 1px solid; border-radius: 7px; padding: 7px 6px; background: #fff; font: inherit; font-size: 11px; font-weight: 700; white-space: nowrap; cursor: pointer; transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease; }
        .item-decision-approve { border-color: #059669; color: #047857; }
        .item-decision-approve.is-selected { background: #059669; color: #fff; box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.16); }
        .item-decision-reject { border-color: #dc2626; color: #b91c1c; }
        .item-decision-reject.is-selected { background: #dc2626; color: #fff; box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.16); }
        .item-decision-button:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
        .decision-status { display: inline-block; margin: 0 0 5px; padding: 3px 6px; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: 10px; font-weight: 700; }
        .decision-status-accepted { background: #ecfdf5; color: #047857; }
        .decision-status-declined { background: #fef2f2; color: #b91c1c; }
        .decision-comment-field { margin-top: 6px; }
        .decision-comment-field[hidden] { display: none; }
        .decision-comment-field textarea { min-height: 52px; padding: 7px; font-size: 11px; }
        .confirm-reject-button { margin-top: 6px; border: 0; border-radius: 6px; padding: 7px 10px; background: #b91c1c; color: #fff; font: inherit; font-size: 11px; font-weight: 700; cursor: pointer; }
        .decision-comment-display { margin-top: 6px; color: #475569; font-size: 11px; line-height: 1.4; overflow-wrap: anywhere; }
        .decision-comment-display[hidden] { display: none; }
        .decision-comment-row[hidden] { display: none; }
        .decision-comment-row td { padding: 0 7px 9px; border-bottom: 1px solid #e2e8f0; }
        .decision-comment-row-content { width: 100%; box-sizing: border-box; padding: 9px 11px; border: 1px solid #e2e8f0; border-radius: 7px; background: #f8fafc; text-align: left; }
        .decision-comment-row-content > .decision-comment-display, .decision-comment-row-content > .decision-comment-field { width: 100%; }
        .decision-pending-status { display: block; margin-top: 5px; color: #1d4ed8; font-size: 10px; font-weight: 700; }
        .decision-pending-status[hidden] { display: none; }
        .decision-item-cards { display: grid; gap: 10px; }
        .decision-item-card { min-width: 0; padding: 12px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fbfdff; }
        .decision-item-card h4 { margin: 0 0 5px; color: #0f172a; font-size: 13px; overflow-wrap: anywhere; }
        .decision-item-card-description { margin: 0 0 9px; color: #64748b; font-size: 12px; line-height: 1.45; overflow-wrap: anywhere; }
        .decision-item-card .item-decision-buttons { max-width: 240px; }
        .decision-comment-field { margin-top: 8px; }
        .decision-comment-field label { display: block; margin-bottom: 5px; }
        .decision-comment-field textarea { min-height: 56px; }
        .decision-comment-field[hidden], .decision-table-layout[hidden], .decision-card-layout[hidden] { display: none; }
        .proposal-item-response { min-width: 0; margin-top: 10px; padding: 11px 12px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fbfdff; }
        .proposal-item-name { margin-bottom: 4px; font-size: 13px; font-weight: 700; color: #0f172a; overflow-wrap: anywhere; }
        .proposal-item-description { margin: 0 0 8px; color: #64748b; font-size: 12px; line-height: 1.45; overflow-wrap: anywhere; }
        .proposal-item-fields { display: grid; grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr); gap: 10px; align-items: start; }
        .proposal-item-fields > div { min-width: 0; }
        .proposal-item-fields label { display: block; margin-bottom: 5px; color: #475569; font-size: 12px; font-weight: 700; }
        .item-current-decision { margin-bottom: 7px; color: #475569; font-size: 11px; }
        .item-response-submit { margin-top: 12px; min-width: 164px; }
        .conforme-approved-stamp { margin-top: 10px; font-size: 11px; font-style: italic; color: #047857; }
        .proposal-frame { overflow: auto; background: #eef2f7; border: 1px solid #dbe2ea; border-radius: 16px; padding: 12px; }
        .proposal-doc { font-family: Georgia, "Times New Roman", serif; color: #111827; font-size: 12px; line-height: 1.58; }
        .proposal-page { box-sizing: border-box; width: 210mm; max-width: 210mm; height: 297mm; margin: 0 auto 16px; background: #fff; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); border: 1px solid #dbe2ea; padding: 44px 52px 70px; position: relative; overflow: hidden; }
        .proposal-inner-page { height: 297mm; padding-top: 52px; overflow: hidden; }
        .proposal-page-body { width: 100%; max-height: calc(297mm - 44px - 70px - 52px - 34px); overflow: hidden; }
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
        @media (max-width: 980px) {
            .decision-workspace { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
            .decision-panel { padding: 16px; }
            .proposal-item-fields { grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr); gap: 8px; }
        }
        @media (max-width: 760px) {
            .decision-workspace { grid-template-columns: minmax(0, 1fr); }
            .decision-workspace .preview-shell { grid-row: 2; }
            .decision-workspace .decision-panel { grid-row: 1; }
        }
        .proposal-contact-strip { margin: 0; text-align: center; color: #0031af; font-style: italic; }
        .proposal-contact-inline { display: flex; justify-content: center; flex-wrap: nowrap; gap: 26px; font-size: 12px; }
        .proposal-contact-address { margin-top: 4px; text-align: center; color: #0031af; font-size: 13px; font-style: italic; }
        .proposal-page-footer { position: absolute; left: 64px; right: 64px; bottom: 22px; z-index: 2; background: #fff; font-size: 10px; line-height: 1.2; color: #111827; }
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
        .proposal-bullet-list li, .proposal-numbered-list li { margin-bottom: 4px; }
        .proposal-requirement-group { margin-bottom: 12px; }
        .proposal-requirement-label { margin-bottom: 6px; font-size: 12px; font-weight: 700; color: #0031af; }
        .proposal-term-block { margin-bottom: 16px; }
        .proposal-service-no { width: 7%; }
        .proposal-service-area { width: 24%; }
        .proposal-service-scope { width: 69%; }
        .proposal-service-area-title { font-style: italic; font-size: 12px; }
        .proposal-service-scope-list ol { margin: 0; padding-left: 18px; }
        .proposal-service-scope-list li { margin: 0 0 4px; }
        .proposal-service-scope-list ol[type="a"] { list-style-type: lower-alpha; }
        .proposal-service-table { margin-top: 22px; }
        .proposal-product-offerings-heading { margin-top: 24px; }
        table { border-collapse: collapse; }
        .proposal-service-table, .proposal-pricing-table, .proposal-data-table { width: 100%; border-collapse: collapse; margin-top: 12px; table-layout: fixed; }
        .proposal-service-table th, .proposal-service-table td, .proposal-pricing-table th, .proposal-pricing-table td, .proposal-data-table th, .proposal-data-table td { border: 1px solid #111827; padding: 8px 10px; font-size: 10.5px; vertical-align: top; }
        .proposal-service-table th, .proposal-pricing-table th, .proposal-data-table th { text-align: left; font-weight: 400; background: transparent; }
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
        @media (max-width: 760px) {
            .topbar, .approval { grid-template-columns: 1fr; align-items: stretch; }
            .topbar { display: grid; }
            .spacer { display: none; }
            .proposal-item-fields { grid-template-columns: minmax(0, 1fr); gap: 8px; }
            .item-response-submit { width: 100%; }
            .proposal-frame { padding: 10px; }
            .proposal-page { width: 210mm; max-width: 210mm; padding: 32px 20px; }
            .proposal-cover-body { margin-top: 72px; }
            .proposal-cover-year { font-size: 56px; }
            .proposal-cover-title { font-size: 26px; }
            .proposal-cover-footer { position: static; margin-top: 72px; }
            .proposal-page-footer { left: 20px; right: 20px; bottom: 18px; }
            .proposal-page-number { top: 16px; right: 20px; font-size: 10px; }
            .proposal-contact-inline { flex-wrap: wrap; gap: 12px 18px; }
            .proposal-signature-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    @php
        $approvalName = $proposal->client_approved_by_name ?: $proposal->quotation_approved_by_name ?: 'Finance';
        $approvalDate = $proposal->client_approved_at ?: $proposal->quotation_approved_at;
    @endphp
    <div class="topbar">
        <div>
            <h1>{{ $proposal->reference_id ?: $deal->deal_code }}</h1>
            <p>
                @if ($proposal->status === 'approved')
                    Approved proposal
                @elseif ($proposal->status === 'rejected')
                    Declined proposal
                @else
                    Proposal review and approval
                @endif
            </p>
        </div>
        <div class="spacer"></div>
        <a href="{{ $downloadUrl }}" class="button button-blue">Download PDF</a>
    </div>

    <main class="shell">
        @if (session('success'))
            <div class="notice">{{ session('success') }}</div>
        @endif

        @if (session('warning'))
            <div class="notice notice-warning">{{ session('warning') }}</div>
        @endif

        @if (session('error'))
            <div class="notice notice-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice notice-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('deals.proposal.partials.decision-summary', ['showNotificationStatus'=>false])
        <div class="decision-workspace">
            <div class="preview-shell">
                <div class="preview-shell-header">
                    <h2 class="preview-shell-title">Proposal Preview</h2>
                    <p class="preview-shell-subtitle">This client view follows the same branded preview format as the proposal draft.</p>
                </div>
                <div class="preview-pane">
                    <div class="proposal-frame">
                        {!! $proposalDocumentHtml !!}
                    </div>
                </div>
            </div>

            <section class="decision-panel">
                <h3>{{ ($proposal->client_submitted_at || in_array(strtolower((string) $proposal->status), ['approved', 'rejected', 'partially_approved'], true)) ? 'Proposal Decision' : ($proposalLineItems->isNotEmpty() ? 'Decide on Individual Items' : 'Proposal Decision') }}</h3>
                @if (($proposal->client_submitted_at || in_array(strtolower((string) $proposal->status), ['approved', 'rejected', 'partially_approved'], true)))
                    @if ($proposal->status === 'partially_approved')
                        <div class="notice">Your final decision has been submitted. Pending items are listed below. Request a revised proposal if further changes are needed.</div>
                    @elseif ($proposal->status === 'approved')
                        <div class="notice">
                            <div><strong>Proposal Approved</strong> &bull; Approved by {{ $approvalName }} on {{ optional($approvalDate)->format('F j, Y g:i A') }}.</div>
                            @if (!empty($proposal->client_approval_note))
                                <div style="margin-top: 6px; font-weight: normal; font-size: 13px;">
                                    <strong>Feedback / Comments:</strong> {{ $proposal->client_approval_note }}
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="notice notice-danger">
                            <div><strong>Proposal Declined</strong> &bull; Declined by {{ $proposal->client_approved_by_name ?: 'Client' }} on {{ optional($proposal->client_approved_at ?: $proposal->updated_at)->format('F j, Y g:i A') }}.</div>
                            @if (!empty($proposal->client_approval_note))
                                <div style="margin-top: 6px; font-weight: normal; font-size: 13px;">
                                    <strong>Feedback / Reason:</strong> {{ $proposal->client_approval_note }}
                                </div>
                            @endif
                        </div>
                    @endif
                    <div class="decision-table-scroll">
                        <table class="decision-items-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Item / Name</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Client Decisions / Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($proposalLineItems as $item)
                                    <tr>
                                        <td><span class="decision-item-type">{{ $item['type'] ?: 'ITEM' }}</span></td>
                                        <td><span class="decision-item-name">{{ $item['name'] }}</span></td>
                                        <td>
                                            @if (is_numeric($item['qty']))
                                                {{ rtrim(rtrim(number_format((float) $item['qty'], 2, '.', ''), '0'), '.') }}{{ $item['unit'] ? ' '.$item['unit'] : '' }}
                                            @else
                                                &mdash;
                                            @endif
                                        </td>
                                        <td>{{ is_numeric($item['unit_price']) ? '₱'.number_format((float) $item['unit_price'], 2) : 'Needs confirmation' }}</td>
                                        <td>{{ is_numeric($item['total']) ? '₱'.number_format((float) $item['total'], 2) : 'Needs confirmation' }}</td>
                                        <td class="decision-item-actions">
                                            <span class="decision-status{{ $item['decision'] === 'accepted' ? ' decision-status-accepted' : ($item['decision'] === 'declined' ? ' decision-status-declined' : '') }}">
                                                Saved: {{ match($item['decision']) { 'accepted'=>'Approved', 'declined'=>'Rejected', default=>'Pending' } }}
                                            </span>
                                            <div class="item-current-decision">Client comment: {{ filled($item['comment']) ? $item['comment'] : 'No comment provided.' }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <form method="POST" action="{{ $itemDecisionsUrl }}">
                        @csrf
                        <div class="decision-representative">
                            <label for="item_decision_client_name">Authorized Representative Full Name</label>
                            <input type="text" id="item_decision_client_name" name="client_name" value="{{ old('client_name', $clientName ?? '') }}" maxlength="255" required>
                        </div>
                        @if ($proposalLineItems->isNotEmpty())
                            <p class="item-response-intro">Approve or reject each item you are ready to decide on. Add a comment when rejecting an item.</p>
                        @endif
                        <div class="decision-card-layout" data-decision-layout="cards" data-active="{{ $hasSavedItemDecisions ? 'false' : 'true' }}" @if ($hasSavedItemDecisions) hidden @endif>
                            <fieldset data-layout-fields @disabled($hasSavedItemDecisions) style="margin:0; padding:0; border:0;">
                                <div class="decision-item-cards">
                                    @foreach ($proposalLineItems as $index => $item)
                                        @php($currentDecision = old("responses.$index.decision", $item['decision'] ?? ''))
                                        <article class="decision-item-card" data-decision-item="{{ $index }}">
                                            <h4>{{ $item['name'] }}</h4>
                                            @if (filled($item['description']))
                                                <p class="decision-item-card-description">{{ $item['description'] }}</p>
                                            @endif
                                            <input type="hidden" name="responses[{{ $index }}][line_item_id]" value="{{ $item['id'] }}">
                                            <input type="hidden" name="responses[{{ $index }}][decision]" value="{{ $currentDecision }}" data-item-decision>
                                            <div class="item-decision-buttons" role="group" aria-label="Decision for {{ $item['name'] }}">
                                                <button type="button" class="item-decision-button item-decision-approve{{ $currentDecision === 'accepted' ? ' is-selected' : '' }}" data-choice="accepted" aria-pressed="{{ $currentDecision === 'accepted' ? 'true' : 'false' }}">Approve</button>
                                                <button type="button" class="item-decision-button item-decision-reject{{ $currentDecision === 'declined' ? ' is-selected' : '' }}" data-choice="declined" aria-pressed="{{ $currentDecision === 'declined' ? 'true' : 'false' }}">Reject</button>
                                            </div>
                                            <span class="decision-pending-status" data-pending-status hidden></span>
                                            <div class="decision-comment-field" data-comment-field @if ($currentDecision !== 'declined') hidden @endif>
                                                <label for="card_item_comment_{{ $index }}">Optional Comment for Rejection</label>
                                                <textarea id="card_item_comment_{{ $index }}" name="responses[{{ $index }}][comment]" maxlength="2000" placeholder="Reason or comment (optional)...">{{ old("responses.$index.comment", $item['comment']) }}</textarea>
                                                <button type="button" class="confirm-reject-button" data-confirm-reject>Confirm Reject</button>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>

                        <div class="decision-table-layout" data-decision-layout="table" data-active="{{ $hasSavedItemDecisions ? 'true' : 'false' }}" @unless ($hasSavedItemDecisions) hidden @endunless>
                            <fieldset data-layout-fields @disabled(! $hasSavedItemDecisions) style="margin:0; padding:0; border:0;">
                                <div class="decision-table-scroll">
                                    <table class="decision-items-table">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Item / Name</th>
                                                <th>Qty</th>
                                                <th>Unit Price</th>
                                                <th>Total</th>
                                                <th>Client Decisions / Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($proposalLineItems as $index => $item)
                                                @php($currentDecision = old("responses.$index.decision", $item['decision'] ?? ''))
                                                @php($savedDecision = $item['decision'] ?? null)
                                                <tr data-decision-item="{{ $index }}">
                                                    <td><span class="decision-item-type">{{ $item['type'] ?: 'ITEM' }}</span></td>
                                                    <td><span class="decision-item-name">{{ $item['name'] }}</span></td>
                                                    <td>
                                                        @if (is_numeric($item['qty']))
                                                            {{ rtrim(rtrim(number_format((float) $item['qty'], 2, '.', ''), '0'), '.') }}{{ $item['unit'] ? ' '.$item['unit'] : '' }}
                                                        @else
                                                            &mdash;
                                                        @endif
                                                    </td>
                                                    <td>{{ is_numeric($item['unit_price']) ? '₱'.number_format((float) $item['unit_price'], 2) : 'Needs confirmation' }}</td>
                                                    <td>{{ is_numeric($item['total']) ? '₱'.number_format((float) $item['total'], 2) : 'Needs confirmation' }}</td>
                                                    <td class="decision-item-actions">
                                                        <input type="hidden" name="responses[{{ $index }}][line_item_id]" value="{{ $item['id'] }}">
                                                        <input type="hidden" name="responses[{{ $index }}][decision]" value="{{ $currentDecision }}" data-item-decision>
                                                        <span class="decision-status{{ $savedDecision === 'accepted' ? ' decision-status-accepted' : ($savedDecision === 'declined' ? ' decision-status-declined' : '') }}">
                                                            Saved: {{ $savedDecision ? ucfirst($savedDecision) : 'Pending' }}
                                                        </span>
                                                        <span class="decision-pending-status" data-pending-status hidden></span>
                                                        <div class="item-decision-buttons" role="group" aria-label="Decision for {{ $item['name'] }}">
                                                            <button type="button" class="item-decision-button item-decision-approve{{ $currentDecision === 'accepted' ? ' is-selected' : '' }}" data-choice="accepted" aria-pressed="{{ $currentDecision === 'accepted' ? 'true' : 'false' }}">Approve</button>
                                                            <button type="button" class="item-decision-button item-decision-reject{{ $currentDecision === 'declined' ? ' is-selected' : '' }}" data-choice="declined" aria-pressed="{{ $currentDecision === 'declined' ? 'true' : 'false' }}">Reject</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="decision-comment-row" data-comment-row="{{ $index }}" @if ($savedDecision !== 'declined' || blank($item['comment'])) hidden @endif>
                                                    <td colspan="6">
                                                        <div class="decision-comment-row-content">
                                                            <div class="decision-comment-display" data-comment-display @if ($savedDecision !== 'declined' || blank($item['comment'])) hidden @endif>
                                                                <strong>Comment / Reason for Rejection:</strong> <span data-comment-text>{{ $item['comment'] }}</span>
                                                            </div>
                                                            <div class="decision-comment-field" data-comment-field hidden>
                                                                <label for="table_item_comment_{{ $index }}">Optional Comment for Rejection</label>
                                                                <textarea id="table_item_comment_{{ $index }}" name="responses[{{ $index }}][comment]" maxlength="2000" placeholder="Reason or comment (optional)...">{{ old("responses.$index.comment", $item['comment']) }}</textarea>
                                                                <button type="button" class="confirm-reject-button" data-confirm-reject>Confirm Reject</button>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </fieldset>
                        </div>
                        @if ($proposalLineItems->isNotEmpty())
                            <button type="submit" class="button button-blue item-response-submit">Submit Final Decision</button>
                        @endif
                    </form>
                @endif
            </section>
        </div>
    </main>
    @if ($proposal->status === 'approved' && $approvalDate)
    <script>
        (() => {
            const clientBlock = document.querySelector('.proposal-signature-grid .proposal-signature-block');
            if (!clientBlock || clientBlock.querySelector('.conforme-approved-stamp')) {
                return;
            }

            const stamp = document.createElement('div');
            stamp.className = 'conforme-approved-stamp';
            stamp.textContent = 'Approved on {{ optional($approvalDate)->format('F j, Y g:i A') }}';
            clientBlock.appendChild(stamp);
        })();
    </script>
    @elseif ($proposal->status === 'rejected')
    <script>
        (() => {
            const clientBlock = document.querySelector('.proposal-signature-grid .proposal-signature-block');
            if (!clientBlock || clientBlock.querySelector('.conforme-approved-stamp')) {
                return;
            }

            const stamp = document.createElement('div');
            stamp.className = 'conforme-approved-stamp';
            stamp.style.color = '#dc2626';
            stamp.textContent = 'Declined on {{ optional($proposal->client_approved_at ?: $proposal->updated_at)->format('F j, Y g:i A') }}';
            clientBlock.appendChild(stamp);
        })();
    </script>
    @endif
    <script>
        document.querySelectorAll('.decision-panel form').forEach((form) => {
            const layouts = Array.from(form.querySelectorAll('[data-decision-layout]'));
            const setActiveLayout = (activeLayout) => {
                layouts.forEach((layout) => {
                    const isActive = layout === activeLayout;
                    layout.hidden = !isActive;
                    layout.dataset.active = isActive ? 'true' : 'false';
                    const fieldset = layout.querySelector('[data-layout-fields]');
                    if (fieldset) {
                        fieldset.disabled = !isActive;
                    }
                });
            };
            const updateButtons = (group, selectedDecision) => {
                group.querySelectorAll('[data-choice]').forEach((button) => {
                    const selected = button.dataset.choice === selectedDecision;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
            };
            const relatedCommentContainer = (item) => {
                if (!item) {
                    return null;
                }

                if (!item.matches('tr')) {
                    return item;
                }

                return item.closest('[data-decision-layout]')
                    ?.querySelector(`[data-comment-row="${item.dataset.decisionItem}"]`);
            };
            const copyItemState = (sourceItem, targetItem) => {
                const sourceDecision = sourceItem.querySelector('[data-item-decision]');
                const targetDecision = targetItem.querySelector('[data-item-decision]');
                const sourceCommentContainer = relatedCommentContainer(sourceItem);
                const targetCommentContainer = relatedCommentContainer(targetItem);
                const sourceComment = sourceCommentContainer?.querySelector('textarea');
                const targetComment = targetCommentContainer?.querySelector('textarea');
                const sourceCommentField = sourceCommentContainer?.querySelector('[data-comment-field]');
                const targetCommentField = targetCommentContainer?.querySelector('[data-comment-field]');
                const sourceCommentDisplay = sourceCommentContainer?.querySelector('[data-comment-display]');
                const targetCommentDisplay = targetCommentContainer?.querySelector('[data-comment-display]');
                const sourceCommentText = sourceCommentContainer?.querySelector('[data-comment-text]');
                const targetCommentText = targetCommentContainer?.querySelector('[data-comment-text]');
                const sourcePendingStatus = sourceItem.querySelector('[data-pending-status]');
                const targetPendingStatus = targetItem.querySelector('[data-pending-status]');
                const sourceButtons = sourceItem.querySelector('.item-decision-buttons');
                const targetButtons = targetItem.querySelector('.item-decision-buttons');

                if (sourceDecision && targetDecision) {
                    targetDecision.value = sourceDecision.value;
                }
                if (sourceComment && targetComment) {
                    targetComment.value = sourceComment.value;
                    targetComment.required = sourceComment.required;
                }
                if (sourceCommentField && targetCommentField) {
                    targetCommentField.hidden = sourceCommentField.hidden;
                }
                if (sourceCommentDisplay && targetCommentDisplay) {
                    targetCommentDisplay.hidden = sourceCommentDisplay.hidden;
                }
                if (sourceCommentText && targetCommentText) {
                    targetCommentText.textContent = sourceCommentText.textContent;
                }
                if (sourcePendingStatus && targetPendingStatus) {
                    targetPendingStatus.textContent = sourcePendingStatus.textContent;
                    targetPendingStatus.hidden = sourcePendingStatus.hidden;
                }
                if (sourceButtons && targetButtons && sourceDecision) {
                    updateButtons(targetButtons, sourceButtons.querySelector('.is-selected')?.dataset.choice || sourceDecision.value);
                }
                if (targetCommentContainer?.matches('[data-comment-row]')) {
                    targetCommentContainer.hidden = Boolean(sourceCommentField?.hidden && sourceCommentDisplay?.hidden);
                }
            };
            const switchToTable = (sourceItem) => {
                const tableLayout = layouts.find((layout) => layout.dataset.decisionLayout === 'table');
                const cardsLayout = layouts.find((layout) => layout.dataset.decisionLayout === 'cards');
                if (!tableLayout || !cardsLayout || sourceItem.closest('[data-decision-layout]') !== cardsLayout) {
                    return;
                }

                const targetItem = tableLayout.querySelector(`[data-decision-item="${sourceItem.dataset.decisionItem}"]`);
                if (targetItem) {
                    copyItemState(sourceItem, targetItem);
                }
                setActiveLayout(tableLayout);
            };

            const initiallyActive = layouts.find((layout) => layout.dataset.active === 'true');
            if (initiallyActive) {
                setActiveLayout(initiallyActive);
            }

            form.querySelectorAll('.item-decision-buttons').forEach((group) => {
                const item = group.closest('[data-decision-item]');
                const commentContainer = relatedCommentContainer(item);
                const decisionInput = item?.querySelector('[data-item-decision]');
                const commentField = commentContainer?.querySelector('[data-comment-field]');
                const commentInput = commentField?.querySelector('textarea');
                const commentDisplay = commentContainer?.querySelector('[data-comment-display]');
                const commentText = commentContainer?.querySelector('[data-comment-text]');
                const pendingStatus = item?.querySelector('[data-pending-status]');
                const confirmReject = commentContainer?.querySelector('[data-confirm-reject]');
                if (!item || !decisionInput || !commentField || !commentInput || !pendingStatus || !confirmReject) {
                    return;
                }

                group.querySelectorAll('[data-choice]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const decision = button.dataset.choice;
                        updateButtons(group, decision);

                        if (decision === 'declined') {
                            decisionInput.value = 'declined';
                            pendingStatus.hidden = true;
                            if (commentDisplay) {
                                commentDisplay.hidden = true;
                            }
                            commentField.hidden = false;
                            if (commentContainer?.matches('[data-comment-row]')) {
                                commentContainer.hidden = false;
                            }
                            commentInput.required = false;
                            switchToTable(item);
                            const activeItem = form.querySelector(`[data-decision-layout][data-active="true"] [data-decision-item="${item.dataset.decisionItem}"]`);
                            relatedCommentContainer(activeItem)?.querySelector('textarea')?.focus();
                            return;
                        }

                        decisionInput.value = 'accepted';
                        commentInput.value = '';
                        commentInput.required = false;
                        commentField.hidden = true;
                        if (commentDisplay) {
                            commentDisplay.hidden = true;
                        }
                        if (commentContainer?.matches('[data-comment-row]')) {
                            commentContainer.hidden = true;
                        }
                        pendingStatus.textContent = 'Pending submission: Approved';
                        pendingStatus.hidden = false;
                        switchToTable(item);
                        const activeItem = form.querySelector(`[data-decision-layout][data-active="true"] [data-decision-item="${item.dataset.decisionItem}"]`);
                        if (activeItem) {
                            updateButtons(activeItem.querySelector('.item-decision-buttons'), decision);
                            activeItem.querySelector('[data-pending-status]')?.replaceChildren('Pending submission: Approved');
                            activeItem.querySelector('[data-pending-status]')?.removeAttribute('hidden');
                        }
                    });
                });

                confirmReject.addEventListener('click', () => {
                    const comment = commentInput.value.trim();

                    decisionInput.value = 'declined';
                    commentInput.value = comment;
                    commentInput.required = false;
                    commentField.hidden = true;
                    if (commentDisplay) {
                        commentDisplay.hidden = false;
                    }
                    if (commentContainer?.matches('[data-comment-row]')) {
                        commentContainer.hidden = false;
                    }
                    if (commentText) {
                        commentText.textContent = comment;
                    }
                    pendingStatus.textContent = 'Pending submission: Declined';
                    pendingStatus.hidden = false;
                    switchToTable(item);
                    const activeItem = form.querySelector(`[data-decision-layout][data-active="true"] [data-decision-item="${item.dataset.decisionItem}"]`);
                    if (activeItem) {
                        updateButtons(activeItem.querySelector('.item-decision-buttons'), 'declined');
                        const activeCommentContainer = relatedCommentContainer(activeItem);
                        activeCommentContainer?.querySelector('[data-comment-text]')?.replaceChildren(document.createTextNode(comment));
                        activeCommentContainer?.querySelector('[data-comment-display]')?.removeAttribute('hidden');
                        activeCommentContainer?.querySelector('[data-comment-field]')?.setAttribute('hidden', '');
                        if (activeCommentContainer?.matches('[data-comment-row]')) {
                            activeCommentContainer.hidden = false;
                        }
                        activeItem.querySelector('[data-pending-status]')?.replaceChildren('Pending submission: Declined');
                        activeItem.querySelector('[data-pending-status]')?.removeAttribute('hidden');
                    }
                });
            });

            form.addEventListener('submit', (event) => {
                const activeLayout = form.querySelector('[data-decision-layout][data-active="true"]');

                activeLayout?.querySelectorAll('[data-decision-item]').forEach((item) => {
                    const decisionInput = item.querySelector('[data-item-decision]');
                    const selectedDecision = item.querySelector('.item-decision-buttons [data-choice].is-selected')?.dataset.choice;
                    if (!decisionInput || !selectedDecision) {
                        return;
                    }

                    if (selectedDecision === 'accepted') {
                        decisionInput.value = 'accepted';
                        const comment = relatedCommentContainer(item)?.querySelector('textarea');
                        if (comment) {
                            comment.value = '';
                        }
                        return;
                    }

                    if (selectedDecision === 'declined' && decisionInput.value === 'declined') {
                        const comment = relatedCommentContainer(item)?.querySelector('textarea');
                        if (comment) {
                            comment.value = comment.value.trim();
                        }
                    }
                });
            });
        });
    </script>
    @include('deals.proposal.partials.pagination-script')
</body>
</html>
