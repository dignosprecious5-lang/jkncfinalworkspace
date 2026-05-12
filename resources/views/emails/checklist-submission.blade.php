<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pre-employment Requirements</title>
    <style>
        body { margin:0; padding:0; background:#f3f4f6; font-family:Arial,sans-serif; color:#111827; }
        .container { max-width:640px; margin:30px auto; background:#fff; border-radius:16px; overflow:hidden; border:1px solid #e5e7eb; box-shadow:0 10px 25px rgba(0,0,0,.06); }
        .header { background:linear-gradient(135deg,#2563eb,#16a34a); color:#fff; padding:32px; text-align:center; }
        .header h1 { margin:0; font-size:24px; letter-spacing:1px; text-transform:uppercase; }
        .content { padding:32px; line-height:1.6; }
        .box { background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:18px; margin:18px 0; }
        .btn-wrap { text-align:center; margin:28px 0; }
        .btn { display:inline-block; background:#2563eb; color:#fff!important; text-decoration:none; padding:15px 26px; border-radius:999px; font-weight:800; text-transform:uppercase; letter-spacing:1px; font-size:13px; }
        .footer { background:#f9fafb; padding:22px 32px; font-size:12px; color:#6b7280; text-align:center; border-top:1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pre-employment Requirements</h1>
            <p style="margin:8px 0 0;">John Kelly & Company</p>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $checklist->employee_name }}</strong>,</p>

            <p>
                Thank you for submitting your Personal Data Sheet. To continue your onboarding process,
                please upload your pre-employment requirements using the secure link below.
            </p>

            <div class="box">
                <p><strong>Position:</strong> {{ $checklist->position ?? 'N/A' }}</p>
                <p><strong>Status:</strong> {{ $checklist->status }}</p>
            </div>

            <div class="btn-wrap">
                <a href="{{ $uploadUrl }}" class="btn">Upload Requirements</a>
            </div>

            <p>
                Please prepare files such as valid ID, birth certificate, government numbers/documents,
                NBI clearance, medical certificate, transcript/diploma, signed job offer, and 2x2 picture.
            </p>

            <p>
                Best regards,<br>
                <strong>Human Capital Team</strong><br>
                John Kelly & Company
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} John Kelly & Company. This is an automated message.
        </div>
    </div>
</body>
</html>
