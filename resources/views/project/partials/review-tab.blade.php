@php
    $dealRef = $deal->deal_reference ?? ('CONDEAL-' . ($project->project_code ?? '2026-001'));
    $versionNo = $sow->version_no ?? '1.0';
    $businessName = $company->company_name ?? ($deal->client_name ?? ($project->title ?? 'PROJECT CLIENT CORP'));
    $sowNo = $sow->sow_number ?? ('SOW-' . date('Y') . '-' . str_pad($project->id ?? 1, 3, '0', STR_PAD_LEFT));
    $clientName = $contact->full_name ?? ($deal->contact_person ?? 'May Flor D. Dabatos and Stephan Tenten');
    $datePrepared = optional($sow->created_at ?? ($project->created_at ?? null))->format('m/d/Y') ?? date('m/d/Y');
    
    $leadConsultant = ($approvalLeadConsultant ?? null) ?: (($project->assigned_consultant ?? null) ?: 'John Kelly Abalde');
    $leadAssociate = ($approvalLeadAssociate ?? null) ?: (($project->lead_associate ?? null) ?: 'Rubeca Potayre');
    $reviewerName = ($approvalReviewer ?? null) ?: 'Maria Santos';
    $approverName = ($approvalApprover ?? null) ?: 'Lyndon Earl Rio';
    
    $stageProgress = $project->stage_progress ?? 0;
    $isApproved = !empty($sow?->approved_at) || (($project->status ?? '') === 'Completed') || ($stageProgress >= 30);
    $statusText = $isApproved ? 'Review Complete' : (($stageProgress >= 20) ? 'Review Running' : 'Locked');
    $statusClass = strtolower(str_replace(' ', '-', $statusText));
    $completedDate = optional($sow?->approved_at ?? ($project->updated_at ?? null))->format('m/d/Y, H:i:s') ?? date('m/d/Y, H:i:s');
@endphp

