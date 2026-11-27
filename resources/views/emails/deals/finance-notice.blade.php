<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $noticeTitle }}</title>
</head>
<body style="margin:0;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <div style="width:100%;background:#f3f6fb;padding:32px 16px;">
        <div style="max-width:720px;margin:0 auto;background:#ffffff;box-shadow:0 18px 50px rgba(15,23,42,.08);">
            <div style="height:6px;background:#21409a;"></div>
            <div style="padding:32px 24px;">
                <p style="margin:0 0 24px;font-size:24px;font-weight:700;color:#0f172a;">John Kelly &amp; Company</p>
                <p style="margin:0 0 18px;font-size:16px;line-height:1.6;">Dear <strong>{{ $financeName ?: 'Finance Team' }}</strong>,</p>
                <h1 style="margin:0 0 16px;font-size:21px;color:#0f172a;">{{ $noticeTitle }}</h1>
                <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">{{ $noticeMessage }}</p>

                <div style="margin:0 0 24px;border:1px solid #dbe4f0;background:#f8fbff;padding:20px;">
                    <p style="margin:0;font-size:15px;line-height:1.8;color:#475569;">
                        <strong>Deal:</strong> {{ $deal->deal_title ?: $deal->deal_name ?: 'Untitled Deal' }}<br>
                        <strong>Deal Reference:</strong> {{ $deal->deal_code ?: '-' }}<br>
                        <strong>Client:</strong> {{ $deal->company_name ?: trim(($deal->first_name ?? '') . ' ' . ($deal->last_name ?? '')) ?: '-' }}<br>
                        <strong>Engagement Value:</strong> ₱{{ number_format($dealValue, 2) }}<br>
                        <strong>Sent by:</strong> {{ $senderName }}
                    </p>
                </div>

                <a href="{{ $dealUrl }}" style="display:inline-block;background:#3153d4;color:#ffffff;text-decoration:none;font-weight:700;padding:14px 22px;">
                    Open Deal Finance Details
                </a>
            </div>
        </div>
    </div>
</body>
</html>
