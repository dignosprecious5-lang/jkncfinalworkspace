<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ORDO Account Created</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:24px 28px;background:#1d4ed8;color:#ffffff;">
                        <h1 style="margin:0;font-size:20px;">Your ORDO account has been created</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:26px 28px;">
                        <p style="font-size:15px;line-height:1.6;">Hello {{ $user->name }},</p>
                        <p style="font-size:15px;line-height:1.6;">Your ORDO account has been created. Use the temporary password below to log in. You will be required to change it immediately on first login.</p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:18px 0;border:1px solid #e5e7eb;">
                            <tr>
                                <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;width:190px;">Login URL</td>
                                <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;"><a href="{{ $loginUrl }}">{{ $loginUrl }}</a></td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-weight:bold;">Registered Email</td>
                                <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">{{ $user->email }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 12px;background:#f9fafb;font-weight:bold;">Temporary Password</td>
                                <td style="padding:10px 12px;font-family:monospace;font-size:16px;">{{ $temporaryPassword }}</td>
                            </tr>
                        </table>

                        <p style="font-size:14px;line-height:1.6;color:#b45309;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px;">
                            Security Notice: This is a temporary password. Please do not share it. You must create a new password before accessing any ORDO module.
                        </p>

                        <p style="margin-top:24px;">
                            <a href="{{ $loginUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:bold;">Log in to ORDO</a>
                        </p>

                        <p style="margin-top:24px;font-size:12px;color:#6b7280;">This is an automated email from ORDO.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
