@php
    $selected = $resolution;
    $document = $document ?? [];
    $approvalRows = collect($document['approval_rows'] ?? []);
    $documentChairman = $document['chairman'] ?? null;

    $companyName = $document['company_name'] ?? 'JK&C INC.';
    $companyRegNo = $document['company_reg_no'] ?? '2025120230900-02';
    $companyAddress = $document['company_address'] ?? '3RD FLOOR, UNIT 305 CEBU HOLDINGS CENTER CARDINAL ROSALES AVE., CEBU BUSINESS PARK HIPPODROMO, CEBU CITY, 6000';

    $resolutionNumber = $selected->resolution_no ?: 'Auto Number';
    $resolutionTitle = $selected->board_resolution ?: 'Resolution Title';
    $resolutionBody = trim((string) ($document['full_resolution_body'] ?? ($selected->resolution_body ?: '')));

    $meetingDate = optional($selected->date_of_meeting)->format('F d, Y') ?: '________________';
    $meetingTypeText = $selected->type_of_meeting ?: 'Regular';
    $certifyingBody = $document['certifying_body'] ?? ($selected->governing_body ?: 'Board of Directors');
    $resolutionNumberLabel = $document['resolution_label'] ?? 'Board Resolution No.';
    $secretaryName = $selected->secretary ?: 'Corporate Secretary';
    $chairmanName = $documentChairman['name'] ?? null;
    $notaryYear = $selected->notary_series_no ?: (optional($selected->notarized_on)->format('Y') ?: now()->year);

    $gisLogoPath = data_get($document ?? [], 'logo_path');
    $gisLogoUrl = null;
    $gisLogoDataUri = null;
    if ($gisLogoPath) {
        $normalizedLogoPath = preg_replace('#^/?storage/#', '', (string) $gisLogoPath);
        try { $gisLogoUrl = route('uploads.show', ['path' => $normalizedLogoPath]); } catch (\Throwable $e) { $gisLogoUrl = asset('storage/' . $normalizedLogoPath); }
        $absoluteLogoPath = storage_path('app/public/' . $normalizedLogoPath);
        if (is_file($absoluteLogoPath)) {
            $mime = function_exists('mime_content_type') ? (mime_content_type($absoluteLogoPath) ?: 'image/png') : 'image/png';
            $gisLogoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absoluteLogoPath));
        }
    }

    $formatResolutionBodyForDisplay = function ($body) {
        $text = html_entity_decode((string) $body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('#<br\s*/?>#i', "\n", $text);
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        // Some saved certificate bodies came from a textarea and became one long
        // paragraph. Force the standard resolution clauses back into separate
        // paragraphs so the Secretary Certificate matches the Resolution format.
        $clauseBreaks = [
            '/\s+(WHEREAS\s+RESOLVED[;,]\s+)/iu',
            '/\s+(WHEREAS\s+FINALLY\s+RESOLVED[;,]\s+)/iu',
            '/\s+(BE\s+IT\s+FURTHER\s+RESOLVED[;,]\s+)/iu',
            '/\s+(FINALLY\s+BE\s+IT\s+FURTHER\s+RESOLVED\s+)/iu',
            '/\s+(All\s+prior\s+inconsistent\s+resolutions\s+or\s+actions\s+of\s+the\s+Board\s+of\s+Directors\s+)/iu',
        ];

        foreach ($clauseBreaks as $pattern) {
            $text = preg_replace($pattern, "\n\n$1", $text);
        }

        $paragraphs = preg_split('/\n\s*\n+/', $text);
        $html = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim(preg_replace('/[ \t]+/', ' ', $paragraph));
            if ($paragraph === '') {
                continue;
            }

            $escaped = e($paragraph);

            // Bold only the required clause heading, not the full sentence.
            $patterns = [
                '/^(WHEREAS\s+RESOLVED[;,]?)(\s*)/iu',
                '/^(WHEREAS\s+FINALLY\s+RESOLVED[;,]?)(\s*)/iu',
                '/^(BE\s+IT\s+FURTHER\s+RESOLVED[;,]?)(\s*)/iu',
                '/^(FINALLY\s+BE\s+IT\s+FURTHER\s+RESOLVED)(\s*)/iu',
                '/^(Whereas[;,]?)(\s*)/iu',
            ];

            foreach ($patterns as $pattern) {
                $new = preg_replace($pattern, '<strong>$1</strong> ', $escaped, 1);
                if ($new !== $escaped) {
                    $escaped = $new;
                    break;
                }
            }

            // Emphasize the auto-filled signing date and place in the FINAL clause.
            $escaped = preg_replace(
                '/(We have affixed our signatures on this\s+)(.*?)(\s+at\s+)(.*?)(\.)$/iu',
                '$1<strong><u>$2</u></strong>$3<strong><u>$4</u></strong>$5',
                $escaped,
                1
            );

            $html[] = '<p>' . $escaped . '</p>';
        }

        return implode("\n", $html);
    };