<style>
/* Scoped Styles for Stage Management & Review Document */
.stage-management-panel {
    display: block !important;
    padding: 0 15px 15px;
    background: transparent;
}
.stage-management-panel section {
    padding-top: 16px;
    margin-bottom: 0;
    border-bottom: 1px solid #edf1f5;
    padding-bottom: 14px;
}
.stage-management-panel section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.stage-management-panel h4 {
    margin: 0 0 10px;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: 1px;
    color: #64748b;
    text-transform: uppercase;
}
.stage-status-value {
    min-height: 40px;
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-width: 1px;
    border-style: solid;
    border-radius: 7px;
    font-size: 10px;
    font-weight: 800;
}
.stage-status-value.locked {
    background: #f8fafc;
    border-color: #dbe4f2;
    color: #475569;
}
.stage-status-value.review-complete,
.stage-status-value.completed {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #065f46;
}
.stage-status-value.review-running,
.stage-status-value.for-review {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}
.stage-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 9px;
    padding: 8px 10px;
    border: 1px solid #dbe4f2;
    border-radius: 7px;
    background: #f3f6fc;
}
.stage-total span {
    color: #64748b;
    font-size: 8px;
    font-weight: 700;
    line-height: 1.2;
}
.stage-total strong {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 14px;
    font-weight: 800;
    color: #1e3a8a;
}
.stage-handling {
    display: grid;
    gap: 4px;
    margin: 0;
    padding: 0;
}
.stage-handling div {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 3px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 8px;
}
.stage-handling dt {
    color: #64748b;
    font-weight: 600;
    margin: 0;
}
.stage-handling dd {
    color: #1e293b;
    font-weight: 700;
    margin: 0;
    text-align: right;
}
.workflow-note {
    font-size: 8px;
    line-height: 1.45;
    color: #7a8495;
    background: #f8fafc;
    border: 1px solid #e5eaf2;
    border-radius: 7px;
    padding: 8px;
}
.ntp-gate {
    font-size: 8px;
    line-height: 1.4;
    padding: 8px;
    border: 1px dashed #d1d5db;
    border-radius: 6px;
    color: #64748b;
    background: #f9fafb;
    margin-bottom: 8px;
}
.ntp-gate.unlocked {
    border-style: solid;
    border-color: #86efac;
    background: #f0fdf4;
    color: #166534;
}
.ntp-btn {
    width: 100%;
    height: 32px;
    border: 1px solid #294b98;
    background: #294b98;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    border-radius: 6px;
    cursor: pointer;
    transition: opacity 0.15s;
}
.ntp-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    background: #94a3b8;
    border-color: #94a3b8;
}
.review-complete-banner {
    display: block;
    font-family: Inter, Segoe UI, Arial, sans-serif;
    margin: 0 0 12px;
    padding: 10px 12px;
    border-left: 4px solid #2d7b59;
    background: #eef9f3;
    color: #286447;
    font-size: 8px;
    font-weight: 600;
    border-radius: 0 4px 4px 0;
}
.builder-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8faff;
    border: 1px solid #e4e9f2;
    border-radius: 8px;
    padding: 8px 10px;
    margin: 10px 0 12px;
}
.sow-view-tabs {
    display: flex;
    gap: 6px;
    align-items: center;
}
.sow-view-tab {
    height: 30px;
    border: 1px solid #d9e1ed;
    border-radius: 6px;
    background: #fff;
    color: #48566c;
    padding: 0 12px;
    font-size: 9px;
    font-weight: 700;
    cursor: pointer;
}
.sow-view-tab.active {
    background: #294b98;
    border-color: #294b98;
    color: #fff;
}
.sow-view-tab.outscope.active {
    background: #815f24;
    border-color: #815f24;
    color: #fff;
}
.scope-title {
    background: #294b98;
    color: #fff;
    font-size: 9px;
    font-weight: 800;
    padding: 8px 12px;
    text-align: center;
    letter-spacing: 0.8px;
    border-radius: 4px 4px 0 0;
}
.scope-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8px;
}
.scope-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    border: 1px solid #e2e8f0;
    padding: 7px 8px;
    text-align: left;
    font-size: 8px;
}
.scope-table td {
    border: 1px solid #e2e8f0;
    padding: 7px 8px;
    font-size: 8px;
    color: #1e293b;
}
.scope-table .parent-row td {
    background: #f8faff;
    font-weight: 700;
}
.scope-table .child-row-sow td {
    background: #fff;
}
.scope-table .item-code {
    font-weight: 800;
    color: #334155;
    text-align: center;
}
.sow-summary-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 9px;
    margin: 16px 0 6px;
}
@media (max-width: 1000px) {
    .sow-summary-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
.sow-summary-card {
    border: 1px solid #cfd9e7;
    background: #f8fafc;
    padding: 9px 12px;
    border-radius: 4px;
}
.sow-summary-card label {
    display: block;
    font-family: Inter, Segoe UI, Arial, sans-serif;
    font-size: 7px;
    color: #667085;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 7px;
}
.sow-summary-card strong {
    font-family: Inter, Segoe UI, Arial, sans-serif;
    font-size: 14px;
    font-weight: 800;
    color: #344054;
}
</style>

<div class="layout">
    <!-- LEFT SIDECARD: EXACT STAGE MANAGEMENT SPECIFICATION -->
    <aside class="sidecard" aria-live="polite">
        <div class="side-title">STAGE MANAGEMENT</div>
        <div class="stage-management-panel wo-management">
            <section>
                <h4>STAGE STATUS</h4>
                <div class="stage-status-value {{ $statusClass }}">{{ $statusText }}</div>
            </section>

            <section>
                <h4>STAGE HANDLING</h4>
                <div class="stage-total">
                    <span>Total Stage Handling<br>Time</span>
                    <strong id="reviewHandlingClock">00:00:00</strong>
                </div>
                <dl class="stage-handling">
                    <div><dt>In Progress</dt><dd>00:00:00</dd></div>
                    <div><dt>On Hold</dt><dd>00:00:00</dd></div>
                    <div><dt>Waiting</dt><dd>00:00:00</dd></div>
                    <div><dt>Responsible</dt><dd>{{ $leadConsultant }}</dd></div>
                    <div><dt>Executor</dt><dd>{{ $leadAssociate }}</dd></div>
                    <div><dt>Waiting On</dt><dd>—</dd></div>
                    <div><dt>Started</dt><dd>—</dd></div>
                    <div><dt>Completed</dt><dd>{{ $completedDate }}</dd></div>
                </dl>
            </section>

            <section>
                <h4>ASSIGNED REVIEW</h4>
                <dl class="stage-handling">
                    <div><dt>Reviewer</dt><dd>{{ $reviewerName }}</dd></div>
                    <div><dt>Approver</dt><dd>{{ $approverName }}</dd></div>
                </dl>
            </section>

            <section>
                <h4>STAGE CONTROLS</h4>
                @if(!$isApproved && $stageProgress < 20)
                    <div class="workflow-note">Complete the Work Order before this stage becomes available.</div>
                @else
                    <div class="review-form" style="margin-bottom: 8px;">
                        <label style="font-size: 8px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Acting Reviewer</label>
                        <div style="font-size: 8px; font-weight: 700; color: #1e293b; padding: 5px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">{{ $reviewerName }}</div>
                    </div>
                    <div style="font-size: 8px; color: #166534; font-weight: 700; padding: 6px 8px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px;">
                        ✓ SOW Reviewed &amp; Approved
                    </div>
                @endif
            </section>

            <section>
                <h4>NTP GATE</h4>
                <div class="ntp-gate @if($isApproved) unlocked @endif" id="ntpGate">
                    @if($isApproved)
                        <strong>NTP Unlocked.</strong> All required reviewers have reviewed and accepted the SOW.
                    @else
                        NTP remains locked until every required reviewer has reviewed and accepted the SOW.
                    @endif
                </div>
                <button class="ntp-btn" id="prepareNtpBtn" @if(!$isApproved) disabled @endif onclick="document.querySelector('[data-tab=ntp]')?.click()">
                    Prepare NTP
                </button>
            </section>
        </div>
    </aside>

    <!-- RIGHT CONTENT: EXACT SOW REVIEW DOCUMENT -->
    <section class="content">
        <div class="docframe review-readonly" id="reviewDocument">
            <div class="dochead">
                <div class="docbrand">
                    John Kelly
                    <div>◉ Company</div>
                </div>
                <div class="doctitle">
                    <h1>SOW</h1>
                    <small>PROJ-F-002</small>
                </div>
            </div>

            <!-- Review Document Meta -->
            <div class="docmeta">
                <div>Condeal Reference No.:</div>
                <div class="line">{{ $dealRef }}</div>
                <div>Version No.:</div>
                <div class="line" id="reviewSowVersionNo">{{ $versionNo }}</div>

                <div>Business Name:</div>
                <div class="line">{{ $businessName }}</div>
                <div>SOW No.:</div>
                <div class="line">{{ $sowNo }}</div>

                <div>Client Name:</div>
                <div class="line">{{ $clientName }}</div>
                <div>Date Prepared:</div>
                <div class="line">{{ $datePrepared }}</div>

                <div>Lead Consultant / Project Manager:</div>
                <div class="line" id="leadConsultantName">{{ $leadConsultant }}</div>
                <div>Lead Associate:</div>
                <div class="line" id="leadAssociateName">{{ $leadAssociate }}</div>

                <div>Within Scope Prepared By:</div>
                <div class="line" id="withinPreparedBy">{{ $leadConsultant }} &nbsp; {{ $leadAssociate }}</div>
                <div>Out of Scope Prepared By:</div>
                <div class="line" id="outScopePreparedBy">{{ $leadConsultant }} &nbsp; {{ $leadAssociate }}</div>
            </div>

            <div class="review-complete-banner show" id="reviewCompleteBanner">
                <strong>Review Complete:</strong> All required reviewers have reviewed and accepted the SOW. NTP preparation is now unlocked.
            </div>

            <div class="builder-toolbar">
                <div class="toolbar" style="margin: 0">
                    <div class="sow-view-tabs">
                        <button type="button" class="sow-view-tab active" id="withinProjTabBtn" onclick="toggleProjScopePanel('within')">
                            Within Scope
                        </button>
                        <button type="button" class="sow-view-tab outscope" id="outScopeProjTabBtn" onclick="toggleProjScopePanel('out')">
                            Out of Scope / Exclusions
                        </button>
                    </div>
                </div>
                <div class="imeta" id="viewHint" style="font-size: 8px; color: #667085;">Reviewing approved scope.</div>
            </div>

            <!-- WITHIN SCOPE -->
            <div class="sow-builder-panel active" id="withinProjScopeSection">
                <div class="scope-title">WITHIN SCOPE</div>
                <div class="scope-wrap">
                    <table class="scope-table">
                        <thead>
                            <tr>
                                <th style="width: 5%">Item</th>
                                <th style="width: 20%">Main Task Description</th>
                                <th style="width: 25%">Sub Task Description</th>
                                <th style="width: 13%">Responsibility</th>
                                <th style="width: 14%">Assigned Person</th>
                                <th style="width: 7%">Duration</th>
                                <th style="width: 8%">Start Date</th>
                                <th style="width: 8%">End Date</th>
                            </tr>
                        </thead>
                        <tbody id="withinRows">
                            <tr class="parent-row">
                                <td class="item-code">1</td>
                                <td>Corporate &amp; Project Documentation</td>
                                <td style="color: #64748b; font-style: italic;">Main scope item / workstream</td>
                                <td><span class="badge" style="background:#eef3ff; color:#294b98; border:1px solid #c9d8fb; padding:2px 6px; border-radius:4px; font-size:7px; font-weight:700;">Responsible</span></td>
                                <td>Project Manager</td>
                                <td>7h</td>
                                <td>—</td>
                                <td>—</td>
                            </tr>
                            <tr class="child-row-sow">
                                <td class="item-code">1.1</td>
                                <td>Corporate &amp; Project Documentation</td>
                                <td>Review existing corporate structure and agreements</td>
                                <td><span class="badge" style="background:#eef3ff; color:#294b98; border:1px solid #c9d8fb; padding:2px 6px; border-radius:4px; font-size:7px; font-weight:700;">Responsible</span></td>
                                <td>{{ $leadAssociate }}</td>
                                <td>2h</td>
                                <td>mm / dd / yyyy</td>
                                <td>mm / dd / yyyy</td>
                            </tr>
                            <tr class="child-row-sow">
                                <td class="item-code">1.2</td>
                                <td>Corporate &amp; Project Documentation</td>
                                <td>Draft master project and technical specifications</td>
                                <td><span class="badge" style="background:#eef3ff; color:#294b98; border:1px solid #c9d8fb; padding:2px 6px; border-radius:4px; font-size:7px; font-weight:700;">Responsible</span></td>
                                <td>{{ $leadAssociate }}</td>
                                <td>3h</td>
                                <td>mm / dd / yyyy</td>
                                <td>mm / dd / yyyy</td>
                            </tr>
                            <tr class="parent-row">
                                <td class="item-code">2</td>
                                <td>Regulatory &amp; Statutory Compliance</td>
                                <td style="color: #64748b; font-style: italic;">Main scope item / workstream</td>
                                <td><span class="badge" style="background:#eef3ff; color:#294b98; border:1px solid #c9d8fb; padding:2px 6px; border-radius:4px; font-size:7px; font-weight:700;">Responsible</span></td>
                                <td>Project Manager</td>
                                <td>5h</td>
                                <td>—</td>
                                <td>—</td>
                            </tr>
                            <tr class="child-row-sow">
                                <td class="item-code">2.1</td>
                                <td>Regulatory &amp; Statutory Compliance</td>
                                <td>Coordinate permits and statutory filing requirements</td>
                                <td><span class="badge" style="background:#eef3ff; color:#294b98; border:1px solid #c9d8fb; padding:2px 6px; border-radius:4px; font-size:7px; font-weight:700;">Responsible</span></td>
                                <td>{{ $leadAssociate }}</td>
                                <td>3h</td>
                                <td>mm / dd / yyyy</td>
                                <td>mm / dd / yyyy</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6"></td>
                                <td style="text-align: right; font-weight: 700">Total:</td>
                                <td id="withinTotal">4 item(s)</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- OUT OF SCOPE -->
            <div class="sow-builder-panel" id="outScopeProjSection" style="display: none;">
                <div class="scope-title" style="background: #815f24;">OUT OF SCOPE / EXCLUSIONS</div>
                <div class="scope-wrap">
                    <table class="scope-table">
                        <thead>
                            <tr>
                                <th style="width: 5%">Item</th>
                                <th style="width: 20%">Main Task Description</th>
                                <th style="width: 25%">Sub Task Description</th>
                                <th style="width: 13%">Responsibility</th>
                                <th style="width: 14%">Assigned Person</th>
                                <th style="width: 7%">Duration</th>
                                <th style="width: 8%">Start Date</th>
                                <th style="width: 8%">End Date</th>
                            </tr>
                        </thead>
                        <tbody id="outScopeRows">
                            <tr class="parent-row">
                                <td class="item-code">1</td>
                                <td>Litigation &amp; Court Representation</td>
                                <td style="color: #64748b; font-style: italic;">Excluded from standard project scope</td>
                                <td><span class="badge" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:2px 6px; border-radius:4px; font-size:7px; font-weight:700;">Excluded</span></td>
                                <td>External Counsel</td>
                                <td>—</td>
                                <td>—</td>
                                <td>—</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6"></td>
                                <td style="text-align: right; font-weight: 700">Total:</td>
                                <td id="outScopeTotal">1 item(s)</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- SOW SUMMARY -->
            <div class="scope-title" id="summaryTitle" style="margin-top: 14px;">SOW SUMMARY</div>
            <div class="sow-summary-grid" id="sowSummary">
                <div class="sow-summary-card">
                    <label>TOTAL ITEMS</label>
                    <strong>10</strong>
                </div>
                <div class="sow-summary-card">
                    <label>MAIN TASKS</label>
                    <strong>2</strong>
                </div>
                <div class="sow-summary-card">
                    <label>CHILD TASKS</label>
                    <strong>8</strong>
                </div>
                <div class="sow-summary-card">
                    <label>PLANNED DURATION</label>
                    <strong>15h</strong>
                </div>
                <div class="sow-summary-card">
                    <label>PLANNED START</label>
                    <strong>—</strong>
                </div>
                <div class="sow-summary-card">
                    <label>PLANNED END</label>
                    <strong>—</strong>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function toggleProjScopePanel(scope) {
    const withinSec = document.getElementById('withinProjScopeSection');
    const outSec = document.getElementById('outScopeProjSection');
    const withinBtn = document.getElementById('withinProjTabBtn');
    const outBtn = document.getElementById('outScopeProjTabBtn');

    if (scope === 'within') {
        withinSec.style.display = 'block';
        outSec.style.display = 'none';
        withinBtn.classList.add('active');
        outBtn.classList.remove('active');
    } else {
        withinSec.style.display = 'none';
        outSec.style.display = 'block';
        withinBtn.classList.remove('active');
        outBtn.classList.add('active');
    }
}
</script>
