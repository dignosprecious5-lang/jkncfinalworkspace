<div class="delivery-layout">
    <!-- LEFT SIDEBAR: STAGE MANAGEMENT -->
    <aside class="delivery-stage">
        <h3>STAGE MANAGEMENT</h3>
        
        <h4>STAGE STATUS</h4>
        <div class="delivery-status">
            {{ $cocApproved ? 'Completed' : 'Pending Requirements' }}
        </div>
        
        <h4>STAGE HANDLING</h4>
        <div class="delivery-total">
            <span>Total Stage Handling<br>Time</span>
            <strong>00:00:00</strong>
        </div>
        
        <dl class="delivery-details">
            <div><dt>In Progress</dt><dd>00:00:00</dd></div>
            <div><dt>On Hold</dt><dd>00:00:00</dd></div>
            <div><dt>Waiting</dt><dd>00:00:00</dd></div>
            <div><dt>Waiting On</dt><dd>—</dd></div>
            <div><dt>Started</dt><dd>—</dd></div>
            <div><dt>Completed</dt><dd>—</dd></div>
        </dl>
        
        <h4>STAGE CONTROLS</h4>
        <div class="delivery-controls">
            <button type="button" class="delivery-control primary" style="background:#8fa7cf; border-color:#8fa7cf; color:#fff;" onclick="alert('Certificate sent to client.')">
                Send Certificate via Email
            </button>
            <button type="button" class="delivery-control" onclick="alert('Enter the client email acknowledgment reference:')">
                Record Email Acknowledgment Proof
            </button>
            <button type="button" class="delivery-control" onclick="document.getElementById('cocUploadInput')?.click()">
                Upload Signed Certificate
            </button>
            <input type="file" id="cocUploadInput" class="hidden" accept="application/pdf,image/*">
            <button type="button" class="delivery-control" onclick="window.print()">
                Print
            </button>
            <a href="{{ route('project.coc.download', $project) }}" class="delivery-control flex items-center justify-start" style="text-decoration:none;">
                Download
            </a>
            <a href="{{ route('project.show', ['project' => $project->id, 'tab' => 'report']) }}" class="delivery-control flex items-center justify-start" style="text-decoration:none;">
                View Attached SOW Report
            </a>
            <button type="button" class="delivery-control primary" style="background:#8fa7cf; border-color:#8fa7cf; color:#fff;" onclick="alert('Submitting transmittal package...')">
                Submit Transmittal Package
            </button>
            <button type="button" class="delivery-control" disabled style="opacity:0.6; cursor:not-allowed; color:#94a3b8;">
                Confirm Successful Transmittal
            </button>
            <button type="button" class="delivery-control" onclick="document.getElementById('transmittalUploadInput')?.click()">
                Upload Transmittal Proof
            </button>
            <input type="file" id="transmittalUploadInput" class="hidden" accept="application/pdf,image/*">
            <button type="button" class="delivery-control primary" style="background:#8fa7cf; border-color:#8fa7cf; color:#fff;" onclick="alert('Project completed.')">
                Complete Project
            </button>
        </div>

        <h4 style="margin-top: 24px;">COMPLETION GATE</h4>
        <div class="delivery-gate" style="background:#f7f8fb; border:1px solid #e1e6ef; border-radius:9px; padding:12px; color:#68748a; font-size:11px; line-height:1.5;">
            Completion remains locked until all Execution tasks and the SOW Report are complete.
        </div>
    </aside>

    <!-- RIGHT PANEL: CERTIFICATE OF COMPLETION DOCUMENT PREVIEW -->
    <section class="certificate-shell">
        <div class="certificate-heading">
            <div>
                <strong>Certificate of Completion</strong>
                <br>
                <span style="font-size:11px; color:#8792a5;">Client-facing document preview</span>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:11px; color:#8792a5;">A4 · Live Preview</span>
                <div class="coc-zoom-toolbar">
                    <button class="coc-zoom-btn" id="cocZoomOut" type="button" title="Zoom Out">- Zoom</button>
                    <span class="coc-zoom-val" id="cocZoomVal">100%</span>
                    <button class="coc-zoom-btn" id="cocZoomIn" type="button" title="Zoom In">+ Zoom</button>
                    <button class="coc-zoom-btn" id="cocZoomReset" type="button" title="Reset Zoom">Fit Page</button>
                </div>
            </div>
        </div>

        <div class="certificate-canvas">
            <article class="certificate-paper" id="certificateDocument">
                <header class="certificate-head">
                    <div class="certificate-brand">
                        John Kelly
                        <small>&amp; Company</small>
                    </div>
                    <div class="certificate-title">
                        <h1>CERTIFICATE OF COMPLETION</h1>
                        <div class="certificate-conf">STRICTLY CONFIDENTIAL</div>
                    </div>
                </header>

                <div class="certificate-bar">PROJECT COMPLETION INFORMATION</div>
                <table class="certificate-info">
                    <tr>
                        <th>Certificate No.</th>
                        <td>{{ $coc['coc_no'] ?? ('COC-2026-' . str_pad((string) $project->id, 3, '0', STR_PAD_LEFT)) }}</td>
                        <th>Date Issued</th>
                        <td>{{ $coc['date_issued'] ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Project No.</th>
                        <td>{{ $project->project_code ?? ('PROJ-2026-' . $project->id) }}</td>
                        <th>Work Order No.</th>
                        <td>{{ $coc['work_order_no'] ?? ('PROJ-WO-2026-' . $project->id) }}</td>
                    </tr>
                    <tr>
                        <th>EPA No.</th>
                        <td>{{ $coc['epa_no'] ?? '—' }}</td>
                        <th>SOW No.</th>
                        <td>{{ $coc['engagement_reference_no'] ?? 'SOW-2026-032' }}</td>
                    </tr>
                    <tr>
                        <th>Client</th>
                        <td>{{ $coc['client_name'] ?? ($project->client_name ?: 'May Flor D. Dabatos') }}</td>
                        <th>Business</th>
                        <td>{{ $coc['business_name'] ?? ($project->business_name ?: 'X10 REAL ESTATE CORPORATION') }}</td>
                    </tr>
                    <tr>
                        <th>Service / Project</th>
                        <td colspan="3">{{ $project->project_title ?: 'Transfer of Share From Dany and Ronald to X10' }}</td>
                    </tr>
                </table>

                <div class="certificate-bar">ATTACHED COMPLETION RECORD</div>
                <table class="certificate-info">
                    <tr>
                        <th>SOW Report No.</th>
                        <td>{{ $coc['report_no'] ?? ('SOWR-2026-' . $project->id) }}</td>
                        <th>Status</th>
                        <td>{{ $coc['report_status'] ?? 'Included in the completion package' }}</td>
                    </tr>
                    <tr>
                        <th>Attached Document</th>
                        <td colspan="3">SOW Report — complete client update and delivery record</td>
                    </tr>
                </table>

                <section class="certificate-body">
                    <h2>CERTIFICATION, ACCEPTANCE AND RELEASE</h2>
                    <p>This is to certify that <strong>John Kelly &amp; Company (JK&amp;C Inc.)</strong> has completed and delivered the agreed services, outputs, and deliverables for the engagement identified above in accordance with the approved RSAT, Scope of Work, Notice to Proceed, approved changes, timelines, and applicable engagement terms, subject only to items expressly documented in writing as pending, excluded, or separately commissioned.</p>
                    <p>The Client acknowledges that <strong>SOW Report No. {{ $coc['report_no'] ?? ('SOWR-2026-' . $project->id) }}</strong> forms part of this Certificate and has been reported or made available to the Client. The Client confirms receipt of, and acknowledges the opportunity to review, the task updates, status reports, communications, outputs, attachments, and deliverables recorded in that SOW Report.</p>
                    <p>By signing or electronically acknowledging this Certificate, the Client confirms that the completed scope and corresponding deliverables have been received, reviewed, and accepted without reservation except for any specific exception written on this Certificate or otherwise recorded and mutually acknowledged before execution.</p>
                    <p>Upon execution of this Certificate, the engagement and the documented completed scope shall be deemed completed and closed. John Kelly &amp; Company (JK&amp;C Inc.) shall have no further obligation regarding that completed scope except an obligation expressly retained or separately agreed in a written instrument signed by the parties.</p>
                    <p><strong>EPA CLOSURE.</strong> Upon valid execution or recorded electronic acceptance of this Certificate, the engagement governed by <strong>EPA No. {{ $coc['epa_no'] ?? '—' }}</strong>, together with its completed SOW and related project authorization, is fully performed, concluded, closed, and no longer active. No continuing service, duty, or deliverable remains under that EPA except an obligation expressly identified as surviving completion in the EPA or separately retained in a written instrument signed by the parties, and except rights or obligations that cannot lawfully be waived.</p>
                    <p>To the fullest extent permitted by applicable law, the Client voluntarily, knowingly, clearly, and unequivocally releases and discharges John Kelly &amp; Company (JK&amp;C Inc.), its consultants, associates, officers, employees, and authorized representatives from claims, demands, or liabilities arising solely from the completed and accepted scope and known to the Client as of the date of acceptance, except rights or obligations that cannot lawfully be waived or that are expressly retained in writing.</p>
                    <p>Any further assistance, correction outside an expressly retained obligation, revision, continuation, new request, or additional service requires separate written approval and may be subject to revised timelines and corresponding professional fees.</p>
                    <p>I, the undersigned Client and/or duly authorized representative, confirm that I have authority to act for the identified Business and voluntarily confirm receipt, review, acceptance, and completion of the engagement described in this Certificate and its attached SOW Report.</p>

                    <div class="certificate-signatures">
                        <div>
                            <div class="certificate-signature">{{ $coc['client_name'] ?? 'May Flor D. Dabatos' }}</div>
                            <div class="certificate-caption">Client or Duly Authorized Representative<br>Name, Signature and Date</div>
                        </div>
                        <div>
                            <div class="certificate-signature">{{ $coc['lead_consultant'] ?: 'John Kelly Abalde' }}</div>
                            <div class="certificate-caption">Lead Consultant<br>Name, Signature and Date</div>
                        </div>
                        <div>
                            <div class="certificate-signature">{{ $coc['associate'] ?: 'Rubeca Potayre' }}</div>
                            <div class="certificate-caption">Associate<br>Name, Signature and Date</div>
                        </div>
                    </div>
                </section>

                <div class="certificate-notice">
                    <b>CONFIDENTIALITY NOTICE:</b> This document is intended solely for the named Client or a duly authorized representative of the identified Business and forms part of the official project completion record.
                </div>
            </article>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentZoom = 100;
    const updateZoom = (z) => {
        currentZoom = Math.min(Math.max(z, 70), 160);
        const doc = document.getElementById("certificateDocument");
        const val = document.getElementById("cocZoomVal");
        if (doc) doc.style.transform = `scale(${currentZoom / 100})`;
        if (val) val.textContent = `${currentZoom}%`;
    };
    document.getElementById("cocZoomIn")?.addEventListener("click", () => updateZoom(currentZoom + 10));
    document.getElementById("cocZoomOut")?.addEventListener("click", () => updateZoom(currentZoom - 10));
    document.getElementById("cocZoomReset")?.addEventListener("click", () => updateZoom(100));
});
</script>
