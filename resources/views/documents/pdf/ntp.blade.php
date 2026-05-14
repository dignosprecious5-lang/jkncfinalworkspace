<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $ntp['title'] ?? 'Notice To Proceed' }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm; }
        body {
            font-family: Georgia, "Times New Roman", serif;
            color: #000;
            margin: 0;
            font-size: 11pt;
            line-height: 1.25;
        }
        .doc {
            max-width: 100%;
        }
        .center { text-align: center; }
        .title {
            font-weight: 700;
            font-size: 18pt;
            line-height: 1.05;
            margin-top: 4px;
        }
        .code {
            font-weight: 700;
            font-size: 8pt;
            margin-bottom: 26px;
        }
        .issued {
            margin: 18px 0 12px;
            font-weight: 700;
            font-size: 12pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .meta td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 12pt;
            font-weight: 700;
        }
        .label {
            display: inline-block;
            min-width: 0;
        }
        .value {
            font-weight: 400;
        }
        .body-copy {
            margin-top: 22px;
            font-size: 11pt;
            font-family: Arial, Helvetica, sans-serif;
        }
        .body-copy p {
            margin: 0 0 18px;
            text-align: justify;
        }
        .body-copy strong {
            font-weight: 700;
        }
        .signatures {
            margin-top: 34px;
        }
        .signatures td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }
        .sign-head {
            font-weight: 700;
            font-size: 12pt;
            min-height: 20px;
        }
        .sign-box {
            height: 92px;
            text-align: center;
            vertical-align: middle;
            font-weight: 700;
            font-size: 11pt;
        }
        .sign-sub {
            display: block;
            font-weight: 700;
        }
        .footer {
            margin-top: 20px;
            font-size: 6.5pt;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="doc">
        <div class="center">
            <div class="title">{{ $ntp['title'] ?? 'NOTICE TO PROCEED' }}</div>
            <div class="code">{{ str_contains(strtolower((string) ($ntp['engagement_type'] ?? '')), 'regular') ? 'REG-F-002' : 'PROJ-F-003' }}</div>
        </div>

        <div class="issued">Date Issued: <span class="value">{{ $ntp['date_issued'] ?? '-' }}</span></div>

        <table class="meta">
            <tr>
                <td><span class="label">NTP No.:</span> <span class="value">{{ $ntp['ntp_no'] ?? '-' }}</span></td>
                <td><span class="label">Engagement Type:</span> <span class="value">{{ $ntp['engagement_type'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Condeal Reference No.:</span> <span class="value">{{ $ntp['condeal_reference_no'] ?? '-' }}</span></td>
                <td><span class="label">Client Name:</span> <span class="value">{{ $ntp['client_name'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Business Name:</span> <span class="value">{{ $ntp['business_name'] ?? '-' }}</span></td>
                <td><span class="label">{{ $ntp['engagement_reference_label'] ?? 'RSAT / SOW Ref No.' }}</span> <span class="value">{{ $ntp['engagement_reference_no'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Approved Start Date:</span> <span class="value">{{ $ntp['approved_start_date'] ?? '-' }}</span></td>
                <td><span class="label">Target Completion Date:</span> <span class="value">{{ $ntp['target_completion_date'] ?? '-' }}</span></td>
            </tr>
        </table>

        <div class="body-copy">
            <p>This Notice to Proceed confirms that the Client has reviewed the engagement details and hereby authorizes <strong>John Kelly &amp; Company (JK&amp;C Inc.)</strong> to commence the agreed services under the approved scope, timelines, deliverables, and commercial terms.</p>
            <p>The Client acknowledges that work may officially begin upon execution of this document and that all services rendered thereafter shall be deemed duly authorized.</p>
            <p>Any additional requests, changes in scope, or delays requiring client action may be subject to separate confirmation, timeline adjustment, or corresponding charges, where applicable.</p>
            <p>I, the undersigned Client and/or duly authorized representative, hereby confirm approval and authorize <strong>John Kelly &amp; Company (JK&amp;C Inc.)</strong> to proceed with the commencement of the engagement stated above.</p>
        </div>

        <table class="signatures">
            <tr>
                <td><div class="sign-head">FOR THE CLIENT</div></td>
                <td><div class="sign-head">FOR JOHN KELLY &amp; COMPANY</div></td>
            </tr>
            <tr>
                <td class="sign-box" rowspan="3">
                    <span class="sign-sub">Name, Signature and Date</span>
                    Authorized Representative<br>
                    Name/Client
                </td>
                <td class="sign-box">
                    <span class="sign-sub">Name, Signature and Date</span>
                    Lead Consultant
                    <div style="margin-top: 8px; font-weight: 400;">{{ $ntp['lead_consultant'] ?? '' }}</div>
                </td>
            </tr>
            <tr>
                <td class="sign-box"></td>
            </tr>
            <tr>
                <td class="sign-box">
                    <span class="sign-sub">Name, Signature and Date</span>
                    Associate
                    <div style="margin-top: 8px; font-weight: 400;">{{ $ntp['associate'] ?? '' }}</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            John Kelly &amp; Company<br>
            3F, Cebu Holdings Center, Cebu Business Park, Cebu City, Philippines 6000<br>
            Email: start@jknc.io • Website: jknc.io • Phone: 0995-535-8729
        </div>
    </div>
</body>
</html>
