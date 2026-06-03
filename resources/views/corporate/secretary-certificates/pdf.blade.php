@php
    $resolution = $certificate->resolution;
    $minute = $certificate->minute ?: $resolution?->minute;
    $corporateContext = $corporateContext ?? [];
    $companyName = $corporateContext['company_name'] ?? '________________';
    $companyRegNo = $corporateContext['company_reg_no'] ?? '________________';
    $companyAddress = $corporateContext['company_address'] ?? '________________';
    $defaultSecretary = $corporateContext['secretary_name'] ?? ($certificate->secretary ?: ($resolution?->secretary ?: ($minute?->secretary ?: '________________')));
    $secretaryAddress = $corporateContext['secretary_address'] ?? 'principal office of the Corporation';
    $defaultTin = $corporateContext['secretary_tin'] ?? null;
    $notarialPlace = $corporateContext['notarial_place'] ?? ($certificate->location ?: 'Cebu City, Philippines');
    $certificatePurpose = $corporateContext['purpose'] ?? ($certificate->purpose ?: ($resolution?->board_resolution ?: 'Certified Resolution'));
    $certificateBody = $corporateContext['resolution_body'] ?? ($certificate->resolution_body ?: ($resolution?->resolution_body ?: ($resolution?->board_resolution ?: ('Certified from Minutes Ref. ' . ($certificate->minutes_ref ?: ($minute?->minutes_ref ?: '________________')) . '.'))));
    $meetingDate = optional($certificate->date_of_meeting)->format('F d, Y') ?: '________________';
    $issuedDate = optional($certificate->date_issued)->format('F d, Y') ?: '________________';
    $resolutionLabel = match ($certificate->governing_body) {
        'Stockholders' => "STOCKHOLDERS' RESOLUTION NO. ",
        'Joint Stockholders and Board of Directors' => 'JOINT BOARD AND STOCKHOLDERS RESOLUTION NO. ',
        default => 'BOARD RESOLUTION NO. ',
    };

    $formatResolutionBodyForDisplay = function ($body) {
        $text = html_entity_decode((string) $body, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Preserve paragraph breaks from saved rich-editor HTML before stripping tags.
        $text = preg_replace('#</(p|div|li|h[1-6])>\s*<(?=(p|div|li|h[1-6])\b)#i', "\n\n<", $text);
        $text = preg_replace('#<br\s*/?>#i', "\n", $text);
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        // Force the standard resolution clauses into separate corporate-style paragraphs.
        $clauseBreaks = [
            '/\s*(WHEREAS\s+RESOLVED[;,]\s+)/iu',
            '/\s*(WHEREAS\s+FINALLY\s+RESOLVED[;,]\s+)/iu',
            '/\s*(BE\s+IT\s+FURTHER\s+RESOLVED[;,]\s+)/iu',
            '/\s*(FINALLY\s+BE\s+IT\s+FURTHER\s+RESOLVED\s+)/iu',
            '/\s*(All\s+prior\s+inconsistent\s+resolutions\s+or\s+actions\s+of\s+the\s+Board\s+of\s+Directors\s+)/iu',
        ];

        foreach ($clauseBreaks as $pattern) {
            $text = preg_replace($pattern, "\n\n$1", $text);
        }

        $text = preg_replace('/\n{3,}/', "\n\n", trim($text));
        $paragraphs = preg_split('/\n\s*\n+/', $text);
        $html = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim(preg_replace('/[ \t]+/', ' ', $paragraph));
            if ($paragraph === '') {
                continue;
            }

            $escaped = e($paragraph);

            // Bold only the clause heading, not the whole sentence.
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

            // Emphasize the auto-filled signing date and place in the final clause.
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


    $dompdfFooterLeft = trim('SECRETARY CERTIFICATE ' . ($certificate->certificate_no ?: '') . ' - ' . strtoupper($companyName));
    $dompdfFooterLeft = html_entity_decode((string) $dompdfFooterLeft, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $dompdfFooterLeft = preg_replace('/\s+/', ' ', $dompdfFooterLeft);
    if (mb_strlen($dompdfFooterLeft) > 90) {
        $dompdfFooterLeft = mb_substr($dompdfFooterLeft, 0, 87) . '...';
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4; margin: 15mm 16mm 24mm; }
        body { margin: 0; font-family: Georgia, "Times New Roman", serif; color: #000; font-size: 13px; line-height: 1.65; }
        .title { text-align: center; font-size: 20px; font-weight: 700; margin: 22px 0 28px; }
        .content p { margin: 0 0 16px; text-align: justify; }

        .resolution-body, .resolution-body * { max-width: 100%; overflow-wrap: anywhere; word-break: break-word; white-space: normal; }
        .resolution-body p { margin: 0 0 18px; text-align: justify; line-height: 1.65; }
        .resolution-body strong { font-weight: 700; }

        .resolution-title { text-align: center; font-weight: 700; text-transform: uppercase; margin: 26px 0 14px; }
        .signature { margin-top: 42px; text-align: right; }
        .signature-line { display: inline-block; min-width: 250px; border-top: 1px solid #000; padding-top: 8px; text-align: center; }
        .meta { margin-top: 36px; font-size: 12px; line-height: 1.5; }
</style>
</head>
<body>
<div>Republic of the Philippines)</div>
    <div>______________________) S.S.</div>

    <div class="title">SECRETARY'S CERTIFICATE</div>

    <div class="content">
        <p>I, <strong>{{ $certificate->secretary ?: $defaultSecretary }}</strong>, of legal age, Filipino and with residence/address at <strong>{{ $secretaryAddress }}</strong>, depose under oath and hereby state:</p>
        <p>That, I am the incumbent Corporate Secretary of <strong>{{ $companyName }}</strong>, a corporation duly organized and existing under the laws of the Republic of the Philippines, with SEC Registration No. <strong>{{ $companyRegNo }}</strong> and principal office at <strong>{{ $companyAddress }}</strong>.</p>
        <p>That, as Corporate Secretary, I have access to the corporate records of <strong>{{ $companyName }}</strong>.</p>
        <p>That, per corporate records, at the {{ $certificate->type_of_meeting ?: 'Special' }} Meeting of the {{ $certificate->governing_body ?: 'Board of Directors' }} of the Corporation held on <strong>{{ $meetingDate }}</strong>, and recorded under Minutes Ref. <strong>{{ $certificate->minutes_ref ?: ($minute?->minutes_ref ?: '________________') }}</strong>, the following corporate action was duly approved and recorded in the Minute Book, a legal quorum being present and voting, viz:</p>

        <div class="resolution-title">{{ $certificate->resolution_no ? $resolutionLabel . $certificate->resolution_no : 'CERTIFIED MINUTES EXTRACT' }}</div>
        <p><strong>{{ $certificatePurpose }}</strong></p>
        <div class="resolution-body">{!! $formatResolutionBodyForDisplay($certificateBody) !!}</div>

        <p>That, the foregoing resolution shall be in full force and effect unless revoked by the Board of Directors. Moreover, the foregoing resolution is in accordance and does not in any way contravene any provision of the Articles of Incorporation or By-Laws of the Corporation.</p>
        <p>WITNESS MY HAND this ________ day of ___________, {{ optional($certificate->date_issued)->format('Y') ?: now()->year }} at {{ $notarialPlace }}.</p>
    </div>

    <div class="signature">
        <div class="signature-line">
            <div><strong>{{ $certificate->secretary ?: $defaultSecretary }}</strong></div>
            <div>Corporate Secretary</div>
            @if ($defaultTin)
                <div style="font-size:11px;">TIN {{ $defaultTin }}</div>
            @endif
        </div>
    </div>

    <div class="meta">
        <p>SUBSCRIBED AND SWORN TO BEFORE ME, a Notary Public for and in {{ $notarialPlace }}, this ____ day of ____________, 20__. Affiant presented to me __________________________ issued at __________________________.</p>
        <div style="text-align:right; margin-top: 32px;">
            <div class="signature-line">
                <div>{{ $certificate->notary_public ?: 'Notary Public' }}</div>
            </div>
        </div>
        <div style="margin-top: 22px;">
            <div>Doc. No. {{ $certificate->notary_doc_no ?: '_____' }};</div>
            <div>Page No. {{ $certificate->notary_page_no ?: '_____' }};</div>
            <div>Book No. {{ $certificate->notary_book_no ?: '_____' }};</div>
            <div>Series of {{ $certificate->notary_series_no ?: now()->year }}.</div>
        </div>
    </div>


@php
    $dompdfFooterLeft = trim('SECRETARY CERTIFICATE ' . ($certificate->certificate_no ?: '') . ' - ' . strtoupper($companyName));
    $dompdfFooterLeft = preg_replace('/\s+/', ' ', (string) $dompdfFooterLeft);
    if (mb_strlen($dompdfFooterLeft) > 90) {
        $dompdfFooterLeft = mb_substr($dompdfFooterLeft, 0, 87) . '...';
    }
@endphp
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("Times-Roman", "normal");
        $boldFont = $fontMetrics->get_font("Times-Roman", "bold");
        $footerLeft = {!! var_export($dompdfFooterLeft, true) !!};

        $pdf->line(40, 800, 555, 800, [0, 0, 0], 0.4);
        $pdf->page_text(40, 808, $footerLeft, $font, 8, [0, 0, 0]);
        $pdf->page_text(500, 808, "Page {PAGE_NUM} of {PAGE_COUNT}", $boldFont, 8, [0, 0, 0]);
    }
</script>

</body>
</html>
