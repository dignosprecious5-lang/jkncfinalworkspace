<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Town Hall Approval Request</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:24px 28px;border-bottom:1px solid #e5e7eb;">
                            <h2 style="margin:0;font-size:20px;color:#111827;">Town Hall Communication Approval Request</h2>
                            <p style="margin:6px 0 0 0;font-size:14px;color:#6b7280;">
                                {{ $levelLabel }} approval is pending for this communication.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 28px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;width:190px;">Reference Number</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $communication->ref_no }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Subject</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $communication->subject ?: 'No Subject' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Requestor Name</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $communication->from_name ?: ($communication->uploader->name ?? 'Unknown') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:#6b7280;">Approval Level</td>
                                    <td style="padding:8px 0;font-weight:bold;color:#111827;">{{ $levelLabel }}</td>
                                </tr>
                            </table>

                            <div style="margin-top:24px;display:flex;gap:10px;flex-wrap:wrap;">
                                <a href="{{ $approvalPageUrl }}"
                                   style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-size:14px;font-weight:bold;margin-right:8px;">
                                    View Communication
                                </a>

                                <a href="{{ $approveUrl }}"
                                   style="display:inline-block;background:#16a34a;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-size:14px;font-weight:bold;margin-right:8px;">
                                    Approve
                                </a>

                                <a href="{{ $rejectUrl }}"
                                   style="display:inline-block;background:#dc2626;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-size:14px;font-weight:bold;">
                                    Reject
                                </a>
                            </div>

                            <p style="margin:22px 0 0 0;font-size:13px;color:#6b7280;line-height:1.5;">
                                The PDF version of the communication is attached. You may open the approval page, or use the signed approval/rejection buttons above.
                            </p>

                            <p style="margin:12px 0 0 0;font-size:12px;color:#9ca3af;line-height:1.5;">
                                Direct approval page link:<br>
                                <a href="{{ $approvalPageUrl }}" style="color:#2563eb;">{{ $approvalPageUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">
                            This is an automated approval notification from the JK&amp;C Town Hall system.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
