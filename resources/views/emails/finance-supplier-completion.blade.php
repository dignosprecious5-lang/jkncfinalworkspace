<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Complete Supplier Information | JK&C INC.</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6; background: #f8fafc; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; display: flex; gap: 16px; align-items: center;">
            <img src="{{ asset('images/imaglogo.png') }}" alt="John Kelly &amp; Company" style="height: 56px; width: auto; object-fit: contain;">
            <div>
                <p style="margin: 0; font-size: 12px; text-transform: uppercase; letter-spacing: .14em; color: #6b7280;">John Kelly &amp; Company</p>
                <h2 style="margin: 8px 0 0; font-size: 24px; color: #111827;">Complete Your Supplier Information</h2>
            </div>
        </div>

        <div style="padding: 24px;">
            <p>Hi,</p>

            <p>
                We have prepared a supplier completion form for <strong>{{ $record->record_title ?: 'your supplier record' }}</strong>.
                Please click the button below to review and fill out the remaining details.
            </p>
            <p>
                A PDF copy of the current form is attached for reference.
            </p>

            <table cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td><strong>Supplier Code / ID:</strong></td>
                    <td>{{ $record->record_number ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Business Name:</strong></td>
                    <td>{{ $record->record_title ?: 'N/A' }}</td>
                </tr>
            </table>

            <div style="margin: 28px 0;">
                <a href="{{ $completionUrl }}" style="display: inline-block; background: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 600;">
                    Complete Supplier Form
                </a>
            </div>

            <p style="margin-bottom: 0;">
                After submission, the details will automatically update the supplier record in Finance.
            </p>

            <p style="margin-top: 24px;">
                For concerns, please contact the Finance Department of JK&amp;C Inc.
            </p>

            <p style="margin-top: 24px;">
                Thank you,<br>
                JK&amp;C INC.
            </p>
        </div>
    </div>
</body>
</html>
