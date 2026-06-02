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

            /*
             * Add break opportunities only in visible text chunks, not HTML tags.
             * This prevents long words from destroying the PDF width.
             */
            return preg_replace_callback('/>([^<]+)</u', function ($matches) {
                $text = $matches[1];

                $text = preg_replace_callback('/[^\s]{35,}/u', function ($longWord) {
                    return trim(chunk_split($longWord[0], 35, ' '));
                }, $text);

                return '>' . $text . '<';
            }, $html);
        };

    @endphp

    <style>
        @page {
            size: A4 portrait;
            margin: 1in;
        }

        * {
            box-sizing: border-box;
        }

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
            page-break-after: always;
            min-height: 9.35in;
            text-align: center;
        }

        .cover-logo {
            width: 100%;
            text-align: center;
            margin-top: -0.35in;
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
            margin-top: 1.65in;
            text-align: center;
            line-height: 1.25;
        }

        .policy-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12pt;
        }

        .policy-subtitle {
            font-size: 10pt;
        }

        .details-wrap {
            width: 4.85in;
            margin: 1.95in auto 0 auto;
            text-align: left;
            font-size: 11pt;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table td {
            padding: 5px 8px;
            vertical-align: top;
            border: none;
        }

        .details-table td:first-child {
            width: 1.45in;
            font-weight: normal;
        }

        .details-table td:last-child {
            font-weight: bold;
        }

        .document-title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 24px 0;
            font-size: 14pt;
        }

        .description-content {
            width: 100%;
            font-size: 12pt;
            line-height: 1.45;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .description-content * {
            max-width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .description-content p {
            margin: 0 0 10px 0;
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
        }

        .description-content h1 { font-size: 16pt; }
        .description-content h2 { font-size: 14pt; }
        .description-content h3 { font-size: 12pt; }

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
            border: 1px solid #000 !important;
            page-break-inside: auto;
        }

        .description-content tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .description-content th,
        .description-content td {
            border: 1px solid #000 !important;
            padding: 7px !important;
            vertical-align: top !important;
            text-align: left !important;
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }

        .description-content th {
            font-weight: bold !important;
            background: #f3f3f3 !important;
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

        /* PDF HARD FIX: prevent long unbroken words from overflowing A4 */
        .policy-title-block {
            width: 100% !important;
            max-width: 100% !important;
            padding-left: 0.15in;
            padding-right: 0.15in;
            overflow: hidden;
        }

        .policy-title,
        .policy-subtitle {
            width: 100% !important;
            max-width: 100% !important;
            text-align: center !important;
            word-break: break-all !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
        }

        .details-wrap {
            width: 4.85in !important;
            max-width: 4.85in !important;
            overflow: hidden !important;
        }

        .details-table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed !important;
        }

        .details-table td:first-child {
            width: 1.35in !important;
        }

        .details-table td:last-child {
            width: 3.35in !important;
            max-width: 3.35in !important;
            word-break: break-all !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
        }

        .description-content,
        .description-content * {
            max-width: 100% !important;
            word-break: break-all !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
        }


    /* Policy module default font */
    .policy-paper,
    .policy-paper *,
    #policy-preview-sheet,
    #policy-preview-sheet *,
    .description-content,
    .description-content *,
    .policy-preview-body,
    .policy-preview-body * {
        font-family: Georgia, "Times New Roman", serif !important;
    }

    /* Quill editor Georgia font option */
    .ql-font-georgia,
    .ql-font-georgia * {
        font-family: Georgia, "Times New Roman", serif !important;
    }

    .ql-picker.ql-font .ql-picker-label[data-value="georgia"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="georgia"]::before {
        content: "Georgia";
        font-family: Georgia, "Times New Roman", serif;
    }

    .ql-picker.ql-font .ql-picker-label[data-value="serif"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="serif"]::before {
        content: "Serif";
    }

    .ql-picker.ql-font .ql-picker-label[data-value="sans-serif"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="sans-serif"]::before {
        content: "Sans Serif";
    }

    .ql-picker.ql-font .ql-picker-label[data-value="monospace"]::before,
    .ql-picker.ql-font .ql-picker-item[data-value="monospace"]::before {
        content: "Monospace";
    }

    #policy-editor .ql-editor {
        font-family: Georgia, "Times New Roman", serif !important;
    }


    /* Quill alignment support for live preview, show pages, and PDF */
    .ql-align-left {
        text-align: left !important;
    }

    .ql-align-center {
        text-align: center !important;
    }

    .ql-align-right {
        text-align: right !important;
    }

    .ql-align-justify {
        text-align: justify !important;
        text-justify: inter-word;
    }

    .description-content .ql-align-left,
    .policy-preview-body .ql-align-left,
    #policy-preview-sheet .ql-align-left {
        text-align: left !important;
    }

    .description-content .ql-align-center,
    .policy-preview-body .ql-align-center,
    #policy-preview-sheet .ql-align-center {
        text-align: center !important;
    }

    .description-content .ql-align-right,
    .policy-preview-body .ql-align-right,
    #policy-preview-sheet .ql-align-right {
        text-align: right !important;
    }

    .description-content .ql-align-justify,
    .policy-preview-body .ql-align-justify,
    #policy-preview-sheet .ql-align-justify {
        text-align: justify !important;
        text-justify: inter-word;
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
                    <td>{!! $pdfSafeText(!empty($data['effectivity_date']) ? \Carbon\Carbon::parse($data['effectivity_date'])->format('F d, Y') : '______________________________', 30) !!}</td>
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
