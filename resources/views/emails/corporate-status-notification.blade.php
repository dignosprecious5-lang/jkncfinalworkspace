<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corporate Submission Update</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    @php
        $logoUrl = rtrim((string) config('app.url'), '/') . '/images/imaglogo.png';
        [$badgeBg, $badgeColor] = match ($decision) {
            'Approved' => ['#dcfce7', '#15803d'],
            'Needs Revision' => ['#fef3c7', '#b45309'],
            'Rejected' => ['#fee2e2', '#b91c1c'],
            default => ['#dbeafe', '#1d4ed8'],
        };
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#eef2f7;padding:28px 12px;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" role="presentation" style="max-width:640px;width:100%;background:#ffffff;border:1px solid #dbe3ef;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#102d79;padding:22px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td>
                                        <img src="{{ $logoUrl }}" alt="John Kelly &amp; Company" style="display:block;width:160px;max-width:100%;height:auto;">
                                    </td>
                                    <td align="right">
                                        <span style="display:inline-block;border:1px solid rgba(255,255,255,.35);border-radius:999px;padding:7px 12px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#ffffff;">
                                            Corporate
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <span style="display:inline-block;background:{{ $badgeBg }};color:{{ $badgeColor }};border-radius:999px;padding:7px 12px;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;">
                                {{ $decision }}
                            </span>

                            <h1 style="margin:18px 0 10px;font-size:24px;line-height:1.25;color:#102d79;">Corporate Submission Update</h1>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#334155;">
                                Hi {{ $employeeName }}, your corporate submission has been reviewed.
                            </p>

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
                                                <td style="padding:6px 0;color:#64748b;width:170px;">Module</td>
                                                <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $moduleName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#64748b;">Corporation Name</td>
                                                <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $corporationName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#64748b;">Company Reg No.</td>
                                                <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $companyRegNo ?: 'N/A' }}</td>
                                            </tr>
                                            @if(!empty($reviewNote))
                                                <tr>
                                                    <td style="padding:6px 0;color:#64748b;vertical-align:top;">Review Note</td>
                                                    <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $reviewNote }}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if($decision === 'Approved')
                                <p style="margin:0;font-size:14px;line-height:1.7;color:#334155;">Your submission has been approved successfully.</p>
                            @elseif($decision === 'Needs Revision')
                                <p style="margin:0;font-size:14px;line-height:1.7;color:#334155;">Your submission needs revision. Please check the note above, update the record, and resubmit.</p>
                            @elseif($decision === 'Rejected')
                                <p style="margin:0;font-size:14px;line-height:1.7;color:#334155;">Your submission has been rejected. Please review the note above for more details.</p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc;border-top:1px solid #dbe3ef;padding:16px 28px;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;">This is an automated notification from the JK&amp;C ORDO system.</p>
                            <p style="margin:6px 0 0;font-size:12px;line-height:1.6;color:#94a3b8;">&copy; {{ date('Y') }} John Kelly &amp; Company. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
