<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4 portrait; margin: 20mm; }
        body {
            color: #111827;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 12pt;
            line-height: 1.65;
        }
        .header {
            border-bottom: 2px solid #1d4ed8;
            margin-bottom: 34px;
            padding-bottom: 18px;
            text-align: center;
        }
        .logo {
            display: block;
            max-height: 90px;
            max-width: 190px;
            margin: 0 auto 10px;
            object-fit: contain;
        }
        .company-name {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .company-address {
            font-size: 10.5pt;
            line-height: 1.35;
            margin-top: 3px;
        }
        .title {
            font-size: 16pt;
            font-weight: 700;
            letter-spacing: .08em;
            margin: 30px 0;
            text-align: center;
            text-transform: uppercase;
        }
        .body p {
            margin: 0 0 18px;
            text-align: justify;
        }
        .meta {
            margin-top: 24px;
        }
        .approval {
            margin-top: 42px;
            line-height: 1.4;
        }
        .notice {
            font-size: 9.5pt;
            line-height: 1.45;
            margin-top: 38px;
            text-align: justify;
        }
        .generated {
            font-size: 10pt;
            font-weight: 700;
            margin-top: 24px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $coe['logo_url'] }}" class="logo" alt="Company Logo">
        <div class="company-name">{{ $coe['company_name'] }}</div>
        <div class="company-address">{{ $coe['company_address'] }}</div>
    </div>

    <div class="title">Certificate of Employment</div>

    <div class="body">
        <p>
            This is to certify that {{ $coe['employee_name'] }} is/was employed with {{ $coe['company_name'] }}
            as {{ $coe['position'] }} under {{ $coe['department'] }} from {{ $coe['start_date'] }} to {{ $coe['end_date'] }}.
        </p>

        <p>
            Based on company records, the employee receives/received a monthly basic salary of {{ $coe['monthly_basic_salary'] }},
            exclusive of incentives, allowances, benefits, and other compensation that may be reflected in the employee's payslip,
            and subject to applicable deductions, taxes, and company policies.
        </p>

        <p>
            This certification is issued upon the request of the employee for {{ $coe['purpose'] }}.
        </p>

        <p>
            Issued on {{ $coe['date_issued'] }} at {{ $coe['company_address'] }}, Philippines.
        </p>
    </div>

    <div class="meta">
        <strong>Certificate No.:</strong> {{ $coe['coe_number'] }}
    </div>

    <div class="approval">
        <strong>Approved By:</strong><br>
        {{ $coe['approver_name'] }}<br>
        Human Capital<br>
        Approved on: {{ $coe['date_approved'] }}
    </div>

    <div class="notice">
        This certificate discloses only information allowed by law and company policy. It is subject to applicable data privacy
        requirements. Unauthorized access, use, disclosure, reproduction, or alteration of this certificate is strictly prohibited.
    </div>

    <div class="generated">
        This is a computer-generated Certificate of Employment. No signature is required.
    </div>
</body>
</html>
