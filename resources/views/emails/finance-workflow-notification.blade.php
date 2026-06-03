<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:24px 28px;border-bottom:1px solid #e5e7eb;background:#f9fafb;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <img src="{{ $logoUrl }}" alt="JK&C Inc." style="display:block;width:168px;max-width:100%;height:auto;">
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;">
                                            Finance Workflow
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 28px;">
                            <span style="display:inline-block;background:{{ $accentSoftColor }};color:{{ $accentColor }};border-radius:999px;padding:7px 12px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;">
                                {{ $badgeLabel }}
                            </span>

                            <h1 style="margin:18px 0 8px;font-size:24px;line-height:1.25;color:#111827;">{{ $title }}</h1>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#475569;">Hi {{ $notifiableName }}, {{ $body }}</p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:20px;">
                                <tr>
                                    <td style="padding:14px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                                        <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;">Record Summary</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.5;">
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;width:180px;">Record Number</td>
                                                <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $recordNumber }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;">Record Title</td>
                                                <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $recordTitle }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;">Record Date</td>
                                                <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $recordDate }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;">Status</td>
                                                <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $workflowStatus }} / {{ $approvalStatus }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;">Relationship Status</td>
                                                <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $relationshipStatus }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;">Submitted By</td>
                                                <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $submittedByName ?: 'N/A' }}</td>
                                            </tr>
                                            @if(filled($approvedByName))
                                                <tr>
                                                    <td style="padding:6px 0;color:#6b7280;">Approved By</td>
                                                    <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $approvedByName }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;">Attachments</td>
                                                <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $attachmentCount }} file{{ $attachmentCount === 1 ? '' : 's' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;">History Entries</td>
                                                <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $historyCount }}</td>
                                            </tr>
                                            @if(filled($reviewNote))
                                                <tr>
                                                    <td style="padding:6px 0;color:#6b7280;vertical-align:top;">Review Note</td>
                                                    <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $reviewNote }}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @php
                                $actionButtons = $actionButtons ?? [];
                                if (empty($actionButtons)) {
                                    $actionButtons = [
                                        [
                                            'label' => $buttonLabel,
                                            'url' => $url,
                                            'color' => $accentColor,
                                        ],
                                    ];
                                }
                            @endphp

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 22px;">
                                <tr>
                                    @foreach($actionButtons as $button)
                                        <td style="padding:0 8px 8px 0;">
                                            <a href="{{ $button['url'] }}"
                                               style="display:inline-block;background:{{ $button['color'] ?? $accentColor }};color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-size:14px;font-weight:700;">
                                                {{ $button['label'] }}
                                            </a>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;line-height:1.6;">The finance record is fully traceable through the workflow summary above.</p>
                            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;line-height:1.6;">Open the record in Finance to review the full details, then approve, revert with reason, or place it on hold with reason from the dashboard.</p>
                            <p style="margin:0 0 8px;font-size:13px;color:#6b7280;line-height:1.6;">A PDF copy of the current record is attached for reference.</p>
                            <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">For concerns, please contact the Finance Department of JK&amp;C Inc.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">
                            This is an automated notification from the JK&amp;C Finance system.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
