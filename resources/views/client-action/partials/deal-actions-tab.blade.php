@php
    $actions = $deal->clientActionRequests ?? collect();
    $pendingCount = $actions->whereIn('status', ['Pending', 'Awaiting Client', 'In Progress'])->count();
    $completedCount = $actions->whereIn('status', ['Approved', 'Accepted', 'Signed', 'Completed', 'Acknowledged', 'Uploaded'])->count();
    $totalCount = $actions->count();
@endphp

<div class="deal-tab-panel" id="tab-panel-client-actions" style="display: none;">

    <div style="width: 100%; max-width: 1250px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px;">

        {{-- Top Summary Bar --}}
        <div style="background: #ffffff; border: 1px solid #dce5f0; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e4f95; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7.5" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0;">Universal Client Actions</h2>
                    <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0;">
                        Controlled client-facing requests across Portal, Secure Link, Email, Wet Signature &amp; External Signing.
                    </p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div style="display: flex; gap: 8px; font-size: 12px; font-weight: 600;">
                    <span style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 6px; color: #475569;">
                        Total: <strong>{{ $totalCount }}</strong>
                    </span>
                    <span style="background: #fef3c7; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 6px; color: #92400e;">
                        Awaiting Client: <strong>{{ $pendingCount }}</strong>
                    </span>
                    <span style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; color: #065f46;">
                        Completed: <strong>{{ $completedCount }}</strong>
                    </span>
                </div>

                <button type="button" class="start-btn-primary" onclick="openCreateClientActionModal()" style="background: #1e4f95; border-color: #1e4f95; padding: 8px 16px; font-size: 13px; font-weight: 700; border-radius: 8px;">
                    + New Client Action
                </button>
            </div>
        </div>

        {{-- Filters Bar --}}
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
                <input type="text" id="ucaSearchInput" onkeyup="filterUcaCards()" placeholder="Search action code, client, document..." style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12.5px; width: 240px; font-family: inherit;">
                
                <select id="ucaStatusFilter" onchange="filterUcaCards()" style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12.5px; font-family: inherit; color: #334155;">
                    <option value="all">All Statuses</option>
                    <option value="Awaiting Client">Awaiting Client / Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Accepted">Accepted</option>
                    <option value="Signed">Signed</option>
                    <option value="Completed">Completed</option>
                    <option value="Declined">Declined</option>
                </select>

                <select id="ucaChannelFilter" onchange="filterUcaCards()" style="padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12.5px; font-family: inherit; color: #334155;">
                    <option value="all">All Channels</option>
                    <option value="Client Portal">Client Portal</option>
                    <option value="Secure Link">Secure Link</option>
                    <option value="Email">Email</option>
                    <option value="Manual / Wet Signature">Manual / Wet Signature</option>
                    <option value="External Signing">External Signing</option>
                </select>
            </div>
        </div>

        {{-- Actions List Container --}}
        <div id="ucaCardsList" style="display: flex; flex-direction: column; gap: 12px;">
            @forelse($actions as $act)
                <div class="uca-action-card-item" 
                     data-code="{{ strtolower($act->action_code) }}"
                     data-client="{{ strtolower($act->client_name) }}"
                     data-doc="{{ strtolower($act->document_title . ' ' . $act->document_version) }}"
                     data-status="{{ $act->status }}"
                     data-channel="{{ $act->response_channel ?: 'None' }}"
                     style="background: #ffffff; border: 1px solid #dce5f0; border-radius: 10px; padding: 18px 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 12px;">
                    
                    {{-- Header Row --}}
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <span style="font-size: 12px; font-weight: 800; color: #1e4f95; background: #eff6ff; border: 1px solid #bfdbfe; padding: 3px 8px; border-radius: 5px; letter-spacing: 0.5px;">
                                {{ $act->action_code }}
                            </span>
                            
                            <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0;">
                                {{ $act->title }}
                            </h3>

                            <span style="display: inline-block; font-size: 11px; font-weight: 700; background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; padding: 2px 8px; border-radius: 4px;">
                                {{ $act->document_version }}
                            </span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            {{-- Status Badge --}}
                            @php
                                $statusBg = match($act->status) {
                                    'Approved', 'Accepted', 'Completed', 'Signed' => '#ecfdf5; color:#065f46; border:1px solid #a7f3d0;',
                                    'Awaiting Client', 'Pending' => '#fef3c7; color:#92400e; border:1px solid #fde68a;',
                                    'Declined', 'Cancelled' => '#fef2f2; color:#991b1b; border:1px solid #fecaca;',
                                    default => '#f1f5f9; color:#475569; border:1px solid #cbd5e1;'
                                };
                            @endphp
                            <span style="font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 12px; background: {{ $statusBg }}">
                                ● {{ $act->status }}
                            </span>

                            {{-- Channel Badge --}}
                            @if($act->response_channel)
                                <span style="font-size: 11.5px; font-weight: 600; padding: 3px 10px; border-radius: 12px; background: #f8fafc; color: #334155; border: 1px solid #cbd5e1;">
                                    Channel: <strong>{{ $act->response_channel }}</strong>
                                </span>
                            @endif

                            {{-- Verification Badge --}}
                            @if($act->verification_state && $act->verification_state !== 'Unverified')
                                <span style="font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; background: #f0fdf4; color: #15803d; border: 1px solid #86efac;">
                                    ✓ {{ $act->verification_state }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Metadata Details Row --}}
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px;">
                        <div>
                            <div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Document</div>
                            <div style="font-weight: 700; color: #0f172a; margin-top: 2px;">{{ $act->document_title }} ({{ $act->document_version }})</div>
                        </div>

                        <div>
                            <div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Intended Client</div>
                            <div style="font-weight: 700; color: #0f172a; margin-top: 2px;">{{ $act->client_name }}</div>
                            <div style="color: #64748b; font-size: 11px;">{{ $act->client_email ?: 'No email' }}</div>
                        </div>

                        <div>
                            <div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Created / Sent</div>
                            <div style="color: #0f172a; margin-top: 2px;">
                                {{ $act->created_at->format('M d, Y') }}
                                @if($act->sent_at)
                                    · <span style="color: #059669;">Sent {{ $act->sent_at->format('M d') }}</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Response / Decision</div>
                            <div style="font-weight: 700; color: #0f172a; margin-top: 2px;">
                                {{ $act->response_decision ?: 'None yet' }}
                                @if($act->responded_at)
                                    <span style="font-weight: normal; color: #64748b;">({{ $act->responded_at->format('M d, Y') }})</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Evidence / Signatory Summary if Recorded --}}
                    @if($act->evidence_notes || $act->signatory_name || $act->evidence_file_name)
                        <div style="padding: 10px 14px; background: #eff6ff; border-left: 3px solid #1e4f95; border-radius: 0 6px 6px 0; font-size: 12px; color: #1e3a8a;">
                            @if($act->signatory_name)
                                <strong>Signatory:</strong> {{ $act->signatory_name }} 
                                @if($act->document_signed_date) · <strong>Doc Date:</strong> {{ $act->document_signed_date->format('M d, Y') }} @endif
                                <br>
                            @endif
                            @if($act->evidence_notes)
                                <strong>Evidence Notes:</strong> {{ $act->evidence_notes }}
                            @endif
                            @if($act->recorded_by_name)
                                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                    Recorded by <strong>{{ $act->recorded_by_name }}</strong> on {{ $act->responded_at ? $act->responded_at->format('M d, Y · g:i A') : '' }}
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Card Footer Action Buttons --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 12px; flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button type="button" class="btn-dli-edit" onclick="copySecureLink('{{ $act->secure_url }}')" title="Copy Secure No-Login Link">
                                🔗 Copy Secure Link
                            </button>
                            <a href="{{ $act->secure_url }}" target="_blank" class="btn-dli-edit" style="text-decoration: none; color: #334155;">
                                ↗ Open Client Link
                            </a>
                            <a href="{{ route('client-actions.controlled-document', $act->id) }}" target="_blank" class="btn-dli-edit" style="text-decoration: none; color: #334155;" title="Print Controlled Document for Physical Signing">
                                📄 Controlled Form
                            </a>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            @if(!$act->is_completed)
                                <button type="button" class="btn-dli-edit" onclick="openRecordEmailModal({{ $act->id }}, '{{ $act->action_code }}', '{{ $act->title }}')" style="background: #f8fafc; color: #1e40af; border-color: #bfdbfe;">
                                    ✉️ Record Email Response
                                </button>
                                <button type="button" class="btn-dli-edit" onclick="openRecordManualModal({{ $act->id }}, '{{ $act->action_code }}', '{{ $act->title }}', '{{ $act->client_name }}')" style="background: #f8fafc; color: #047857; border-color: #a7f3d0;">
                                    ✍️ Upload Wet Signature
                                </button>
                                <button type="button" class="btn-dli-edit" onclick="openRecordExternalModal({{ $act->id }}, '{{ $act->action_code }}', '{{ $act->title }}', '{{ $act->client_name }}')" style="background: #f8fafc; color: #7c3aed; border-color: #ddd6fe;">
                                    🏢 Record External Signing
                                </button>
                            @else
                                <button type="button" class="btn-dli-edit" onclick="openVerifyModal({{ $act->id }}, '{{ $act->action_code }}')" style="color: #059669;">
                                    🛡️ Verify Evidence
                                </button>
                            @endif

                            <button type="button" class="btn-dli-edit" onclick="viewAuditTrail({{ $act->id }})" style="color: #475569;">
                                ⏱️ Audit History ({{ count($act->audit_trail ?: []) }})
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 50px 20px; text-align: center;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #eff6ff; color: #1e4f95; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">No Client Action Requests Yet</h3>
                    <p style="font-size: 13px; color: #64748b; max-width: 420px; margin: 0 auto 18px;">
                        Create a controlled Client Action Request when the client needs to review, approve, sign, or acknowledge a proposal or document.
                    </p>
                    <button type="button" class="start-btn-primary" onclick="openCreateClientActionModal()" style="background: #1e4f95; border-color: #1e4f95; padding: 8px 18px;">
                        + Create First Action Request
                    </button>
                </div>
            @endforelse
        </div>

    </div>

