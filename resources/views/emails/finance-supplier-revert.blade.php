<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Information Reverted | JK&C INC.</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f8fc; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f8fc; margin:0; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 14px 36px rgba(15, 23, 42, 0.10);">
                    <tr>
                        <td style="background:linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); padding:28px 32px 22px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <img src="{{ asset('images/imaglogo.png') }}" alt="JK&C Inc." style="display:block; width:170px; max-width:100%; height:auto;">
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block; background-color:rgba(255,255,255,0.16); color:#ffffff; border:1px solid rgba(255,255,255,0.24); border-radius:999px; padding:8px 14px; font-size:12px; font-weight:700; letter-spacing:0.04em; text-transform:uppercase;">
                                            Reverted for Revision
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <span style="display:inline-block; background-color:#fef2f2; color:#b91c1c; border-radius:999px; padding:8px 14px; font-size:12px; font-weight:700; letter-spacing:0.04em; text-transform:uppercase;">
                                Supplier Update
                            </span>

                            <h1 style="margin:18px 0 12px; font-size:28px; line-height:1.2; color:#0f172a;">Your supplier form was reverted</h1>
                            <p style="margin:0 0 24px; font-size:16px; line-height:1.7; color:#334155;">
                                Hi {{ $record->data['representative_full_name'] ?? 'Supplier' }}, the supplier form for <strong>{{ $record->record_title ?: 'your business' }}</strong> has been returned for revision.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #f3c7c7; border-radius:16px; overflow:hidden; margin-bottom:22px;">
                                <tr>
                                    <td style="padding:18px 20px; background-color:#fff7f7; border-bottom:1px solid #f3c7c7;">
                                        <p style="margin:0; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#b91c1c;">Revert Details</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:20px;">
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Record:</strong> {{ $record->record_number ?: 'N/A' }} - {{ $record->record_title ?: 'N/A' }}</p>
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Record Date:</strong> {{ optional($record->record_date)->format('Y-m-d') ?: 'N/A' }}</p>
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Attachments:</strong> {{ count((array) ($record->attachments ?? [])) }} file{{ count((array) ($record->attachments ?? [])) === 1 ? '' : 's' }}</p>
                                        @if(!empty($revertedByName))
                                            <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Reverted By:</strong> {{ $revertedByName }}</p>
                                        @endif
                                        <p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Reason:</strong> {{ $reason }}</p>
                                        @if($completionUrl)
                                            <p style="margin:0; font-size:14px; line-height:1.6; color:#334155;"><strong style="color:#0f172a;">Next Step:</strong> Please review the form and resubmit the corrected information.</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            @if($completionUrl)
                                <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 24px;">
                                    <tr>
                                        <td align="center" bgcolor="#dc2626" style="border-radius:12px;">
                                            <a href="{{ $completionUrl }}" style="display:inline-block; padding:14px 24px; font-size:14px; font-weight:700; color:#ffffff; text-decoration:none;">Review Supplier Form</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <p style="margin:0 0 8px; font-size:14px; line-height:1.7; color:#475569;">For concerns, please contact the Finance Department of JK&amp;C Inc.</p>
                            <p style="margin:0 0 8px; font-size:14px; line-height:1.7; color:#475569;">The attached PDF copy reflects the same revision request details shown here.</p>
                            <p style="margin:0; font-size:14px; line-height:1.7; color:#475569;">Regards,<br>JK&amp;C Inc.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
