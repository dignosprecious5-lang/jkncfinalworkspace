<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Assistance Request</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:24px 28px;background:#1d4ed8;color:#ffffff;">
                        <h1 style="margin:0;font-size:20px;">Password Assistance Request</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:26px 28px;">
                        <p style="font-size:15px;line-height:1.6;">A password assistance request has been submitted.</p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:18px 0;border:1px solid #e5e7eb;">
                            <tr>
                                <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;width:190px;">Requestor Name</td>
                                <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $assistanceRequest->full_name }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;">Registered Email</td>
                                <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $assistanceRequest->registered_email }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;">Contact Number</td>
                                <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $assistanceRequest->contact_number ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;">Message</td>
                                <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $assistanceRequest->message ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f9fafb;font-weight:bold;">Date and Time Submitted</td>
                                <td style="padding:10px 12px;">{{ optional($assistanceRequest->submitted_at)->format('M d, Y h:i A') }}</td>
                            </tr>
                        </table>

                        <p style="font-size:12px;color:#6b7280;">Please review the account and use the Reset Password action only after verifying the request.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
