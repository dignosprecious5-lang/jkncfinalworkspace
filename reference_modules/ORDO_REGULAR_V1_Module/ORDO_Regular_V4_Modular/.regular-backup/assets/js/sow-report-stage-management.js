(function () {
  "use strict";
  if (!location.pathname.endsWith("/sow-report.html")) return;

  const side = document.querySelector(".sidecard");
  const stage = ORDO_STATE.stageManagement.stages[5];
  const project = ORDO_STATE.projects.find((item) => item.id === ORDO_PROJECT_ID) || {};
  const tasks = () => ORDO.allTasks().map((item) => item.task);
  const allComplete = () => tasks().length > 0 && tasks().every((task) => task.status === "Completed");
  const now = () => new Date().toISOString();
  const elapsed = (base, started) => Number(base || 0) + (started ? Math.max(0, Math.floor((Date.now() - new Date(started).getTime()) / 1000)) : 0);
  const assigned = (responsibility) => [...new Set((ORDO_STATE.workOrder?.stageAssignments || []).filter((row) => row.stage === "Reporting" && row.responsibilities?.includes(responsibility)).flatMap((row) => row.persons || []))];

  ORDO_STATE.approvedSowReport ||= null;

  function status() {
    if (stage.status === "Completed") return "Completed";
    if (!allComplete()) return "Receiving Execution Updates";
    if (ORDO_STATE.approvedSowReport?.sentAt || ORDO_STATE.approvedSowReport?.uploadedAt) return "Ready for Completion";
    return "Ready for Client";
  }

  function reportHtml() {
    const workOrder=ORDO_STATE.workOrder||{},ntpNumbers=(ORDO_STATE.ntpNumbers||[]).join(", ")||"—";
    const statusOf=(task)=>task.status==="Completed"?"Done":["On Hold","Pending Client"].includes(task.status)?"On Hold":"In Progress";
    const approvedOut=(ORDO_STATE.sowVersions?.history||[]).filter((version)=>version.status==="approved").flatMap((version)=>version.items||[]),outStreams=approvedOut.length?approvedOut:(ORDO_STATE.sowOutOfScope||[]);
    const taskRows=(streams,prefix="")=>streams.flatMap((stream,streamIndex)=>(stream.tasks||[]).map((task,taskIndex)=>{const updates=(task.executionWorkspace?.updates||[]).filter((update)=>update.reportToClient!==false).length;return `<tr><td>${prefix}${streamIndex+1}.${taskIndex+1}</td><td>${ORDO.esc(stream.name)}</td><td>${ORDO.esc(task.title)}</td><td>${ORDO.esc((task.responsibilities||["Responsible"]).join(", "))}</td><td>${ORDO.esc(task.assignees?.join(", ")||task.assignee||"Unassigned")}</td><td>${updates}</td><td><strong>${statusOf(task)}</strong></td></tr>`;})).join("");
    const taskHeader='<thead><tr><th style="width:7%">Item</th><th style="width:17%">Main Task</th><th style="width:25%">Sub Task</th><th style="width:13%">Responsibility</th><th style="width:16%">Assigned Person</th><th style="width:11%">Client Updates</th><th style="width:11%">Status</th></tr></thead>';
    const info=[["NTP No. (All Notices to Proceed)",ntpNumbers],["Engagement Proposal Agreement (EPA) No.",project.epaNo||workOrder.epaNo||"—"],["Work Order No.",workOrder.workOrderNo||"—"],["Project Ref No.",project.ref||`PROJ-${ORDO_PROJECT_ID}`],["Source Service Memo",workOrder.serviceMemo||"—"],["Source START",workOrder.startRef||"—"],["Source Deal",project.deal||"—"],["Client",project.client||"—"],["Business / Company",project.business||"—"],["Service / Project",workOrder.service||project.title||"—"],["Service Area",workOrder.serviceArea||"Corporate Services"],["Engagement Type",workOrder.engagementType||"Project"],["Target Start Date",workOrder.targetStart||"—"],["Target Project End Date",workOrder.targetEnd||project.target||"—"]];
    const infoRows=[];for(let i=0;i<info.length;i+=2){const a=info[i],b=info[i+1]||["",""];infoRows.push(`<tr><th>${a[0]}</th><td>${ORDO.esc(a[1])}</td><th>${b[0]}</th><td>${ORDO.esc(b[1])}</td></tr>`);}
    const narrative=(id)=>ORDO.esc(document.getElementById(id)?.value||"—");
    return `<!doctype html><html><head><meta charset="utf-8"><title>${ORDO.esc(project.ref||"Project")} SOW Report</title><style>@page{size:A4 portrait;margin:12mm}*{box-sizing:border-box}body{margin:0;color:#172033;background:#fff;font-family:Georgia,"Times New Roman",serif;font-size:10px;line-height:1.45}.sheet{width:100%;min-height:273mm;position:relative;padding-bottom:24mm}.header{display:flex;justify-content:space-between;align-items:flex-start;border:1.5px solid #203a7c;border-bottom:0;padding:13px 16px 11px}.brand{font-size:20px;font-weight:700;line-height:1.05}.brand span{display:block;color:#3157b8;font-size:12px;margin-top:4px}.title{text-align:right}.title h1{margin:0;color:#172033;font-size:21px;letter-spacing:.8px}.confidential{display:block;margin-top:4px;color:#c62828;font-size:10px;font-weight:700;letter-spacing:1px}.section{padding:5px 8px;background:#203a7c;color:#fff;text-align:center;font-size:11px;font-weight:700;letter-spacing:.35px}.info,.tasks{width:100%;border-collapse:collapse;table-layout:fixed}.info th,.info td,.tasks th,.tasks td{border:1px solid #36435c;padding:6px;vertical-align:top}.info th{width:18%;background:#f1f4f9;text-align:left;font-size:8px}.info td{width:32%;font-weight:700}.tasks{font-size:8px}.tasks thead{display:table-header-group}.tasks th{background:#edf1f7;color:#243a73;text-align:left;font-size:7px;text-transform:uppercase}.tasks tr{break-inside:avoid;page-break-inside:avoid}.narrative{border:1px solid #36435c;border-top:0;padding:9px 11px;text-align:justify}.narrative h3{margin:8px 0 3px;color:#203a7c;font-size:10px}.narrative p{margin:0 0 7px}.notice{margin-top:12px;padding-top:8px;border-top:1px solid #9aa5b7;color:#4d586a;font-size:7.5px;text-align:justify}.footer{position:absolute;left:0;right:0;bottom:0;display:flex;justify-content:space-between;border-top:2px solid #203a7c;padding-top:6px;color:#536078;font-size:8px}@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}.sheet{break-after:auto}}</style></head><body><main class="sheet"><header class="header"><div class="brand">John Kelly<span>&amp; Company</span></div><div class="title"><h1>SOW REPORT</h1><span class="confidential">STRICTLY CONFIDENTIAL</span></div></header><div class="section">PROJECT INFORMATION</div><table class="info"><tbody>${infoRows.join("")}</tbody></table><div class="section">WITHIN SCOPE — EXECUTION STATUS &amp; CLIENT UPDATES</div><table class="tasks">${taskHeader}<tbody>${taskRows(ORDO_STATE.streams||[])||'<tr><td colspan="7">No Within Scope tasks recorded.</td></tr>'}</tbody></table><div class="section">OUT OF SCOPE — EXECUTION STATUS &amp; CLIENT UPDATES</div><table class="tasks">${taskHeader}<tbody>${taskRows(outStreams,"OOS-")||'<tr><td colspan="7">No approved Out of Scope tasks recorded.</td></tr>'}</tbody></table><div class="section">REPORT NARRATIVE</div><section class="narrative"><h3>Issues &amp; Observations</h3><p>${narrative("issues")}</p><h3>Recommendations</h3><p>${narrative("recommendations")}</p><h3>Summary / Way Forward</h3><p>${narrative("way")}</p></section><p class="notice"><strong>CONFIDENTIALITY NOTICE:</strong> This report and its contents are intended only for the Client named herein, a duly authorized representative of the identified Business, and authorized personnel of John Kelly &amp; Company involved in the engagement. Unauthorized access, reproduction, alteration, use, or disclosure is prohibited except as permitted by applicable policy and law.</p><footer class="footer"><span>SOW Report · Controlled project record · ${ORDO.esc(project.ref||`PROJ-${ORDO_PROJECT_ID}`)}</span><strong>John Kelly &amp; Company</strong></footer></main></body></html>`;
  }

  function printReport() {
    const win = window.open("", "_blank");
    if (!win) return alert("Allow pop-ups to open the print preview.");
    win.document.write(reportHtml());
    win.document.close();
    win.focus();
    win.print();
  }

  function downloadReport() {
    const blob = new Blob([reportHtml()], { type: "text/html" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `${project.ref || "project"}-sow-report.html`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 1000);
  }

  function previewApproved() {
    const modal = document.getElementById("updatesModal");
    document.getElementById("updatesModalTitle").textContent = "Approved SOW Report";
    document.getElementById("updatesModalMeta").textContent = ORDO_STATE.approvedSowReport?.fileName
      ? `${ORDO_STATE.approvedSowReport.fileName} · uploaded ${new Date(ORDO_STATE.approvedSowReport.uploadedAt).toLocaleString()}`
      : "Electronically issued report preview";
    document.getElementById("updatesModalBody").innerHTML = `<iframe title="Approved SOW Report preview" style="width:100%;height:60vh;border:1px solid #dce4f2" srcdoc="${ORDO.esc(reportHtml())}"></iframe>`;
    modal.classList.add("open");
  }

  function sendToClient() {
    const sentAt = now();
    ORDO_STATE.approvedSowReport = { ...(ORDO_STATE.approvedSowReport || {}), method: "Electronic", sentAt };
    ORDO_STATE.clientReportCommunications ||= [];
    ORDO_STATE.clientReportCommunications.unshift({ id:`CLIENT-FINAL-REPORT-${ORDO_PROJECT_ID}-${Date.now()}`, type:"Final SOW Report", taskTitle:"SOW Report", recipient:project.clientEmail || ORDO_STATE.clientContact?.email || "Client email on file", client:project.client || "Client", text:"Final SOW Report sent to client.", files:[], sentAt, lastSentAt:sentAt, status:"Sent to Client", attempts:1, proofId:`FINAL-REPORT-${ORDO_PROJECT_ID}-${Date.now()}` });
    ORDO.log("SOW Report sent to client");
    ORDO.save();
    render();
  }

  function completeReport() {
    if (!allComplete()) return alert("Complete every Execution task first.");
    const at = now();
    stage.status = "Completed";
    stage.completedAt = at;
    if (stage.inProgressStartedAt) {
      stage.inProgressSeconds = elapsed(stage.inProgressSeconds, stage.inProgressStartedAt);
      stage.inProgressStartedAt = null;
    }
    ORDO.log("SOW Report stage completed");
    ORDO.save();
    render();
  }

  function render() {
    const progress = elapsed(stage.inProgressSeconds, stage.inProgressStartedAt);
    const hold = elapsed(stage.onHoldSeconds, stage.onHoldStartedAt);
    const waiting = elapsed(stage.waitingSeconds, stage.waitingStartedAt);
    const uploaded = ORDO_STATE.approvedSowReport?.uploadedAt;
    let controls = `<button class="stage-control" id="reportPrint">Print</button><button class="stage-control" id="reportDownload">Download</button>`;
    if (!allComplete()) controls = `<a class="stage-control primary" href="execution.html?project=${ORDO_PROJECT_ID}">Open Execution</a>${controls}<div class="workflow-note">Complete every Execution task before completing the SOW Report.</div>`;
    else if (stage.status === "Completed") controls = `<button class="stage-control primary" id="reportSend">Send Another Report to Client</button><button class="stage-control" id="reportPrint">Print</button><button class="stage-control" id="reportDownload">Download</button><button class="stage-control primary" id="reportPreview">Preview Approved SOW Report</button>`;
    else controls = `<button class="stage-control primary" id="reportSend">${ORDO_STATE.approvedSowReport?.sentAt ? "Send Another Report to Client" : "Send to Client"}</button><label class="stage-control" for="approvedReportUpload">${uploaded ? "Change Uploaded SOW Report" : "Upload Approved SOW Report"}</label><input id="approvedReportUpload" type="file" accept="application/pdf" hidden>${uploaded ? '<button class="stage-control danger" id="reportRemove">Remove Uploaded SOW Report</button>' : ""}${controls}<button class="stage-control" id="reportPreview">Preview Approved SOW Report</button><button class="stage-control primary" id="reportComplete">Complete SOW Report</button>`;
    const issuedReports=(ORDO_STATE.clientReportCommunications||[]).filter((entry)=>entry.type==="Final SOW Report").length;
    side.innerHTML = `<div class="side-title">STAGE MANAGEMENT</div><div class="stage-management-panel wo-management"><section><h4>STAGE STATUS</h4><div class="stage-status-value ${status().toLowerCase().replaceAll(" ", "-")}">${ORDO.esc(status())}</div></section><section><h4>STAGE HANDLING</h4><div class="stage-total"><span>Total Stage Handling<br>Time</span><strong>${ORDO.fmt(progress + hold)}</strong></div><dl class="stage-handling"><div><dt>In Progress</dt><dd>${ORDO.fmt(progress)}</dd></div><div><dt>On Hold</dt><dd>${ORDO.fmt(hold)}</dd></div><div><dt>Waiting</dt><dd>${ORDO.fmt(waiting)}</dd></div><div><dt>Responsible</dt><dd>${ORDO.esc(assigned("Responsible").join(", ") || "Unassigned")}</dd></div><div><dt>Waiting On</dt><dd>${allComplete() ? "—" : "Completion of Execution tasks"}</dd></div><div><dt>Started</dt><dd>${stage.startedAt ? new Date(stage.startedAt).toLocaleString() : "—"}</dd></div><div><dt>Completed</dt><dd>${stage.completedAt ? new Date(stage.completedAt).toLocaleString() : "—"}</dd></div></dl></section><section><h4>REPORT RECORD</h4><dl class="stage-handling"><div><dt>Reports Issued</dt><dd>${issuedReports}</dd></div><div><dt>Latest Electronic Report</dt><dd>${ORDO_STATE.approvedSowReport?.sentAt ? new Date(ORDO_STATE.approvedSowReport.sentAt).toLocaleString() : "Not sent"}</dd></div><div><dt>Uploaded Copy</dt><dd>${uploaded ? ORDO.esc(ORDO_STATE.approvedSowReport.fileName) : "None"}</dd></div></dl></section><section><h4>STAGE CONTROLS</h4><div class="stage-controls">${controls}</div></section></div>`;
    side.querySelector("#reportSend")?.addEventListener("click", sendToClient);
    side.querySelector("#reportPrint")?.addEventListener("click", printReport);
    side.querySelector("#reportDownload")?.addEventListener("click", downloadReport);
    side.querySelector("#reportPreview")?.addEventListener("click", previewApproved);
    side.querySelector("#reportComplete")?.addEventListener("click", completeReport);
    side.querySelector("#approvedReportUpload")?.addEventListener("change", (event) => { const file = event.target.files?.[0]; if (!file) return; ORDO_STATE.approvedSowReport = { ...(ORDO_STATE.approvedSowReport || {}), method:"Manual", fileName:file.name, fileSize:file.size, uploadedAt:now() }; ORDO.log("Approved SOW Report uploaded"); ORDO.save(); render(); });
    side.querySelector("#reportRemove")?.addEventListener("click", () => { if (!confirm("Remove the uploaded SOW Report?")) return; const sentAt=ORDO_STATE.approvedSowReport?.sentAt; ORDO_STATE.approvedSowReport=sentAt?{method:"Electronic",sentAt}:null; ORDO.log("Uploaded SOW Report removed"); ORDO.save(); render(); });
  }

  render();
  setInterval(render, 1000);
})();