</div>

{{-- =========================================================
     MODALS FOR UNIVERSAL CLIENT ACTIONS
     ========================================================= --}}

{{-- 1. CREATE ACTION REQUEST MODAL --}}
<div id="createClientActionModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeCreateClientActionModal()">
    <div class="start-modal-card" style="max-width: 580px;">
        <div class="start-modal-head">
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">New Client Action Request</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeCreateClientActionModal()">&times;</button>
        </div>

        <form id="createClientActionForm" onsubmit="submitCreateClientAction(event)">
            <input type="hidden" name="deal_id" value="{{ $deal->id }}">
            <div class="start-modal-body-scroll" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="inquiry-form-label">Action Type <span class="req-star">*</span></label>
                        <select name="action_type" id="ucaFormActionType" class="inquiry-form-select" required onchange="updateUcaTitlePreview()">
                            <option value="Approve">Approve</option>
                            <option value="Accept">Accept</option>
                            <option value="Sign">Sign</option>
                            <option value="Acknowledge">Acknowledge</option>
                            <option value="Review">Review</option>
                            <option value="Upload">Upload</option>
                            <option value="Select">Select</option>
                            <option value="Respond">Respond</option>
                        </select>
                    </div>

                    <div>
                        <label class="inquiry-form-label">Document Type <span class="req-star">*</span></label>
                        <select name="document_type" id="ucaFormDocType" class="inquiry-form-select" required onchange="updateUcaTitlePreview()">
                            <option value="Proposal">Proposal</option>
                            <option value="START Service Memo">START Service Memo</option>
                            <option value="CASA Agreement">CASA Agreement</option>
                            <option value="Engagement Letter">Engagement Letter</option>
                            <option value="KYC Requirement">KYC Requirement</option>
                            <option value="General Document">General Document</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                    <div>
                        <label class="inquiry-form-label">Document Name <span class="req-star">*</span></label>
                        <input type="text" name="document_title" id="ucaFormDocTitle" class="inquiry-form-input" value="Proposal - {{ $deal->company_name ?: $deal->deal_title }}" required oninput="updateUcaTitlePreview()">
                    </div>

                    <div>
                        <label class="inquiry-form-label">Exact Version <span class="req-star">*</span></label>
                        <input type="text" name="document_version" id="ucaFormDocVersion" class="inquiry-form-input" value="V1" required oninput="updateUcaTitlePreview()" placeholder="e.g. V3">
                    </div>
                </div>

                <div>
                    <label class="inquiry-form-label">Action Title</label>
                    <input type="text" name="title" id="ucaFormTitle" class="inquiry-form-input" value="Approve Proposal V1" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="inquiry-form-label">Client / Contact Name <span class="req-star">*</span></label>
                        <input type="text" name="client_name" class="inquiry-form-input" value="{{ $contactName !== 'Not provided' ? $contactName : ($deal->primary_contact_name ?: $deal->company) }}" required>
                    </div>

                    <div>
                        <label class="inquiry-form-label">Client Email</label>
                        <input type="email" name="client_email" class="inquiry-form-input" value="{{ $deal->email }}" placeholder="client@company.com">
                    </div>
                </div>

                <div>
                    <label class="inquiry-form-label">Instructions for Client</label>
                    <textarea name="instructions" class="inquiry-form-textarea" rows="2" placeholder="Specific instructions for client..."></textarea>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="requires_otp" id="ucaOtpReq" value="1">
                    <label for="ucaOtpReq" style="font-size: 13px; color: #334155; cursor: pointer;">
                        <strong>Require OTP / Email Verification</strong> before client can respond (High sensitivity)
                    </label>
                </div>
            </div>

            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeCreateClientActionModal()">Cancel</button>
                <button type="submit" class="btn-modal-save" style="background: #1e4f95; border-color: #1e4f95;">Create Action Request</button>
            </div>
        </form>
    </div>
