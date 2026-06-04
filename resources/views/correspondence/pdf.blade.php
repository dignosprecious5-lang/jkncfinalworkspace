<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $correspondence->ref_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm;
        }

        body {
            margin: 0;
            padding: 0;
            color: #000;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 12pt;
            line-height: 1.45;
        }

        .center { text-align: center; }

        .header {
            display: table;
            width: 100%;
            margin-top: 2mm;
            margin-bottom: 38px;
        }

        .header-logo {
            display: table-cell;
            width: 38%;
            text-align: center;
            vertical-align: middle;
        }

        .header-logo img {
            max-width: 180px;
            max-height: 90px;
        }

        .header-company {
            display: table-cell;
            width: 62%;
            text-align: left;
            vertical-align: middle;
            font-size: 10.5pt;
            line-height: 1.25;
        }

        .title {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .14em;
            font-size: 14pt;
            margin-bottom: 24px;
        }

        .company-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12pt;
        }

        .company-block {
            text-align: center;
            margin-bottom: 34px;
            line-height: 1.25;
        }

        .field {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .label {
            display: table-cell;
            width: 95px;
            font-weight: bold;
        }

        .line {
            display: table-cell;
            min-height: 18px;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .field-divider {
            border-top: 1px solid #6b7280;
            margin: 10px 0 24px 0;
        }

        .body-content {
            margin-top: 30px;
            font-size: 12pt;
            line-height: 1.55;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .body-content table,
        .correspondence-pdf-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 12px 0 !important;
        }

        .body-content td,
        .body-content th,
        .correspondence-pdf-table td,
        .correspondence-pdf-table th {
            border: 1px solid #888 !important;
            padding: 7px !important;
            vertical-align: top !important;
            word-break: normal !important;
            overflow-wrap: break-word !important;
        }

        .ql-indent-1 { margin-left: 3em !important; }
        .ql-indent-2 { margin-left: 6em !important; }
        .ql-indent-3 { margin-left: 9em !important; }
        .ql-indent-4 { margin-left: 12em !important; }
        .ql-indent-5 { margin-left: 15em !important; }
        .ql-indent-6 { margin-left: 18em !important; }
        .ql-indent-7 { margin-left: 21em !important; }
        .ql-indent-8 { margin-left: 24em !important; }

        .ql-align-center { text-align: center !important; }
        .ql-align-right { text-align: right !important; }
        .ql-align-justify { text-align: justify !important; }

        /* Signature footer: consistent preview/PDF formatting */
        .signature-block {
            margin-top: 46px !important;
            font-size: 11pt !important;
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
    <div class="header">
        <div class="header-logo">
            @if(!empty($correspondenceLogoSrc))
                <img src="{{ $correspondenceLogoSrc }}" alt="Company Logo">
            @else
                <img src="{{ public_path('images/jk-logo.png') }}" alt="Company Logo">
            @endif
        </div>

        <div class="header-company">
            <div class="company-name">{{ $correspondence->company_name }}</div>
            <div>Registration No.: {{ $correspondence->registration_number ?: '____________________' }}</div>
            <div>{{ $correspondence->principal_address }}</div>
        </div>
    </div>

    <div class="center title">{{ $correspondence->type }}</div>

    <div class="field">
        <div class="label">Date:</div>
        <div class="line">{{ optional($correspondence->correspondence_date)->format('F d, Y') }}</div>
    </div>

    <div class="field">
        <div class="label">{{ $correspondence->to_for_label ?: 'To' }}:</div>
        <div class="line">{{ $correspondence->to_for }}</div>
    </div>

    <div class="field">
        <div class="label">From:</div>
        <div class="line">{{ $correspondence->from_name }}</div>
    </div>

    <div class="field">
        <div class="label">SUBJECT:</div>
        <div class="line"><strong>{{ $correspondence->subject }}</strong></div>
    </div>

    <div class="field-divider"></div>

    <div class="body-content">
        {!! $correspondence->body ?: '<p>No body provided.</p>' !!}
    </div>

    <div class="signature-block">
        <div class="signature-section">
            <p class="signature-heading">Prepared By:</p>
                <p class="signature-line">{{ $correspondence->from_name ?: ($correspondence->creator?->name ?? 'System Super Admin') }}</p>
                <p class="signature-line">Position</p>
                <p class="signature-line">—</p>
                <p class="signature-line">Prepared on: Date and Time</p>
            </div>

        <div class="signature-section">
            <p class="signature-heading">From Management</p>
                <p class="signature-line">{{ $correspondence->management_approver_name ?: 'Name' }}</p>
                <p class="signature-line">{{ $correspondence->management_approver_position ?: 'Position' }}</p>
                <p class="signature-line">{{ $correspondence->management_approver_department ?: 'Department' }}</p>
                <p class="signature-line">Approved on: Date and Time</p>
            </div>

        <div class="signature-section">
            <p class="signature-heading">From Executive Management</p>
                <p class="signature-line">{{ $correspondence->executive_approver_name ?: 'Name' }}</p>
                <p class="signature-line">{{ $correspondence->executive_approver_position ?: 'Position' }}</p>
                <p class="signature-line">Executive Management</p>
                <p class="signature-line">Approved on: Date and Time</p>
            </div>

            <p class="computer-generated-note">
                This is a computer-generated document. Signature is not required.
            </p>
    </div>
</body>
</html>
