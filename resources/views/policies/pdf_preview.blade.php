<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Policy Preview</title>
    <style>
        @page {
            margin: 0.5in;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Georgia, serif;
            color: #111827;
            margin: 0;
            padding: 20px;
            line-height: 1.5;
            background: #ffffff;
            font-size: 14px;
        }

        .policy-memo-header {
            width: 100%;
            margin-bottom: 34px;
            text-align: center;
        }

        .policy-memo-logo {
            display: block;
            width: 100%;
            text-align: center;
            padding-top: 4px;
        }

        .policy-memo-logo img {
            height: 90px;
            width: auto;
        }
.policy-memo-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .policy-memo-title h2 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.12em;
            color: #4b5563;
            font-family: "Times New Roman", Georgia, serif;
            text-transform: uppercase;
            margin: 0;
        }

        .policy-memo-meta {
            font-size: 14px;
            line-height: 1.45;
            color: #111827;
            font-family: "Times New Roman", Georgia, serif;
            margin-bottom: 10px;
        }

        .policy-memo-meta p {
            margin: 2px 0;
        }

        .policy-memo-meta strong {
            font-weight: 700;
        }

        .policy-memo-divider {
            border-bottom: 1px solid #6b7280;
            margin: 12px 0 26px 0;
        }

        .description-content {
            margin-top: 20px;
            width: 100%;
            font-size: 14px;
        }

        .description-content p {
            margin: 0 0 8px 0;
        }

        .description-content ul,
        .description-content ol {
            margin: 0 0 10px 20px;
            padding: 0;
        }

        .description-content li {
            margin-bottom: 4px;
        }

        .description-content h1,
        .description-content h2,
        .description-content h3,
        .description-content h4,
        .description-content h5,
        .description-content h6 {
            margin: 12px 0 8px 0;
            line-height: 1.3;
        }

        .description-content strong {
            font-weight: bold;
        }

        .description-content em {
            font-style: italic;
        }

        .description-content u {
            text-decoration: underline;
        }

        .description-content blockquote {
            border-left: 3px solid #cbd5e0;
            padding-left: 10px;
            margin: 10px 0;
            color: #4a5568;
        }

        .description-content hr {
            border: none;
            border-top: 1px solid #cbd5e0;
            margin: 12px 0;
        }

        .description-content img {
            max-width: 100% !important;
            height: auto !important;
        }

        .description-content table {
            width: 100% !important;
            max-width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 12px 0 !important;
            border: 1px solid #94a3b8 !important;
            page-break-inside: auto;
        }

        .description-content tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .description-content th,
        .description-content td {
            border: 1px solid #94a3b8 !important;
            padding: 8px !important;
            vertical-align: top !important;
            text-align: left !important;
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            min-height: 24px !important;
        }

        .description-content th {
            background: #f8fafc !important;
            font-weight: bold !important;
        }

        .description-content colgroup,
        .description-content col {
            display: none !important;
            width: auto !important;
        }

        .description-content td p,
        .description-content th p,
        .description-content td div,
        .description-content th div,
        .description-content td span,
        .description-content th span {
            margin: 0 !important;
            padding: 0 !important;
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }

        .description-content td:empty::before,
        .description-content th:empty::before {
            content: " ";
            white-space: pre;
        }
    </style>
</head>
<body>

    <div class="policy-memo-header">
        <div class="policy-memo-logo">
            <img src="{{ public_path('images/jk-logo.png') }}" alt="John Kelly & Company Logo">
        </div>
    </div>

    <div class="policy-memo-title">
        <h2>{{ $data['policy'] ?: 'POLICY TITLE' }}</h2>
    </div>

    <div class="policy-memo-meta">
        <p><strong>Policy Title:</strong> {{ $data['policy'] ?: '______________________________' }}</p>
        <p><strong>Code:</strong> {{ $data['code'] ?: 'AUTO-GENERATED' }}</p>
        <p><strong>Version:</strong> {{ $data['version'] ?: '1.0' }}</p>
        <p><strong>Effectivity Date:</strong> {{ !empty($data['effectivity_date']) ? \Carbon\Carbon::parse($data['effectivity_date'])->format('F d, Y') : '______________________________' }}</p>
        <p><strong>Prepared by:</strong> {{ $data['prepared_by'] ?: '______________________________' }}</p>
        <p><strong>Reviewed by:</strong> {{ $data['reviewed_by'] ?: '______________________________' }}</p>
        <p><strong>Approved by:</strong> {{ $data['approved_by'] ?: '______________________________' }}</p>
        <p><strong>Review Cycle:</strong> {{ $data['review_cycle'] ?: '______________________________' }}</p>
        <p><strong>Classification:</strong> {{ $data['classification'] ?: 'Internal Use Only' }}</p>
    </div>

    <div class="policy-memo-divider"></div>

    <div class="description-content">
        {!! $data['description'] ?? '<p style="color:#cbd5e0;">No description provided.</p>' !!}
    </div>

</body>
</html>
