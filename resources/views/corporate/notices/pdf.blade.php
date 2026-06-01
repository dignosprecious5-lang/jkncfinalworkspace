@php
    $selected = $notice;
    $meetingTitle = strtoupper(trim(($selected->type_of_meeting ?: 'Special') . ' ' . ($selected->governing_body ?: 'Board of Directors') . ' Meeting'));
    $noticeDate = optional($selected->date_of_notice)->format('F d, Y') ?: optional($selected->created_at)->format('F d, Y') ?: now()->format('F d, Y');
    $meetingDate = optional($selected->date_of_meeting)->format('F d, Y') ?: '________________';
    $meetingTime = $selected->time_started ? \Carbon\Carbon::parse($selected->time_started)->format('h:i a') : '________________';
    $recipientLabel = match ($selected->governing_body) {
        'Stockholders' => 'ALL STOCKHOLDERS',
        'Joint Stockholders and Board of Directors' => 'ALL STOCKHOLDERS AND DIRECTORS',
        default => 'ALL DIRECTORS',
    };
    $companyName = strtoupper((data_get($corporateContext ?? [], 'company_name') ?: data_get($corporateContext ?? [], 'companyName')) ?: ($selected->corporation_name ?: 'JOHN KELLY & COMPANY'));
    $companyRegNo = (data_get($corporateContext ?? [], 'company_reg_no') ?: data_get($corporateContext ?? [], 'companyRegNo')) ?: ($selected->company_reg_no ?: '2025120230900-02');
    $companyAddress = (data_get($corporateContext ?? [], 'company_address') ?: data_get($corporateContext ?? [], 'companyAddress')) ?: ($selected->company_address ?: '3RD FLOOR, UNIT 305 CEBU HOLDINGS CENTER CARDINAL ROSALES AVE., CEBU BUSINESS PARK HIPPODROMO, CEBU CITY, 6000');

    $gisLogoPath = (data_get($corporateContext ?? [], 'logo_path') ?: data_get($corporateContext ?? [], 'logoPath')) ?: data_get($document ?? [], 'logo_path') ?: data_get($corporateContext['gis'] ?? null, 'logo_path');
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

    $meetingTypeLabel = $selected->type_of_meeting ?: 'Special';
    $governingBodyLabel = $selected->governing_body ?: 'Board of Directors';
    $meetingLocation = $selected->location ?: '________________';
    $secretaryName = $selected->secretary ?: 'Corporate Secretary';
    $agendaHtml = $bodyHtml ?: '<p>&nbsp;</p>';
    $selectedMode = $selected->meeting_mode ?: '________________';
    $meetingPlatform = $selected->meeting_platform ?: '________________';
    $meetingLinkDetails = $selected->meeting_link_details ?: '________________';
    $accessDetails = trim($meetingPlatform . (($meetingLinkDetails && $meetingLinkDetails !== '________________') ? ' - ' . $meetingLinkDetails : ''));
    $chairmanName = $selected->chairman ?: '________________';
    $meetingOfficer = $selected->authorized_meeting_officer ?: ($selected->secretary ?: 'Corporate Secretary');
    $confirmationEmail = $selected->confirmation_email ?: '________________';
    $confirmationPhone = $selected->confirmation_phone ?: '________________';
    $officeAddress = $selected->office_address ?: '________________';
    $emailDeadline = $selected->email_phone_confirmation_deadline ?: 'forty-eight (48) hours';
    $physicalDeadline = $selected->physical_submission_deadline ?: 'three (3) days';
    $authorityCalling = $selected->authority_calling_meeting ?: '________________';

    // Do not show internal meeting officer/contact/deadline details in the issued notice.

