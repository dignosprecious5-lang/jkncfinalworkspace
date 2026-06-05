<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $correspondence->ref_no }}</title>
    <style>
        body {
            margin: 0;
            padding: 32px;
            background: #f3f4f6;
            font-family: Georgia, "Times New Roman", serif;
            color: #000;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 18mm;
            box-sizing: border-box;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
        }

        .center { text-align: center; }

        .header {
            display: grid;
            grid-template-columns: 38% 62%;
            align-items: center;
            gap: 18px;
            margin-top: 6px;
            margin-bottom: 38px;
        }

        .header-logo {
            text-align: center;
        }

        .header-logo img {
            max-width: 190px;
            max-height: 95px;
            object-fit: contain;
        }

        .header-company {
            text-align: left;
            font-size: 13px;
            line-height: 1.35;
        }

        .title {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .14em;
            font-size: 18px;
            margin-bottom: 28px;
        }

        .company-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 15px;
        }

        .field {
            display: grid;
            grid-template-columns: 105px 1fr;
            gap: 10px;
            margin-bottom: 3px;
            font-size: 14px;
            line-height: 1.3;
        }

        .line {
            min-height: 18px;
            word-break: break-word;
        }

        .field-divider {
            border-top: 1px solid #6b7280;
            margin: 10px 0 24px 0;
        }

        .body-content {
            margin-top: 30px;
            font-size: 15px;
            line-height: 1.65;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .body-content table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .body-content td,
        .body-content th {
            border: 1px solid #888;
            padding: 8px;
            vertical-align: top;
            overflow-wrap: break-word;
        }

        .ql-indent-1 { margin-left: 3em; }
        .ql-indent-2 { margin-left: 6em; }
        .ql-indent-3 { margin-left: 9em; }
        .ql-align-center { text-align: center; }
        .ql-align-right { text-align: right; }
        .ql-align-justify { text-align: justify; }

        /* Signature footer: consistent preview/PDF formatting */
        .signature-block {
            margin-top: 46px !important;
            font-size: 14px !important;
            line-height: 1.28 !important;
            color: #000 !important;
            font-family: Georgia, "Times New Roman", serif !important;
        }

        .signature-section {
            display: block !important;
            margin: 0 0 20px 0 !important;
            padding: 0 !important;
        }

        .signature-heading {
            display: block !important;
            margin: 0 0 8px 0 !important;
            padding: 0 !important;
            font-weight: 700 !important;
            line-height: 1.28 !important;
        }

        .signature-line {
            display: block !important;
            margin: 0 0 2px 0 !important;
            padding: 0 !important;
            font-weight: 400 !important;
            line-height: 1.28 !important;
        }

        .computer-generated-note {
            display: block !important;
            margin: 24px 0 0 0 !important;
            padding: 0 !important;
            font-weight: 700 !important;
            line-height: 1.28 !important;
        }
</style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="header-logo">
                <img src="{{ $correspondenceLogoUrl ?? asset('images/jk-logo.png') }}" alt="Company Logo">
            </div>

            <div class="header-company">
                <div class="company-name">{{ $correspondence->company_name }}</div>
                <div>Registration No.: {{ $correspondence->registration_number ?: '____________________' }}</div>
                <div>{{ $correspondence->principal_address }}</div>
            </div>
        </div>

        <div class="center title">{{ $correspondence->type }}</div>

        <div class="field">
            <strong>Date:</strong>
            <div class="line">{{ optional($correspondence->correspondence_date)->format('F d, Y') }}</div>
        </div>

        <div class="field">
            <strong>{{ $correspondence->to_for_label ?: 'To' }}:</strong>
            <div class="line">{{ $correspondence->to_for }}</div>
        </div>

        <div class="field">
            <strong>From:</strong>
            <div class="line">{{ $correspondence->from_name }}</div>
        </div>

        <div class="field">
            <strong>SUBJECT:</strong>
            <div class="line"><strong>{{ $correspondence->subject }}</strong></div>
        </div>

        <div class="field-divider"></div>

        <div class="body-content">
            {!! $correspondence->body ?: '<p>No body provided.</p>' !!}
        </div>

        <div class="signature-block">
            <div class="signature-section">
            <p class="signature-heading">Prepared By:</p>
                <p class="signature-line">{{ $correspondence->prepared_by_name ?: ($correspondence->from_name ?: ($correspondence->creator?->name ?? 'System Super Admin')) }}</p>
                <p class="signature-line">{{ $correspondence->prepared_by_position ?: 'Position' }}</p>
                <p class="signature-line">{{ $correspondence->prepared_by_department ?: '—' }}</p>
                <p class="signature-line">Prepared on: {{ optional($correspondence->prepared_on ?: $correspondence->created_at)->format('F d, Y h:i A') ?: 'Date and Time' }}</p>
            </div>

            <div class="signature-section">
            <p class="signature-heading">From Management</p>
                <p class="signature-line">{{ $correspondence->management_signature_name ?: ($correspondence->management_approver_name ?: 'Name') }}</p>
                <p class="signature-line">{{ $correspondence->management_signature_position ?: ($correspondence->management_approver_position ?: 'Position') }}</p>
                <p class="signature-line">{{ $correspondence->management_signature_department ?: ($correspondence->management_approver_department ?: 'Department') }}</p>
                <p class="signature-line">Approved on: {{ optional($correspondence->management_approved_on ?: $correspondence->management_approved_at)->format('F d, Y h:i A') ?: 'Date and Time' }}</p>
            </div>

            <div class="signature-section">
            <p class="signature-heading">From Executive Management</p>
                <p class="signature-line">{{ $correspondence->executive_signature_name ?: ($correspondence->executive_approver_name ?: 'Name') }}</p>
                <p class="signature-line">{{ $correspondence->executive_signature_position ?: ($correspondence->executive_approver_position ?: 'Position') }}</p>
                <p class="signature-line">{{ $correspondence->executive_signature_department ?: ($correspondence->executive_approver_department ?: 'Executive Management') }}</p>
                <p class="signature-line">Approved on: {{ optional($correspondence->executive_approved_on ?: $correspondence->executive_approved_at)->format('F d, Y h:i A') ?: 'Date and Time' }}</p>
            </div>

            <p class="computer-generated-note">
                This is a computer-generated document. Signature is not required.
            </p>
        </div>
    </div>
</body>
</html>
