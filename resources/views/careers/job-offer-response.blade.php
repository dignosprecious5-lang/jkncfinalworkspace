<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: 100%;
            max-width: 560px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .header {
            padding: 34px;
            color: white;
            text-align: center;
            background: {{ $decision === 'accepted' ? '#16a34a' : '#dc2626' }};
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content {
            padding: 32px;
            text-align: center;
        }

        .details {
            margin-top: 22px;
            text-align: left;
            background: #f9fafb;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 18px;
            font-size: 14px;
        }

        .label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 12px;
        }

        .value {
            font-size: 15px;
            font-weight: 700;
            margin-top: 3px;
        }

        .footer {
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>{{ $title }}</h1>
        </div>

        <div class="content">
            <p>{{ $message }}</p>

            <div class="details">
                <div class="label">Applicant</div>
                <div class="value">{{ $jobOffer->name }}</div>

                <div class="label">Position</div>
                <div class="value">{{ $jobOffer->position }}</div>

                <div class="label">Current Status</div>
                <div class="value">{{ $jobOffer->status }}</div>
            </div>
        </div>

        <div class="footer">
            John Kelly & Company Human Capital
        </div>
    </div>
</body>
</html>
