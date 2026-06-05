<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOW Report Approved</title>
</head>
<body style="margin:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <div style="width:100%;background:#f3f6fb;padding:32px 16px;">
        <div style="max-width:820px;margin:0 auto;background:#ffffff;border-radius:0;overflow:hidden;box-shadow:0 18px 50px rgba(15,23,42,.08);">
            <div style="height:6px;background:#10b981;"></div>
            <div style="padding:34px 22px 14px;">
                <div style="margin:0 0 28px;">
                    <p style="margin:0;font-size:28px;line-height:1.1;font-weight:700;color:#0f172a;">John Kelly &amp; Company</p>
                </div>
                <p style="margin:0 0 28px;font-size:18px;line-height:1.7;color:#1f2937;">Hello,</p>
                <p style="margin:0 0 34px;font-size:18px;line-height:1.7;color:#1f2937;">
                    The client has reviewed and approved the <strong>Scope of Work Report</strong> for <strong>{{ $project->name }}</strong>.
                </p>

                <div style="margin-top:36px;border:1px solid #dbe4f0;background:#f8fbff;border-radius:16px;padding:24px 28px;">
                    <p style="margin:0 0 14px;font-size:16px;font-weight:700;color:#111827;">Approval Details</p>
                    <table style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="padding:8px 0;font-size:15px;color:#475569;font-weight:700;width:140px;">Report No.</td>
                            <td style="padding:8px 0;font-size:15px;color:#0f172a;">{{ $report->report_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-size:15px;color:#475569;font-weight:700;">Approved By</td>
                            <td style="padding:8px 0;font-size:15px;color:#0f172a;">{{ $report->client_approved_name }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-size:15px;color:#475569;font-weight:700;">Approved At</td>
                            <td style="padding:8px 0;font-size:15px;color:#0f172a;">{{ $report->client_approved_at ? $report->client_approved_at->format('M d, Y h:i A') : '-' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-size:15px;color:#475569;font-weight:700;vertical-align:top;">Client Notes</td>
                            <td style="padding:8px 0;font-size:15px;color:#0f172a;white-space:pre-wrap;">{{ $report->client_response_notes ?: 'None' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-size:15px;color:#475569;font-weight:700;">Attachment</td>
                            <td style="padding:8px 0;font-size:15px;color:#0f172a;">{{ $report->client_attachment_path ? 'Uploaded' : 'None' }}</td>
                        </tr>
                    </table>
                </div>

                <a href="{{ $internalUrl }}" style="display:inline-block;margin-top:28px;background:#3153d4;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;line-height:1;padding:18px 32px;border-radius:12px;">
                    View SOW Report
                </a>

                <div style="margin:36px 0 0;font-size:13px;line-height:1.8;color:#64748b;">
                    <p style="margin:0 0 8px;">ordo.jknc.io is the official system of John Kelly &amp; Company.</p>
                    <p style="margin:0 0 8px;">This is a system-generated notification.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
