@php
    $woNumber = 'PROJ-WO-' . substr($project->project_code, -8);
    $smRef = 'SM-' . $project->project_code;
    $startRef = 'START-' . substr($project->project_code, -8);
    $dealRef = $project->deal?->deal_code ?? 'No Deal reference';
    $businessName = $project->business_name ?: ($project->company?->company_name ?: '—');
    $serviceArea = $project->service_area ?: 'Corporate Services';
    $serviceName = $project->name ?: ($project->deal?->deal_title ?: 'Project Engagement');
    $engagementType = $project->engagement_type ?: 'Project';
    $targetStart = $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->format('M d, Y') : 'Aug 17, 2026';
    $targetEnd = $project->target_completion_date ? \Carbon\Carbon::parse($project->target_completion_date)->format('M d, Y') : 'Sep 18, 2026';
    $woStatus = $project->status === 'Draft' ? 'Draft' : ($projectLocked ? 'Completed' : 'Draft');

    $allResponsibilities = ["Responsible", "Executor", "Monitor", "Reviewer", "Approver", "Coordinator", "Support"];
    $allPeople = ["John Kelly Abalde", "Lyndon Earl Rio", "Maria Santos", "Rubeca Potayre", "Anne Reyes", "Carlos Mendoza", "Diana Cruz", "Paolo Lim"];

    $projectRoles = [
        ['role' => 'Project Manager', 'responsibilities' => ['Responsible'], 'persons' => [$project->assigned_project_manager ?: 'John Kelly Abalde'], 'ack' => 'Pending'],
        ['role' => 'Lead Consultant', 'responsibilities' => ['Executor'], 'persons' => [], 'ack' => 'Pending'],
        ['role' => 'Accounting Representative', 'responsibilities' => [], 'persons' => [], 'ack' => 'Pending'],
        ['role' => 'Operations Representative', 'responsibilities' => [], 'persons' => [], 'ack' => 'Pending'],
        ['role' => 'Management Representative', 'responsibilities' => [], 'persons' => [], 'ack' => 'Pending'],
        ['role' => 'Finance Representative', 'responsibilities' => [], 'persons' => [], 'ack' => 'Pending'],
        ['role' => 'Admin / Records Representative', 'responsibilities' => [], 'persons' => [], 'ack' => 'Pending'],
        ['role' => 'Sales / Account Representative', 'responsibilities' => [], 'persons' => [], 'ack' => 'Pending'],
    ];

    $allStagesList = ['Work Order', 'SOW', 'Review', 'NTP', 'Execution', 'Reporting', 'Delivery', 'Completion'];
    $defaultAssignments = [
        'Work Order' => [
            'Reviewer' => ['Maria Santos'],
            'Approver' => ['Lyndon Earl Rio'],
        ],
        'SOW' => [
            'Responsible' => [$project->assigned_project_manager ?: 'John Kelly Abalde'],
            'Executor' => [$project->assigned_associate ?: 'Rubeca Potayre'],
            'Reviewer' => ['Maria Santos'],
            'Approver' => ['Lyndon Earl Rio'],
        ],
        'Review' => [
            'Reviewer' => [$project->assigned_consultant ?: 'John Kelly Abalde'],
            'Approver' => ['Lyndon Earl Rio'],
        ],
        'NTP' => [
            'Approver' => [$contactName ?: 'Elena Rodriguez'],
        ],
        'Execution' => [
            'Responsible' => [$project->assigned_associate ?: 'Rubeca Potayre'],
        ],
    ];

    $projectStages = [];
    foreach ($allStagesList as $sName) {
        foreach ($allResponsibilities as $resp) {
            $assignedPersons = $defaultAssignments[$sName][$resp] ?? [];
            $projectStages[] = [
                'stage' => $sName,
                'responsibilities' => [$resp],
                'persons' => $assignedPersons,
                'ack' => 'Pending'
            ];
        }
    }

    $scheduleRows = [
        ['stage' => 'Work Order', 'start' => $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->format('Y-m-d') : '2026-08-17', 'end' => $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->addDays(3)->format('Y-m-d') : '2026-08-20'],
        ['stage' => 'SOW', 'start' => $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->addDays(3)->format('Y-m-d') : '2026-08-20', 'end' => $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->addDays(7)->format('Y-m-d') : '2026-08-24'],
        ['stage' => 'Review', 'start' => $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->addDays(7)->format('Y-m-d') : '2026-08-24', 'end' => $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->addDays(10)->format('Y-m-d') : '2026-08-27'],
        ['stage' => 'NTP', 'start' => $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->addDays(10)->format('Y-m-d') : '2026-08-27', 'end' => $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->addDays(14)->format('Y-m-d') : '2026-08-31'],
        ['stage' => 'Execution', 'start' => $project->planned_start_date ? \Carbon\Carbon::parse($project->planned_start_date)->addDays(14)->format('Y-m-d') : '2026-08-31', 'end' => $project->target_completion_date ? \Carbon\Carbon::parse($project->target_completion_date)->format('Y-m-d') : '2026-09-18'],
    ];
