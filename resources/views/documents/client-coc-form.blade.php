<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
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
        .actions { margin-top:28px; border:1px solid #dbe3f0; background:#f8fbff; padding:18px; }
        .field { margin-bottom:18px; }
        .field:last-child { margin-bottom:0; }
        .field label { display:block; margin-bottom:8px; font-size:0.74rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#475569; }
        .input,.textarea,.file { width:100%; border:1px solid #cbd5e1; background:#fff; padding:10px 12px; font-size:.95rem; box-sizing:border-box; }
        .textarea { min-height:120px; resize:vertical; }
        .checkbox { display:flex; gap:10px; align-items:flex-start; line-height:1.6; }
        .error { margin-top:6px; font-size:.82rem; color:#dc2626; }
        .success { margin: 0 0 18px; border:1px solid #b7dfc5; background:#edf9f1; padding:14px 16px; font-size:.88rem; color:#166534; }
        .buttons { display:flex; justify-content:space-between; gap:12px; margin-top:18px; }
        .button { display:inline-flex; align-items:center; justify-content:center; min-height:44px; border:1px solid #1c4587; background:#1c4587; padding:0 18px; font-size:.9rem; font-weight:700; color:#fff; text-decoration:none; cursor:pointer; }
        @media (max-width: 700px) { .doc { padding:20px 18px 24px; } }
    </style>
</head>
<body>
    <div class="page">
        <section class="sheet">
            <div class="doc">
                @if (session('success'))
                    <div class="success">{{ session('success') }}</div>
                @endif
                @include('project.partials.approved-coc-document', ['coc' => $coc ?? []])
                <div class="actions">
                    <form method="POST" action="{{ $clientFormAction }}" enctype="multipart/form-data">
                        @csrf
                        <div class="field">
                            <label for="client_approval_name">Printed Name</label>
                            <input id="client_approval_name" type="text" name="client_approval_name" value="{{ old('client_approval_name', data_get($project->metadata ?? [], 'coc.client_approved_name') ?: $contactName) }}" class="input">
                            @error('client_approval_name')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="client_response_notes">Notes / Comments</label>
                            <textarea id="client_response_notes" name="client_response_notes" class="textarea">{{ old('client_response_notes', data_get($project->metadata ?? [], 'coc.client_response_notes')) }}</textarea>
                            @error('client_response_notes')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label for="client_attachment">Upload Signed COC</label>
                            <input id="client_attachment" type="file" name="client_attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file">
                            @error('client_attachment')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="field">
                            <label class="checkbox">
                                <input type="checkbox" name="client_approval" value="1">
                                <span>I confirm approval of this Certificate of Completion. After printing and physical signature, I uploaded the signed COC above for submission back to John Kelly &amp; Company.</span>
                            </label>
                            @error('client_approval')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div class="buttons">
                            <a href="{{ $clientDownloadUrl }}" class="button" style="background:#fff;color:#1c4587;">Download PDF</a>
                            <button type="submit" class="button">Submit Approved COC</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
