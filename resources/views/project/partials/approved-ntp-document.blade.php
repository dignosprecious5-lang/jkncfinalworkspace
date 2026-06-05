<div class="project-ntp-sheet">
    <div class="project-ntp-doc">
        <div class="text-center">
            <div class="project-ntp-title">{{ $ntp['title'] ?? 'NOTICE TO PROCEED' }}</div>
            <div class="project-ntp-code">{{ str_contains(strtolower((string) ($ntp['engagement_type'] ?? '')), 'regular') ? 'REG-F-002' : 'PROJ-F-003' }}</div>
        </div>
        <div class="project-ntp-issued">Date Issued: <span class="project-ntp-light">{{ $ntp['date_issued'] ?? '-' }}</span></div>
        <table class="project-ntp-meta">
            <tr>
                <td>NTP No.: <span class="project-ntp-light">{{ $ntpRecord->ntp_number }}</span></td>
                <td>Engagement Type: <span class="project-ntp-light">{{ $ntp['engagement_type'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>Condeal Reference No.: <span class="project-ntp-light">{{ $ntp['condeal_reference_no'] ?? '-' }}</span></td>
                <td>Client Name: <span class="project-ntp-light">{{ $ntp['client_name'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>Business Name: <span class="project-ntp-light">{{ $ntp['business_name'] ?? '-' }}</span></td>
                <td>{{ $ntp['engagement_reference_label'] ?? 'Reference No.:' }} <span class="project-ntp-light">{{ $ntp['engagement_reference_no'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>Approved Start Date: <span class="project-ntp-light">{{ $ntp['approved_start_date'] ?? '-' }}</span></td>
                <td>Target Completion Date: <span class="project-ntp-light">{{ $ntp['target_completion_date'] ?? '-' }}</span></td>
            </tr>
        </table>
        <div class="project-ntp-copy">
            <p>This Notice to Proceed confirms that the Client has reviewed the engagement details and hereby authorizes <strong>John Kelly &amp; Company (JK&amp;C Inc.)</strong> to commence the agreed services under the approved scope, timelines, deliverables, and commercial terms.</p>
            <p>The Client acknowledges that work may officially begin upon execution of this document and that all services rendered thereafter shall be deemed duly authorized.</p>
            <p>Any additional requests, changes in scope, or delays requiring client action may be subject to separate confirmation, timeline adjustment, or corresponding charges, where applicable.</p>
            <p>I, the undersigned Client and/or duly authorized representative, hereby confirm approval and authorize <strong>John Kelly &amp; Company (JK&amp;C Inc.)</strong> to proceed with the commencement of the engagement stated above.</p>
        </div>
        <table class="project-ntp-signatures">
            <tr>
                <td><div class="project-ntp-sign-head">FOR THE CLIENT</div></td>
                <td><div class="project-ntp-sign-head">FOR JOHN KELLY &amp; COMPANY</div></td>
            </tr>
            <tr>
                <td class="project-ntp-sign-box" rowspan="3">
                    Name, Signature and Date<br>
                    Authorized Representative<br>
                    <span id="ntpSignName" class="project-ntp-light">{{ ($ntpRecord ?? null)?->client_approved_name ?? 'Name/Client' }}</span>
                    @if(($ntpRecord ?? null)?->client_approved_at)
                        <br><span id="ntpSignDate" class="project-ntp-light">{{ ($ntpRecord ?? null)->client_approved_at->format('M d, Y') }}</span>
                    @else
                        <span id="ntpSignDate" style="display: none;"></span>
                    @endif
                </td>
                <td class="project-ntp-sign-box">Name, Signature and Date<br>Lead Consultant<br><span class="project-ntp-light">{{ $ntp['lead_consultant'] ?? '' }}</span></td>
            </tr>
            <tr><td class="project-ntp-sign-box"></td></tr>
            <tr><td class="project-ntp-sign-box">Name, Signature and Date<br>Associate<br><span class="project-ntp-light">{{ $ntp['associate'] ?? '' }}</span></td></tr>
        </table>
    </div>

    <div id="ntpClientResponsePanel" class="project-ntp-panel" style="display: {{ ($ntpRecord ?? null)?->client_response_status === 'approved_to_proceed' ? 'block' : 'none' }};">
        <div style="margin-bottom:14px; font-size:12pt; font-weight:700; font-family:Georgia,'Times New Roman',serif;">Client Response Details</div>
        <div class="project-ntp-grid">
            <div>
                <span class="project-ntp-label">Approved By</span>
                <div class="project-ntp-value-box" id="ntpClientName">{{ ($ntpRecord ?? null)?->client_approved_name }}</div>
            </div>
            <div>
                <span class="project-ntp-label">Date Approved</span>
                <div class="project-ntp-value-box" id="ntpClientDate">{{ optional(($ntpRecord ?? null)?->client_approved_at)->format('M d, Y h:i A') }}</div>
            </div>
            <div id="ntpClientNotesContainer" style="grid-column: 1 / -1; display: {{ ($ntpRecord ?? null)?->client_response_notes ? 'block' : 'none' }};">
                <span class="project-ntp-label">Notes/Comments</span>
                <div class="project-ntp-value-box" id="ntpClientNotes">{{ ($ntpRecord ?? null)?->client_response_notes }}</div>
            </div>
            <div id="ntpClientAttachmentContainer" style="grid-column: 1 / -1; margin-top: 8px; display: {{ ($ntpRecord ?? null)?->client_attachment_path ? 'block' : 'none' }};">
                <a href="{{ ($ntpRecord ?? null)?->client_attachment_path ? route('uploads.show', ['path' => ($ntpRecord ?? null)->client_attachment_path, 'download' => 1]) : '#' }}" id="ntpClientAttachment" class="project-ntp-attachment-link">
                    Download Signed Attachment
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .project-ntp-panel { margin-top:28px; border:1px solid #dbe3f0; background:#f8fbff; padding:18px; }
    .project-ntp-grid { display:grid; gap:14px; grid-template-columns:repeat(2,minmax(0,1fr)); }
    .project-ntp-label { display:block; margin-bottom:6px; font-size:.74rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#475569; font-family:Arial,Helvetica,sans-serif; }
    .project-ntp-value-box { min-height:44px; border:1px solid #cbd5e1; background:#fff; padding:10px 12px; font-size:.95rem; box-sizing:border-box; font-family:Arial,Helvetica,sans-serif; color:#0f172a; }
    .project-ntp-attachment-link { display:inline-flex; align-items:center; justify-content:center; min-height:44px; border:1px solid #1c4587; background:#1c4587; padding:0 18px; font-size:.9rem; font-weight:700; color:#fff; text-decoration:none; font-family:Arial,Helvetica,sans-serif; }
    @media (max-width: 700px) { .project-ntp-grid { grid-template-columns:minmax(0,1fr); } }
</style>
