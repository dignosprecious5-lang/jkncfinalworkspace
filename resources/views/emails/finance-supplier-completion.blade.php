<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Supplier Information | JK&amp;C INC.</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    @php
        $supplierLabels = $supplierLabels ?? [];
        $supplierLabel = fn ($key, $default) => $supplierLabels[$key] ?? $default;
    @endphp
    @php
        $logoUrl = $logoUrl ?? rtrim((string) config('app.url'), '/') . '/images/imaglogo.png';
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:24px 28px;border-bottom:1px solid #e5e7eb;background:#f9fafb;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <img src="{{ $logoUrl }}" alt="John Kelly &amp; Company" style="display:block;width:160px;max-width:100%;height:auto;">
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;">
                                            Supplier Completion
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 28px;">
                            <h1 style="margin:0 0 8px;font-size:24px;line-height:1.25;color:#111827;">Please Complete Your Supplier Information</h1>
                            <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#475569;">
                                We have prepared your supplier completion form for <strong>{{ $record->record_title ?: 'this supplier record' }}</strong>.
                                Kindly review the details and complete the remaining fields using the button below.
                            </p>
                            <div style="margin:0 0 20px;">
                                <a href="{{ $completionUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-size:14px;font-weight:700;">
                                    Complete Supplier Form
                                </a>
                            </div>

                            <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.6;">A PDF copy is attached for your reference. Once submitted, your supplier record will update automatically in our system.</p>
                            <p style="margin:12px 0 0;font-size:13px;color:#6b7280;line-height:1.6;">For concerns, please contact the Finance Department of JK&amp;C Inc.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">
                            This is an automated notification from the JK&amp;C Finance system.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
