<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $level }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial, Helvetica, sans-serif;color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:24px 28px;background:#1d4ed8;color:#ffffff;">
                            <h1 style="margin:0;font-size:20px;line-height:1.3;">Corporate Correspondence Approval</h1>
                            <p style="margin:6px 0 0;font-size:14px;">{{ $level }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:26px 28px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                A corporate correspondence has been submitted and requires your review.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:18px 0;border:1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;width:180px;">Reference No.</td>
                                    <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $correspondence->ref_no ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;">Type</td>
                                    <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $correspondence->type ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;">Company</td>
                                    <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $correspondence->company_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;">Subject</td>
                                    <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $correspondence->subject ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;">From</td>
                                    <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $correspondence->from_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px;background:#f9fafb;font-weight:bold;">{{ $correspondence->to_for_label ?? 'To' }}</td>
                                    <td style="padding:10px 12px;">{{ $correspondence->to_for ?? 'N/A' }}</td>
                                </tr>
                            </table>

                            <p style="margin:22px 0;font-size:15px;line-height:1.6;">
                                Please open the correspondence dashboard to review and take action.
                            </p>

                            <p style="margin:24px 0;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:bold;">
                                    Review Correspondence
                                </a>
                            </p>

                            <p style="margin:24px 0 0;font-size:12px;line-height:1.6;color:#6b7280;">
                                This is an automated notification from JKNC Portal.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
