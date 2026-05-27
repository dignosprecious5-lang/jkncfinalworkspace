@extends('layouts.app')
@section('title', 'Notice Preview')

@section('content')
@php
    $selected = $notice;
    $sectionRibbonPartial = $sectionRibbonPartial ?? 'corporate.partials.section-ribbon';
    $backRoute = $backRoute ?? route('notices');
    $companyName = $companyName ?? strtoupper($selected->corporation_name ?: 'JOHN KELLY & COMPANY');
    $companyRegNo = $companyRegNo ?? ($selected->company_reg_no ?: '2025120230900-02');
    $companyAddress = $companyAddress ?? ($selected->company_address ?: '3RD FLOOR, UNIT 305 CEBU HOLDINGS CENTER CARDINAL ROSALES AVE., CEBU BUSINESS PARK HIPPODROMO, CEBU CITY, 6000');
    $documentPathCandidates = collect([
        $selected->document_path,
        preg_replace('#^/?storage/#', '', (string) $selected->document_path),
    ])
        ->filter()
        ->unique()
        ->values();

    $resolvedDocumentPath = $documentPathCandidates->first(
        fn ($path) => \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
    );

    $documentUrl = $resolvedDocumentPath
        ? route('uploads.show', ['path' => $resolvedDocumentPath])
        : null;

    $meetingTitle = strtoupper(trim(($selected->type_of_meeting ?: 'Special') . ' ' . ($selected->governing_body ?: 'Board of Directors') . ' Meeting'));
    $noticeDate = optional($selected->date_of_notice)->format('F d, Y')
        ?: optional($selected->created_at)->format('F d, Y')
        ?: now()->format('F d, Y');

    $meetingDate = optional($selected->date_of_meeting)->format('F d, Y') ?: '________________';

    $meetingTime = $selected->time_started
        ? \Carbon\Carbon::parse($selected->time_started)->format('h:i a')
        : '________________';

    $recipientLabel = match ($selected->governing_body) {
        'Stockholders' => 'ALL STOCKHOLDERS',
        'Joint Stockholders and Board of Directors' => 'ALL STOCKHOLDERS AND DIRECTORS',
        default => 'ALL DIRECTORS',
    };

    $meetingTypeLabel = $selected->type_of_meeting ?: 'Special';
    $governingBodyLabel = $selected->governing_body ?: 'Board of Directors';
    $meetingLocation = $selected->location ?: '________________';
    $secretaryName = $selected->secretary ?: 'Corporate Secretary';
    $agendaHtml = $selected->body_html ?: '<p>&nbsp;</p>';
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

    $procedureDetailsHtml = '
        <div class="procedure-details">
            <div><strong>Chairman / Presiding Officer:</strong> ' . e($chairmanName) . '</div>
            <div><strong>Corporate Secretary / Authorized Meeting Officer:</strong> ' . e($meetingOfficer) . '</div>
            <div><strong>Email Address:</strong> ' . e($confirmationEmail) . '</div>
            <div><strong>Phone Number:</strong> ' . e($confirmationPhone) . '</div>
            <div><strong>Office Address:</strong> ' . e($officeAddress) . '</div>
            <div><strong>Email / Phone Confirmation Deadline:</strong> ' . e($emailDeadline) . '</div>
            <div><strong>Physical Submission Deadline:</strong> ' . e($physicalDeadline) . '</div>
            <div><strong>Authority Calling the Meeting:</strong> ' . e($authorityCalling) . '</div>
        </div>
    ';


    $generatedNoticePane = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generated Notice Draft</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            font-family: Georgia, "Times New Roman", serif;
            color: #000;
        }

        * {
            box-sizing: border-box;
            max-width: 100%;
        }

        .page {
            width: 840px;
            min-height: 1188px;
            margin: 24px auto;
            background: #fff;
            border: 1px solid #d1d5db;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            padding: 48px;
            font-size: 15px;
            line-height: 1.85;
            overflow-wrap: anywhere;
            word-wrap: break-word;
            word-break: break-word;
        }

        .center {
            text-align: center;
            line-height: 1.4;
        }

        .title {
            margin-top: 40px;
            text-align: center;
            font-size: 1.05rem;
            font-weight: 700;
            text-transform: uppercase;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .meta {
            margin-top: 48px;
        }

        .meta-row {
            font-weight: 700;
            margin-bottom: 20px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .body,
        .body *,
        .agenda,
        .agenda *,
        .footer,
        .footer * {
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
            max-width: 100% !important;
        }

        .body {
            margin-top: 40px;
            text-align: justify;
        }

        .body p {
            margin: 0 0 24px;
        }

        .agenda {
            margin-top: 24px;
        }

        .agenda ol,
        .agenda ul {
            margin: 12px 0 0 24px;
            padding: 0;
        }

        .agenda li {
            margin: 6px 0;
        }

        table {
            width: 100% !important;
            max-width: 100% !important;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
            overflow-wrap: anywhere !important;
            word-wrap: break-word !important;
            word-break: break-word !important;
            white-space: normal !important;
        }

        img,
        iframe,
        embed,
        object,
        video {
            max-width: 100% !important;
            height: auto !important;
        }

        pre,
        code {
            white-space: pre-wrap !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }


        .procedure-text {
            margin-top: 24px;
            text-align: justify;
            break-inside: auto;
            page-break-inside: auto;
        }

        .procedure-details {
            margin-top: 18px;
            font-size: 13px;
            line-height: 1.65;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .procedure-details div {
            margin-bottom: 4px;
        }

        .signature {
            margin-top: 48px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .signature .name {
            margin-top: 48px;
            font-weight: 700;
        }

        .footer {
            margin-top: 64px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            font-size: 11px;
            line-height: 1.4;
        }

        .footer > div {
            overflow-wrap: anywhere;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="center">
            <div style="font-size:1.1rem;font-weight:700;text-transform:uppercase;">{$companyName}</div>
            <div style="font-size:0.95rem;font-weight:700;">COMPANY REG. NO.: {$companyRegNo}</div>
            <div style="margin-top:4px;font-size:0.95rem;">{$companyAddress}</div>
        </div>

        <div class="title">NOTICE AND AGENDA OF THE {$meetingTitle}</div>

        <div class="meta">
            <div class="meta-row">To: <span style="margin-left:12px;">{$recipientLabel}</span></div>
            <div class="meta-row">Date: <span style="margin-left:12px;">{$noticeDate}</span></div>
        </div>

        <div class="body">
            <p>
                <strong>
                    NOTICE is hereby given that a {$meetingTypeLabel} {$governingBodyLabel} Meeting of {$companyName}
                    will be held at {$meetingLocation} on {$meetingDate} at {$meetingTime}.
                </strong>
            </p>

            <p>
                The meeting shall proceed through {$selectedMode}. For virtual or hybrid meetings, access shall be through {$accessDetails}. Only confirmed persons with proper identity, authority, and right to attend, vote, approve, or submit documents shall be allowed or recognized, in accordance with applicable law, the By-Laws, SEC rules, approved procedures, and duly adopted internal policies.
            </p>

            <div class="agenda">
                <div><strong>Agenda:</strong></div>
                <div>{$agendaHtml}</div>
            </div>

            <p class="procedure-text">
                The meeting shall be presided over by {$chairmanName}, or by another duly authorized person, and shall be conducted in accordance with the Revised Corporation Code of the Philippines, the Corporation’s Articles of Incorporation, By-Laws, approved rules of procedure, applicable SEC rules and issuances, and duly adopted internal policies. All participants, proxies, written consents, resolutions by circulation, email approvals, confirmations, and related submissions must be sent to {$meetingOfficer} through {$confirmationEmail}, {$confirmationPhone}, or by personal delivery to {$officeAddress}. Email or phone confirmations must be received at least {$emailDeadline} before the meeting, and physical submissions must be received at least {$physicalDeadline} before the meeting, unless such periods are waived, shortened, or otherwise allowed by the authority calling the meeting. Failure to comply with the required notice, submission, identification, or verification requirements may result in denial of access, attendance, participation, voting, approval, or recognition of the submission, subject to applicable law, the Articles of Incorporation, By-Laws, approved rules of procedure, SEC rules and issuances, and duly adopted internal policies.
            </p>

            {$procedureDetailsHtml}
        </div>

        <div class="signature">
            <div>Very truly yours,</div>
            <div class="name">{$secretaryName}</div>
            <div>Corporate Secretary</div>
        </div>

        <div class="footer">
            <div>
                <div style="font-weight:700;text-transform:uppercase;">Notice for {$meetingTitle}</div>
                <div>{$companyName}</div>
                <div>Company Reg. No.: {$companyRegNo}</div>
                <div>{$companyAddress}</div>
            </div>
            <div style="font-weight:700;">Page</div>
        </div>
    </div>
</body>
</html>
HTML;

    $generatedNoticePaneUrl = 'data:text/html;charset=UTF-8,' . rawurlencode($generatedNoticePane);
@endphp

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
        @if (isset($company))
            @include('company.partials.company-header', ['company' => $company])
        @endif

        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
            @include($sectionRibbonPartial, ['activeTab' => 'notices', 'topButtonLabel' => 'Add Notice'])
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        #notice-print,
        #notice-print * {
            visibility: visible;
        }

        #notice-print {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none !important;
            filter: none !important;
        }
    }
</style>

<div class="w-full px-4 sm:px-6 lg:px-8 mt-4">
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
            <a href="{{ $backRoute }}" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-arrow-left"></i>
            </a>

            <div>
                <div class="text-lg font-semibold">Notice Preview</div>
                <div class="text-xs text-gray-500">Notice #: {{ $selected->notice_number ?: 'Draft Notice' }}</div>
            </div>

            <div class="flex-1"></div>

            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                {{ $selected->type_of_meeting }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 p-6">
            <div class="lg:col-span-3 space-y-4">
                @if ($documentUrl)
                    <iframe
                        src="{{ $documentUrl }}"
                        class="w-full h-[700px] border rounded bg-white">
                    </iframe>
                @else
                    <iframe
                        src="{{ $generatedNoticePaneUrl }}"
                        class="w-full h-[700px] border rounded bg-white">
                    </iframe>
                @endif
            </div>

            <div class="lg:col-span-2 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">Meeting Details</div>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Governing Body</span>
                            <div class="font-medium text-gray-900">{{ $selected->governing_body }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Type of Meeting</span>
                            <div class="font-medium text-gray-900">{{ $selected->type_of_meeting }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Meeting Date</span>
                            <div class="font-medium text-gray-900">{{ optional($selected->date_of_meeting)->format('M d, Y') }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Time</span>
                            <div class="font-medium text-gray-900">{{ $selected->time_started }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Location</span>
                            <div class="font-medium text-gray-900">{{ $selected->location }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">References</div>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Notice No.</span>
                            <div class="font-medium text-gray-900">{{ $selected->notice_number }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Meeting No.</span>
                            <div class="font-medium text-gray-900">{{ $selected->meeting_no }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Updated</span>
                            <div class="font-medium text-gray-900">{{ optional($selected->date_updated)->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">Connected Records</div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-600 uppercase tracking-wide">Minutes</div>
                            <div class="font-medium text-gray-900">{{ $selected->minutes->count() }} linked</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 uppercase tracking-wide">Resolutions</div>
                            <div class="font-medium text-gray-900">{{ $selected->resolutions->count() }} linked</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 uppercase tracking-wide">Secretary Certificates</div>
                            <div class="font-medium text-gray-900">{{ $selected->secretaryCertificates->count() }} linked</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="text-sm font-semibold text-gray-900 mb-3">Signatories</div>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Chairman</span>
                            <div class="font-medium text-gray-900">{{ $selected->chairman }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Secretary</span>
                            <div class="font-medium text-gray-900">{{ $selected->secretary }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-gray-600 uppercase tracking-wide">Uploaded By</span>
                            <div class="font-medium text-gray-900">{{ $selected->uploaded_by }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
