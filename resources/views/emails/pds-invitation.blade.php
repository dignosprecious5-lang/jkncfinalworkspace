<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PDS Form Invitation</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .container {
            max-width: 640px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
        }

        .header {
            background: linear-gradient(135deg, #2563eb, #16a34a);
            color: white;
            padding: 32px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .content {
            padding: 32px;
            line-height: 1.6;
        }

        .box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 18px;
            margin: 18px 0;
        }

        .label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .value {
            font-size: 15px;
            color: #111827;
            font-weight: bold;
            margin-bottom: 14px;
        }

        .btn-wrap {
            text-align: center;
            margin: 28px 0;
        }

        .btn {
            display: inline-block;
            background: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 26px;
            border-radius: 999px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 13px;
        }

        .note {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            border-radius: 12px;
            padding: 14px;
            font-size: 13px;
        }

        .footer {
            background: #f9fafb;
            padding: 22px 32px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PDS Form</h1>
            <p style="margin: 8px 0 0;">John Kelly &amp; Company (JK&amp;C Inc.)</p>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $jobOffer->name }}</strong>,</p>

            <p>
                Thank you for accepting the job offer for <strong>{{ $jobOffer->position }}</strong>.
                To continue your onboarding process, please complete your Personal Data Sheet (PDS).
            </p>

            <div class="box">
                <div class="label">Position</div>
                <div class="value">{{ $jobOffer->position }}</div>

                <div class="label">Department</div>
                <div class="value">{{ $jobOffer->department ?? 'N/A' }}</div>
            </div>

            <div class="btn-wrap">
                <a href="{{ $pdsUrl }}" class="btn">Fill Out PDS Form</a>
            </div>

            <div class="note">
                Please complete the form carefully. The Human Capital team will review your submitted information before the next onboarding step.
            </div>

            <p style="margin-top: 24px;">
                Best regards,<br>
                <strong>Human Capital Team</strong><br>
                John Kelly &amp; Company (JK&amp;C Inc.)
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} John Kelly &amp; Company (JK&amp;C Inc.). This is an automated message.
        </div>
    </div>
</body>
</html>
