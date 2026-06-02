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
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .content {
            padding: 40px 30px;
        }
        .content p {
            margin-bottom: 25px;
            font-size: 16px;
            color: #4b5563;
        }
        .content .name {
            font-weight: 800;
            color: #1d4ed8;
        }
        .btn-container {
            text-align: center;
            margin: 35px 0;
        }
        .btn {
            background-color: #2563eb;
            color: #ffffff;
            padding: 18px 35px;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            display: inline-block;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
        .footer {
            background: #f9fafb;
            padding: 25px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
        }
        .test-box {
            background: #eff6ff;
            border: 2px dashed #bfdbfe;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .test-label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            color: #3b82f6;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        .test-type {
            font-size: 18px;
            font-weight: 900;
            color: #1e3a8a;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Assessment Invitation</h1>
        </div>
        <div class="content">
            <p>Dear <span class="name">{{ $candidateName }}</span>,</p>
            
            <p>As part of our recruitment process at John Kelly & Company, we would like to invite you to take the following assessment:</p>
            
            <div class="test-box">
                <span class="test-label">Assessment Type</span>
                <span class="test-type">{{ $testType }}</span>
            </div>
            
            <p>Please click the button below to start your assessment. Ensure you have a stable internet connection and a quiet environment before beginning.</p>
            
            <div class="btn-container">
                <a href="{{ $testUrl }}" class="btn" style="color: #ffffff !important;">Start Assessment Test</a>
            </div>
            
            <p>The link will remain active for the next 48 hours. For concerns, please contact the Finance Department of JK&amp;C Inc.</p>
            
            <p>Good luck!</p>
            
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
