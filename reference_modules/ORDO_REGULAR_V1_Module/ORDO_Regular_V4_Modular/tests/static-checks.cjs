const fs = require("node:fs"),
  path = require("node:path"),
  vm = require("node:vm"),
  assert = require("node:assert/strict");
const root = path.resolve(__dirname, "..");
process.chdir(root);
const walk = (d) =>
  fs
    .readdirSync(d, { withFileTypes: true })
    .flatMap((e) =>
      e.name.startsWith(".") || e.name === "vendor"
        ? []
        : e.isDirectory()
          ? walk(path.join(d, e.name))
          : [path.join(d, e.name)],
    );
const files = walk(".");
let scripts = 0;
for (const file of files.filter((f) => f.endsWith(".html"))) {
  const html = fs.readFileSync(file, "utf8");
  for (const m of html.matchAll(/(?:href|src)=["']([^"']+)["']/g)) {
    const url = m[1].split(/[?#]/)[0];
    if (!url || /^(?:[a-z]+:|\/|\{)/i.test(url)) continue;
    assert.ok(
      fs.existsSync(path.resolve(path.dirname(file), url)),
      file + " has missing target " + url,
    );
  }
  for (const m of html.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)) {
    if (m[1].trim()) {
      new vm.Script(m[1], { filename: file });
      scripts++;
    }
  }
}
for (const file of files.filter((f) => f.endsWith(".js"))) {
  new vm.Script(fs.readFileSync(file, "utf8"), { filename: file });
  scripts++;
}
console.log("Local links and " + scripts + " JavaScript blocks/files passed.");
let JSDOM, Parser;
try {
  ({ JSDOM } = require("../.qa/node_modules/jsdom"));
  Parser = require("../.qa/node_modules/php-parser");
} catch {
  console.log(
    "Optional DOM/PHP checks: install jsdom and php-parser under .qa.",
  );
  process.exit(0);
}
function page(file, id = 120, saved = {}) {
  const html = fs.readFileSync(file, "utf8");
  const dom = new JSDOM(html, {
      url: "http://localhost/" + file + "?project=" + id,
      runScripts: "outside-only",
    }),
    w = dom.window;
  Object.entries(saved).forEach(([k, v]) => w.localStorage.setItem(k, v));
  w.alert = () => {};
  w.confirm = () => true;
  w.setInterval = () => 0;
  w.setTimeout = () => 0;
  w.scrollTo = () => {};
  if (w.HTMLDialogElement) {
    w.HTMLDialogElement.prototype.showModal = function () {
      this.setAttribute("open", "");
    };
    w.HTMLDialogElement.prototype.close = function () {
      this.removeAttribute("open");
    };
  }
  for (const script of [...w.document.scripts]) {
    const source = script.getAttribute("src");
    w.eval(
      source
        ? fs.readFileSync(path.resolve(path.dirname(file), source), "utf8")
        : script.textContent,
    );
  }
  return w;
}
const active = [
  "index.html",
  ...files.filter((f) => /^(workspace|modules)[\\/].*\.html$/.test(f)),
];
for (const file of active) {
  for (const id of [120, 119, 110]) {
    const w = page(file, id);
    assert.equal(w.ORDO_PROJECT_ID, id);
    w.close();
  }
}
console.log(
  "All " +
    active.length +
    " active/versioned pages initialize for execution, draft, and completed projects.",
);
let w = page("index.html");
const links = [...w.document.querySelectorAll("#rows a")].map((a) =>
  new URL(a.href).searchParams.get("project"),
);
assert.equal(new Set(links).size, 5);
const cancelButton = w.document.querySelector('[data-registry-action="Cancelled"]');
assert.ok(cancelButton);
cancelButton.click();
assert.ok(w.document.querySelector(".registry-action-dialog").hasAttribute("open"));
assert.match(w.document.getElementById("registryActionTitle").textContent, /Cancel/i);
const completedTab = [...w.document.querySelectorAll("#registryRecordTabs button")].find(
  (button) => button.dataset.recordStatus === "Completed",
);
assert.ok(completedTab);
completedTab.click();
const completedLinks = [...w.document.querySelectorAll("#rows a")].map((a) =>
  new URL(a.href).searchParams.get("project"),
);
assert.equal(new Set(completedLinks).size, 1);
w.close();
w = page("workspace/project-dashboard.html");
w.document.getElementById("timerStart").click();
assert.equal(w.ORDO_STATE.projectTimer.state, "running");
w.ORDO_STATE.projectTimer.seconds = 123;
w.document.getElementById("timerStop").click();
assert.ok(w.ORDO_STATE.projectTimer.seconds >= 123);
w.close();
w = page("workspace/execution.html", 119);
w.document.querySelector("#streams .open-task-workspace").click();
w.document.getElementById("taskPlay").click();
assert.notEqual(w.ORDO_STATE.taskTimers["t-119"]?.state, "running");
w.close();
w = page("workspace/sow-report.html");
assert.equal(w.document.querySelectorAll("#items .report-coverage-row").length, w.ORDO.allTasks().length);
const issues = w.document.getElementById("issues");
issues.value = "Saved narrative";
issues.dispatchEvent(new w.Event("input", { bubbles: true }));
const saved = Object.fromEntries(
  Object.keys(w.localStorage).map((k) => [k, w.localStorage.getItem(k)]),
);
w.close();
w = page("workspace/sow-report.html", 120, saved);
assert.equal(w.document.getElementById("issues").value, "Saved narrative");
w.close();
console.log(
  "Record navigation, timer preservation/gating, automatic report coverage and narrative persistence passed.",
);
const snapshot = (window) =>
  Object.fromEntries(
    Object.keys(window.localStorage).map((k) => [
      k,
      window.localStorage.getItem(k),
    ]),
  );
// Work Order policy flow and explicit waiting periods.
w = page("workspace/work-order.html", 119);
w.ORDO_STATE.workOrder.status = "Draft";
w.ORDO_STATE.workOrder.completedAt = null;
w.ORDO_STATE.workOrder.notificationsSentAt = null;
w.ORDO_STATE.workOrder.projectRoles.forEach((row) => (row.acknowledgment = "Pending"));
w.ORDO_STATE.workOrder.stageAssignments.forEach((row) => (row.acknowledgment = "Pending"));
w.ORDO_STATE.stageManagement.stages[0].status = "Draft";
w.ORDO_STATE.stageManagement.stages[0].completedAt = null;
w.ORDO_SAVE();
let workOrderFlow = snapshot(w);
w.close();
w = page("workspace/work-order.html", 119, workOrderFlow);
assert.equal(w.document.querySelectorAll("#stageSchedule tbody tr").length, 5);
const scheduleStart = w.document.querySelector('#stageSchedule [data-schedule="targetStart"]');
const scheduleEnd = w.document.querySelector('#stageSchedule [data-schedule="targetEnd"]');
scheduleStart.value = "2026-09-01";
scheduleStart.dispatchEvent(new w.Event("change", { bubbles: true }));
scheduleEnd.value = "2026-09-02";
scheduleEnd.dispatchEvent(new w.Event("change", { bubbles: true }));
assert.equal(w.ORDO_STATE.workOrder.stageSchedule[0].targetEnd, "2026-09-02");
w.document.getElementById("woStart").click();
assert.ok(w.ORDO_STATE.stageManagement.stages[0].inProgressStartedAt);
w.document.getElementById("woPause").click();
assert.ok(w.ORDO_STATE.stageManagement.stages[0].onHoldStartedAt);
w.document.getElementById("woStart").click();
w.document.querySelector('[data-wo="submit"]').click();
assert.equal(w.ORDO_STATE.workOrder.status, "For Review");
assert.ok(w.ORDO_STATE.stageManagement.stages[0].waitingStartedAt);
assert.equal(w.ORDO_STATE.stageManagement.stages[0].inProgressStartedAt, null);
w.document.querySelector('[data-wo="approval"]').click();
assert.equal(w.ORDO_STATE.workOrder.status, "For Approval");
assert.match(w.ORDO_STATE.stageManagement.stages[0].waitingReason, /Approval/);
w.document.querySelector('[data-wo="approve"]').click();
assert.equal(w.ORDO_STATE.workOrder.status, "Approved");
assert.equal(w.ORDO_STATE.stageManagement.stages[0].waitingStartedAt, null);
w.document.querySelector('[data-wo="notify"]').click();
assert.equal(w.ORDO_STATE.workOrder.status, "Awaiting Acknowledgment");
for (const select of w.document.querySelectorAll('[data-field="acknowledgment"]')) {
  if (!select.disabled) { select.value = "Acknowledged"; select.dispatchEvent(new w.Event("change", { bubbles: true })); }
}
assert.equal(w.ORDO_STATE.workOrder.status, "Completed");
assert.equal(w.ORDO_STATE.stageManagement.stages[0].status, "Completed");
w.close();
console.log("Work Order review, approval, notification, waiting, acknowledgment and SOW gate passed.");
w = page("workspace/scope-of-work.html", 119);
w.ORDO_STATE.regular.period="September 2026";
w.RSAT_FORM.rows().forEach(row=>row.schedules.forEach(rule=>rule.configured=true));
w.RSAT_ADAPTER.save(w.RSAT_FORM.rows());
assert.equal(w.document.querySelectorAll(".rsat-table th").length,6);
assert.ok(!/Within Scope|Out of Scope/.test(w.document.body.textContent));
w.document.getElementById("submitReviewBtn").onclick();
assert.equal(w.ORDO_STATE.sowWorkflow.status, "review");
let flow = snapshot(w);
w.close();
w = page("workspace/review.html", 119, flow);
assert.equal(w.ORDO_STATE.sowReviewApproval.phase, "review");
w.document.getElementById("policyAdvance").click();
assert.equal(w.ORDO_STATE.sowReviewApproval.phase, "approval");
flow = snapshot(w);
w.close();
w = page("workspace/review.html", 119, flow);
w.document.getElementById("policyAdvance").click();
assert.equal(w.ORDO_STATE.sowReviewApproval.phase, "approved");
assert.equal(w.ORDO_STATE.sowWorkflow.status, "final");
assert.ok(w.ORDO_STATE.notifications.length > 0);
flow = snapshot(w);
w.close();
w = page("workspace/ntp.html", 119, flow);
w.document.getElementById("issueNtpBtn").onclick();
w.document.getElementById("confirmRecordEmail").checked = true;
w.document.getElementById("confirmRecordEmail").onchange({ target: { checked: true } });
w.document.getElementById("confirmEmailApproval").onclick();
for (const party of ["client", "leadConsultant", "leadAssociate"]) {
  w.document.getElementById("approvalParty").value = party;
  w.document.getElementById("approveNtpBtn").onclick();
}
assert.equal(w.ORDO_STATE.ntpApproved, true);
flow = snapshot(w);
w.close();
w = page("workspace/execution.html", 119, flow);
assert.ok(w.ORDO.allTasks().some(x=>x.task.status!=="Completed")); // Periodic reports must include unfinished work.
flow = snapshot(w);
w.close();
w = page("workspace/sow-report.html", 119, flow);
w.ORDO_STATE.regular.period="September 2026";
w.document.getElementById("approve").onclick();
assert.equal(
  w.ORDO_STATE.deliverables.find((d) => d.name === "Final RSAT Report").status,
  "Ready",
);
flow = snapshot(w);
w.close();
w = page("workspace/delivery-completion.html", 119, flow);
assert.equal(w.ORDO.stages.join(' → '),'Work Order → Plan → Review → NTP → Execution');
w.ORDO_REGULAR.completeCycle('TRN-TEST-001');
assert.equal(w.ORDO_STATE.closed,false);
assert.equal(w.ORDO_STATE.regular.status,'Cycle Completed');
assert.equal(w.ORDO_STATE.regular.archives.length,1);
w.ORDO_REGULAR.nextCycle('October 2026');
assert.equal(w.ORDO_STATE.ntpApproved,false);
assert.equal(w.ORDO_STATE.sowWorkflow.status,'draft');
assert.equal(w.ORDO_STATE.regular.cycle,2);
assert.equal(w.ORDO_STATE.regular.archives[0].period,'September 2026');
assert.throws(()=>w.ORDO_REGULAR.snapshotReport(),/Approve/);
assert.throws(()=>w.ORDO_REGULAR.completeCycle('TRN-2'),/Approve/);
flow=snapshot(w);w.close();
w=page('workspace/scope-of-work.html',119,flow);
assert.equal(w.ORDO_STATE.ntpApproved,false);
assert.equal(w.document.querySelectorAll('.rsat-table tbody tr').length,w.ORDO.allTasks().length);
w.close();
console.log(
  "Regular RSAT → review → NTP → periodic report with pending work → cycle completion → next-cycle approval reset passed.",
);
const php = new Parser({
  parser: { phpVersion: "8.3" },
  ast: { withPositions: true },
});
let count = 0;
for (const file of files.filter(
  (f) => f.endsWith(".php") && !f.endsWith(".blade.php"),
)) {
  php.parseCode(fs.readFileSync(file, "utf8"), file);
  count++;
}
console.log(
  count +
    " PHP files passed syntax parsing. Full Laravel tests require PHP + Composer.",
);