</div>

{{-- 2. RECORD EMAIL RESPONSE MODAL --}}
<div id="recordEmailModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeRecordEmailModal()">
    <div class="start-modal-card" style="max-width: 520px;">
        <div class="start-modal-head">
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Record Client Email Response</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeRecordEmailModal()">&times;</button>
        </div>

        <form id="recordEmailForm" onsubmit="submitRecordEmail(event)">
            <input type="hidden" id="recordEmailActionId" name="action_id">
            <input type="hidden" name="channel" value="Email">

            <div class="start-modal-body-scroll" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; font-size: 12.5px; color: #1e3a8a;">
                    <strong id="recordEmailActionLabel">CA-2026-001</strong><br>
                    This will update the underlying Client Action Request and explicitly record in the audit trail that the decision was captured <strong>FROM EMAIL EVIDENCE</strong>.
                </div>

                <div>
                    <label class="inquiry-form-label">Client Decision <span class="req-star">*</span></label>
                    <select name="decision" class="inquiry-form-select" required>
                        <option value="Approved">Approved</option>
                        <option value="Accepted">Accepted</option>
                        <option value="Declined">Declined</option>
                        <option value="Acknowledged">Acknowledged</option>
                        <option value="Changes Requested">Changes Requested</option>
                    </select>
                </div>

                <div>
                    <label class="inquiry-form-label">Email Summary / Notes <span class="req-star">*</span></label>
                    <textarea name="notes" class="inquiry-form-textarea" required placeholder="Paste client email text or summarize response..."></textarea>
                </div>

                <div>
                    <label class="inquiry-form-label">Attach Email File / Screenshot (Optional)</label>
                    <input type="file" name="evidence_file" class="inquiry-form-input">
                </div>
            </div>

            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeRecordEmailModal()">Cancel</button>
                <button type="submit" class="btn-modal-save" style="background: #1e40af; border-color: #1e40af;">Save Email Evidence</button>
            </div>
        </form>
    </div>
