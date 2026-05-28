<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Verification | John Kelly &amp; Company</title>
    <link rel="icon" type="image/png" href="{{ asset('images/image.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, sans-serif; color: #0f172a; background: #f8fafc; }
        .wrap { width: min(1040px, calc(100% - 32px)); margin: 0 auto; padding: 32px 0; }
        .header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; }
        .header img { width: 56px; height: 56px; object-fit: contain; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 900; letter-spacing: -.03em; }
        .header p { margin: 3px 0 0; color: #64748b; font-size: 13px; font-weight: 700; }
        .grid { display: grid; grid-template-columns: 1fr 420px; gap: 22px; align-items: start; }
        .panel { background: white; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px; box-shadow: 0 18px 42px rgba(15,23,42,.06); }
        .label { display: block; color: #475569; font-size: 11px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; margin-bottom: 6px; }
        .input, textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 9px; padding: 10px 12px; font: inherit; font-size: 14px; outline: none; }
        .input:focus, textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.13); }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .full { grid-column: 1 / -1; }
        .btn { border: 0; border-radius: 9px; background: #2563eb; color: white; padding: 12px 16px; font-weight: 900; cursor: pointer; width: 100%; }
        .result { border-left: 5px solid #16a34a; }
        .no-match { border-left: 5px solid #dc2626; }
        .row { display: flex; justify-content: space-between; gap: 18px; border-bottom: 1px solid #e2e8f0; padding: 10px 0; font-size: 14px; }
        .row strong { color: #64748b; }
        .row span { text-align: right; font-weight: 800; }
        .disclaimer { margin-top: 14px; color: #64748b; font-size: 12px; line-height: 1.6; }
        @media (max-width: 880px) { .grid { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } }
        @media print { .form-panel, .header p { display: none; } body { background: white; } .panel { box-shadow: none; } }
    </style>
</head>
<body>
<main class="wrap">
    <header class="header">
        <img src="{{ asset('images/image.png') }}" alt="JK&C Logo">
        <div>
            <h1>Employee Verification</h1>
            <p>John Kelly &amp; Company / JK&amp;C Inc.</p>
        </div>
    </header>

    <div class="grid">
        <section class="panel form-panel">
            <form method="POST" action="{{ route('employee.verify.submit') }}">
                @csrf
                <div class="form-grid">
                    <div><label class="label">Employee ID Number</label><input class="input" name="employee_code" required pattern="\d{5}" value="{{ old('employee_code', $employeeCode ?? '') }}"></div>
                    <div><label class="label">Employee Full Name</label><input class="input" name="employee_name" required value="{{ old('employee_name') }}"></div>
                    <div class="full"><label class="label">Purpose of Verification</label><input class="input" name="purpose" required value="{{ old('purpose') }}"></div>
                    <div><label class="label">Requestor Full Name</label><input class="input" name="requestor_name" required value="{{ old('requestor_name') }}"></div>
                    <div><label class="label">Company / Organization</label><input class="input" name="requestor_company" value="{{ old('requestor_company') }}"></div>
                    <div><label class="label">Email Address</label><input class="input" type="email" name="requestor_email" required value="{{ old('requestor_email') }}"></div>
                    <div><label class="label">Contact Number</label><input class="input" name="requestor_contact" required value="{{ old('requestor_contact') }}"></div>
                    <div class="full"><label class="label">Position / Notes</label><textarea name="notes" rows="3">{{ old('notes') }}</textarea></div>
                    <div class="full"><button class="btn" type="submit">Verify Employee</button></div>
                </div>
            </form>
        </section>

        <section class="panel {{ !empty($noMatch) ? 'no-match' : (!empty($result) ? 'result' : '') }}">
            @if(!empty($result))
                <h2 style="margin-top:0;">Verification Result</h2>
                @foreach($result as $label => $value)
                    <div class="row"><strong>{{ str_replace('_', ' ', Str::title($label)) }}</strong><span>{{ $value ?: '-' }}</span></div>
                @endforeach
                <button onclick="window.print()" class="btn" style="margin-top:16px;">Print / Save PDF</button>
                <p class="disclaimer">This verification is system-generated by John Kelly &amp; Company / JK&amp;C Inc. and is intended solely for employment verification purposes. Additional confidential employee information shall not be disclosed without proper authorization and applicable legal basis.</p>
            @elseif(!empty($noMatch))
                <h2 style="margin-top:0;">No matching employee record found.</h2>
                <p class="disclaimer">Please check the 5-digit Employee ID Number and full name, then submit again.</p>
            @else
                <h2 style="margin-top:0;">Public Verification</h2>
                <p class="disclaimer">Submit the request form to view limited employment verification details. Salary, government IDs, addresses, payroll data, and internal records are never shown publicly.</p>
            @endif
        </section>
    </div>
</main>
</body>
</html>