@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice {{ $selected->notice_number ?: 'Draft Notice' }}</title>
    <style>
        @page {
            size: A4;
            margin: 12mm 12mm 18mm;
        }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: #000;
            font-size: 14px;
            line-height: 1.75;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page {
            box-sizing: border-box;
            width: 100%;
        }
        .center {
            text-align: center;
            line-height: 1.4;
        }
        .gis-logo { max-height: 58px; max-width: 210px; object-fit: contain; margin: 0 auto 6px; display: block; }
        .title {
            margin-top: 28px;
            text-align: center;
            font-size: 1.05rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .meta {
            margin-top: 34px;
        }
        .meta-row {
            font-weight: 700;
            margin-bottom: 16px;
        }
        .body {
            margin-top: 30px;
            text-align: justify;
        }
        .body p {
            margin: 0 0 18px;
        }
        .agenda {
            margin-top: 16px;
        }
        .agenda ol,
        .agenda ul {
            margin: 12px 0 0 24px;
            padding: 0;
        }
        .agenda li {
            margin: 6px 0;
        }
        .footer {
            margin-top: 64px;
            display: flex;
            align-items: end;
            justify-content: space-between;
            font-size: 11px;
            line-height: 1.4;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .page-number:after {
            content: counter(page);
        }

        .procedure-text {
            margin-top: 18px;
            text-align: justify;
            break-inside: auto;
            page-break-inside: auto;
        }
        .procedure-details {
            margin-top: 14px;
            font-size: 12px;
            line-height: 1.55;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .procedure-details div {
            margin-bottom: 3px;
        }

        .signature {
            margin-top: 38px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .signature .name {
            margin-top: 36px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="center">
            @if($gisLogoDataUri)
                <img src="{{ $gisLogoDataUri }}" class="gis-logo" alt="Company Logo">
            @endif
            <div style="font-size:1.1rem;font-weight:700;text-transform:uppercase;">{{ $companyName }}</div>
            <div style="font-size:0.95rem;font-weight:700;">COMPANY REG. NO.: {{ $companyRegNo }}</div>
            <div style="margin-top:4px;font-size:0.95rem;">{{ $companyAddress }}</div>
        </div>

        <div class="title">Notice and Agenda of the {{ $meetingTitle }}</div>

        <div class="meta">
            <div class="meta-row">To: <span style="margin-left:12px;">{{ $recipientLabel }}</span></div>
            <div class="meta-row">Date: <span style="margin-left:12px;">{{ $noticeDate }}</span></div>
        </div>

        <div class="body">
            <p><strong>NOTICE is hereby given that a {{ $meetingTypeLabel }} {{ $governingBodyLabel }} Meeting of {{ $companyName }} will be held at {{ $meetingLocation }} on {{ $meetingDate }} at {{ $meetingTime }}.</strong></p>

            <p>The meeting shall proceed through {{ $selectedMode }}. For virtual or hybrid meetings, access shall be through {{ $accessDetails }}. Only confirmed persons with proper identity, authority, and right to attend, vote, approve, or submit documents shall be allowed or recognized, in accordance with applicable law, the By-Laws, SEC rules, approved procedures, and duly adopted internal policies.</p>

            <div class="agenda">
                <div><strong>Agenda:</strong></div>
                {!! $agendaHtml !!}
            </div>

            <p class="procedure-text">The meeting shall be presided over by {{ $chairmanName }}, or by another duly authorized person, and shall be conducted in accordance with the Revised Corporation Code of the Philippines, the Corporation’s Articles of Incorporation, By-Laws, approved rules of procedure, applicable SEC rules and issuances, and duly adopted internal policies. Only confirmed persons with proper identity, authority, and right to attend, vote, approve, or submit documents shall be allowed or recognized, subject to applicable law and the Corporation’s approved procedures.</p>
        </div>

        <div class="signature">
            <div>Very truly yours,</div>
            <div class="name">{{ $secretaryName }}</div>
            <div>Corporate Secretary</div>
        </div>

        <div class="footer">
            <div>
                <div style="font-weight:700;text-transform:uppercase;">Notice for {{ $meetingTitle }}</div>
                <div>{{ $companyName }}</div>
                <div>Company Reg. No.: {{ $companyRegNo }}</div>
                <div>{{ $companyAddress }}</div>
            </div>
            <div style="font-weight:700;">Page <span class="page-number"></span></div>
        </div>
    </div>
</body>
</html>
