<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Communication Acknowledged</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="680" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:24px 28px;border-bottom:1px solid #e5e7eb;">
                            <h2 style="margin:0;font-size:20px;color:#111827;">Communication Acknowledged</h2>
                            <p style="margin:6px 0 0 0;font-size:14px;color:#6b7280;">
                                A recipient has acknowledged a TownHall communication.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 28px;">
                            <h3 style="margin:0 0 12px 0;font-size:15px;color:#111827;">Communication Details</h3>

                            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin-bottom:22px;">
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;width:230px;">Reference Number</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $communication->ref_no }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Title / Subject</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $communication->subject ?: 'No Subject' }}</td>
                                </tr>
                            </table>

                            <h3 style="margin:0 0 12px 0;font-size:15px;color:#111827;">Acknowledgment Details</h3>

                            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin-bottom:22px;">
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;width:230px;">Recipient Name</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $acknowledgement->recipient_name ?: optional($acknowledgement->user)->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Position</td>
                                    <td style="padding:8px 0;color:#111827;">{{ $acknowledgement->recipient_position ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Department</td>
                                    <td style="padding:8px 0;color:#111827;">{{ $acknowledgement->recipient_department ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Date and Time Acknowledged</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">
                                        {{ $acknowledgement->acknowledged_at ? \Carbon\Carbon::parse($acknowledgement->acknowledged_at)->format('F d, Y h:i A') : '-' }}
                                    </td>
                                </tr>
                            </table>

                            <h3 style="margin:0 0 12px 0;font-size:15px;color:#111827;">Acknowledgment Progress</h3>

                            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin-bottom:22px;">
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;width:230px;">Required Recipients</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $summary['required_count'] ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Acknowledged</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#16a34a;">{{ $summary['acknowledged_count'] ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Pending</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#dc2626;">{{ $summary['pending_count'] ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Current Percentage</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#2563eb;">{{ $summary['percentage'] ?? 0 }}%</td>
                                </tr>
                            </table>

                            <div style="margin-top:24px;">
                                <a href="{{ route('admin.townhall.acknowledgement-report', ['communication_id' => $communication->id]) }}"
                                   style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-size:14px;font-weight:bold;">
                                    View Acknowledgment Report
                                </a>
                            </div>

                            <p style="margin:22px 0 0 0;font-size:13px;color:#6b7280;line-height:1.5;">
                                This notification helps the preparer and approvers monitor acknowledgment progress.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">
                            This is an automated notification from the JK&amp;C TownHall system.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
