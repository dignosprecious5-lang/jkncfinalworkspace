(function () {
  "use strict";
  if (!location.pathname.endsWith("/work-order.html")) return;
  const project = ORDO_STATE.projects.find((item) => item.id === ORDO_PROJECT_ID);
  const workOrder = ORDO_STATE.workOrder;
  const stage = ORDO_STATE.stageManagement.stages[0];
  const responsibilities = ["Responsible", "Executor", "Monitor", "Reviewer", "Approver", "Coordinator", "Support"];
  const people = ["John Kelly Abalde", "Lyndon Earl Rio", "Maria Santos", "Rubeca Potayre", "Anne Reyes", "Carlos Mendoza", "Diana Cruz", "Paolo Lim"];
  const roles = ["Regular Manager", "Lead Consultant", "Accounting Representative", "Operations Representative", "Management Representative", "Finance Representative", "Admin / Records Representative", "Sales / Account Representative"];
  const stages = ["Work Order", "Plan", "Review", "NTP", "Execution"];
  const esc = ORDO.esc;
  const now = () => new Date().toISOString();
  const selected = (values, value) => (values || []).includes(value) ? " selected" : "";
  const options = (items, values) => items.map((item) => `<option${selected(values, item)}>${esc(item)}</option>`).join("");
  const multi = (field, items, values, label) => `<details class="wo-multi" data-field="${field}"><summary>${esc((values || []).join(", ") || `Select ${label}`)}</summary><div class="wo-multi-menu">${items.map((item)=>`<label><input type="checkbox" value="${esc(item)}"${(values||[]).includes(item)?" checked":""}> ${esc(item)}</label>`).join("")}</div></details>`;
  const seconds = (base, started) => Number(base || 0) + (started ? Math.max(0, Math.floor((Date.now() - new Date(started).getTime()) / 1000)) : 0);
  const fmt = (value) => { const n=Math.max(0,Math.floor(value||0)); return `${String(Math.floor(n/3600)).padStart(2,"0")}:${String(Math.floor((n%3600)/60)).padStart(2,"0")}:${String(n%60).padStart(2,"0")}`; };

  function makeProjectRole(role, index) {
    return { id: `pr-${Date.now()}-${index}`, role, responsibilities: role === "Regular Manager" ? ["Responsible"] : role === "Lead Consultant" ? ["Executor"] : [], persons: role === "Regular Manager" ? [project.lead] : [], acknowledgment: workOrder.status === "Completed" ? "Acknowledged" : "Pending", required: true };
  }
  function makeStageAssignment(name, index) {
    const responsibility = name === "Work Order" ? (index ? ["Approver"] : ["Reviewer"]) : ["Responsible"];
    const personsForStage = name === "Work Order" ? [index ? "Lyndon Earl Rio" : "Maria Santos"] : [];
    return { id:`sa-${Date.now()}-${name}-${index}`, stage:name, responsibilities:responsibility, persons:personsForStage, acknowledgment:workOrder.status === "Completed" ? "Acknowledged" : "Pending", required:true };
  }
  if (!workOrder.projectRoles.length) workOrder.projectRoles = roles.map(makeProjectRole);
  if (!workOrder.stageAssignments.length) workOrder.stageAssignments = stages.map((name) => makeStageAssignment(name, 0));
  if (!workOrder.stageAssignments.some((row) => row.stage === "Work Order" && row.responsibilities.includes("Reviewer"))) workOrder.stageAssignments.push(makeStageAssignment("Work Order", 0));
  if (!workOrder.stageAssignments.some((row) => row.stage === "Work Order" && row.responsibilities.includes("Approver"))) workOrder.stageAssignments.push(makeStageAssignment("Work Order", 1));
  if (Number(workOrder.stageAssignmentsVersion || 0) < 2) {
    const previous = workOrder.stageAssignments;
    workOrder.stageAssignments = stages.flatMap((stageName) =>
      responsibilities.map((responsibility, index) => {
        const existing = previous.find((row) => row.stage === stageName && (row.responsibilities || []).includes(responsibility));
        return existing
          ? { ...existing, id: `sa-${stageName}-${responsibility}`.replaceAll(" ", "-"), stage: stageName, responsibilities: [responsibility] }
          : { id: `sa-${stageName}-${responsibility}`.replaceAll(" ", "-"), stage: stageName, responsibilities: [responsibility], persons: [], acknowledgment: "Pending", required: true };
      }),
    );
    workOrder.stageAssignmentsVersion = 2;
    ORDO_SAVE();
  }
  if (!Array.isArray(workOrder.stageSchedule)) workOrder.stageSchedule = [];
  stages.forEach((stageName) => {
    if (!workOrder.stageSchedule.some((row) => row.stage === stageName))
      workOrder.stageSchedule.push({ stage: stageName, targetStart: "", targetEnd: "" });
  });

  function assignedRows() { return [...workOrder.projectRoles, ...workOrder.stageAssignments].filter((row) => row.required && row.persons.length); }
  function allAcknowledged() { const rows=assignedRows(); return rows.length > 0 && rows.every((row) => row.acknowledgment === "Acknowledged"); }
  function assignees(kind) { return [...new Set(workOrder.stageAssignments.filter((row) => row.stage === "Work Order" && row.responsibilities.includes(kind)).flatMap((row) => row.persons))]; }
  function stopWaiting() {
    if (stage.waitingStartedAt) stage.waitingSeconds += Math.max(0, Math.floor((Date.now() - new Date(stage.waitingStartedAt).getTime()) / 1000));
    stage.waitingStartedAt = null; stage.waitingOn = null; stage.waitingReason = null;
  }
  function stopHandling() {
    if (stage.inProgressStartedAt) stage.inProgressSeconds += Math.max(0, Math.floor((Date.now() - new Date(stage.inProgressStartedAt).getTime()) / 1000));
    if (stage.onHoldStartedAt) stage.onHoldSeconds += Math.max(0, Math.floor((Date.now() - new Date(stage.onHoldStartedAt).getTime()) / 1000));
    stage.inProgressStartedAt = null; stage.onHoldStartedAt = null; stage.handlingState = "stopped";
  }
  function startHandling() {
    if (stage.waitingStartedAt) return alert("Work cannot start while this Work Order is waiting on another person.");
    if (stage.onHoldStartedAt) stage.onHoldSeconds += Math.max(0, Math.floor((Date.now() - new Date(stage.onHoldStartedAt).getTime()) / 1000));
    stage.onHoldStartedAt = null; stage.inProgressStartedAt ||= now(); stage.startedAt ||= now(); stage.handlingState = "running"; ORDO_SAVE(); renderControls();
  }
  function pauseHandling() {
    if (!stage.inProgressStartedAt) return;
    stage.inProgressSeconds += Math.max(0, Math.floor((Date.now() - new Date(stage.inProgressStartedAt).getTime()) / 1000));
    stage.inProgressStartedAt = null; stage.onHoldStartedAt = now(); stage.handlingState = "paused"; ORDO_SAVE(); renderControls();
  }
  function startWaiting(personsToWaitFor, reason) {
    stopHandling(); stopWaiting(); stage.startedAt ||= now(); stage.waitingStartedAt=now(); stage.waitingOn=personsToWaitFor.join(", "); stage.waitingReason=reason;
  }
  function setStatus(status, history) {
    workOrder.status=status; stage.status=status; stage.history ||= []; stage.history.push({action:history,at:now()}); ORDO_STATE.history.push({action:history,at:new Date().toLocaleString()}); ORDO_SAVE(); render();
  }
  function saveFields() {
    // Source/project fields are read-only and already persisted from their source records.
  }
  function syncComplete() {
    if (workOrder.notificationsSentAt && allAcknowledged() && workOrder.status !== "Completed") {
      stopWaiting(); workOrder.completedAt=now(); stage.completedAt=workOrder.completedAt; stage.status="Completed"; workOrder.status="Completed";
      stage.history.push({action:"All required assignments acknowledged; Work Order completed",at:now()});
    }
  }
  function updateRow(collection, id, field, element) {
    const row=collection.find((item)=>item.id===id); if(!row) return;
    const previous = row[field];
    row[field]=["responsibilities","persons"].includes(field) ? [...element.querySelectorAll('input:checked')].map((input)=>input.value) : element.value;
    if (field === "acknowledgment" && previous !== row[field]) ORDO.log(`Assignment acknowledgment changed to ${row[field]}`, { user:(row.persons||[]).join(", ")||ORDO_STATE.viewer, module:"Work Order", type:"Acknowledgment", details:`${row.role||row.stage} · ${(row.responsibilities||[]).join(", ")}` });
    syncComplete(); ORDO_SAVE(); render();
  }
  function tableRows(collection, type) {
    return collection.map((row) => `<tr data-id="${esc(row.id)}"><td>${type === "project" ? `<input value="${esc(row.role)}" data-field="role" ${roles.includes(row.role) ? "readonly" : ""}>` : `<input value="${esc(row.stage)}" readonly>`}</td><td>${type === "project" ? multi("responsibilities",responsibilities,row.responsibilities,"Responsibility") : `<input value="${esc(row.responsibilities[0])}" readonly>`}</td><td>${multi("persons",people,row.persons,"Person")}</td><td class="wo-status"><select data-field="acknowledgment" ${!workOrder.notificationsSentAt ? "disabled" : ""}>${options(["Pending","Acknowledged","Declined","Issue Raised"],[row.acknowledgment])}</select></td></tr>`).join("");
  }
  function sourceField(label,value,link) { return `<label class="wo-field"><span>${label}</span>${link ? `<a class="wo-source-link" href="${link}">${esc(value || "—")}</a>` : `<input readonly value="${esc(value || "—")}">`}</label>`; }

  function renderContent() {
    document.querySelector(".content").innerHTML = `<div class="wo-stack">
      <div class="wo-banner"><div><strong>Regular Work Order · Operations</strong><br><span>Accountability must be approved and acknowledged before RSAT becomes available.</span></div><span class="badge">${esc(workOrder.status)}</span></div>
      <section class="card"><div class="cardhead"><div><strong>Regular Information</strong><div class="imeta">Auto-filled from the Deal, START, and issued Service Memo.</div></div></div><div class="cardbody wo-grid">
        ${sourceField("Work Order No.",workOrder.workOrderNo)}${sourceField("Regular Ref No.",project.ref)}${sourceField("Source Service Memo",workOrder.serviceMemo)}${sourceField("Source START",workOrder.startRef)}
        ${sourceField("Source Deal",project.deal)}${sourceField("Client",project.client)}${sourceField("Business / Company",project.business)}${sourceField("Service / Regular",workOrder.service)}
        ${sourceField("Service Area",workOrder.serviceArea)}${sourceField("Engagement Type",workOrder.engagementType)}${sourceField("Target Start Date",workOrder.targetStart)}${sourceField("Target Regular End Date",workOrder.targetEnd)}
      </div></section>
      <section class="card"><div class="cardhead"><div><strong>Regular Roles &amp; Accountability</strong><div class="imeta">One assignment per row, with one or more responsibilities and people.</div></div></div><div class="cardbody"><div class="wo-table-wrap"><table class="wo-table" id="projectRoles"><thead><tr><th>Regular Role</th><th>Responsibility</th><th>Assigned Person</th><th>Acknowledgment Status</th></tr></thead><tbody>${tableRows(workOrder.projectRoles,"project")}</tbody></table></div><div class="wo-footer"><button class="btn" id="addProjectRole">+ Add Regular Role</button><p class="wo-note">Acknowledgment becomes available after approved assignments are notified.</p></div></div></section>
      <section class="card"><div class="cardhead"><div><strong>Stage Roles &amp; Accountability</strong><div class="imeta">Each lifecycle stage has one row for every standard responsibility category.</div></div></div><div class="cardbody"><div class="wo-table-wrap"><table class="wo-table" id="stageRoles"><thead><tr><th>Stage</th><th>Responsibility</th><th>Assigned Person</th><th>Acknowledgment Status</th></tr></thead><tbody>${tableRows(workOrder.stageAssignments,"stage")}</tbody></table></div><div class="wo-footer"><p class="wo-note">Assign one or more people independently under each responsibility row.</p></div></div></section>
      <section class="card"><div class="cardhead"><div><strong>Stage Schedule</strong><div class="imeta">Define the planned start and end date for every project lifecycle stage.</div></div></div><div class="cardbody"><div class="wo-table-wrap"><table class="wo-table wo-schedule" id="stageSchedule"><thead><tr><th>Stage</th><th>Target Start Date</th><th>Target End Date</th></tr></thead><tbody>${workOrder.stageSchedule.map((row)=>`<tr data-stage="${esc(row.stage)}"><td><strong>${esc(row.stage)}</strong></td><td><input type="date" data-schedule="targetStart" value="${esc(row.targetStart||"")}" aria-label="${esc(row.stage)} target start date"></td><td><input type="date" data-schedule="targetEnd" value="${esc(row.targetEnd||"")}" min="${esc(row.targetStart||"")}" aria-label="${esc(row.stage)} target end date"></td></tr>`).join("")}</tbody></table></div><div class="wo-footer"><p class="wo-note">Target end date cannot be earlier than the stage target start date.</p></div></div></section>
    </div>`;
  }
  function bindTable(id, collection) {
    document.querySelectorAll(`#${id} tbody tr`).forEach((tr)=>{
      tr.querySelectorAll("[data-field]").forEach((el)=>el.onchange=()=>updateRow(collection,tr.dataset.id,el.dataset.field,el));
      const roleInput=tr.querySelector('input[data-field="role"]'); if(roleInput) roleInput.oninput=()=>{const row=collection.find(x=>x.id===tr.dataset.id); row.role=roleInput.value; ORDO_SAVE();};
      const remove=tr.querySelector("[data-remove]"); if(remove)remove.onclick=()=>{const index=collection.findIndex(x=>x.id===tr.dataset.id); if(index>=0) collection.splice(index,1); ORDO_SAVE(); render();};
    });
  }
  function bindSchedule() {
    document.querySelectorAll("#stageSchedule tbody tr").forEach((tr) => {
      const row=workOrder.stageSchedule.find((item)=>item.stage===tr.dataset.stage);
      const start=tr.querySelector('[data-schedule="targetStart"]'),end=tr.querySelector('[data-schedule="targetEnd"]');
      start.onchange=()=>{row.targetStart=start.value;end.min=start.value;if(row.targetEnd&&row.targetEnd<row.targetStart){row.targetEnd="";end.value="";alert("Target End Date must be on or after Target Start Date.");}ORDO_SAVE();};
      end.onchange=()=>{if(start.value&&end.value<start.value){alert("Target End Date must be on or after Target Start Date.");end.value=row.targetEnd||"";return;}row.targetEnd=end.value;ORDO_SAVE();};
    });
  }
  function renderControls() {
    const panel=document.querySelector(".sidecard");
    const waiting=seconds(stage.waitingSeconds,stage.waitingStartedAt);
    const elapsed=stage.startedAt ? Math.floor(((stage.completedAt ? new Date(stage.completedAt) : new Date()) - new Date(stage.startedAt))/1000) : 0;
    const reviewers=assignees("Reviewer"), approvers=assignees("Approver");
    let actions="";
    if(workOrder.status === "Draft" || workOrder.status === "Returned for Revision") actions=`<button class="stage-control" data-wo="save">Save Draft</button><button class="stage-control primary" data-wo="submit">Submit for Review</button>`;
    else if(workOrder.status === "For Review") actions=`<button class="stage-control" data-wo="return">Return for Revision</button><button class="stage-control primary" data-wo="approval">Submit for Approval</button>`;
    else if(workOrder.status === "For Approval") actions=`<button class="stage-control" data-wo="return">Return for Revision</button><button class="stage-control primary" data-wo="approve">Approve Work Order</button>`;
    else if(workOrder.status === "Approved") actions=`<button class="stage-control primary" data-wo="notify">Notify Assigned Persons</button>`;
    else if(workOrder.status === "Awaiting Acknowledgment") actions=`<button class="stage-control" data-wo="notify">Resend Assignment Notifications</button>`;
    else actions=`<a class="stage-control primary" href="scope-of-work.html?project=${project.id}">Open RSAT</a>`;
    const inProgress=seconds(stage.inProgressSeconds,stage.inProgressStartedAt),onHold=seconds(stage.onHoldSeconds,stage.onHoldStartedAt),handling=inProgress+onHold;
    const handlingActions=workOrder.status === "Completed" ? "" : stage.inProgressStartedAt ? `<button class="stage-control" id="woPause">Put Work Order On Hold</button><button class="stage-control" id="woStop">Stop Handling</button>` : stage.onHoldStartedAt ? `<button class="stage-control primary" id="woStart">Resume Work Order</button><button class="stage-control" id="woStop">Stop Handling</button>` : `<button class="stage-control" id="woStart">Start Work Order</button>`;
    panel.classList.remove("stage-management-only");
    panel.innerHTML=`<div class="side-title">STAGE MANAGEMENT</div><div class="stage-management-panel wo-management">
      <section><h4>STAGE STATUS</h4><div class="stage-status-value ${workOrder.status.toLowerCase().replaceAll(" ","-")}">${esc(workOrder.status)}</div></section>
      <section><h4>STAGE HANDLING</h4><div class="stage-total"><span>Total Stage Handling<br>Time</span><strong id="woHandling">${fmt(handling)}</strong></div><dl class="stage-handling"><div><dt>In Progress</dt><dd>${fmt(inProgress)}</dd></div><div><dt>On Hold</dt><dd>${fmt(onHold)}</dd></div><div><dt>Waiting Time</dt><dd id="woWaiting">${fmt(waiting)}</dd></div><div><dt>Responsible</dt><dd>Operations</dd></div><div><dt>Waiting On</dt><dd>${esc(stage.waitingOn||"—")}</dd></div><div><dt>Started</dt><dd>${stage.startedAt?new Date(stage.startedAt).toLocaleString():"—"}</dd></div><div><dt>Completed</dt><dd>${stage.completedAt?new Date(stage.completedAt).toLocaleString():"—"}</dd></div><div><dt>Canceled</dt><dd>${stage.canceledAt?new Date(stage.canceledAt).toLocaleString():"—"}</dd></div><div><dt>Stage Elapsed</dt><dd id="woElapsed">${fmt(elapsed)}</dd></div></dl>${stage.waitingOn?`<div class="stage-waiting"><span>WAITING FOR</span><strong>${esc(stage.waitingOn)}</strong><small>${esc(stage.waitingReason||"")}</small></div>`:""}</section>
      <section><h4>STAGE CONTROLS</h4><div class="stage-controls">${handlingActions}${actions}</div><div class="workflow-note">RSAT unlocks after approval, notification, and every required acknowledgment.</div></section></div>`;
    panel.querySelectorAll("[data-wo]").forEach((button)=>button.onclick=()=>act(button.dataset.wo,reviewers,approvers));
    const startButton=panel.querySelector("#woStart"),pauseButton=panel.querySelector("#woPause"),stopButton=panel.querySelector("#woStop");
    if(startButton)startButton.onclick=startHandling;
    if(pauseButton)pauseButton.onclick=pauseHandling;
    if(stopButton)stopButton.onclick=()=>{stopHandling();ORDO_SAVE();renderControls();};
  }
  function validateAssignments(kind) {
    const personsFound=assignees(kind); if(!personsFound.length){alert(`Assign at least one ${kind} to the Work Order stage first.`); return null;} return personsFound;
  }
  function act(action, reviewers, approvers) {
    saveFields();
    if(action === "save") { stopWaiting(); return setStatus("Draft","Work Order saved as Draft"); }
    if(action === "submit") { const assigned=validateAssignments("Reviewer"); if(!assigned)return; startWaiting(assigned,"Review of submitted Work Order"); return setStatus("For Review","Work Order submitted for review"); }
    if(action === "return") { stopWaiting(); startHandling(); return setStatus("Returned for Revision","Work Order returned for revision"); }
    if(action === "approval") { const assigned=validateAssignments("Approver"); if(!assigned)return; stopWaiting(); startWaiting(assigned,"Approval of reviewed Work Order"); return setStatus("For Approval","Work Order submitted for approval"); }
    if(action === "approve") { stopWaiting(); stopHandling(); return setStatus("Approved","Work Order approved"); }
    if(action === "notify") { if(!assignedRows().length)return alert("Assign at least one person before sending notifications."); workOrder.notificationsSentAt=now(); assignedRows().forEach((row)=>{if(row.acknowledgment!=="Acknowledged")row.acknowledgment="Pending";}); startWaiting([...new Set(assignedRows().flatMap(row=>row.persons))],"Acknowledgment of project and stage accountability"); setStatus("Awaiting Acknowledgment","Assignment notifications sent; awaiting acknowledgment"); }
  }
  function render() {
    syncComplete(); renderContent(); bindTable("projectRoles",workOrder.projectRoles); bindTable("stageRoles",workOrder.stageAssignments); bindSchedule();
    document.getElementById("addProjectRole").onclick=()=>{workOrder.projectRoles.push({...makeProjectRole("Custom Regular Role",workOrder.projectRoles.length),role:"Custom Regular Role"});ORDO_SAVE();render();};
    renderControls();
  }
  render(); setInterval(()=>{const waiting=document.getElementById("woWaiting"),elapsed=document.getElementById("woElapsed"),handling=document.getElementById("woHandling");if(waiting)waiting.textContent=fmt(seconds(stage.waitingSeconds,stage.waitingStartedAt));if(handling)handling.textContent=fmt(seconds(stage.inProgressSeconds,stage.inProgressStartedAt)+seconds(stage.onHoldSeconds,stage.onHoldStartedAt));if(elapsed&&stage.startedAt)elapsed.textContent=fmt(Math.floor(((stage.completedAt?new Date(stage.completedAt):new Date())-new Date(stage.startedAt))/1000));},1000);
})();
