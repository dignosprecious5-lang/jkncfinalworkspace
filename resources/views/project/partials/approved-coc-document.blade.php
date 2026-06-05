<div class="project-doc-view-sheet">
    <div class="project-doc-view-paper">
        <div class="text-center">
            <div class="project-ntp-title">{{ $coc['title'] ?? 'CERTIFICATE OF COMPLETION' }}</div>
            <div class="project-ntp-code">{{ $coc['form_code'] ?? '' }}</div>
        </div>

        <table class="project-ntp-meta">
            <tr>
                <td>Date Issued: <span class="project-ntp-light">{{ $coc['date_issued'] ?? '-' }}</span></td>
                <td>COC No.: <span class="project-ntp-light">{{ $coc['coc_no'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>NTP No.: <span class="project-ntp-light">{{ $coc['ntp_no'] ?? '-' }}</span></td>
                <td>Engagement Type: <span class="project-ntp-light">{{ $coc['engagement_type'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>Condeal Reference No.: <span class="project-ntp-light">{{ $coc['condeal_reference_no'] ?? '-' }}</span></td>
                <td>Client Name: <span class="project-ntp-light">{{ $coc['client_name'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>Business Name: <span class="project-ntp-light">{{ $coc['business_name'] ?? '-' }}</span></td>
                <td>{{ $coc['engagement_reference_label'] ?? 'RSAT / SOW Ref No.:' }} <span class="project-ntp-light">{{ $coc['engagement_reference_no'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>Approved Start Date: <span class="project-ntp-light">{{ $coc['approved_start_date'] ?? '-' }}</span></td>
                <td>Target Completion Date: <span class="project-ntp-light">{{ $coc['target_completion_date'] ?? '-' }}</span></td>
            </tr>
            <tr>
                <td>Approved Start Date: <span class="project-ntp-light">{{ $coc['approved_start_date'] ?? '-' }}</span></td>
                <td>Actual Completion Date: <span class="project-ntp-light">{{ $coc['actual_completion_date'] ?? '-' }}</span></td>
            </tr>
        </table>

        <div class="project-ntp-copy">
            <p>This is to certify that <strong>John Kelly &amp; Company (JK&amp;C Inc.)</strong> has completed and delivered the agreed services, outputs, and deliverables for the above engagement in accordance with the approved scope, timelines, and applicable terms, subject only to any items expressly noted in writing as pending, excluded, or separate.</p>
            <p>The Client acknowledges that the services under the approved RSAT, SOW, Notice to Proceed, and related engagement documents have been rendered, received, and completed to the extent applicable.</p>
            <p>Upon signing this Certificate of Completion, the engagement shall be deemed completed and closed, and <strong>John Kelly &amp; Company (JK&amp;C Inc.)</strong> shall have no further obligation on the completed scope except those separately agreed in writing.</p>
            <p>The Client further confirms acceptance of the completed work and releases <strong>John Kelly &amp; Company (JK&amp;C Inc.)</strong>, its consultants, associates, officers, and representatives from further claims or liabilities arising from the completed and accepted scope, except obligations expressly retained in writing.</p>
            <p>Any further assistance, revision, continuation, or additional service shall require separate approval and may be subject to corresponding fees.</p>
            <p>I, the undersigned <strong>Client and/or duly authorized representative</strong>, hereby confirm receipt, acceptance, and completion of the engagement stated above.</p>
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
                    <span id="cocSignName" class="project-ntp-light">{{ data_get($project->metadata ?? [], 'coc.client_approved_name') ?: ($coc['client_confirmation_name'] ?? 'Name/Client') }}</span>
                    @if(data_get($project->metadata ?? [], 'coc.approved_at'))
                        <br><span id="cocSignDate" class="project-ntp-light">{{ \Carbon\Carbon::parse(data_get($project->metadata ?? [], 'coc.approved_at'))->format('M d, Y') }}</span>
                    @else
                        <span id="cocSignDate" style="display: none;"></span>
                    @endif
                </td>
                <td class="project-ntp-sign-box">
                    Name, Signature and Date<br>
                    Lead Consultant
                    <br>
                    <span class="project-ntp-light">{{ $coc['lead_consultant'] ?? '' }}</span>
                </td>
            </tr>
            <tr><td class="project-ntp-sign-box"></td></tr>
            <tr>
                <td class="project-ntp-sign-box">
                    Name, Signature and Date<br>
                    Associate
                    <br>
                    <span class="project-ntp-light">{{ $coc['associate'] ?? '' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div id="cocClientResponsePanel" class="project-ntp-panel" style="display: {{ data_get($project->metadata ?? [], 'coc.approval_status') === 'approved' ? 'block' : 'none' }};">
        <div style="margin-bottom:14px; font-size:12pt; font-weight:700; font-family:Georgia,'Times New Roman',serif;">Client Response Details</div>
        <div class="project-ntp-grid">
            <div>
                <span class="project-ntp-label">Approved By</span>
                <div class="project-ntp-value-box" id="cocClientName">{{ data_get($project->metadata ?? [], 'coc.approved_by_name') ?: data_get($project->metadata ?? [], 'coc.client_approved_name') }}</div>
            </div>
            <div>
                <span class="project-ntp-label">Date Approved</span>
                <div class="project-ntp-value-box" id="cocClientDate">{{ data_get($project->metadata ?? [], 'coc.approved_at') ? \Carbon\Carbon::parse(data_get($project->metadata ?? [], 'coc.approved_at'))->format('M d, Y h:i A') : '' }}</div>
            </div>
            <div id="cocClientNotesContainer" style="grid-column: 1 / -1; display: {{ data_get($project->metadata ?? [], 'coc.approval_note') ? 'block' : 'none' }};">
                <span class="project-ntp-label">Notes/Comments</span>
                <div class="project-ntp-value-box" id="cocClientNotes">{{ data_get($project->metadata ?? [], 'coc.approval_note') }}</div>
            </div>
            <div id="cocClientAttachmentContainer" style="grid-column: 1 / -1; margin-top: 8px; display: {{ data_get($project->metadata ?? [], 'coc.signed_attachment_path') ? 'block' : 'none' }};">
                <a href="{{ data_get($project->metadata ?? [], 'coc.signed_attachment_path') ? route('uploads.show', ['path' => data_get($project->metadata ?? [], 'coc.signed_attachment_path'), 'download' => 1]) : '#' }}" id="cocClientAttachment" class="project-ntp-attachment-link">
                    Download Signed Attachment
                </a>
            </div>
        </div>
    </div>
</div>
