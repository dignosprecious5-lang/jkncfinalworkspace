<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $action->title }} | ORDO Client Action</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e4f95;
            --primary-dark: #153a70;
            --primary-light: #eff6ff;
            --success: #059669;
            --success-light: #ecfdf5;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-500: #64748b;
            --slate-400: #94a3b8;
            --slate-300: #cbd5e1;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-main);
            background: #f1f5f9;
            color: var(--slate-900);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Brand Header */
        .uca-top-header {
            background: #ffffff;
            border-bottom: 1px solid var(--slate-200);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .uca-brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--slate-900);
        }

        .uca-brand-mark {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: #ffffff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 15px;
            letter-spacing: -0.5px;
        }

        .uca-brand-text {
            font-weight: 800;
            font-size: 17px;
            letter-spacing: -0.2px;
            color: var(--slate-900);
        }

        .uca-security-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--slate-600);
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            padding: 5px 12px;
            border-radius: 20px;
        }

        .uca-security-badge svg {
            width: 14px;
            height: 14px;
            color: var(--success);
        }

        /* Main Container */
        .uca-main-wrapper {
            flex: 1 0 auto;
            max-width: 820px;
            width: 100%;
            margin: 32px auto;
            padding: 0 20px;
        }

        .uca-card {
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .uca-card-header {
            padding: 28px 32px 24px;
            border-bottom: 1px solid var(--slate-200);
            background: linear-gradient(180deg, #ffffff 0%, var(--slate-50) 100%);
        }

        .uca-header-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .uca-action-code-tag {
            font-size: 12px;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-light);
            border: 1px solid #bfdbfe;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .uca-status-pill {
            font-size: 12px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .uca-status-pending { background: var(--warning-light); color: var(--warning); border: 1px solid #fde68a; }
        .uca-status-completed { background: var(--success-light); color: var(--success); border: 1px solid #a7f3d0; }
        .uca-status-expired { background: var(--danger-light); color: var(--danger); border: 1px solid #fecaca; }

        .uca-main-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--slate-900);
            line-height: 1.25;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }

        .uca-recipient-line {
            font-size: 14px;
            color: var(--slate-600);
            font-weight: 500;
        }

        .uca-recipient-line strong {
            color: var(--slate-800);
            font-weight: 700;
        }

        .uca-card-body {
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Document Preview Block */
        .uca-doc-box {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .uca-doc-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .uca-doc-icon {
            width: 44px;
            height: 44px;
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }

        .uca-doc-details h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 3px;
        }

        .uca-doc-details p {
            font-size: 12.5px;
            color: var(--slate-500);
            font-weight: 500;
        }

        .uca-doc-version-pill {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            background: #e0e7ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
            padding: 2px 7px;
            border-radius: 4px;
            margin-left: 6px;
        }

        .uca-btn-outline {
            background: #ffffff;
            border: 1px solid var(--slate-300);
            color: var(--slate-700);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 7px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .uca-btn-outline:hover {
            background: var(--slate-100);
            color: var(--slate-900);
            border-color: var(--slate-400);
        }

        /* Instructions Notice */
        .uca-instructions-box {
            background: #eff6ff;
            border-left: 4px solid var(--primary);
            border-radius: 0 8px 8px 0;
            padding: 16px 20px;
            font-size: 13.5px;
            color: #1e3a8a;
            line-height: 1.5;
        }

        .uca-instructions-box strong {
            display: block;
            margin-bottom: 3px;
            font-weight: 700;
            color: #172554;
        }

        /* Form Inputs */
        .uca-form-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .uca-form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .uca-form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--slate-700);
        }

        .uca-form-label .req {
            color: var(--danger);
        }

        .uca-form-input,
        .uca-form-textarea,
        .uca-form-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--slate-300);
            border-radius: 8px;
            font-size: 14px;
            color: var(--slate-900);
            background: #ffffff;
            font-family: inherit;
            outline: none;
            transition: all 0.15s ease;
        }

        .uca-form-input:focus,
        .uca-form-textarea:focus,
        .uca-form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 79, 149, 0.12);
        }

        .uca-form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Checkbox Ack */
        .uca-ack-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px;
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: 8px;
            cursor: pointer;
        }

        .uca-ack-row input[type="checkbox"] {
            margin-top: 3px;
            width: 17px;
            height: 17px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .uca-ack-text {
            font-size: 13px;
            color: var(--slate-700);
            line-height: 1.4;
            font-weight: 500;
        }

        /* Button Action Bar */
        .uca-actions-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid var(--slate-200);
            flex-wrap: wrap;
        }

        .uca-btn-primary {
            background: var(--primary);
            color: #ffffff;
            border: 1px solid var(--primary);
            padding: 11px 26px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
            box-shadow: 0 1px 3px rgba(30, 79, 149, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .uca-btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            box-shadow: 0 4px 12px rgba(30, 79, 149, 0.3);
        }

        .uca-btn-danger {
            background: #ffffff;
            color: var(--danger);
            border: 1px solid #fca5a5;
            padding: 11px 22px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .uca-btn-danger:hover {
            background: var(--danger-light);
            border-color: var(--danger);
        }

        /* OTP Screen */
        .uca-otp-box {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }

        .uca-otp-input {
            letter-spacing: 8px;
            font-size: 26px;
            font-weight: 800;
            text-align: center;
            max-width: 240px;
            padding: 10px;
            border: 2px solid var(--slate-300);
            border-radius: 8px;
            background: #ffffff;
        }

        /* Success & Expired Screens */
        .uca-banner-success {
            background: var(--success-light);
            border: 1px solid #a7f3d0;
            border-radius: 10px;
            padding: 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .uca-banner-success h3 {
            color: #065f46;
            font-size: 18px;
            font-weight: 800;
        }

        .uca-banner-success p {
            color: #047857;
            font-size: 13.5px;
        }

        .uca-banner-expired {
            background: var(--danger-light);
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .uca-banner-expired h3 {
            color: #991b1b;
            font-size: 18px;
            font-weight: 800;
        }

        .uca-footer {
            text-align: center;
            padding: 24px;
            color: var(--slate-500);
            font-size: 12px;
        }
    </style>
</head>
<body>

    <!-- Top Header -->
    <header class="uca-top-header">
        <a href="#" class="uca-brand-logo">
            <div class="uca-brand-mark">O</div>
            <div class="uca-brand-text">ORDO</div>
        </a>

        <div class="uca-security-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Secure Client Action Portal
        </div>
    </header>

    <main class="uca-main-wrapper">
        <div class="uca-card">

            <!-- Card Header -->
            <div class="uca-card-header">
                <div class="uca-header-meta">
                    <span class="uca-action-code-tag">{{ $action->action_code }}</span>
                    
                    @if($isExpired)
                        <span class="uca-status-pill uca-status-expired">● Expired</span>
                    @elseif($isCompleted)
                        <span class="uca-status-pill uca-status-completed">✓ {{ $action->status }}</span>
                    @else
                        <span class="uca-status-pill uca-status-pending">⏳ Awaiting Your Response</span>
                    @endif
                </div>

                <h1 class="uca-main-title">{{ $action->title }}</h1>
                <p class="uca-recipient-line">
                    Prepared for <strong>{{ $action->client_name }}</strong> {{ $action->client_company ? "({$action->client_company})" : '' }}
                </p>
            </div>

            <!-- Card Body -->
            <div class="uca-card-body">

                <!-- 1. ALREADY COMPLETED SCREEN -->
                @if($isCompleted)
                    <div class="uca-banner-success">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <h3>Action Completed</h3>
                        <p>
                            Decision <strong>"{{ $action->response_decision ?: $action->status }}"</strong> has been recorded on {{ $action->completed_at ? $action->completed_at->format('M d, Y · g:i A') : 'recently' }}.
                        </p>
                        @if($action->signatory_name)
                            <p style="font-size:12.5px; color:#065f46; margin-top:4px;">
                                Signed / Authorized by: <strong>{{ $action->signatory_name }}</strong>
                            </p>
                        @endif
                    </div>

                <!-- 2. EXPIRED SCREEN -->
                @elseif($isExpired)
                    <div class="uca-banner-expired">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <h3>Link Has Expired</h3>
                        <p>This action request expired on {{ $action->expires_at ? $action->expires_at->format('M d, Y') : 'earlier' }}. Please contact your account representative to request a new link.</p>
                    </div>

                <!-- 3. OTP VERIFICATION SCREEN -->
                @elseif($needsOtp)
                    <div class="uca-otp-box">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <h3 style="font-size:18px; font-weight:700; color:var(--slate-900);">Verification Required</h3>
                        <p style="font-size:13.5px; color:var(--slate-600); max-width:440px;">
                            For your security, a 6-digit verification code is required to access and respond to this document.
                        </p>

                        <button type="button" class="uca-btn-outline" onclick="requestOtpCode()" id="btnRequestOtp">
                            Send Verification Code to {{ $action->client_email ?: 'Email' }}
                        </button>

                        <div id="otpInputSection" style="display:none; width:100%; max-width:320px; margin-top:14px;">
                            <input type="text" id="otpCodeInput" class="uca-otp-input" maxlength="6" placeholder="000000">
                            <div style="margin-top:12px;">
                                <button type="button" class="uca-btn-primary" style="width:100%; justify-content:center;" onclick="verifyOtpCode()">
                                    Verify &amp; Proceed
                                </button>
                            </div>
                        </div>
                        <div id="otpMsg" style="font-size:13px; margin-top:6px;"></div>
                    </div>

                <!-- 4. ACTIVE CLIENT ACTION FORM -->
                @else

                    <!-- Document Box -->
                    <div class="uca-doc-box">
                        <div class="uca-doc-info">
                            <div class="uca-doc-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </div>
                            <div class="uca-doc-details">
                                <h4>
                                    {{ $action->document_title }}
                                    <span class="uca-doc-version-pill">{{ $action->document_version }}</span>
                                </h4>
                                <p>Type: {{ $action->document_type }} · Ref: {{ $action->document_reference ?: $action->action_code }}</p>
                            </div>
                        </div>

                        @if($action->document_url)
                            <a href="{{ $action->document_url }}" target="_blank" class="uca-btn-outline">
                                View Document ↗
                            </a>
                        @endif
                    </div>

                    <!-- Instructions -->
                    <div class="uca-instructions-box">
                        <strong>Requested Action: {{ $action->action_type }}</strong>
                        {{ $action->instructions ?: "Please review the details above and provide your confirmation or signature below." }}
                    </div>

                    <!-- Response Form -->
                    <form id="publicActionForm" onsubmit="submitClientResponse(event)">
                        <div class="uca-form-section">

                            <!-- Action-Specific Inputs -->
                            @if(in_array($action->action_type, ['Approve', 'Accept', 'Review']))
                                <div class="uca-form-group">
                                    <label class="uca-form-label">Signatory Full Name <span class="req">*</span></label>
                                    <input type="text" name="signatory_name" class="uca-form-input" value="{{ $action->client_name }}" required placeholder="e.g. Maria Santos">
                                </div>

                                <div class="uca-form-group">
                                    <label class="uca-form-label">Position / Designation</label>
                                    <input type="text" name="signatory_title" class="uca-form-input" placeholder="e.g. Managing Director / Authorized Representative">
                                </div>

                                <div class="uca-ack-row">
                                    <input type="checkbox" id="ackTerms" required>
                                    <label for="ackTerms" class="uca-ack-text">
                                        I confirm that I have reviewed <strong>{{ $action->document_title }} ({{ $action->document_version }})</strong> and have the authority to act on behalf of the client.
                                    </label>
                                </div>

                                <div class="uca-form-group">
                                    <label class="uca-form-label">Comments / Notes (Optional)</label>
                                    <textarea name="notes" class="uca-form-textarea" placeholder="Add any specific comments or instructions..."></textarea>
                                </div>

                                <div class="uca-actions-bar">
                                    <button type="button" class="uca-btn-danger" onclick="submitDecline()">
                                        Decline / Request Revision
                                    </button>
                                    <button type="submit" class="uca-btn-primary" name="decision" value="Approved">
                                        ✓ {{ $action->action_type === 'Accept' ? 'Accept Proposal' : 'Approve & Confirm' }}
                                    </button>
                                </div>

                            @elseif($action->action_type === 'Sign')
                                <div class="uca-form-group">
                                    <label class="uca-form-label">Full Legal Name <span class="req">*</span></label>
                                    <input type="text" name="signatory_name" class="uca-form-input" value="{{ $action->client_name }}" required>
                                </div>

                                <div class="uca-form-group">
                                    <label class="uca-form-label">Official Title</label>
                                    <input type="text" name="signatory_title" class="uca-form-input" placeholder="e.g. President / CEO">
                                </div>

                                <div class="uca-ack-row">
                                    <input type="checkbox" id="signAck" required>
                                    <label for="signAck" class="uca-ack-text">
                                        By checking this box, I affix my electronic authorization and acknowledge that this represents my formal signature for <strong>{{ $action->document_title }}</strong>.
                                    </label>
                                </div>

                                <div class="uca-actions-bar">
                                    <button type="submit" class="uca-btn-primary" name="decision" value="Signed">
                                        Sign &amp; Complete Document
                                    </button>
                                </div>

                            @elseif($action->action_type === 'Acknowledge')
                                <div class="uca-ack-row">
                                    <input type="checkbox" id="ackOnly" required>
                                    <label for="ackOnly" class="uca-ack-text">
                                        I hereby acknowledge receipt and understanding of <strong>{{ $action->document_title }} ({{ $action->document_version }})</strong>.
                                    </label>
                                </div>

                                <div class="uca-form-group" style="margin-top:12px;">
                                    <label class="uca-form-label">Acknowledged By (Name)</label>
                                    <input type="text" name="signatory_name" class="uca-form-input" value="{{ $action->client_name }}" required>
                                </div>

                                <div class="uca-actions-bar">
                                    <button type="submit" class="uca-btn-primary" name="decision" value="Acknowledged">
                                        Confirm Acknowledgment
                                    </button>
                                </div>

                            @elseif($action->action_type === 'Upload')
                                <div class="uca-form-group">
                                    <label class="uca-form-label">Select File to Upload <span class="req">*</span></label>
                                    <input type="file" name="uploaded_file" class="uca-form-input" required>
                                    <small style="color:var(--slate-500); font-size:12px;">PDF, DOCX, JPG, or PNG up to 15MB.</small>
                                </div>

                                <div class="uca-form-group">
                                    <label class="uca-form-label">Notes (Optional)</label>
                                    <textarea name="notes" class="uca-form-textarea" placeholder="Describe the uploaded file..."></textarea>
                                </div>

                                <div class="uca-actions-bar">
                                    <button type="submit" class="uca-btn-primary" name="decision" value="Uploaded">
                                        Upload &amp; Submit Document
                                    </button>
                                </div>

                            @else
                                <div class="uca-form-group">
                                    <label class="uca-form-label">Your Response <span class="req">*</span></label>
                                    <textarea name="notes" class="uca-form-textarea" required placeholder="Type your response or selection here..."></textarea>
                                </div>

                                <div class="uca-actions-bar">
                                    <button type="submit" class="uca-btn-primary" name="decision" value="Responded">
                                        Submit Response
                                    </button>
                                </div>
                            @endif

                        </div>
                    </form>

                @endif

            </div>
        </div>
    </main>

    <footer class="uca-footer">
        Powered by ORDO Platform · Secure Client Action Layer · Strictly Scoped &amp; Encrypted
    </footer>

    <script>
        const token = "{{ $action->secure_token }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function requestOtpCode() {
            const btn = document.getElementById('btnRequestOtp');
            btn.disabled = true;
            btn.innerText = 'Sending code...';

            fetch(`/client-action/${token}/otp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('otpInputSection').style.display = 'block';
                    document.getElementById('otpMsg').innerHTML = `<span style="color:#059669;">${data.message}</span>` + 
                        (data.debug_code ? ` <br><strong>[DEV CODE: ${data.debug_code}]</strong>` : '');
                    btn.style.display = 'none';
                } else {
                    document.getElementById('otpMsg').innerHTML = `<span style="color:#dc2626;">${data.message}</span>`;
                    btn.disabled = false;
                    btn.innerText = 'Retry';
                }
            })
            .catch(err => {
                document.getElementById('otpMsg').innerHTML = `<span style="color:#dc2626;">Failed to request code.</span>`;
                btn.disabled = false;
            });
        }

        function verifyOtpCode() {
            const code = document.getElementById('otpCodeInput').value.trim();
            if (!code) return;

            fetch(`/client-action/${token}/verify-otp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ otp_code: code })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    document.getElementById('otpMsg').innerHTML = `<span style="color:#dc2626;">${data.message}</span>`;
                }
            });
        }

        function submitClientResponse(e) {
            e.preventDefault();
            const form = e.target;
            const submitBtn = e.submitter;
            const decision = submitBtn ? submitBtn.value : 'Approved';

            const formData = new FormData(form);
            formData.append('decision', decision);

            submitBtn.disabled = true;
            submitBtn.innerText = 'Submitting...';

            fetch(`/client-action/${token}/respond`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error submitting response.');
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Submit';
                }
            })
            .catch(err => {
                alert('Connection error. Please try again.');
                submitBtn.disabled = false;
            });
        }

        function submitDecline() {
            const reason = prompt('Please specify the reason for declining or requesting changes:');
            if (reason === null) return;

            const formData = new FormData();
            formData.append('decision', 'Declined');
            formData.append('notes', reason);

            fetch(`/client-action/${token}/respond`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
