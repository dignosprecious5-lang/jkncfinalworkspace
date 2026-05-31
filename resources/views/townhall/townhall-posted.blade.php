<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Posted Communication</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:24px 28px;border-bottom:1px solid #e5e7eb;">
                            <h2 style="margin:0;font-size:20px;color:#111827;">New Town Hall Communication Posted</h2>
                            <p style="margin:6px 0 0 0;font-size:14px;color:#6b7280;">
                                A final approved communication has been posted and is now available for viewing.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;width:180px;">Reference Number</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $communication->ref_no }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Subject</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $communication->subject ?: 'No Subject' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Posted Date and Time</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">
                                        {{ $communication->posted_at ? \Carbon\Carbon::parse($communication->posted_at)->format('F d, Y h:i A') : now()->format('F d, Y h:i A') }}
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-top:24px;">
                                <a href="{{ route('townhall.show', $communication->id) }}"
                                   style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-size:14px;font-weight:bold;">
                                    View Communication
                                </a>
                            </div>

                            <p style="margin:22px 0 0 0;font-size:13px;color:#6b7280;line-height:1.5;">
                                The final approved PDF is attached to this email. All recipients receive the same approved document.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">
                            This is an automated notification from the JK&amp;C Town Hall system.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
