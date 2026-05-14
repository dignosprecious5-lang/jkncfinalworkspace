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
        .button { display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 8px; padding: 10px 14px; font-size: 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button-blue { background: #2563eb; color: #fff; }
        .button-green { background: #059669; color: #fff; }
        .button-muted { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; }
        .shell { max-width: 1220px; margin: 22px auto; padding: 0 16px 32px; }
        .preview-shell { border: 1px solid #e5e7eb; background: #f8fafc; border-radius: 16px; overflow: hidden; }
        .preview-shell-header { border-bottom: 1px solid #f1f5f9; background: #fff; padding: 16px 20px; }
        .preview-shell-title { margin: 0; font-size: 14px; font-weight: 700; color: #111827; }
        .preview-shell-subtitle { margin: 4px 0 0; color: #6b7280; font-size: 12px; }
        .preview-pane { padding: 20px; }
        .notice { margin-bottom: 14px; border: 1px solid #bbf7d0; background: #ecfdf5; color: #047857; padding: 12px 14px; border-radius: 10px; font-size: 14px; font-weight: 700; }
        .approval { margin-bottom: 16px; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; align-items: end; padding: 14px; border: 1px solid #dbe2ea; background: #fff; border-radius: 10px; }
        .approval label { display: block; margin-bottom: 6px; font-size: 12px; color: #475569; font-weight: 700; }
        .approval input, .approval textarea { width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; font-size: 14px; }
        .approval textarea { min-height: 42px; resize: vertical; }
        .approval-only { display: flex; justify-content: flex-end; }
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
            <p>{{ $proposal->status === 'approved' ? 'Approved proposal' : 'Proposal review and approval' }}</p>
        </div>
        <div class="spacer"></div>
        <a href="{{ $downloadUrl }}" class="button button-blue">Download PDF</a>
    </div>

    <main class="shell">
        @if (session('success'))
            <div class="notice">{{ session('success') }}</div>
        @endif

        @if ($proposal->status === 'approved')
            <div class="notice">
                Approved by {{ $approvalName }} on {{ optional($approvalDate)->format('F j, Y g:i A') }}.
            </div>
        @else
            <form method="POST" action="{{ $approveUrl }}" class="approval">
                @csrf
                <div class="approval-only">
                    <button type="submit" class="button button-green">Approve Proposal</button>
                </div>
            </form>
        @endif

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
    </main>
    @if ($approvalDate)
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
    @endif
    @include('deals.proposal.partials.pagination-script')
</body>
</html>
