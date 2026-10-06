(function () {
        const modal = document.getElementById("taskWorkspaceModal");
        let activeTaskId = null, visibleUpdateCount = 20;
        const localDemo = false;
        // Execution uses an authorization snapshot, not the live RSAT editor collection.
        // The exact legacy copy below was created by the old Duplicate Workstream demo control
        // and was never part of the approved RSAT shown to the user.
        if (!Array.isArray(ORDO_STATE.executionAuthorizedStreams)) {
          const approvedSource = Array.isArray(ORDO_STATE.approvedSowStreams)
            ? ORDO_STATE.approvedSowStreams
            : (ORDO_STATE.streams || []).filter((stream) => stream.name !== "BIR Share Transfer Processing (Copy)");
          ORDO_STATE.executionAuthorizedStreams = JSON.parse(JSON.stringify(approvedSource));
          ORDO.save();
        }
        ORDO_STATE.streams = ORDO_STATE.executionAuthorizedStreams;
        document.getElementById("resetTaskForTesting").hidden = !localDemo;
        const workspaceFor = (task) => {
          task.executionWorkspace ||= { attachments: [], updates: [] };
          task.executionWorkspace.attachments ||= [];
          task.executionWorkspace.updates ||= [];
          if (!task.executionWorkspace.timer) {
            const legacy = ORDO.timerFor(task.id);
            task.executionWorkspace.timer = {
              state: legacy.state === "running" ? "working" : legacy.state === "paused" ? "paused" : task.status === "Completed" ? "done" : "stopped",
              workingSeconds: Number(task.seconds || 0) + Number(legacy.seconds || 0),
              pausedSeconds: 0,
              workingStartedAt: legacy.state === "running" && legacy.started ? legacy.started : null,
              pausedStartedAt: legacy.state === "paused" ? Date.now() : null,
            };
            task.seconds = 0;
            legacy.state = "stopped"; legacy.seconds = 0; legacy.started = null;
          }
          return task.executionWorkspace;
        };
        const findTaskContext = (taskId) => {
          for (const stream of ORDO_STATE.streams) {
            const task = stream.tasks.find((item) => item.id === taskId);
            if (task) return { stream, task };
          }
          return null;
        };
        const formatBytes = (bytes) => bytes < 1024 ? `${bytes} B` : bytes < 1048576 ? `${Math.round(bytes / 1024)} KB` : `${(bytes / 1048576).toFixed(1)} MB`;
        const responsibilitiesFor = (item) => Array.isArray(item.responsibilities) && item.responsibilities.length ? item.responsibilities : ["Responsible"];
        const personsFor = (item, fallbackField) => {
          const people = item.persons || item.assignedPersons;
          if (Array.isArray(people) && people.length) return [...new Set(people.filter(Boolean))];
          return item[fallbackField] ? [...new Set(String(item[fallbackField]).split(/[,;]+/).map((name) => name.trim()).filter(Boolean))] : [];
        };
        const runningSeconds = (startedAt) => startedAt ? Math.max(0, Math.floor((Date.now() - Number(startedAt)) / 1000)) : 0;
        const taskMetrics = (task) => {
          const timer = workspaceFor(task).timer;
          const working = Number(timer.workingSeconds || 0) + (timer.state === "working" ? runningSeconds(timer.workingStartedAt) : 0);
          const paused = Number(timer.pausedSeconds || 0) + (timer.state === "paused" ? runningSeconds(timer.pausedStartedAt) : 0);
          return { working, paused, total: working + paused, state: timer.state };
        };
        const parseEstimate = (value) => { const match = String(value || "").match(/[\d.]+/); return match ? Number(match[0]) : 0; };
        const displayDate = (value) => value ? new Date(`${value}T00:00:00`).toLocaleDateString() : "Not set";
        const effectiveTargetEnd = (task) => workspaceFor(task).extensions?.[0]?.revisedEnd || task.end || "";
        const taskSchedule = (task) => {
          const workspace = workspaceFor(task), targetEnd = effectiveTargetEnd(task), nowTime = Date.now(), endTime = targetEnd ? new Date(`${targetEnd}T23:59:59`).getTime() : null;
          const targetStartTime = task.start ? new Date(`${task.start}T00:00:00`).getTime() : null;
          const startTime = workspace.actualStartedAt ? new Date(workspace.actualStartedAt).getTime() : null;
          const elapsedDays = startTime ? Math.max(0, Math.ceil(((workspace.completedAt ? new Date(workspace.completedAt).getTime() : nowTime) - startTime) / 86400000)) : 0;
          const overdueDays = endTime && task.status !== "Completed" && nowTime > endTime ? Math.ceil((nowTime - endTime) / 86400000) : 0;
          const remainingDays = endTime ? Math.ceil((endTime - nowTime) / 86400000) : null;
          const completedLateDays = task.status === "Completed" && workspace.completedAt && endTime && new Date(workspace.completedAt).getTime() > endTime ? Math.ceil((new Date(workspace.completedAt).getTime() - endTime) / 86400000) : 0;
          if (task.status === "Completed") return { label:completedLateDays ? `Completed ${completedLateDays}d late` : "Completed on time", tone:completedLateDays ? "warning" : "good", overdueDays:completedLateDays, elapsedDays, targetEnd };
          if (overdueDays) return { label:`Overdue ${overdueDays}d`, tone:"overdue", overdueDays, elapsedDays, targetEnd };
          if (!startTime && targetStartTime && nowTime > targetStartTime) return { label:"Start overdue", tone:"warning", overdueDays:0, elapsedDays, targetEnd };
          if (remainingDays !== null && remainingDays <= 2) return { label:remainingDays < 0 ? "Overdue" : `Due in ${remainingDays}d`, tone:"warning", overdueDays:0, elapsedDays, targetEnd };
          return { label:startTime ? "On Schedule" : "Not Started", tone:startTime ? "good" : "neutral", overdueDays:0, elapsedDays, targetEnd };
        };
        const streamSchedule = (stream) => {
          const starts = stream.tasks.map((task) => task.start).filter(Boolean).sort(), ends = stream.tasks.map(effectiveTargetEnd).filter(Boolean).sort();
          return { start:starts[0] || "", end:ends.at(-1) || "", duration:stream.tasks.reduce((sum, task) => sum + parseEstimate(task.est), 0), completed:stream.tasks.filter((task) => task.status === "Completed").length };
        };
        const settleTaskTimer = (task) => {
          const timer = workspaceFor(task).timer;
          if (timer.state === "working") timer.workingSeconds = Number(timer.workingSeconds || 0) + runningSeconds(timer.workingStartedAt);
          if (timer.state === "paused") timer.pausedSeconds = Number(timer.pausedSeconds || 0) + runningSeconds(timer.pausedStartedAt);
          timer.workingStartedAt = null; timer.pausedStartedAt = null;
        };
        const fileDataUrl = (file) => new Promise((resolve) => { const reader=new FileReader();reader.onload=()=>resolve(reader.result);reader.onerror=()=>resolve(null);reader.readAsDataURL(file); });
        const clientCommunication = (updateId) => (ORDO_STATE.clientReportCommunications || []).find((entry) => String(entry.updateId) === String(updateId));
        const clientProofControls = (update) => {
          if (!update.reportToClient) return "";
          update.acknowledgment ||= { email:null, manual:null };
          const email=update.acknowledgment.email,manual=update.acknowledgment.manual;
          return `<div class="client-proof-panel"><div class="client-proof-status"><span>Delivery: <strong>Sent to Client</strong></span><span>Email acknowledgment: <strong>${email?`Acknowledged ${new Date(email.at).toLocaleString()}`:"Pending"}</strong></span><span>Signed copy: <strong>${manual?`Uploaded ${new Date(manual.at).toLocaleString()}`:"Pending"}</strong></span></div><div class="client-proof-actions"><button type="button" class="print-client-update" data-update-id="${ORDO.esc(update.id)}">Print Update</button><button type="button" class="record-email-ack" data-update-id="${ORDO.esc(update.id)}">${email?"Email Acknowledged":"Record Email Acknowledgment"}</button><label for="signed-update-${ORDO.esc(update.id)}">${manual?"Change Signed Update":"Upload Signed Update"}</label><input hidden type="file" accept="application/pdf,image/*" id="signed-update-${ORDO.esc(update.id)}" class="signed-update-upload" data-update-id="${ORDO.esc(update.id)}">${manual?.dataUrl?`<button type="button" class="view-signed-update" data-update-id="${ORDO.esc(update.id)}">View Signed Update</button>`:""}</div></div>`;
        };
        function printClientUpdate(update,context) {
          const project=ORDO_STATE.projects.find((item)=>item.id===ORDO_PROJECT_ID)||{},workOrder=ORDO_STATE.workOrder||{},communication=clientCommunication(update.id),preparedBy=update.user||ORDO_STATE.viewer||"Regular Team";
          const html=window.ORDO_CLIENT_UPDATE_PRINT_HTML({update,context,project,workOrder,communication,preparedBy,responsibilities:responsibilitiesFor(context.task),persons:personsFor(context.task,"assignee")});
          const win=window.open("","_blank");if(!win)return alert("Allow pop-ups to open the print preview.");win.document.write(html);win.document.close();win.focus();win.print();
        }

        function renderTaskWorkspace() {
          const context = findTaskContext(activeTaskId);
          if (!context) return;
          const { stream, task } = context, workspace = workspaceFor(task), metrics = taskMetrics(task);
          const schedule = taskSchedule(task);
          document.getElementById("taskWorkspaceTitle").textContent = task.title;
          document.getElementById("taskWorkspacePath").textContent = `${stream.name} · Approved RSAT task`;
          document.getElementById("taskWorkspaceMeta").innerHTML = `<span>Assigned Persons: <strong>${ORDO.esc(personsFor(task, "assignee").join(", ") || "Unassigned")}</strong></span><span>Responsibility:</span><div class="responsibility-list">${responsibilitiesFor(task).map((item)=>`<b>${ORDO.esc(item)}</b>`).join("")}</div>`;
          document.getElementById("taskScheduleSummary").innerHTML = `<div><span>Task Status</span><strong id="taskWorkspaceStatus">${ORDO.esc(task.status)}</strong></div><div><span>Target Start</span><strong>${displayDate(task.start)}</strong></div><div><span>Target End</span><strong>${displayDate(schedule.targetEnd)}</strong></div><div><span>Planned Duration</span><strong>${ORDO.esc(task.est || "—")}</strong></div><div><span>Working Time</span><strong id="taskWorkingTime">${ORDO.esc(ORDO.dur(metrics.working))}</strong></div><div><span>On Hold Time</span><strong id="taskPausedTime">${ORDO.esc(ORDO.dur(metrics.paused))}</strong></div><div><span>Total Time</span><strong id="taskWorkspaceTime">${ORDO.esc(ORDO.dur(metrics.total))}</strong></div><div><span>Actual Elapsed</span><strong>${schedule.elapsedDays ? `${schedule.elapsedDays} day${schedule.elapsedDays === 1 ? "" : "s"}` : "Not started"}</strong></div><div><span>Schedule</span><strong><span class="schedule-state ${schedule.tone}">${schedule.label}</span></strong></div>`;
          workspace.extensions ||= [];
          const extensionPanel = document.getElementById("taskExtensionPanel");
          extensionPanel.classList.toggle("visible", schedule.overdueDays > 0 || schedule.tone === "warning" || workspace.extensions.length > 0);
          document.getElementById("extensionTargetEnd").min = schedule.targetEnd || task.end || new Date().toISOString().slice(0,10);
          document.getElementById("extensionHistory").innerHTML = workspace.extensions.length ? `<strong>Extension History</strong><br>${workspace.extensions.map((item) => `${displayDate(item.previousEnd)} → ${displayDate(item.revisedEnd)} · ${ORDO.esc(item.category)} · ${ORDO.esc(item.details)} · ${new Date(item.at).toLocaleString()}`).join("<br>")}` : "No extension recorded.";
          const filter = document.getElementById("taskActivityFilter").value;
          const filteredUpdates = workspace.updates.filter((update) => filter === "all" || (filter === "updates" && Boolean(update.text)) || (filter === "files" && Boolean(update.files?.length)) || (filter === "extensions" && Boolean(update.extensionId)));
          const visibleUpdates = filteredUpdates.slice(0, visibleUpdateCount), fileRecords = new Map(workspace.attachments.map((file) => [file.id,file]));
          document.getElementById("taskUpdateList").innerHTML = visibleUpdates.length ? visibleUpdates.map((update) => { const snapshot = update.timing || taskMetrics(task), audience = update.submissionType || (update.reportToClient ? "client" : "internal"), audienceLabel = audience === "both" ? "Internal & Client Submission" : audience === "client" ? "Client Submission" : "Internal Submission"; return `<article class="task-timeline-entry"><div class="task-record-meta">${new Date(update.at).toLocaleString()} · ${ORDO.esc(update.user)}</div><span class="report-record">${audienceLabel}</span>${update.text ? `<p>${ORDO.esc(update.text)}</p>` : ""}${update.files?.length ? `<div class="timeline-files">${update.files.map((file) => { const saved=fileRecords.get(file.id)||file; return `<div class="timeline-file"><span>${ORDO.esc(saved.name)} · ${formatBytes(saved.size||0)}</span><div>${saved.dataUrl?`<button type="button" class="view-task-attachment" data-attachment-id="${ORDO.esc(saved.id)}">View</button>`:""}<button type="button" class="remove-task-attachment" data-attachment-id="${ORDO.esc(saved.id)}" aria-label="Remove ${ORDO.esc(saved.name)}">Remove</button></div></div>`; }).join("")}</div>` : ""}<div class="task-record-meta">Working ${ORDO.dur(snapshot.working)} · On Hold ${ORDO.dur(snapshot.paused)} · Total ${ORDO.dur(snapshot.total)}</div>${update.reportToClient ? '<span class="report-record">Included in Client RSAT Report</span>' : `<span class="report-record">Internal Record Only</span><br><button type="button" class="history-client-action" data-update-id="${ORDO.esc(update.id)}">Submit to Client</button>`}${clientProofControls(update)}</article>`; }).join("") : '<p class="task-empty">No activity matches this filter.</p>';
          document.getElementById("taskTimelineLoad").hidden = filteredUpdates.length <= visibleUpdateCount;
          // Keep production lifecycle gates, but allow the static localhost mockup to be exercised freely.
          const locked = !ORDO_REGULAR.executionAllowed() || Boolean(ORDO_STATE.transmittal);
          document.getElementById("taskPlay").disabled = locked || task.status === "Completed" || metrics.state === "working";
          document.getElementById("taskPause").disabled = locked || metrics.state !== "working";
          document.getElementById("taskStop").disabled = locked || task.status === "Completed" || metrics.state === "stopped";
          document.getElementById("taskDone").disabled = locked || task.status === "Completed";
          document.getElementById("taskUpdateText").disabled = locked;
          document.getElementById("taskUpdateFiles").disabled = locked;
          document.getElementById("taskSubmissionType").disabled = locked;
          document.getElementById("addTaskUpdate").disabled = locked;
          ["taskPlay","taskPause","taskStop","taskDone"].forEach((id) => document.getElementById(id).classList.remove("is-active"));
          if (metrics.state === "working") document.getElementById("taskPlay").classList.add("is-active");
          if (metrics.state === "paused") document.getElementById("taskPause").classList.add("is-active");
          if (metrics.state === "stopped") document.getElementById("taskStop").classList.add("is-active");
          if (metrics.state === "done") document.getElementById("taskDone").classList.add("is-active");
          document.querySelectorAll(".remove-task-attachment").forEach((button) => button.onclick = () => {
            if (!confirm("Remove this attachment from the task workspace?")) return;
            workspace.attachments = workspace.attachments.filter((file) => file.id !== button.dataset.attachmentId);
            workspace.updates.forEach((update) => { if (update.files) update.files = update.files.filter((file) => file.id !== button.dataset.attachmentId); });
            ORDO.log(`Attachment removed from execution task: ${task.title}`);
            ORDO.save(); renderTaskWorkspace();
          });
          document.querySelectorAll(".view-task-attachment").forEach((button)=>button.onclick=()=>{const file=workspace.attachments.find((item)=>item.id===button.dataset.attachmentId);if(file?.dataUrl)window.open(file.dataUrl,"_blank");});
          document.querySelectorAll(".print-client-update").forEach((button)=>button.onclick=()=>{const update=workspace.updates.find((item)=>item.id===button.dataset.updateId);if(update)printClientUpdate(update,context);});
          document.querySelectorAll(".record-email-ack").forEach((button)=>button.onclick=()=>{const update=workspace.updates.find((item)=>item.id===button.dataset.updateId);if(!update)return;update.acknowledgment||={email:null,manual:null};update.acknowledgment.email={at:new Date().toISOString(),method:"Email",recordedBy:ORDO_STATE.viewer||"Current User",proofId:`EMAIL-ACK-${Date.now()}`};const communication=clientCommunication(update.id);if(communication)communication.status="Client Acknowledged via Email";ORDO.log(`Client email acknowledgment recorded: ${task.title}`);ORDO.save();renderTaskWorkspace();});
          document.querySelectorAll(".signed-update-upload").forEach((input)=>input.onchange=async()=>{const file=input.files?.[0],update=workspace.updates.find((item)=>item.id===input.dataset.updateId);if(!file||!update)return;update.acknowledgment||={email:null,manual:null};update.acknowledgment.manual={name:file.name,type:file.type,size:file.size,dataUrl:await fileDataUrl(file),at:new Date().toISOString(),uploadedBy:ORDO_STATE.viewer||"Current User",proofId:`SIGNED-UPDATE-${Date.now()}`};const communication=clientCommunication(update.id);if(communication)communication.status=update.acknowledgment.email?"Acknowledged via Email and Signed Copy":"Acknowledged via Signed Copy";ORDO.log(`Signed client update uploaded: ${task.title}`);ORDO.save();renderTaskWorkspace();});
          document.querySelectorAll(".view-signed-update").forEach((button)=>button.onclick=()=>{const update=workspace.updates.find((item)=>item.id===button.dataset.updateId);if(update?.acknowledgment?.manual?.dataUrl)window.open(update.acknowledgment.manual.dataUrl,"_blank");});
          document.querySelectorAll(".history-client-action").forEach((button) => button.onclick = () => {
            const current = findTaskContext(activeTaskId), update = workspace.updates.find((item) => item.id === button.dataset.updateId);
            if (!current || !update || update.reportToClient) return;
            if (!confirm("Submit this internal record to the client and include it in the client RSAT Report?")) return;
            update.reportToClient = true;
            update.submissionType = "both";
            current.task.report = true;
            const project = ORDO_STATE.projects.find((item) => item.id === ORDO_PROJECT_ID) || {};
            ORDO_STATE.clientReportCommunications ||= [];
            ORDO_STATE.clientReportCommunications.unshift({ id:`CLIENT-UPDATE-${ORDO_PROJECT_ID}-${Date.now()}`, type:"Internal & Client Execution Update", updateId:update.id, taskId:current.task.id, taskTitle:current.task.title, streamTitle:current.stream.name, recipient:project.clientEmail || ORDO_STATE.clientContact?.email || "Client email on file", client:project.client || "Client", text:update.text || "", files:(update.files || []).map((file) => ({...file})), sentAt:new Date().toISOString(), lastSentAt:new Date().toISOString(), status:"Sent to Client", attempts:1, proofId:`REPORT-${ORDO_PROJECT_ID}-${Date.now()}` });
            ORDO.log(`Internal execution update submitted to client: ${current.task.title}`);
            ORDO.save(); renderTaskWorkspace();
          });
        }
        function openTaskWorkspace(taskId) {
          activeTaskId = taskId;
          visibleUpdateCount = 20;
          document.getElementById("taskActivityFilter").value = "all";
          renderTaskWorkspace();
          modal.classList.add("open");
        }
        function closeTaskWorkspace() {
          modal.classList.remove("open");
          activeTaskId = null;
          document.getElementById("taskUpdateText").value = "";
          document.getElementById("taskUpdateFiles").value = "";
          document.getElementById("taskSubmissionType").value = "internal";
        }
        document.getElementById("closeTaskWorkspace").onclick = closeTaskWorkspace;
        document.getElementById("resetTaskForTesting").onclick = () => {
          const context = findTaskContext(activeTaskId);
          if (!context || !localDemo || !confirm("Reset this task and unlock Execution for testing? Existing updates and attachments will be preserved.")) return;
          const workspace = workspaceFor(context.task);
          workspace.timer = { state:"stopped", workingSeconds:0, pausedSeconds:0, workingStartedAt:null, pausedStartedAt:null };
          workspace.actualStartedAt = null; workspace.completedAt = null; workspace.reportedAt = null;
          context.task.status = "Ready";
          ORDO_STATE.sowWorkflow ||= {};
          ORDO_STATE.sowWorkflow.status = "final";
          ORDO_STATE.sowWorkflow.editMode = false;
          ORDO_STATE.ntpApproved = true;
          ORDO_STATE.closed = false;
          ORDO_STATE.transmittal = null;
          ORDO_STATE.coc = null;
          ORDO_STATE.stageManagement.selected = 4;
          const executionStage = ORDO_STATE.stageManagement.stages[4];
          const now = new Date().toISOString();
          executionStage.status = "In Progress";
          executionStage.completedAt = null;
          executionStage.canceledAt = null;
          executionStage.startedAt = now;
          executionStage.inProgressStartedAt = now;
          executionStage.onHoldStartedAt = null;
          executionStage.waitingStartedAt = null;
          executionStage.waitingOn = null;
          executionStage.waitingReason = null;
          executionStage.inProgressSeconds = 0;
          executionStage.onHoldSeconds = 0;
          executionStage.waitingSeconds = 0;
          ORDO_STATE.stageManagement.stages.slice(5).forEach((stage) => {
            stage.status = "Not Started";
            stage.startedAt = null;
            stage.completedAt = null;
            stage.canceledAt = null;
            stage.inProgressStartedAt = null;
            stage.onHoldStartedAt = null;
            stage.waitingStartedAt = null;
            stage.waitingOn = null;
            stage.waitingReason = null;
          });
          ORDO.log(`Execution task reset for local testing: ${context.task.title}`);
          ORDO.save(); r(); renderTaskWorkspace();
          alert("Execution is unlocked for testing. You can now Start the task and add updates or attachments.");
        };
        document.getElementById("taskActivityFilter").onchange = () => { visibleUpdateCount = 20; renderTaskWorkspace(); };
        document.getElementById("loadMoreTaskUpdates").onclick = () => { visibleUpdateCount += 20; renderTaskWorkspace(); };
        modal.addEventListener("click", (event) => { if (event.target === modal) closeTaskWorkspace(); });
        document.addEventListener("keydown", (event) => { if (event.key === "Escape" && modal.classList.contains("open")) closeTaskWorkspace(); });
        const submitAutomaticActionUpdate = (context, action, writtenInformation = "") => {
          const workspace = workspaceFor(context.task), at = new Date().toISOString(), updateId = `execution-update-${activeTaskId}-${Date.now()}`;
          const actionLabels = { play:"Task Started", pause:"Task Paused", stop:"Task Session Stopped", done:"Task Completed" };
          const taskName = context.task.title;
          const text = action === "pause"
            ? `${taskName} has been paused due to: ${writtenInformation}`
            : action === "stop"
              ? `${taskName} has been stopped due to: ${writtenInformation}`
              : writtenInformation
                ? `${taskName} — ${action === "play" ? "started" : "completed"}. ${writtenInformation}`
                : `${taskName} has been ${action === "play" ? "started" : "completed"}.`;
          const fileRecords = [];
          workspace.attachments.unshift(...fileRecords);
          const timing = taskMetrics(context.task);
          const update = { id:updateId, text, files:fileRecords.map((file)=>({...file})), user:ORDO_STATE.viewer || "Current User", at, submissionType:"both", reportToClient:true, includeInWorkReport:true, automaticAction:true, action, source:`Execution Task ${actionLabels[action]}`, sowTaskId:context.task.id, acknowledgment:{email:null,manual:null}, timing:{working:timing.working,paused:timing.paused,total:timing.total} };
          workspace.updates.unshift(update);
          context.task.report = true;
          const project = ORDO_STATE.projects.find((item) => item.id === ORDO_PROJECT_ID) || {};
          ORDO_STATE.clientReportCommunications ||= [];
          ORDO_STATE.clientReportCommunications.unshift({ id:`CLIENT-UPDATE-${ORDO_PROJECT_ID}-${Date.now()}`, type:`Internal & Client Execution Update — ${actionLabels[action]}`, updateId, taskId:context.task.id, taskTitle:context.task.title, streamTitle:context.stream.name, recipient:project.clientEmail || ORDO_STATE.clientContact?.email || "Client email on file", client:project.client || "Client", text, files:fileRecords.map((file)=>({...file})), sentAt:at, lastSentAt:at, status:"Sent to Client", attempts:1, proofId:`REPORT-${ORDO_PROJECT_ID}-${Date.now()}` });
          document.getElementById("taskActivityFilter").value = "all";
          visibleUpdateCount = Math.max(20, visibleUpdateCount);
          ORDO.log(`Execution task ${action}: ${context.task.title}`);
          ORDO.save(); r(); renderTaskWorkspace();
        };
        const runTaskAction = (action, reason = "") => {
          if (!ORDO_REGULAR.executionAllowed() || ORDO_STATE.transmittal) return alert("Approve the current RSAT and NTP before execution.");
          const context = findTaskContext(activeTaskId);
          if (!context) return;
          const { task } = context, timer = workspaceFor(task).timer;
          if (action === "play") {
            ORDO_STATE.streams.flatMap((stream) => stream.tasks).filter((item) => item.id !== task.id).forEach((item) => {
              const otherTimer = workspaceFor(item).timer;
              if (otherTimer.state === "working") { settleTaskTimer(item); otherTimer.state = "stopped"; if (item.status !== "Completed") item.status = "In Progress"; }
            });
            settleTaskTimer(task); timer.state = "working"; timer.workingStartedAt = Date.now(); task.status = "In Progress"; workspaceFor(task).actualStartedAt ||= new Date().toISOString();
          }
          if (action === "pause" && timer.state === "working") { settleTaskTimer(task); timer.state = "paused"; timer.pausedStartedAt = Date.now(); task.status = "On Hold"; }
          if (action === "stop" && (timer.state === "working" || timer.state === "paused")) { settleTaskTimer(task); timer.state = "stopped"; task.status = workspaceFor(task).actualStartedAt ? "In Progress" : "Ready"; }
          if (action === "done") { settleTaskTimer(task); timer.state = "done"; task.status = "Completed"; task.report = true; workspaceFor(task).reportedAt = new Date().toISOString(); workspaceFor(task).completedAt = new Date().toISOString(); }
          submitAutomaticActionUpdate(context, action, reason);
        };
        const reasonModal = document.getElementById("taskReasonModal"), reasonText = document.getElementById("taskReasonText");
        let pendingReasonAction = null;
        const openReasonModal = (action) => { pendingReasonAction=action; reasonText.value=""; document.getElementById("taskReasonTitle").textContent=action === "pause" ? "Pause Task" : "Stop Task Session"; document.getElementById("taskReasonHelp").textContent=`Explain why this task is being ${action === "pause" ? "paused" : "stopped"}. Submitting will create an Internal & Client update and ${action === "pause" ? "pause the timer" : "stop the current session"}.`; document.getElementById("taskReasonError").classList.remove("visible"); reasonModal.classList.add("open"); reasonText.focus(); };
        const closeReasonModal = () => { reasonModal.classList.remove("open"); pendingReasonAction=null; reasonText.value=""; };
        document.getElementById("cancelTaskReason").onclick = closeReasonModal;
        document.getElementById("submitTaskReason").onclick = () => { const reason=reasonText.value.trim(); if(!reason)return document.getElementById("taskReasonError").classList.add("visible"); const action=pendingReasonAction; closeReasonModal(); runTaskAction(action,reason); };
        reasonText.oninput=()=>document.getElementById("taskReasonError").classList.remove("visible");
        document.getElementById("taskPlay").onclick = () => runTaskAction("play");
        document.getElementById("taskPause").onclick = () => openReasonModal("pause");
        document.getElementById("taskStop").onclick = () => openReasonModal("stop");
        document.getElementById("taskDone").onclick = () => runTaskAction("done");
        document.getElementById("saveTaskExtension").onclick = () => {
          const context = findTaskContext(activeTaskId);
          if (!context) return;
          const workspace = workspaceFor(context.task), category = document.getElementById("extensionReasonCategory").value, details = document.getElementById("extensionReasonDetails").value.trim(), revisedEnd = document.getElementById("extensionTargetEnd").value, previousEnd = effectiveTargetEnd(context.task);
          if (!category || !details || !revisedEnd) return alert("Select a reason, enter the explanation, and provide the revised target end date.");
          if (previousEnd && revisedEnd <= previousEnd) return alert("The revised target end date must be later than the current target end date.");
          const at = new Date().toISOString(), timing = taskMetrics(context.task), extension = { id:`extension-${activeTaskId}-${Date.now()}`, previousEnd, revisedEnd, category, details, at, user:"Current User" };
          workspace.extensions ||= []; workspace.extensions.unshift(extension);
          const updateId = `execution-update-${activeTaskId}-${Date.now()}`;
          const text = `Schedule extension: ${category}. Target end revised from ${displayDate(previousEnd)} to ${displayDate(revisedEnd)}. ${details}`;
          workspace.updates.unshift({ id:updateId, text, files:[], user:"Current User", at, reportToClient:true, includeInWorkReport:true, source:"Execution Schedule Extension", sowTaskId:context.task.id, timing:{working:timing.working,paused:timing.paused,total:timing.total}, extensionId:extension.id });
          context.task.report = true;
          ORDO_STATE.clientReportCommunications ||= [];
          const project = ORDO_STATE.projects.find((item) => item.id === ORDO_PROJECT_ID) || {};
          ORDO_STATE.clientReportCommunications.unshift({ id:`CLIENT-EXTENSION-${ORDO_PROJECT_ID}-${Date.now()}`, type:"Schedule Extension Update", updateId, taskId:context.task.id, taskTitle:context.task.title, streamTitle:context.stream.name, recipient:project.clientEmail || ORDO_STATE.clientContact?.email || "Client email on file", client:project.client || "Client", text, files:[], sentAt:at, lastSentAt:at, status:"Sent to Client", attempts:1, proofId:`EXTENSION-${ORDO_PROJECT_ID}-${Date.now()}` });
          ORDO.log(`Execution schedule extended: ${context.task.title}`);
          ORDO.save();
          document.getElementById("extensionReasonCategory").value = ""; document.getElementById("extensionReasonDetails").value = ""; document.getElementById("extensionTargetEnd").value = "";
          r(); renderTaskWorkspace();
        };
        document.getElementById("addTaskUpdate").onclick = async () => {
          const context = findTaskContext(activeTaskId), input = document.getElementById("taskUpdateText"), fileInput = document.getElementById("taskUpdateFiles"), submissionType = document.getElementById("taskSubmissionType").value, text = input.value.trim(), files = [...fileInput.files];
          if (!context || (!text && !files.length)) return alert("Enter an update, attach at least one file, or do both.");
          const workspace = workspaceFor(context.task), at = new Date().toISOString(), updateId = `execution-update-${activeTaskId}-${Date.now()}`;
          const fileRecords = await Promise.all(files.map(async (file, index) => ({ id: `execution-file-${activeTaskId}-${Date.now()}-${index}`, updateId, name: file.name, type: file.type || "File", size: file.size, dataUrl: await fileDataUrl(file), addedAt: at, addedBy: "Current User" })));
          workspace.attachments.unshift(...fileRecords);
          const timing = taskMetrics(context.task);
          const reportToClient = submissionType === "client" || submissionType === "both";
          workspace.updates.unshift({ id: updateId, text, files: fileRecords.map(({id,name,type,size,dataUrl}) => ({id,name,type,size,dataUrl})), user: ORDO_STATE.viewer || "Current User", at, submissionType, reportToClient, includeInWorkReport: true, source: "Execution Task Update", sowTaskId: context.task.id, acknowledgment: reportToClient ? { email:null, manual:null } : null, timing: { working: timing.working, paused: timing.paused,total: timing.total } });
          if (reportToClient) {
            context.task.report = true;
            ORDO_STATE.clientReportCommunications ||= [];
            const project = ORDO_STATE.projects.find((item) => item.id === ORDO_PROJECT_ID) || {};
            ORDO_STATE.clientReportCommunications.unshift({ id: `CLIENT-UPDATE-${ORDO_PROJECT_ID}-${Date.now()}`, type: submissionType === "both" ? "Internal & Client Execution Update" : "Client Execution Update", updateId, taskId: context.task.id, taskTitle: context.task.title, streamTitle: context.stream.name, recipient: project.clientEmail || ORDO_STATE.clientContact?.email || "Client email on file", client: project.client || "Client", text, files: fileRecords.map((file) => ({...file})), sentAt: at, lastSentAt: at, status: "Sent to Client", attempts: 1, proofId: `REPORT-${ORDO_PROJECT_ID}-${Date.now()}` });
          }
          ORDO.log(`Update added to execution task: ${context.task.title}`);
          ORDO.save(); input.value = ""; fileInput.value = ""; document.getElementById("taskSubmissionType").value = "internal"; renderTaskWorkspace();
        };

        function r() {
          document.getElementById("streams").innerHTML = ORDO_STATE.streams
            .map(
              (w) => {
                const plan = streamSchedule(w), total = w.tasks.reduce((sum, task) => sum + taskMetrics(task).total, 0), overdue = w.tasks.filter((task) => taskSchedule(task).overdueDays > 0).length;
                return `<section class="execution-stream"><header class="execution-stream-head"><div><h3>${ORDO.esc(w.name)}</h3><div class="imeta">Main Task · ${ORDO.esc(responsibilitiesFor(w).join(", "))} · Assigned: ${ORDO.esc(personsFor(w, "owner").join(", ") || "Unassigned")}</div></div><div class="execution-stream-kpis"><span>Target ${displayDate(plan.start)} – ${displayDate(plan.end)}</span><span>Planned ${plan.duration || 0}h</span><span>Actual ${ORDO.dur(total)}</span><span>${plan.completed}/${w.tasks.length} completed</span>${overdue ? `<span class="schedule-state overdue">${overdue} overdue</span>` : '<span class="schedule-state good">On schedule</span>'}</div></header><div class="execution-task-head"><span>Sub Task Description</span><span>Responsibility / Assigned Persons</span><span>Approved Schedule</span><span>Actual Time</span><span>Task Status</span><span>Action</span></div>${w.tasks.map((t) => { const metrics = taskMetrics(t), schedule = taskSchedule(t); return `<div class="execution-task-row execution-task-open" tabindex="0" role="button" data-workspace-id="${t.id}" aria-label="Open task execution for ${ORDO.esc(t.title)}"><div><div class="taskname">${ORDO.esc(t.title)}</div><div class="imeta">Planned duration ${ORDO.esc(t.est || "—")}</div></div><div><strong>${ORDO.esc(responsibilitiesFor(t).join(", "))}</strong><div class="imeta">${ORDO.esc(personsFor(t, "assignee").join(", ") || "Unassigned")}</div></div><div class="execution-schedule"><strong>${displayDate(t.start)}</strong><span>to ${displayDate(schedule.targetEnd)}</span><span class="schedule-state ${schedule.tone}">${schedule.label}</span></div><div><strong>${ORDO.dur(metrics.total)}</strong><div class="imeta">Work ${ORDO.dur(metrics.working)} · Hold ${ORDO.dur(metrics.paused)}</div></div><div>${ORDO.badge(t.status)}</div><div><button class="btn open-task-workspace" type="button" data-id="${t.id}">Open Task</button></div></div>`; }).join("")}</section>`;
              },
            )
            .join("");
          document.querySelectorAll(".execution-task-open").forEach((row) => {
            row.onclick = (event) => { if (!event.target.closest("button,input,label")) openTaskWorkspace(row.dataset.workspaceId); };
            row.onkeydown = (event) => { if ((event.key === "Enter" || event.key === " ") && !event.target.closest("button,input")) { event.preventDefault(); openTaskWorkspace(row.dataset.workspaceId); } };
          });
          document.querySelectorAll(".open-task-workspace").forEach((button) => button.onclick = () => openTaskWorkspace(button.dataset.id));
        }
        r();
        setInterval(() => {
          if (!document.querySelector("#streams :focus") && !modal.classList.contains("open")) r();
          if (modal.classList.contains("open")) {
            const context = findTaskContext(activeTaskId), time = document.getElementById("taskWorkspaceTime"), working = document.getElementById("taskWorkingTime"), paused = document.getElementById("taskPausedTime"), status = document.getElementById("taskWorkspaceStatus");
            const metrics = context ? taskMetrics(context.task) : null;
            if (metrics && time) time.textContent = ORDO.dur(metrics.total);
            if (metrics && working) working.textContent = ORDO.dur(metrics.working);
            if (metrics && paused) paused.textContent = ORDO.dur(metrics.paused);
            if (context && status) status.textContent = context.task.status;
          }
        }, 1000);
      })();
