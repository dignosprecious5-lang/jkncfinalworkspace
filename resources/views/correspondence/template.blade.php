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
            gap: 12px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .line {
            border-bottom: 1px solid #333;
            min-height: 20px;
            word-break: break-word;
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
    </style>
</head>
<body>
    <div class="page">
        <div class="center title">{{ $correspondence->type }}</div>

        <div class="center" style="margin-bottom: 34px;">
            <div class="company-name">{{ $correspondence->company_name }}</div>
            <div>Registration No.: {{ $correspondence->registration_number ?: '____________________' }}</div>
            <div>{{ $correspondence->principal_address }}</div>
        </div>

        <div class="center title">{{ $correspondence->type }}</div>

        <div class="field">
            <strong>Date:</strong>
            <div class="line">{{ optional($correspondence->correspondence_date)->format('F d, Y') }}</div>
        </div>

        <div class="field">
            <strong>To / For:</strong>
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

        <div class="body-content">
            {!! $correspondence->body ?: '<p>No body provided.</p>' !!}
        </div>

        <div style="margin-top: 54px; display:grid; grid-template-columns: 1fr 1fr; gap:20px; font-size: 13px;">
            <div>
                <strong>From Management</strong><br>
                {{ $correspondence->management_approver_name ?: '—' }}<br>
                {{ $correspondence->management_approver_position ?: '—' }}<br>
                {{ $correspondence->management_approver_department ?: '—' }}
            </div>
            <div>
                <strong>From Executive Management</strong><br>
                {{ $correspondence->executive_approver_name ?: '—' }}<br>
                {{ $correspondence->executive_approver_position ?: '—' }}<br>
                {{ $correspondence->executive_approver_department ?: '—' }}
            </div>
        </div>
    </div>
</body>
</html>
