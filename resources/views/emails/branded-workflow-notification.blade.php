<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#eef2f7;padding:28px 12px;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" role="presentation" style="max-width:640px;width:100%;background:#ffffff;border:1px solid #dbe3ef;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#ffffff;padding:22px 28px;border-bottom:1px solid #dbe3ef;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <img src="{{ $logoUrl }}" alt="John Kelly &amp; Company" style="display:block;width:160px;max-width:100%;height:auto;">
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;border:1px solid #bfdbfe;background:#eff6ff;border-radius:999px;padding:7px 12px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#102d79;">
                                            {{ $moduleName }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#2563eb;">
                                JK&amp;C ORDO Notification
                            </p>
                            <h1 style="margin:0 0 14px;font-size:24px;line-height:1.25;color:#102d79;">{{ $title }}</h1>
                            <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#334155;">Hi {{ $notifiableName }}, {{ $body }}</p>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border:1px solid #dbe3ef;border-radius:12px;overflow:hidden;margin:0 0 22px;">
                                <tr>
                                    <td style="background:#f8fafc;padding:13px 16px;border-bottom:1px solid #dbe3ef;">
                                        <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;">Record Summary</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="font-size:14px;line-height:1.55;">
                                            <tr>
                                                <td style="padding:6px 0;color:#64748b;width:160px;">Module</td>
                                                <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $moduleName }}</td>
                                            </tr>
                                            @if(filled($recordTitle))
                                                <tr>
                                                    <td style="padding:6px 0;color:#64748b;">Record</td>
                                                    <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $recordTitle }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($actorName))
                                                <tr>
                                                    <td style="padding:6px 0;color:#64748b;">Submitted / Updated By</td>
                                                    <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $actorName }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($reviewNote))
                                                <tr>
                                                    <td style="padding:6px 0;color:#64748b;vertical-align:top;">Review Note</td>
                                                    <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $reviewNote }}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if(filled($url))
                                <table cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 20px;">
                                    <tr>
                                        <td>
                                            <a href="{{ $url }}" style="display:inline-block;background:#102d79;color:#ffffff;text-decoration:none;border-radius:9px;padding:12px 18px;font-size:14px;font-weight:700;">
                                                {{ $buttonLabel }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">For concerns, please contact the assigned JK&amp;C department.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc;border-top:1px solid #dbe3ef;padding:16px 28px;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">
                                This is an automated notification from the JK&amp;C ORDO system.
                            </p>
                            <p style="margin:6px 0 0;font-size:12px;line-height:1.6;color:#94a3b8;">
                                &copy; {{ date('Y') }} John Kelly &amp; Company. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