@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resolution {{ $resolutionNumber }}</title>
    <style>
        @page {
            size: A4;
            margin: 16mm 18mm 18mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: #000;
            font-size: 12.5px;
            line-height: 1.35;
        }

        .header {
            text-align: center;
            line-height: 1.15;
            margin-bottom: 22px;
        }

        .gis-logo { max-height: 68px; max-width: 240px; object-fit: contain; margin: 0 auto 8px; display: block; }

        .brand-name {
            font-size: 31px;
            line-height: 0.95;
            font-weight: 400;
        }

        .brand-amp {
            color: #2563eb;
            font-size: 30px;
            font-weight: bold;
        }

        .company-name {
            margin-top: 7px;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .company-meta {
            font-size: 10.5px;
            text-transform: uppercase;
            max-width: 660px;
            margin: 0 auto;
        }

        .rule-title {
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 8px 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        .resolution-title {
            text-align: center;
            border-bottom: 1px solid #000;
            padding: 8px 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        .content {
            margin-top: 24px;
            text-align: justify;
        }

        .content,
        .content * {
            max-width: 100%;
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: normal;
        }


        p {
            margin: 0 0 12px 0;
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            margin: 18px 0 12px;
        }

        .signature-center {
            text-align: center;
            margin-top: 28px;
        }

        .signature-name {
            display: inline-block;
            min-width: 230px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .approval-page {
            page-break-before: always;
            break-before: page;
            padding-top: 26mm;
        }

        .approval-title {
            margin-top: 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        .approval-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .approval-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 12px 10px;
        }

        .approval-name {
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .role {
            margin-top: 4px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .notary-page {
            margin-top: 34px;
            padding-top: 0;
        }

        .notary-public {
            margin-top: 30px;
            text-align: right;
            font-weight: bold;
            text-transform: uppercase;
        }

        .notary-meta {
            margin-top: 32px;
            line-height: 1.35;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($gisLogoDataUri)
            <img src="{{ $gisLogoDataUri }}" class="gis-logo" alt="Company Logo">
        @else
            <div class="brand-name">John Kelly</div>
            <div><span class="brand-amp">&amp;</span> <span class="brand-name">Company</span></div>
        @endif
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-meta"><strong>Company Reg. No.:</strong> {{ $companyRegNo }}</div>
        <div class="company-meta">{{ $companyAddress }}</div>
    </div>

    <div class="rule-title">{{ strtoupper($resolutionNumberLabel) }} {{ $resolutionNumber }}</div>
    <div class="resolution-title">{{ $resolutionTitle }}</div>

    @if ($resolutionBody !== '')
        <div class="content">{!! $formatResolutionBodyForDisplay($resolutionBody) !!}</div>
    @endif

    <div class="section-title">Certification</div>

    <div class="content" style="margin-top: 0;">
        <p>
            I, <strong><u>{{ $secretaryName }}</u></strong>, the duly appointed Corporate Secretary of
            <strong><u>{{ $companyName }}</u></strong>, do hereby certify that the
            {{ $certifyingBody }} in a {{ strtolower($meetingTypeText) }} meeting held on
            <strong><u>{{ $meetingDate }}</u></strong>, approved the foregoing Resolution in favor hereof.
        </p>
    </div>

    <div class="approval-page">
        <div class="signature-center" style="margin-top: 0; margin-bottom: 24px;">
            <div class="signature-name">{{ $secretaryName }}</div>
            <div>Corporate Secretary</div>
        </div>

    @if ($approvalRows->isNotEmpty() || $chairmanName)
        <div class="approval-title">Approved:</div>

        @if ($approvalRows->isNotEmpty())
            <table class="approval-table">
                @foreach ($approvalRows->chunk(2) as $rowPair)
                    <tr>
                        @foreach ($rowPair as $row)
                            <td>
                                <div class="approval-name">{{ $row['name'] }}</div>
                                <div class="role">{{ $row['role'] ?? 'Director' }}</div>
                            </td>
                        @endforeach

                        @if ($rowPair->count() === 1)
                            <td>&nbsp;</td>
                        @endif
                    </tr>
                @endforeach
            </table>
        @endif

        @if ($chairmanName)
            <div class="signature-center" style="margin-top: 18px;">
                <div class="approval-name">{{ $chairmanName }}</div>
                <div style="margin-top: 4px; font-weight:bold;">Chairman</div>
            </div>
        @endif
    @endif

    <div class="notary-page">
        @if ($chairmanName)
            <p>
                IN WITNESS WHEREOF, I, <strong><u>{{ $chairmanName }}</u></strong>, in my capacity as Chairman of the Board, have signed these presents this ___ day of _______, 20 at _____________________.
            </p>

            <div class="signature-center" style="margin-top: 34px;">
                <div class="approval-name">{{ $chairmanName }}</div>
                <div style="margin-top: 4px; font-weight:bold;">Chairman</div>
            </div>
        @else
            <p>
                IN WITNESS WHEREOF, I, __________________________, in my capacity as Chairman of the Board, have signed these presents this ___ day of _______, 20 at _____________________.
            </p>

            <div class="signature-center" style="margin-top: 34px;">
                <div class="signature-name">&nbsp;</div>
                <div>Chairman</div>
            </div>
        @endif

        <p style="margin-top: 34px;">
            <strong>SUBSCRIBED AND SWORN TO BEFORE ME,</strong> a Notary Public for and in ______________________ this ___ day of _______, 20.
            Affiant presented to me __________________________ issued at __________________________.
        </p>

        <div class="notary-public">NOTARY PUBLIC</div>

        <div class="notary-meta">
            <div>Doc. No. {{ $selected->notary_doc_no ?: '______' }};</div>
            <div>Page No. {{ $selected->notary_page_no ?: '______' }};</div>
            <div>Book No. {{ $selected->notary_book_no ?: '______' }};</div>
            <div>Series of {{ $notaryYear }}.</div>
        </div>
    </div>
    </div>


@php
    $dompdfFooterLeft = trim(strtoupper($resolutionNumberLabel) . ' ' . $resolutionNumber . ' - ' . strtoupper($companyName));
    $dompdfFooterLeft = preg_replace('/\s+/', ' ', (string) $dompdfFooterLeft);
    if (mb_strlen($dompdfFooterLeft) > 90) {
        $dompdfFooterLeft = mb_substr($dompdfFooterLeft, 0, 87) . '...';
    }
@endphp
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("Times-Roman", "normal");
        $boldFont = $fontMetrics->get_font("Times-Roman", "bold");
        $footerLeft = @json($dompdfFooterLeft);

        $pdf->line(40, 800, 555, 800, [0, 0, 0], 0.4);
        $pdf->page_text(40, 808, $footerLeft, $font, 8, [0, 0, 0]);
        $pdf->page_text(500, 808, "Page {PAGE_NUM} of {PAGE_COUNT}", $boldFont, 8, [0, 0, 0]);
    }
</script>

</body>
</html>
