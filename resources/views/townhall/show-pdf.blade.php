<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Memorandum</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 12mm 28mm 12mm;
        }

        body {
            margin: 0;
            font-family: "Times New Roman", DejaVu Serif, serif;
            font-size: 13px;
            line-height: 1.55;
            color: #222;
        }

        .page {
            width: 100%;
        }

        .content-inset {
            margin-left: 10mm;
            margin-right: 10mm;
        }

        .header {
            margin-bottom: 8mm;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
    width: 42mm;
    vertical-align: top;
}

.logo-cell {
    width: 42mm;
    vertical-align: top;
    padding-left: 5.5mm;
}

.logo {
    width: 36mm;
    height: auto;
    display: block;
    margin-top: 1mm;
}

.partners {
    font-size: 11px;
    line-height: 1.35;
    color: #444;
    padding-top: 0;
}

        .title {
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            color: #555;
            letter-spacing: 1px;
            margin: 6mm 0 8mm 0;
        }

        .meta {
            margin-bottom: 4mm;
            font-size: 13px;
        }

        .meta p {
            margin: 1.2mm 0;
        }

        .divider {
            border-bottom: 1px solid #666;
            margin-top: 4mm;
            margin-bottom: 7mm;
        }

        .body-content {
            font-size: 13px;
            line-height: 1.3;
            text-align: justify;
            padding-bottom: 6mm;
        }

        .body-content,
        .body-content p,
        .body-content div,
        .body-content li,
        .body-content span,
        .body-content td,
        .body-content th {
            font-family: "Times New Roman", DejaVu Serif, serif !important;
        }

        .body-content p,
        .body-content li {
            text-align: justify;
        }

        .body-content p {
            margin: 0 0 3mm 0;
        }

        .body-content ul,
        .body-content ol {
            margin: 0 0 4mm 7mm;
        }

        .body-content table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 3mm 0 4mm 0;
        }

        .body-content th,
        .body-content td {
            border: 1px solid #888;
            padding: 2.2mm 2.8mm;
            vertical-align: top;
            word-wrap: break-word;
        }

        .closing-section {
            margin-top: 8mm;
            page-break-inside: avoid;
        }

        .issued {
            margin: 0 0 6mm 0;
        }

        .prepared-by {
            margin: 0 0 5mm 0;
        }

        .approved-by {
            margin: 8mm 0 5mm 0;
        }

        .signature-block {
            width: 70mm;
            page-break-inside: avoid;
        }

        .signature-line {
            border-bottom: 1px solid #555;
            height: 8mm;
            margin-bottom: 1mm;
        }

        .footer-fixed {
            position: fixed;
            bottom: -16mm;
            left: 0;
            right: 0;
            font-size: 10px;
            line-height: 1.25;
            color: #333;
            box-sizing: border-box;
        }

        .footer-inner {
            margin-left: 10mm;
            margin-right: 10mm;
        }

        .footer-note {
            margin: 0 0 3mm 0;
            text-align: justify;
            line-height: 1.4;
        }

        .footer-address {
            margin: 0;
            text-align: left;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <div class="footer-fixed">
        <div class="footer-inner">
            <div class="footer-note">
                This Memorandum is an official corporate record of JK&amp;C INC. Unauthorized reproduction,
                alteration, disclosure, or misuse of this Memorandum, in whole or in part, is strictly prohibited
                and may result in administrative sanctions, termination of employment or engagement, and/or the
                institution of appropriate civil, criminal, or regulatory actions, in accordance with applicable laws
                and company policies.
            </div>

            <div class="footer-address">
                JK&amp;C INC.<br>
                3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000
            </div>
        </div>
    </div>

    <div class="page">
        <div class="header">
            <table class="header-table">
    <tr>
        <td class="logo-cell">
            <img src="{{ public_path('images/jk-logo.png') }}" alt="JK Logo" class="logo">
        </td>
        <td class="partners">
            Atty. Jose B. Ogang, CPA, MMPSM · Jose Tamayo Rio,<br>
            MM-BM, CPA · Lyndon Earl P. Rio, RN, CB · John Kelly Abalde,<br>
            CLSSBB, CPM
        </td>
    </tr>
</table>
        </div>

        <div class="title">MEMORANDUM</div>

        <div class="meta content-inset">
            <p><strong>Memo NO.:</strong> {{ $communication->ref_no }}</p>
            <p><strong>Date:</strong>
                {{ $communication->communication_date ? \Carbon\Carbon::parse($communication->communication_date)->format('F d, Y') : '—' }}
            </p>
            <p><strong>{{ $communication->recipient_label ?? 'To' }}:</strong> {{ $communication->to_for ?: '—' }}</p>
            <p><strong>From:</strong> {{ $communication->from_name ?: '—' }}</p>
            <p><strong>SUBJECT:</strong> {{ $communication->subject ?: '—' }}</p>
        </div>

        <div class="divider content-inset"></div>

        <div class="body-content content-inset">
            {!! $communication->message ?: '<p>No memorandum body provided.</p>' !!}
        </div>

        <div class="closing-section content-inset">
            <div class="issued">
                Issued this
                <strong>
                    {{ $communication->communication_date ? \Carbon\Carbon::parse($communication->communication_date)->format('jS \\d\\a\\y \\o\\f F, Y') : '______________' }}
                </strong>
                in Cebu City, Philippines.
            </div>

            <div class="prepared-by">Prepared by:</div>

            <div class="signature-block">
                <div><strong>{{ $communication->from_name ?: '—' }}</strong></div>
            </div>

            <div class="approved-by">Approved by:</div>

            <div class="signature-block">
                <div><strong>John Kelly D. Abalde</strong></div>
                <div>President and CEO</div>
            </div>
        </div>
    </div>

</body>
</html>