</div>

{{-- 3. RECORD MANUAL / WET SIGNATURE MODAL --}}
<div id="recordManualModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeRecordManualModal()">
    <div class="start-modal-card" style="max-width: 520px;">
        <div class="start-modal-head">
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Upload Manual / Wet Signature</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeRecordManualModal()">&times;</button>
        </div>

        <form id="recordManualForm" onsubmit="submitRecordManual(event)">
            <input type="hidden" id="recordManualActionId" name="action_id">
            <input type="hidden" name="channel" value="Manual / Wet Signature">

            <div class="start-modal-body-scroll" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px; font-size: 12.5px; color: #166534;">
                    <strong id="recordManualActionLabel">CA-2026-001</strong><br>
                    Record physically signed document evidence. Explicitly recorded as <em>Manual / Wet Signature</em> (NOT an ORDO native digital signature).
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="inquiry-form-label">Signatory Name <span class="req-star">*</span></label>
                        <input type="text" name="signatory_name" id="recordManualSignatory" class="inquiry-form-input" required>
                    </div>

                    <div>
                        <label class="inquiry-form-label">Date on Document <span class="req-star">*</span></label>
                        <input type="date" name="document_date" class="inquiry-form-input" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div>
                    <label class="inquiry-form-label">Upload Scanned / Signed Document <span class="req-star">*</span></label>
                    <input type="file" name="evidence_file" class="inquiry-form-input" required>
                    <small style="color: #64748b; font-size: 11.5px;">PDF, PNG, JPG up to 15MB.</small>
                </div>

                <div>
                    <label class="inquiry-form-label">Verification State</label>
                    <select name="verification_state" class="inquiry-form-select">
                        <option value="Verified (Physical Copy)">Verified (Physical Copy Verified)</option>
                        <option value="Pending">Pending Verification</option>
                    </select>
                </div>

                <div>
                    <label class="inquiry-form-label">Notes (Optional)</label>
                    <textarea name="notes" class="inquiry-form-textarea" rows="2" placeholder="Any additional notes..."></textarea>
                </div>
            </div>

            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeRecordManualModal()">Cancel</button>
                <button type="submit" class="btn-modal-save" style="background: #047857; border-color: #047857;">Upload &amp; Record Signature</button>
            </div>
        </form>
    </div>
