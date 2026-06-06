<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Your ORDO Password</title>
</head>
<body style="margin:0;padding:0;background:#f1f2f2;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f2f2;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:94%;background:#ffffff;border:1px solid #dbe3f0;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(16,45,121,0.10);">
                    <tr>
                        <td align="center" style="padding:34px 32px 22px;background:#ffffff;">
                            <img
                                src="{{ asset('images/imaglogo.png') }}"
                                alt="John Kelly & Company"
                                style="display:block;max-width:210px;height:auto;margin:0 auto 18px;"
                            >

                            <div style="display:inline-block;background:#eef4ff;border:1px solid #cfe0ff;color:#1d54e2;border-radius:999px;padding:6px 12px;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;">
                                ORDO Account Security
                            </div>

                            <h1 style="margin:18px 0 8px;font-size:26px;line-height:1.25;color:#102d79;font-weight:800;">
                                Reset Your Password
                            </h1>

                            <p style="margin:0;color:#64748b;font-size:14px;line-height:1.6;">
                                A secure password reset was requested for your ORDO account.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 38px 10px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#334155;">
                                Hello {{ $user->name ?? 'there' }},
                            </p>

                            <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#334155;">
                                We received a request to reset the password for your JK&C ORDO account. Click the button below to create a new password.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $resetUrl }}"
                                           style="display:inline-block;background:#1d54e2;color:#ffffff;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px;font-weight:700;box-shadow:0 10px 22px rgba(29,84,226,0.22);">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin:24px 0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:16px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;padding-bottom:6px;">
                                            Registered Email
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:14px;color:#0f172a;font-weight:700;padding-bottom:14px;">
                                            {{ $user->email ?? 'N/A' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;padding-bottom:6px;">
                                            Link Expiration
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size:14px;color:#0f172a;font-weight:700;">
                                            {{ $expiration }} minutes
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin:0 0 16px;font-size:13px;line-height:1.7;color:#64748b;">
                                If the button does not work, copy and paste this secure link into your browser:
                            </p>

                            <p style="margin:0 0 22px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;font-size:12px;line-height:1.5;color:#334155;word-break:break-all;">
                                {{ $resetUrl }}
                            </p>

                            <div style="margin:20px 0;background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:16px;">
                                <p style="margin:0;font-size:13px;line-height:1.7;color:#9a3412;">
                                    <strong>Security Notice:</strong> If you did not request this password reset, no action is required. Your password will remain unchanged. Please report suspicious activity to your system administrator.
                                </p>
                            </div>

                            <p style="margin:24px 0 0;font-size:14px;line-height:1.7;color:#334155;">
                                Regards,<br>
                                <strong style="color:#102d79;">John Kelly &amp; Company</strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:22px 32px 30px;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#94a3b8;">
                                This is an automated message from ORDO. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:18px 0 0;font-size:11px;color:#94a3b8;">
                    © {{ date('Y') }} John Kelly &amp; Company. All rights reserved.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
