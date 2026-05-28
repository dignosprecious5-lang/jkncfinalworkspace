<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice To Proceed</title>
    <style>
        body { margin:0; background:linear-gradient(180deg,#f2f6fc 0%,#fbfcfe 100%); font-family:Arial,Helvetica,sans-serif; color:#0f172a; }
        .page { max-width: 980px; margin: 0 auto; padding: 32px 18px 48px; }
        .sheet { border:1px solid #d8e1ee; background:#fff; box-shadow:0 16px 34px rgba(15,23,42,.05); }
        .doc { padding: 32px 34px 36px; border:2px solid #1c4587; }
        .center { text-align:center; }
        .title { font-family:Georgia,"Times New Roman",serif; font-size:18pt; font-weight:700; line-height:1.05; }
        .code { font-family:Georgia,"Times New Roman",serif; font-size:8pt; font-weight:700; margin-bottom:24px; }
        .issued { margin:18px 0 12px; font-family:Georgia,"Times New Roman",serif; font-size:12pt; font-weight:700; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        .meta td { border:1px solid #000; padding:8px 10px; vertical-align:top; font-family:Georgia,"Times New Roman",serif; font-size:11pt; font-weight:700; }
        .value { font-weight:400; }
        .body-copy { margin-top:22px; font-size:11pt; line-height:1.35; }
        .body-copy p { margin:0 0 18px; text-align:justify; }
        .signatures { margin-top:34px; }
        .signatures td { border:1px solid #000; padding:8px 10px; vertical-align:top; }
        .sign-head { font-family:Georgia,"Times New Roman",serif; font-size:12pt; font-weight:700; }
        .sign-box { height:96px; text-align:center; vertical-align:middle; font-family:Georgia,"Times New Roman",serif; font-size:11pt; font-weight:700; }
        .panel { margin-top:28px; border:1px solid #dbe3f0; background:#f8fbff; padding:18px; }
        .grid { display:grid; gap:14px; grid-template-columns:repeat(2,minmax(0,1fr)); }
        .label { display:block; margin-bottom:6px; font-size:.74rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#475569; }
        .value-box { min-height:44px; border:1px solid #cbd5e1; background:#fff; padding:10px 12px; font-size:.95rem; box-sizing:border-box; }
        .attachment-link { display:inline-flex; align-items:center; justify-content:center; min-height:44px; border:1px solid #1c4587; background:#1c4587; padding:0 18px; font-size:.9rem; font-weight:700; color:#fff; text-decoration:none; }
        @media (max-width: 700px) { .doc { padding:20px 18px 24px; } .grid { grid-template-columns:minmax(0,1fr); } }
    </style>
</head>
<body>
    <div class="page">
        <section class="sheet">
            <div class="doc">
                @include('project.partials.approved-ntp-document', compact('ntp', 'ntpRecord', 'contactName'))
            </div>
        </section>
    </div>
</body>
</html>