</div>

{{-- 4. RECORD EXTERNAL SIGNING MODAL --}}
<div id="recordExternalModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeRecordExternalModal()">
    <div class="start-modal-card" style="max-width: 520px;">
        <div class="start-modal-head">
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Record External Signing / In-Person</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeRecordExternalModal()">&times;</button>
        </div>

        <form id="recordExternalForm" onsubmit="submitRecordExternal(event)">
            <input type="hidden" id="recordExternalActionId" name="action_id">
            <input type="hidden" name="channel" value="External Signing">

            <div class="start-modal-body-scroll" style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 12px; font-size: 12.5px; color: #6b21a8;">
                    <strong id="recordExternalActionLabel">CA-2026-001</strong><br>
                    Signing completed outside ORDO. Recorded as <em>Externally Completed / Manual Evidence</em>.
                </div>

                <div>
                    <label class="inquiry-form-label">External Signing Method <span class="req-star">*</span></label>
                    <select name="external_method" class="inquiry-form-select" required>
                        <option value="In-Person Signing">In-Person Signing</option>
                        <option value="DocuSign">DocuSign</option>
                        <option value="Adobe Sign">Adobe Sign</option>
                        <option value="Physical Courier">Physical Courier</option>
                        <option value="External Portal">External Portal</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="inquiry-form-label">Signatory Name</label>
                        <input type="text" name="signatory_name" id="recordExternalSignatory" class="inquiry-form-input">
                    </div>

                    <div>
                        <label class="inquiry-form-label">Date on Document</label>
                        <input type="date" name="document_date" class="inquiry-form-input" value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div>
                    <label class="inquiry-form-label">Attach External Signed Proof (Optional)</label>
                    <input type="file" name="evidence_file" class="inquiry-form-input">
                </div>

                <div>
                    <label class="inquiry-form-label">Notes (Optional)</label>
                    <textarea name="notes" class="inquiry-form-textarea" rows="2" placeholder="Details about external signing execution..."></textarea>
                </div>
            </div>

            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeRecordExternalModal()">Cancel</button>
                <button type="submit" class="btn-modal-save" style="background: #7c3aed; border-color: #7c3aed;">Record External Signing</button>
            </div>
        </form>
    </div>
