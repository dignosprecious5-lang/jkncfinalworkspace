<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Interview Schedule</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            color: #111827;
        }
        .container {
            max-width: 620px;
            margin: 28px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .header {
            background: linear-gradient(135deg, #4f46e5, #2563eb);
            padding: 32px;
            color: #ffffff;
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
        .detail-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
            margin: 22px 0;
        }
        .row {
            margin-bottom: 14px;
        }
        .label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }
        .value {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: #ffffff !important;
            padding: 14px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer {
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 20px 32px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
        .link {
            word-break: break-all;
            color: #2563eb;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Interview Schedule</h1>
            <p style="margin: 8px 0 0; color: #dbeafe;">John Kelly & Company</p>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $interview->name }}</strong>,</p>

            <p>
                Congratulations on passing the assessment stage. You are now scheduled for an interview.
                Please review your interview details below.
            </p>

            <div class="detail-box">
                <div class="row">
                    <span class="label">Position</span>
                    <span class="value">{{ $interview->position }}</span>
                </div>

                <div class="row">
                    <span class="label">Interview Type</span>
                    <span class="value">{{ $interview->type ?? $interview->round }}</span>
                </div>

                <div class="row">
                    <span class="label">Date & Time</span>
                    <span class="value">
                        {{ optional($interview->interview_date)->format('F d, Y h:i A') ?? $interview->interview_date }}
                    </span>
                </div>

                <div class="row">
                    <span class="label">Duration</span>
                    <span class="value">{{ $interview->duration ?? 60 }} minutes</span>
                </div>

                <div class="row">
                    <span class="label">Interviewer</span>
                    <span class="value">{{ $interview->interviewer }}</span>
                </div>

                @if($interview->meeting_link)
                    <div class="row">
                        <span class="label">Meeting Link</span>
                        <a class="link" href="{{ $interview->meeting_link }}">{{ $interview->meeting_link }}</a>
                    </div>
                @endif
            </div>

            @if($interview->meeting_link)
                <p style="text-align:center; margin: 30px 0;">
                    <a href="{{ $interview->meeting_link }}" class="btn">Join Interview</a>
                </p>
            @else
                <p>
                    This is an in-person interview. Please proceed to the assigned office or coordinate with the Human Capital team for the exact location.
                </p>
            @endif

            <p>
                Please be available at the scheduled time. If you need to reschedule, contact the Human Capital team as soon as possible.
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
