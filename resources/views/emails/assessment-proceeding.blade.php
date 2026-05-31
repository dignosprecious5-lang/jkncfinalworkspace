<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f7f9;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header {
            background: linear-gradient(135deg, #0d9488 0%, #059669 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .content {
            padding: 40px 30px;
        }
        .content p {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .content .name {
            font-weight: bold;
            color: #0d9488;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #edf2f7;
        }
        .highlight {
            background: #f0fdfa;
            border-left: 4px solid #0d9488;
            padding: 15px;
            margin: 20px 0;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Application Update</h1>
        </div>
        <div class="content">
            <p>Dear <span class="name">{{ $candidateName }}</span>,</p>
            
            <p>We are pleased to inform you that your application for the position of <strong>{{ $position }}</strong> at John Kelly & Company has successfully passed our initial screening.</p>
            
            <div class="highlight">
                "We are now inviting you to proceed to the <strong>Assessment Stage</strong> of our recruitment process."
            </div>
            
            <p>Our recruitment team will be in touch with you shortly to provide the details regarding the assessment, including the schedule and necessary instructions.</p>
            
            <p>Thank you for your interest in joining our team. We look forward to seeing your performance in the next stage.</p>
            
            <p>Best regards,<br>
            <strong>Human Capital Team</strong><br>
            John Kelly & Company</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} John Kelly & Company. All rights reserved.<br>
            For concerns, please contact the Finance Department of JK&amp;C Inc.
        </div>
    </div>
</body>
</html>