@endphp

<div class="layout">
    <!-- LEFT SIDECARD: EXACT MATCH TO USER SCREENSHOT -->
    <aside class="sidecard" aria-live="polite">
        <div class="side-title">STAGE MANAGEMENT</div>
        <div class="stage-management-panel wo-management">
            <section>
                <h4>STAGE STATUS</h4>
                <div class="stage-status-value {{ strtolower($woStatus) }}">{{ $woStatus }}</div>
            </section>

            <section>
                <h4>STAGE HANDLING</h4>
                <div class="stage-total">
                    <span>Total Stage Handling<br>Time</span>
                    <strong id="woHandling">00:00:00</strong>
                </div>
                <dl class="stage-handling">
                    <div><dt>In Progress</dt><dd>00:00:00</dd></div>
                    <div><dt>On Hold</dt><dd>00:00:00</dd></div>
                    <div><dt>Waiting Time</dt><dd id="woWaiting">00:00:00</dd></div>
                    <div><dt>Responsible</dt><dd>Operations</dd></div>
                    <div><dt>Waiting On</dt><dd>—</dd></div>
                    <div><dt>Started</dt><dd>—</dd></div>
                    <div><dt>Completed</dt><dd>—</dd></div>
                    <div><dt>Canceled</dt><dd>—</dd></div>
                    <div><dt>Stage Elapsed</dt><dd id="woElapsed">00:00:00</dd></div>
                </dl>
            </section>

            <section>
                <h4>STAGE CONTROLS</h4>
                <div class="stage-controls">
                    <button class="stage-control" id="woStart" type="button">Start Work Order</button>
                    <button class="stage-control" data-wo="save" type="button">Save Draft</button>
                    <button class="stage-control primary" data-wo="submit" type="button">Submit for Review</button>
                </div>
                <div class="workflow-note">SOW unlocks after approval, notification, and every required acknowledgment.</div>
            </section>
        </div>
    </aside>

    <!-- RIGHT CONTENT AREA -->
    <section class="content" aria-live="polite">
        <div class="wo-stack">
            <!-- BANNER -->
            <div class="wo-banner">
                <div>
                    <strong>Project Work Order &middot; Operations</strong><br>
                    <span>Accountability must be approved and acknowledged before SOW becomes available.</span>
                </div>
                <span class="badge">{{ $woStatus }}</span>
            </div>

            <!-- CARD 1: PROJECT INFORMATION -->
            <section class="card">
                <div class="cardhead">
                    <div>
                        <strong>Project Information</strong>
                        <div class="imeta">Auto-filled from the Deal, START, and issued Service Memo.</div>
                    </div>
                </div>
                <div class="cardbody wo-grid">
                    <div class="wo-field"><span>Work Order No.</span><input readonly value="{{ $woNumber }}"></div>
                    <div class="wo-field"><span>Project Ref No.</span><input readonly value="{{ $project->project_code }}"></div>
                    <div class="wo-field"><span>Source Service Memo</span><input readonly value="{{ $smRef }}"></div>
                    <div class="wo-field"><span>Source START</span><input readonly value="{{ $startRef }}"></div>
                    <div class="wo-field"><span>Source Deal</span><input readonly value="{{ $dealRef }}"></div>
                    <div class="wo-field"><span>Client</span><input readonly value="{{ $contactName }}"></div>
                    <div class="wo-field"><span>Business / Company</span><input readonly value="{{ $businessName }}"></div>
                    <div class="wo-field"><span>Service / Project</span><input readonly value="{{ $serviceName }}"></div>
                    <div class="wo-field"><span>Service Area</span><input readonly value="{{ $serviceArea }}"></div>
                    <div class="wo-field"><span>Engagement Type</span><input readonly value="{{ $engagementType }}"></div>
                    <div class="wo-field"><span>Target Start Date</span><input readonly value="{{ $targetStart }}"></div>
                    <div class="wo-field"><span>Target Project End Date</span><input readonly value="{{ $targetEnd }}"></div>
                </div>
            </section>

            <!-- CARD 2: PROJECT ROLES & ACCOUNTABILITY -->
            <section class="card">
                <div class="cardhead">
                    <div>
                        <strong>Project Roles &amp; Accountability</strong>
                        <div class="imeta">One assignment per row, with one or more responsibilities and people.</div>
                    </div>
                </div>
                <div class="cardbody">
                    <div class="wo-table-wrap">
                        <table class="wo-table" id="projectRoles">
                            <thead>
                                <tr>
                                    <th>Project Role</th>
                                    <th>Responsibility</th>
                                    <th>Assigned Person</th>
                                    <th>Acknowledgment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projectRoles as $i => $r)
                                    <tr data-id="pr-{{ $i }}">
                                        <td>
                                            <input value="{{ $r['role'] }}" data-field="role" readonly>
                                        </td>
                                        <td>
                                            <details class="wo-multi" data-field="responsibilities">
                                                <summary>{{ count($r['responsibilities']) ? implode(', ', $r['responsibilities']) : 'Select Responsibility' }}</summary>
                                                <div class="wo-multi-menu">
                                                    @foreach($allResponsibilities as $resp)
                                                        <label>
                                                            <input type="checkbox" value="{{ $resp }}" {{ in_array($resp, $r['responsibilities']) ? 'checked' : '' }}> {{ $resp }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </details>
                                        </td>
                                        <td>
                                            <details class="wo-multi" data-field="persons">
                                                <summary>{{ count($r['persons']) ? implode(', ', $r['persons']) : 'Select Person' }}</summary>
                                                <div class="wo-multi-menu">
                                                    @foreach($allPeople as $person)
                                                        <label>
                                                            <input type="checkbox" value="{{ $person }}" {{ in_array($person, $r['persons']) ? 'checked' : '' }}> {{ $person }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </details>
                                        </td>
                                        <td class="wo-status">
                                            <select data-field="acknowledgment">
                                                <option value="Pending" {{ $r['ack'] === 'Pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="Acknowledged" {{ $r['ack'] === 'Acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                                                <option value="Declined" {{ $r['ack'] === 'Declined' ? 'selected' : '' }}>Declined</option>
                                                <option value="Issue Raised" {{ $r['ack'] === 'Issue Raised' ? 'selected' : '' }}>Issue Raised</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="wo-footer">
                        <button class="btn" id="addProjectRole" type="button">+ Add Project Role</button>
                        <p class="wo-note">Acknowledgment becomes available after approved assignments are notified.</p>
                    </div>
                </div>
            </section>

            <!-- CARD 3: STAGE ROLES & ACCOUNTABILITY -->
            <section class="card">
                <div class="cardhead">
                    <div>
                        <strong>Stage Roles &amp; Accountability</strong>
                        <div class="imeta">Each lifecycle stage has one row for every standard responsibility category.</div>
                    </div>
                </div>
                <div class="cardbody">
                    <div class="wo-table-wrap">
                        <table class="wo-table" id="stageRoles">
                            <thead>
                                <tr>
                                    <th>Stage</th>
                                    <th>Responsibility</th>
                                    <th>Assigned Person</th>
                                    <th>Acknowledgment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projectStages as $i => $st)
                                    <tr data-id="sa-{{ $i }}">
                                        <td>
                                            <input value="{{ $st['stage'] }}" readonly>
                                        </td>
                                        <td>
                                            <input value="{{ implode(', ', $st['responsibilities']) }}" readonly>
                                        </td>
                                        <td>
                                            <details class="wo-multi" data-field="persons">
                                                <summary>{{ count($st['persons']) ? implode(', ', $st['persons']) : 'Select Person' }}</summary>
                                                <div class="wo-multi-menu">
                                                    @foreach($allPeople as $person)
                                                        <label>
                                                            <input type="checkbox" value="{{ $person }}" {{ in_array($person, $st['persons']) ? 'checked' : '' }}> {{ $person }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </details>
                                        </td>
                                        <td class="wo-status">
                                            <select data-field="acknowledgment">
                                                <option value="Pending" {{ $st['ack'] === 'Pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="Acknowledged" {{ $st['ack'] === 'Acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                                                <option value="Declined" {{ $st['ack'] === 'Declined' ? 'selected' : '' }}>Declined</option>
                                                <option value="Issue Raised" {{ $st['ack'] === 'Issue Raised' ? 'selected' : '' }}>Issue Raised</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="wo-footer">
                        <p class="wo-note">Assign one or more people independently under each responsibility row.</p>
                    </div>
                </div>
            </section>

            <!-- CARD 4: STAGE SCHEDULE -->
            <section class="card">
                <div class="cardhead">
                    <div>
                        <strong>Stage Schedule</strong>
                        <div class="imeta">Define the planned start and end date for every project lifecycle stage.</div>
                    </div>
                </div>
                <div class="cardbody">
                    <div class="wo-table-wrap">
                        <table class="wo-table wo-schedule" id="stageSchedule">
                            <thead>
                                <tr>
                                    <th>Stage</th>
                                    <th>Target Start Date</th>
                                    <th>Target End Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheduleRows as $sch)
                                    <tr data-stage="{{ $sch['stage'] }}">
                                        <td><strong>{{ $sch['stage'] }}</strong></td>
                                        <td><input type="date" data-schedule="targetStart" value="{{ $sch['start'] }}" aria-label="{{ $sch['stage'] }} target start date"></td>
                                        <td><input type="date" data-schedule="targetEnd" value="{{ $sch['end'] }}" min="{{ $sch['start'] }}" aria-label="{{ $sch['stage'] }} target end date"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="wo-footer">
                        <p class="wo-note">Target end date cannot be earlier than the stage target start date.</p>
                    </div>
                </div>
            </section>
        </div>
    </section>
</div>
