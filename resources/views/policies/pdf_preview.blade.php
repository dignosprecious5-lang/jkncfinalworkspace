<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $data['policy'] ?: 'Policy Preview' }}</title>

    @php
        $logoSrc = $data['logo_src'] ?? null;

        $logoCandidates = [
            public_path('images/jk-logo.png'),
            public_path('images/jk-logo.jpg'),
            public_path('images/jk-logo.jpeg'),
            public_path('images/logo.png'),
            public_path('images/logo.jpg'),
            public_path('images/john-kelly-logo.png'),
            storage_path('app/public/images/jk-logo.png'),
            storage_path('app/public/images/logo.png'),
        ];

        if (!$logoSrc) {
            foreach ($logoCandidates as $logoPath) {
            if ($logoPath && file_exists($logoPath)) {
                $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };

                $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                break;
            }
        }
        }

        $pdfSafeText = function ($value, int $chunk = 34) {
            $value = trim((string) ($value ?? ''));

            if ($value === '') {
                return '';
            }

            $words = preg_split('/(\s+)/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE);

            $wrapped = collect($words)->map(function ($word) use ($chunk) {
                if (preg_match('/^\s+$/u', $word)) {
                    return ' ';
                }

                if (mb_strlen($word) <= $chunk) {
                    return $word;
                }

                return trim(chunk_split($word, $chunk, ' '));
            })->implode('');

            return e($wrapped);
        };
        $pdfPreparedDescription = function ($html) {
            $html = (string) ($html ?? '');

            if (trim($html) === '') {
                return '<p style="color:#777;">No description provided.</p>';
            }

            return $html;
        };





    @endphp

    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 12pt;
            line-height: 1.45;
        }

        .cover-page {
            padding: 72px;
            text-align: center;
            page-break-after: always;
            overflow: hidden;
        }

        .cover-logo {
            margin-top: 16px;
            width: 100%;
            text-align: center;
        }

        .cover-logo img {
            display: block;
            width: 260px;
            max-height: 120px;
            object-fit: contain;
            margin-left: auto;
            margin-right: auto;
        }

        .company-block {
            margin-top: 10px;
            font-size: 11pt;
            line-height: 1.2;
            text-align: center;
        }

        .company-name {
            font-weight: bold;
        }

        .policy-title-block {
            margin-top: 145px;
            line-height: 1.25;
            text-align: center;
            width: 100%;
            max-width: 100%;
        }

        .policy-title {
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12pt;
            text-align: center;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .policy-subtitle {
            margin: 2px 0 0;
            font-size: 10pt;
            text-align: center;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .details-wrap {
            width: 485px;
            max-width: 485px;
            margin: 150px auto 0 auto;
            text-align: left;
            font-size: 11pt;
            overflow: hidden;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .details-table td {
            padding: 5px 8px;
            vertical-align: top;
            border: none;
            overflow-wrap: break-word;
            word-break: normal;
            white-space: normal;
        }

        .details-table td:first-child {
            width: 150px;
            min-width: 150px;
            max-width: 150px;
            font-weight: normal;
        }

        .details-table td:last-child {
            width: auto;
            max-width: 335px;
            font-weight: bold;
            overflow: hidden;
        }

        .content-page {
            padding: 72px;
            page-break-before: auto;
            overflow: visible;
        }

        .document-title {
            margin: 0 0 24px 0;
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.25;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .description-content {
            width: 100%;
            max-width: 100%;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 12pt;
            line-height: 1.45;
            white-space: normal;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .description-content p {
            margin: 0 0 10px 0;
        }

        .description-content div,
        .description-content span,
        .description-content li {
            white-space: normal;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .description-content ul,
        .description-content ol {
            margin: 0 0 10px 22px;
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
            margin: 14px 0 8px 0;
            line-height: 1.25;
            font-weight: bold;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .description-content h1 { font-size: 16pt; }
        .description-content h2 { font-size: 14pt; }
        .description-content h3 { font-size: 12pt; }

        .description-content img {
            max-width: 100% !important;
            height: auto !important;
        }

        .description-content .ql-align-left { text-align: left !important; }
        .description-content .ql-align-center { text-align: center !important; }
        .description-content .ql-align-right { text-align: right !important; }
        .description-content .ql-align-justify {
            text-align: justify !important;
            text-justify: inter-word;
        }

        /* Quill paragraph indentation for PDF */
        .description-content .ql-indent-1 { margin-left: 3em !important; padding-left: 0 !important; text-indent: 0 !important; }
        .description-content .ql-indent-2 { margin-left: 6em !important; padding-left: 0 !important; text-indent: 0 !important; }
        .description-content .ql-indent-3 { margin-left: 9em !important; padding-left: 0 !important; text-indent: 0 !important; }
        .description-content .ql-indent-4 { margin-left: 12em !important; padding-left: 0 !important; text-indent: 0 !important; }
        .description-content .ql-indent-5 { margin-left: 15em !important; padding-left: 0 !important; text-indent: 0 !important; }
        .description-content .ql-indent-6 { margin-left: 18em !important; padding-left: 0 !important; text-indent: 0 !important; }
        .description-content .ql-indent-7 { margin-left: 21em !important; padding-left: 0 !important; text-indent: 0 !important; }
        .description-content .ql-indent-8 { margin-left: 24em !important; padding-left: 0 !important; text-indent: 0 !important; }

        .description-content table,
        .policy-pdf-table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            margin: 12px 0 !important;
            page-break-inside: auto !important;
        }

        .description-content tr,
        .policy-pdf-table tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }


        .description-content th,
        .description-content td,
        .policy-pdf-table th,
        .policy-pdf-table td {
            min-width: 0 !important;
            border: 1px solid #94a3b8 !important;
            padding: 10px 12px !important;
            vertical-align: top !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
            text-align: left !important;
        }

        .description-content th,
        .policy-pdf-table th {
            font-weight: bold !important;
            background: #f3f3f3 !important;
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
            word-break: normal !important;
            overflow-wrap: break-word !important;
        }

        .ql-font-georgia,
        .ql-font-georgia * {
            font-family: Georgia, "Times New Roman", serif !important;
        }

        /* PDF BODY NO FORCED LETTER BREAKS */

        .description-content,
        .description-content p,
        .description-content div,
        .description-content span,
        .description-content li {
            white-space: normal !important;
            word-break: normal !important;
            overflow-wrap: break-word !important;
        }

    </style>
</head>
<body>
<section class="cover-page">
        <div class="cover-logo">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="John Kelly & Company Logo">
            @else
                <div style="font-weight:bold; font-size:18pt;">John Kelly<br>&amp; Company</div>
            @endif
        </div>

        <div class="company-block">
            <div class="company-name">JOHN KELLY &amp; COMPANY (JK&amp;C INC)</div>
            <div>3F Cebu Holdings Center Cebu Business Park, Cebu City, Philippines, 6000</div>
        </div>

        <div class="policy-title-block">
            <div class="policy-title">{!! $pdfSafeText($data['policy'] ?: 'POLICY TITLE', 32) !!}</div>
            <div class="policy-subtitle">{!! $pdfSafeText($data['policy_subtitle'] ?: 'Policy Document', 42) !!}</div>
        </div>

        <div class="details-wrap">
            <table class="details-table">
                <tr>
                    <td>Code</td>
                    <td>{!! $pdfSafeText($data['code'] ?: 'AUTO-GENERATED', 30) !!}</td>
                </tr>
                <tr>
                    <td>Version</td>
                    <td>{!! $pdfSafeText($data['version'] ?: '1.0', 30) !!}</td>
                </tr>
                <tr>
                    <td>Effectivity Date</td>
                    <td>{!! $pdfSafeText($data['effectivity_date'] ?: '______________________________', 30) !!}</td>
                </tr>
                <tr>
                    <td>Prepared by</td>
                    <td>{!! $pdfSafeText($data['prepared_by'] ?: '______________________________', 30) !!}</td>
                </tr>
                <tr>
                    <td>Reviewed by</td>
                    <td>{!! $pdfSafeText($data['reviewed_by'] ?: '______________________________', 30) !!}</td>
                </tr>
                <tr>
                    <td>Approved by</td>
                    <td>{!! $pdfSafeText($data['approved_by'] ?: '______________________________', 30) !!}</td>
                </tr>
                <tr>
                    <td>Review Cycle</td>
                    <td>{!! $pdfSafeText($data['review_cycle'] ?: '______________________________', 30) !!}</td>
                </tr>
                <tr>
                    <td>Classification</td>
                    <td>{!! $pdfSafeText($data['classification'] ?: 'Internal Use Only', 30) !!}</td>
                </tr>
            </table>
        </div>
    </section>

    <section class="content-page">
        <h1 class="document-title">{!! $pdfSafeText($data['policy'] ?: 'POLICY TITLE', 32) !!}</h1>

        <div class="description-content">
            {!! $pdfPreparedDescription($data['description'] ?? '') !!}
        </div>
    </section>
</body>
</html>
