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
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4; margin: 15mm 16mm 18mm; }
        body { margin: 0; font-family: Georgia, "Times New Roman", serif; color: #000; font-size: 13px; line-height: 1.65; }
        .title { text-align: center; font-size: 20px; font-weight: 700; margin: 22px 0 28px; }
        .content p { margin: 0 0 16px; text-align: justify; }
        .resolution-title { text-align: center; font-weight: 700; text-transform: uppercase; margin: 26px 0 14px; }
        .signature { margin-top: 42px; text-align: right; }
        .signature-line { display: inline-block; min-width: 250px; border-top: 1px solid #000; padding-top: 8px; text-align: center; }
        .meta { margin-top: 36px; font-size: 12px; line-height: 1.5; }
    </style>
</head>
<body>
    <div>Republic of the Philippines)</div>
    <div>{{ $notarialPlace }}) S.S.</div>

    <div class="title">SECRETARY'S CERTIFICATE</div>

    <div class="content">
        <p>I, <strong>{{ $certificate->secretary ?: $defaultSecretary }}</strong>, of legal age, Filipino and with residence/address at <strong>{{ $secretaryAddress }}</strong>, depose under oath and hereby state:</p>
        <p>That, I am the incumbent Corporate Secretary of <strong>{{ $companyName }}</strong>, a corporation duly organized and existing under the laws of the Republic of the Philippines, with SEC Registration No. <strong>{{ $companyRegNo }}</strong> and principal office at <strong>{{ $companyAddress }}</strong>.</p>
        <p>That, as Corporate Secretary, I have access to the corporate records of <strong>{{ $companyName }}</strong>.</p>
        <p>That, per corporate records, at the {{ $certificate->type_of_meeting ?: 'Special' }} Meeting of the {{ $certificate->governing_body ?: 'Board of Directors' }} of the Corporation held on <strong>{{ $meetingDate }}</strong>, and recorded under Minutes Ref. <strong>{{ $certificate->minutes_ref ?: ($minute?->minutes_ref ?: '________________') }}</strong>, the following corporate action was duly approved and recorded in the Minute Book, a legal quorum being present and voting, viz:</p>

        <div class="resolution-title">{{ $certificate->resolution_no ? $resolutionLabel . $certificate->resolution_no : 'CERTIFIED MINUTES EXTRACT' }}</div>
        <p><strong>{{ $certificatePurpose }}</strong></p>
        <p>{!! $certificateBody !!}</p>

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
</body>
</html>
