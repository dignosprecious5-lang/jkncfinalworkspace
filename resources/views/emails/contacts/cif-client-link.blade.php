<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Information Form</title>
</head>
<body style="margin:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <div style="width:100%;background:#f3f6fb;padding:32px 16px;">
        <div style="max-width:820px;margin:0 auto;background:#ffffff;border-radius:0;overflow:hidden;box-shadow:0 18px 50px rgba(15,23,42,.08);">
            <div style="height:6px;background:#21409a;"></div>
            <div style="padding:34px 22px 14px;">
                <div style="margin:0 0 28px;">
                    <p style="margin:0;font-size:28px;line-height:1.1;font-weight:700;color:#0f172a;">John Kelly &amp; Company</p>
                </div>
                <p style="margin:0 0 28px;font-size:18px;line-height:1.7;color:#1f2937;">Dear <strong>{{ $clientName }}</strong>,</p>
                <p style="margin:0 0 28px;font-size:18px;line-height:1.7;color:#1f2937;">Good day.</p>
                <p style="margin:0 0 34px;font-size:18px;line-height:1.7;color:#1f2937;">
                    To get things started smoothly, we kindly ask you to complete your <strong>Client Information Form (CIF).</strong>
                </p>

                <a href="{{ $clientUrl }}" style="display:inline-block;background:#3153d4;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;line-height:1;padding:18px 32px;border-radius:12px;">
                    Complete Client Information Form
                </a>

                <div style="margin-top:36px;border:1px solid #dbe4f0;background:#f8fbff;border-radius:16px;padding:24px 28px;">
                    <p style="margin:0 0 14px;font-size:16px;font-weight:700;color:#111827;">Important</p>
                    <p style="margin:0;font-size:16px;line-height:1.9;color:#475569;">
                        The form is mobile-friendly and can be completed using your phone or computer. You will also be asked to upload the required supporting documents below the form. Foreign or dual-citizen clients will also see additional document requirements.
                    </p>
                </div>

                <p style="margin:28px 0 0;font-size:13px;line-height:1.8;color:#64748b;">
                    This secure link is active until <strong>{{ $expiresAt->format('F j, Y g:i A') }}</strong>.
                </p>
                <div style="margin:20px 0 0;font-size:13px;line-height:1.8;color:#64748b;">
                    <p style="margin:0 0 8px;">ordo.jknc.io is the official system of John Kelly &amp; Company.</p>
                    <p style="margin:0 0 8px;">This is a system-generated message. Do not reply to this email.</p>
                    <p style="margin:0;">For assistance, please contact your assigned JK&amp;C Associate or Consultant.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
