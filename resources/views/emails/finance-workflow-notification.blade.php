<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f8fc; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f8fc; margin:0; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 14px 36px rgba(15, 23, 42, 0.10);">
                    <tr>
                        <td style="background:linear-gradient(135deg, #0f4c81 0%, #1d4ed8 100%); padding:28px 32px 22px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <img src="{{ $logoUrl }}" alt="JK&C Inc." style="display:block; width:170px; max-width:100%; height:auto;">
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block; background-color:rgba(255,255,255,0.16); color:#ffffff; border:1px solid rgba(255,255,255,0.24); border-radius:999px; padding:8px 14px; font-size:12px; font-weight:700; letter-spacing:0.04em; text-transform:uppercase;">
                                            Finance Workflow
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <span style="display:inline-block; background-color:{{ $accentSoftColor }}; color:{{ $accentColor }}; border-radius:999px; padding:8px 14px; font-size:12px; font-weight:700; letter-spacing:0.04em; text-transform:uppercase;">
                                {{ $badgeLabel }}
                            </span>

                            <h1 style="margin:18px 0 12px; font-size:28px; line-height:1.2; color:#0f172a;">{{ $title }}</h1>
                            <p style="margin:0 0 24px; font-size:16px; line-height:1.7; color:#334155;">Hi {{ $notifiableName }}, {{ $body }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #dbe7f3; border-radius:16px; overflow:hidden; margin-bottom:22px;">
                                <tr>
                                    <td style="padding:18px 20px; background-color:#f8fbff; border-bottom:1px solid #dbe7f3;">
                                        <p style="margin:0; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#0f4c81;">Record Summary</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:20px;">
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Record:</strong> {{ $recordNumber }} - {{ $recordTitle }}</p>
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Record Date:</strong> {{ $recordDate }}</p>
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Status:</strong> {{ $workflowStatus }} / {{ $approvalStatus }}</p>
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Submitted By:</strong> {{ $submittedByName ?: 'N/A' }}</p>
                                        @if(filled($approvedByName))
                                            <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Approved By:</strong> {{ $approvedByName }}</p>
                                        @endif
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Attachments:</strong> {{ $attachmentCount }} file{{ $attachmentCount === 1 ? '' : 's' }}</p>
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">History Entries:</strong> {{ $historyCount }}</p>
                                        @if(filled($reviewNote))
                                            <p style="margin:0; font-size:14px; line-height:1.7; color:#334155;"><strong style="color:#0f172a;">Review Note:</strong> {{ $reviewNote }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 24px;">
                                <tr>
                                    <td align="center" bgcolor="{{ $accentColor }}" style="border-radius:12px;">
                                        <a href="{{ $url }}" style="display:inline-block; padding:14px 24px; font-size:14px; font-weight:700; color:#ffffff; text-decoration:none;">{{ $buttonLabel }}</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px; font-size:14px; line-height:1.7; color:#475569;">For concerns, please contact the Finance Department of JK&amp;C Inc.</p>
                            <p style="margin:0 0 8px; font-size:14px; line-height:1.7; color:#475569;">A PDF copy of the current record is attached for your reference.</p>
                            <p style="margin:0; font-size:14px; line-height:1.7; color:#475569;">Regards,<br>JK&amp;C Inc.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
