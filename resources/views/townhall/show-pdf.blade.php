<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Memorandum</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 12mm 30mm 12mm;
        }

        body {
            margin: 0;
            font-family: "Times New Roman", DejaVu Serif, serif;
            font-size: 13px;
            line-height: 1.45;
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
            color: #0447a7;
            padding-top: 0;
        }

        .title {
            text-align: center;
            font-size: 23px;
            font-weight: bold;
            color: #111;
            letter-spacing: 0;
            margin: 8mm 0 7mm 0;
        }

        .meta {
            margin-bottom: 3mm;
            font-size: 13px;
        }

        .meta p {
            margin: 0.8mm 0;
        }

        .divider {
            border-bottom: 1px solid #666;
            margin-top: 3mm;
            margin-bottom: 5mm;
        }

        .body-content {
            font-size: 13px;
            line-height: 1.25;
            text-align: justify;
            padding-bottom: 4mm;
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

        .effectivity {
            margin: 3mm 0 5mm 0;
            text-align: justify;
        }

        .issued {
            margin: 0 0 7mm 0;
        }

        .approval-section {
            page-break-inside: avoid;
            font-size: 12.5px;
            line-height: 1.25;
        }

        .approval-block {
            margin-bottom: 5mm;
        }

        .approval-block p {
            margin: 0 0 0.8mm 0;
        }

        .approval-title {
            font-weight: bold;
            margin-bottom: 3mm !important;
        }

        .computer-generated {
            margin-top: 1mm;
            font-weight: bold;
        }


        .acknowledgement-section {
            page-break-inside: avoid;
            margin-top: 7mm;
            padding: 4mm 5mm;
            border: 1px solid #999;
            font-size: 12.5px;
            line-height: 1.35;
        }

        .acknowledgement-section-title {
            font-weight: bold;
            margin: 0 0 3mm 0;
            text-transform: uppercase;
        }

        .acknowledgement-section p {
            margin: 0 0 1.2mm 0;
        }

        .footer-fixed {
            position: fixed;
            bottom: -18mm;
            left: 0;
            right: 0;
            font-size: 9px;
            line-height: 1.25;
            color: #333;
            box-sizing: border-box;
        }

        .footer-inner {
            margin-left: 10mm;
            margin-right: 10mm;
        }

        .footer-meta {
            border-top: 1px solid #999;
            padding-top: 2mm;
            margin-bottom: 2mm;
            text-align: center;
            font-size: 9px;
        }
.footer-note {
            margin: 0 0 3mm 0;
            text-align: justify;
            line-height: 1.35;
        }

        .footer-address {
            margin: 0;
            text-align: left;
            line-height: 1.35;
        }
    </style>
</head>
<body>
    @php
        $logoCandidates = [
            public_path('images/jk-logo.png'),
            public_path('images/jk-logo.jpg'),
            public_path('images/jk-logo.jpeg'),
            public_path('images/logo.png'),
            public_path('images/logo.jpg'),
            public_path('storage/images/jk-logo.png'),
        ];

        $logoDataUri = null;

        foreach ($logoCandidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => 'image/png',
                };

                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($candidate));
                break;
            }
        }

        $acknowledgedRecords = \App\Models\TownHallAcknowledgement::where('townhall_communication_id', $communication->id)
            ->whereNotNull('acknowledged_at')
            ->orderBy('acknowledged_at')
            ->get();

        $acknowledgmentRequired = false;
        $recipientType = $communication->recipient_type ?? 'all';

        if (($communication->approval_status ?? null) === 'Approved' && !($communication->is_archived ?? false)) {
            $acknowledgmentRequired = in_array($recipientType, [
                'all',
                'all_users',
                'all_admins',
                'employee',
            ], true);

            if (!empty($communication->recipient_user_id)
                || !empty($communication->recipient_user_ids)
                || !empty($communication->recipient_contact_ids)
            ) {
                $acknowledgmentRequired = true;
            }
        }
    @endphp

