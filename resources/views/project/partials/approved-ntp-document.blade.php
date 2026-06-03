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
                <td class="project-ntp-sign-box" rowspan="3">Name, Signature and Date<br>Authorized Representative<br>Name/Client</td>
                <td class="project-ntp-sign-box">Name, Signature and Date<br>Lead Consultant<br><span class="project-ntp-light">{{ $ntp['lead_consultant'] ?? '' }}</span></td>
            </tr>
            <tr><td class="project-ntp-sign-box"></td></tr>
            <tr><td class="project-ntp-sign-box">Name, Signature and Date<br>Associate<br><span class="project-ntp-light">{{ $ntp['associate'] ?? '' }}</span></td></tr>
        </table>
    </div>
</div>
