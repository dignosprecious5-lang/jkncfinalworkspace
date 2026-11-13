<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f3f6fa;color:#172033;font:16px/1.5 Arial,sans-serif">
    <main style="max-width:640px;margin:24px auto;padding:28px;background:#fff;border:1px solid #dce3ed;border-radius:10px">
        <h1 style="font-size:22px">Transmittal acknowledgment requested</h1>
        <p>Hello {{ $recipient->name ?: $recipient->email }},</p>
        <p>Please confirm receipt of the transmittal below using the secure, time-limited link.</p>
        <p><strong>Reference:</strong> {{ $transmittal->tracking_number }}<br>
            <strong>Subject:</strong> {{ $transmittal->subject }}</p>
        <p><a href="{{ $acknowledgmentUrl }}">Review and acknowledge transmittal</a></p>
        <p>This link is personal to the addressed recipient and expires at {{ $recipient->acknowledgment_expires_at?->format('M j, Y g:i A') }}.</p>
        <p>If you were not expecting this message, you may ignore it.</p>
    </main>
</body>
</html>