<div class="footer-fixed">
        <div class="footer-inner">
            <div class="footer-meta">&nbsp;</div>

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
                        @if($logoDataUri)
                            <img src="{{ $logoDataUri }}" alt="" class="logo">
                        @else
                            <div style="width:36mm;height:18mm;border:1px solid #ddd;text-align:center;font-size:10px;line-height:18mm;color:#888;">JK&amp;C</div>
                        @endif
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
            <p>
                <strong>{{ $communication->recipient_label ?? 'To' }}:</strong>
                @if(($communication->recipient_type ?? 'all') === 'all')
                    All Employees
                @else
                    {{ $communication->recipient_names ?: '—' }}
                @endif
            </p>
            <p><strong>From:</strong> {{ $communication->from_name ?: '—' }}</p>
            <p><strong>SUBJECT:</strong> {{ $communication->subject ?: '—' }}</p>
        </div>

        <div class="divider content-inset"></div>

        <div class="body-content content-inset">
            {!! $communication->message ?: '<p>No memorandum body provided.</p>' !!}
        </div>

        <div class="content-inset">
            <div class="effectivity">
                This Memorandum shall take effect immediately and shall remain in force until amended,
                superseded, or revoked by a subsequent issuance.
            </div>

            <div class="issued">
                Issued this
                <strong>
                    {{ $communication->communication_date ? \Carbon\Carbon::parse($communication->communication_date)->format('jS') : '______________' }}
                </strong>
                day of
                <strong>
                    {{ $communication->communication_date ? \Carbon\Carbon::parse($communication->communication_date)->format('F, Y') : '______________' }}
                </strong>
                in Cebu City, Philippines.
            </div>

            <div class="approval-section">
                <div class="approval-block">
                    <p class="approval-title">Prepared By:</p>
                    <p>{{ $communication->from_name ?: 'Name' }}</p>
                    <p>{{ $communication->uploader?->employee?->position ?? 'Position' }}</p>
                    <p>{{ $communication->department_stakeholder ?: 'Department' }}</p>
                    <p>
                        Prepared on:
                        {{ $communication->submitted_at ? \Carbon\Carbon::parse($communication->submitted_at)->format('F d, Y h:i A') : optional($communication->created_at)->format('F d, Y h:i A') }}
                    </p>
                </div>

                <div class="approval-block">
                    <p class="approval-title">From Management</p>
                    <p>{{ $communication->management_approver_name ?: 'Name' }}</p>
                    <p>{{ $communication->management_approver_position ?: 'Position' }}</p>
                    <p>{{ $communication->management_approver_department ?: 'Department' }}</p>
                    <p>
                        Approved on:
                        {{ $communication->management_approved_at ? \Carbon\Carbon::parse($communication->management_approved_at)->format('F d, Y h:i A') : 'Date and Time' }}
                    </p>
                </div>

                <div class="approval-block">
                    <p class="approval-title">From Executive Management</p>
                    <p>{{ $communication->executive_approver_name ?: 'John Kelly D. Abalde' }}</p>
                    <p>{{ $communication->executive_approver_position ?: 'President and CEO' }}</p>
                    <p>{{ $communication->executive_approver_department ?: 'Executive Management' }}</p>
                    <p>
                        Approved on:
                        {{ $communication->executive_approved_at ? \Carbon\Carbon::parse($communication->executive_approved_at)->format('F d, Y h:i A') : 'Date and Time' }}
                    </p>
                </div>

                <p class="computer-generated">
                    This is a computer-generated document. Signature is not required.
                </p>
            </div>

            @if($acknowledgmentRequired)
                <div class="acknowledgement-section">
                    <p class="acknowledgement-section-title">Acknowledgment Tracking</p>
                    <p><strong>Acknowledgment Required:</strong> YES</p>

                    @if($acknowledgedRecords->count() > 0)
                        @foreach($acknowledgedRecords as $acknowledgementRecord)
                            <div style="margin-top: 3mm; padding-top: 3mm; border-top: 1px solid #cccccc;">
                                <p><strong>Acknowledged By:</strong> {{ $acknowledgementRecord->recipient_name ?: optional($acknowledgementRecord->user)->name ?: '—' }}</p>
                                <p><strong>Position:</strong> {{ $acknowledgementRecord->recipient_position ?: '—' }}</p>
                                <p><strong>Department:</strong> {{ $acknowledgementRecord->recipient_department ?: '—' }}</p>
                                <p><strong>Date and Time Acknowledged:</strong> {{ \Carbon\Carbon::parse($acknowledgementRecord->acknowledged_at)->format('F d, Y h:i A') }}</p>
                                <p><strong>IP Address:</strong> {{ $acknowledgementRecord->ip_address ?: '—' }}</p>
                                <p><strong>User Account ID:</strong> {{ $acknowledgementRecord->user_account_id ?: $acknowledgementRecord->user_id }}</p>
                            </div>
                        @endforeach
                    @else
                        <p><strong>Status:</strong> Pending acknowledgment</p>
                    @endif

                    <p style="margin-top: 3mm; font-weight: bold;">
                        This is a computer-generated document. Signature is not required.
                    </p>
                </div>
            @endif
        </div>
    </div>

</body>
</html>
