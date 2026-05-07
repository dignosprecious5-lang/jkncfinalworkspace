<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Job Offer</title>
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
            background: linear-gradient(135deg, #7c3aed, #2563eb);
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
            <h1>Job Offer</h1>
            <p style="margin: 8px 0 0;">John Kelly & Company</p>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $jobOffer->name }}</strong>,</p>

            <p>
                Congratulations. We are pleased to extend a job offer to you for the position below.
            </p>

            <div class="box">
                <div class="label">Position</div>
                <div class="value">{{ $jobOffer->position }}</div>

                <div class="label">Department</div>
                <div class="value">{{ $jobOffer->department ?? 'N/A' }}</div>

                <div class="label">Employment Type</div>
                <div class="value">{{ $jobOffer->employment_type ?? 'N/A' }}</div>

                <div class="label">Salary</div>
                <div class="value">{{ $jobOffer->salary ?? 'N/A' }}</div>

                <div class="label">Start Date</div>
                <div class="value">
                    {{ optional($jobOffer->start_date)->format('F d, Y') ?? 'N/A' }}
                </div>

                <div class="label">Company Address</div>
                <div class="value">{{ $jobOffer->company_address ?? 'N/A' }}</div>

                <div class="label">Benefits</div>
                <div class="value">{{ $jobOffer->benefits ?? 'N/A' }}</div>
            </div>

            <p>
                Please wait for the Human Capital team for the next instructions regarding confirmation,
                onboarding, and employment requirements.
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
