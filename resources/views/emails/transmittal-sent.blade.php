<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #111827; line-height: 1.5;">
    <p>Good day,</p>
    <p>Please see attached transmittal form for your reference and processing.</p>
    <p>This email includes the generated transmittal form and any supporting attachments related to the transmitted item/s.</p>
    <p><strong>Reference No.:</strong> {{ $transmittal->transmittal_no }}<br>
       <strong>Company:</strong> {{ $corporateContext['companyName'] ?? 'Corporate Document' }}<br>
       <strong>Date:</strong> {{ optional($transmittal->transmittal_date)->format('F d, Y') }}</p>
    <p>Thank you.</p>
</body>
</html>
