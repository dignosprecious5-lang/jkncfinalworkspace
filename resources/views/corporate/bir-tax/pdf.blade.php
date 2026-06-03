<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4; margin: 14mm; }
        body { margin: 0; font-family: Arial, sans-serif; color: #111827; font-size: 12px; }
        .card { border: 1px solid #d1d5db; border-radius: 12px; padding: 16px; margin-bottom: 14px; }
        .title { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .subtitle { color: #6b7280; margin-bottom: 18px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { border-bottom: 1px solid #e5e7eb; padding: 10px 0; vertical-align: top; }
        .label { width: 34%; color: #6b7280; text-transform: uppercase; font-size: 10px; letter-spacing: .08em; }
        .value { font-weight: 600; }
    </style>
</head>
<body>
    <div class="title">BIR &amp; Tax Draft Preview</div>
    <div class="subtitle">Generated from the saved BIR &amp; Tax record.</div>
    <div class="card">
        <table class="grid">
            <tr><td class="label">TIN</td><td class="value">{{ $tax->tin ?: '-' }}</td></tr>
            <tr><td class="label">Taxpayer</td><td class="value">{{ $tax->tax_payer ?: '-' }}</td></tr>
            <tr><td class="label">RDO</td><td class="value">{{ $tax->rdo ?: $tax->registering_office ?: '-' }}</td></tr>
            <tr><td class="label">Registered Address</td><td class="value">{{ $tax->registered_address ?: '-' }}</td></tr>
            <tr><td class="label">Tax Type/s</td><td class="value">{{ $tax->tax_types ?: '-' }}</td></tr>
            <tr><td class="label">Form Type</td><td class="value">{{ $tax->form_type ?: '-' }}</td></tr>
            <tr><td class="label">Tax Due</td><td class="value">{{ $tax->tax_due !== null ? number_format((float) $tax->tax_due, 2) : '-' }}</td></tr>
            <tr><td class="label">Filing Frequency</td><td class="value">{{ $tax->filing_frequency ?: '-' }}</td></tr>
            <tr><td class="label">Due Date</td><td class="value">{{ optional($tax->due_date)->format('F d, Y') ?: '-' }}</td></tr>
            <tr><td class="label">Status</td><td class="value">{{ $tax->display_status }}</td></tr>
            <tr><td class="label">Uploaded By</td><td class="value">{{ $tax->uploaded_by ?: '-' }}</td></tr>
            <tr><td class="label">Date Uploaded</td><td class="value">{{ optional($tax->date_uploaded)->format('F d, Y') ?: '-' }}</td></tr>
        </table>
    </div>
</body>
</html>