</div>

{{-- 5. AUDIT TRAIL MODAL --}}
<div id="ucaAuditModal" class="start-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeUcaAuditModal()">
    <div class="start-modal-card" style="max-width: 600px;">
        <div class="start-modal-head">
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Client Action Traceability &amp; Audit</h3>
            <button type="button" class="start-modal-close-btn" onclick="closeUcaAuditModal()">&times;</button>
        </div>

        <div class="start-modal-body-scroll" style="padding: 20px;">
            <div id="ucaAuditTrailContent" style="display: flex; flex-direction: column; gap: 12px;">
                <!-- Filled via JS -->
            </div>
        </div>

        <div class="inquiry-modal-foot">
            <button type="button" class="btn-modal-cancel" onclick="closeUcaAuditModal()">Close</button>
        </div>
    </div>
</div>

<script>
    function updateUcaTitlePreview() {
        const actionType = document.getElementById('ucaFormActionType').value;
        const docTitle = document.getElementById('ucaFormDocTitle').value;
        const version = document.getElementById('ucaFormDocVersion').value;
        document.getElementById('ucaFormTitle').value = `${actionType} ${docTitle} ${version}`;
    }

    function openCreateClientActionModal() {
        document.getElementById('createClientActionModal').style.display = 'flex';
    }

    function closeCreateClientActionModal() {
        document.getElementById('createClientActionModal').style.display = 'none';
    }

    function openRecordEmailModal(id, code, title) {
        document.getElementById('recordEmailActionId').value = id;
        document.getElementById('recordEmailActionLabel').innerText = `${code} — ${title}`;
        document.getElementById('recordEmailModal').style.display = 'flex';
    }

    function closeRecordEmailModal() {
        document.getElementById('recordEmailModal').style.display = 'none';
    }

    function openRecordManualModal(id, code, title, clientName) {
        document.getElementById('recordManualActionId').value = id;
        document.getElementById('recordManualActionLabel').innerText = `${code} — ${title}`;
        document.getElementById('recordManualSignatory').value = clientName || '';
        document.getElementById('recordManualModal').style.display = 'flex';
    }

    function closeRecordManualModal() {
        document.getElementById('recordManualModal').style.display = 'none';
    }

    function openRecordExternalModal(id, code, title, clientName) {
        document.getElementById('recordExternalActionId').value = id;
        document.getElementById('recordExternalActionLabel').innerText = `${code} — ${title}`;
        document.getElementById('recordExternalSignatory').value = clientName || '';
        document.getElementById('recordExternalModal').style.display = 'flex';
    }

    function closeRecordExternalModal() {
        document.getElementById('recordExternalModal').style.display = 'none';
    }

    function closeUcaAuditModal() {
        document.getElementById('ucaAuditModal').style.display = 'none';
    }

    function copySecureLink(url) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Secure client link copied to clipboard:\n' + url);
            });
        } else {
            prompt('Copy secure client link:', url);
        }
    }

    function submitCreateClientAction(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch("{{ route('client-actions.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to create Client Action Request.');
            }
        });
    }

    function submitRecordEmail(e) {
        e.preventDefault();
        const form = e.target;
        const id = document.getElementById('recordEmailActionId').value;
        const formData = new FormData(form);

        fetch(`/client-actions/${id}/record-response`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to record email response.');
            }
        });
    }

    function submitRecordManual(e) {
        e.preventDefault();
        const form = e.target;
        const id = document.getElementById('recordManualActionId').value;
        const formData = new FormData(form);
        formData.append('decision', 'Signed');

        fetch(`/client-actions/${id}/record-response`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to record manual signature.');
            }
        });
    }

    function submitRecordExternal(e) {
        e.preventDefault();
        const form = e.target;
        const id = document.getElementById('recordExternalActionId').value;
        const formData = new FormData(form);
        formData.append('decision', 'Completed');

        fetch(`/client-actions/${id}/record-response`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to record external signing.');
            }
        });
    }

    function openVerifyModal(id, code) {
        const state = prompt(`Verify evidence for ${code}:\nEnter 'Verified' or 'Rejected':`, 'Verified');
        if (!state) return;

        fetch(`/client-actions/${id}/verify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ verification_state: state })
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

    function viewAuditTrail(id) {
        fetch(`/client-actions/${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const act = data.action;
                const trail = act.audit_trail || [];
                let html = `
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:8px; font-size:12.5px; margin-bottom:12px;">
                        <strong>Action Code:</strong> ${act.action_code}<br>
                        <strong>Document:</strong> ${act.document_title} (${act.document_version})<br>
                        <strong>Status:</strong> ${act.status} · <strong>Channel:</strong> ${act.response_channel || 'Not responded yet'}
                    </div>
                `;

                if (trail.length === 0) {
                    html += `<div style="text-align:center; color:#64748b; padding:20px;">No audit logs yet.</div>`;
                } else {
                    html += `<div class="start-history-list" style="padding-left:18px;">`;
                    trail.forEach(entry => {
                        html += `
                            <div class="start-history-item" style="margin-bottom:10px; padding:10px 14px;">
                                <div style="display:flex; justify-content:space-between; font-weight:700; font-size:13px; color:#0f172a;">
                                    <span>${entry.action}</span>
                                    <span style="font-size:11.5px; color:#64748b; font-weight:normal;">${entry.formatted_time || entry.timestamp}</span>
                                </div>
                                <div style="font-size:12.5px; color:#334155; margin-top:3px;">${entry.description}</div>
                                <div style="font-size:11px; color:#64748b; margin-top:4px;">By: <strong>${entry.user_name || 'System'}</strong></div>
                            </div>
                        `;
                    });
                    html += `</div>`;
                }

                document.getElementById('ucaAuditTrailContent').innerHTML = html;
                document.getElementById('ucaAuditModal').style.display = 'flex';
            }
        });
    }

    function filterUcaCards() {
        const search = document.getElementById('ucaSearchInput').value.toLowerCase();
        const status = document.getElementById('ucaStatusFilter').value;
        const channel = document.getElementById('ucaChannelFilter').value;
        const cards = document.querySelectorAll('.uca-action-card-item');

        cards.forEach(card => {
            const code = card.getAttribute('data-code');
            const client = card.getAttribute('data-client');
            const doc = card.getAttribute('data-doc');
            const cardStatus = card.getAttribute('data-status');
            const cardChannel = card.getAttribute('data-channel');

            const matchesSearch = !search || code.includes(search) || client.includes(search) || doc.includes(search);
            const matchesStatus = (status === 'all') || (status === 'Awaiting Client' ? ['Pending', 'Awaiting Client', 'In Progress'].includes(cardStatus) : cardStatus === status);
            const matchesChannel = (channel === 'all') || (cardChannel === channel);

            card.style.display = (matchesSearch && matchesStatus && matchesChannel) ? 'flex' : 'none';
        });
    }
</script>
