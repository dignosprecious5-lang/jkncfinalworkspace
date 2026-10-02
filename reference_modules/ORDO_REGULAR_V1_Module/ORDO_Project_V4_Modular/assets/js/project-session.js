/* Per-project state for the file-based prototype. Loaded after seed data. */
(function () {
  "use strict";
  const KEY = "ordoModularV5",
    STAGES = [
      "Work Order",
      "SOW",
      "Review",
      "NTP",
      "Execution",
      "Reporting",
      "Presentation",
      "Delivery",
      "Completion",
    ],
    copy = (x) => JSON.parse(JSON.stringify(x));
  const read = () => {
    try {
      return JSON.parse(localStorage.getItem(KEY) || "null");
    } catch {
      return null;
    }
  };
  let db = read();
  if (!db || db.version !== 5 || !Array.isArray(db.projects) || !db.states)
    db = {
      version: 5,
      projects: copy(ORDO_STATE.projects),
      states: {},
      viewer: "John Kelly Abalde",
    };
  db.projects.forEach((savedProject) => {
    const sourceProject = ORDO_STATE.projects.find((item) => item.id === savedProject.id);
    if (!savedProject.clientEmail && sourceProject?.clientEmail)
      savedProject.clientEmail = sourceProject.clientEmail;
    if (!savedProject.epaNo && sourceProject?.epaNo)
      savedProject.epaNo = sourceProject.epaNo;
  });
  const requested = new URLSearchParams(location.search).get("project");
  const project =
    db.projects.find((p) => String(p.id) === requested) ||
    (!requested ? db.projects[0] : null);
  if (!project) {
    document.body.innerHTML =
      '<main><h1>Project not found</h1><a href="../index.html">Back to registry</a></main>';
    throw new Error("Unknown project ID");
  }
  window.ORDO_PROJECT_ID = project.id;
  window.ORDO_DB = db;
  function fresh(p) {
    const s = copy(ORDO_STATE),
      active = !["SOW", "Work Order"].includes(p.stage),
      closed = p.stage === "Completed";
    delete s.projects;
    if (p.id !== 120) {
      s.streams = [
        {
          id: "ws-" + p.id,
          name: p.title,
          tasks: [
            {
              id: "t-" + p.id,
              title: p.title,
              assignee: p.lead,
              est: "1h",
              status: closed ? "Completed" : "Ready",
              seconds: 0,
              report: true,
            },
          ],
        },
      ];
      s.files = [];
      s.updates = [];
      s.history = [];
    }
    s.sowWorkflow = {
      demoVersion: 10,
      status: active ? "final" : "draft",
      editMode: !active,
      autoSave: true,
    };
    s.ntpApproved = active;
    s.closed = closed;
    s.transmittal = closed ? "TRN-" + p.id : null;
    s.coc = closed ? "COC-" + p.id : null;
    s.projectTimer = { state: "stopped", seconds: 0, started: null };
    s.taskTimers = {};
    s.deliverables = s.deliverables.map((d) => ({
      ...d,
      status: closed ? "Ready" : d.status,
    }));
    s.reportDraft = { issues: "", recommendations: "", way: "" };
    s.clientActions = [];
    const workOrderComplete = p.stage !== "Work Order";
    s.workOrder = {
      version: 1,
      status: workOrderComplete ? "Completed" : "Draft",
      title: p.title,
      classification: "Corporate Services Project",
      priority: "Normal",
      remarks: "",
      workOrderNo: `PROJ-WO-${new Date().getFullYear()}-${p.id}`,
      projectRef: p.ref,
      serviceMemo: p.serviceMemo || "SM-2026-087",
      startRef: p.startRef || "START-2026-018",
      service: p.service || p.title,
      serviceArea: p.serviceArea || "Corporate Services",
      engagementType: p.engagementType || "Project",
      targetStart: p.targetStart || "Aug 17, 2026",
      targetEnd: p.target,
      projectRoles: [],
      stageAssignments: [],
      stageSchedule: STAGES.map((stage) => ({ stage, targetStart: "", targetEnd: "" })),
      notificationsSentAt: workOrderComplete ? new Date().toISOString() : null,
      completedAt: workOrderComplete ? new Date().toISOString() : null,
    };
    const currentStage = STAGES.indexOf(p.stage);
    s.stageManagement = {
      selected: currentStage >= 0 ? currentStage : 0,
      stages: STAGES.map((name, index) => ({
        name,
        status: index < currentStage ? "Completed" : index === currentStage ? "In Progress" : "Not Started",
        inProgressSeconds: 0,
        onHoldSeconds: 0,
        inProgressStartedAt: null,
        onHoldStartedAt: null,
        waitingSeconds: 0,
        waitingStartedAt: null,
        waitingOn: null,
        waitingReason: null,
        responsiblePerson: p.lead || null,
        startedAt: null,
        completedAt: null,
        canceledAt: null,
        history: [],
      })),
    };
    return s;
  }
  const state = db.states[project.id] || fresh(project);
  if (!state.workOrder) {
    const completed = project.stage !== "Work Order";
    state.workOrder = {
      version: 1, status: completed ? "Completed" : "Draft", title: project.title,
      classification: "Corporate Services Project", priority: "Normal", remarks: "",
      workOrderNo: `PROJ-WO-${new Date().getFullYear()}-${project.id}`, projectRef: project.ref,
      serviceMemo: project.serviceMemo || "SM-2026-087", startRef: project.startRef || "START-2026-018",
      service: project.service || project.title, serviceArea: project.serviceArea || "Corporate Services",
      engagementType: project.engagementType || "Project", targetStart: project.targetStart || "Aug 17, 2026",
      targetEnd: project.target, projectRoles: [], stageAssignments: [],
      stageSchedule: STAGES.map((stage) => ({ stage, targetStart: "", targetEnd: "" })),
      notificationsSentAt: completed ? new Date().toISOString() : null,
      completedAt: completed ? new Date().toISOString() : null,
    };
  }
  // Present the primary mock project as an unfinished Work Order once after this UI upgrade.
  if (project.id === 120 && Number(state.workOrder.version || 0) < 2) {
    state.workOrder.version = 2;
    state.workOrder.status = "Draft";
    state.workOrder.notificationsSentAt = null;
    state.workOrder.completedAt = null;
    const workOrderStage = state.stageManagement?.stages?.[0];
    if (workOrderStage) {
      workOrderStage.status = "Draft";
      workOrderStage.completedAt = null;
      workOrderStage.waitingSeconds = 0;
      workOrderStage.waitingStartedAt = null;
      workOrderStage.waitingOn = null;
      workOrderStage.waitingReason = null;
    }
  }
  const sowDefaults = { Responsible: project.lead, Executor: "Rubeca Potayre", Reviewer: "Maria Santos", Approver: "Lyndon Earl Rio" };
  Object.entries(sowDefaults).forEach(([responsibility, person]) => {
    state.workOrder.stageAssignments ||= [];
    let row = state.workOrder.stageAssignments.find((item) => item.stage === "SOW" && item.responsibilities?.includes(responsibility));
    if (!row) {
      row = { id: `sa-SOW-${responsibility}`, stage: "SOW", responsibilities: [responsibility], persons: [], acknowledgment: "Pending", required: true };
      state.workOrder.stageAssignments.push(row);
    }
    if (!row.persons?.length) row.persons = [person];
  });
  if (!state.stageManagement) {
    const current = Math.max(0, STAGES.indexOf(project.stage));
    state.stageManagement = {
      selected: current,
      stages: STAGES.map((name, index) => ({
        name,
        status: index < current ? "Completed" : index === current ? "In Progress" : "Not Started",
        inProgressSeconds: 0,
        onHoldSeconds: 0,
        inProgressStartedAt: null,
        onHoldStartedAt: null,
        startedAt: null,
        completedAt: null,
        canceledAt: null,
        history: [],
      })),
    };
  }
  state.stageManagement.stages.forEach((stage) => {
    stage.waitingSeconds = Number(stage.waitingSeconds || 0);
    stage.waitingStartedAt ||= null;
    stage.waitingOn ||= null;
    stage.waitingReason ||= null;
    stage.responsiblePerson ||= project.lead || null;
  });
  state.registryLocked = false;
  try {
    const lifecycleRecords = JSON.parse(localStorage.getItem("ordoProjectRegistryLifecycle") || "{}");
    const lifecycle = lifecycleRecords[project.id];
    if (lifecycle) {
      state.registryLifecycle = lifecycle;
      state.registryLocked = ["Cancelled", "Deleted"].includes(lifecycle.status);
      state.history ||= [];
      const lifecycleAction = lifecycle.lastAction || lifecycle.status;
      const eventId = `REGISTRY-${project.id}-${lifecycleAction}-${lifecycle.at}`;
      if (!state.history.some((event) => event.id === eventId)) {
        const actionText = lifecycleAction === "Restored"
          ? `Project restored to ${String(lifecycle.status).toLowerCase()}: ${lifecycle.reason}`
          : `Project ${String(lifecycle.status).toLowerCase()}: ${lifecycle.reason}`;
        state.history.unshift({
          id: eventId,
          at: lifecycle.at,
          user: lifecycle.user || "Current User",
          module: "Project Registry",
          type: lifecycleAction,
          action: actionText,
          details: lifecycle.reason,
          status: "Recorded",
        });
      }
    }
  } catch {
    // A malformed optional registry audit record must not block the project workspace.
  }
  state.projects = db.projects;
  state.viewer = db.viewer || state.viewer || "Current User";
  window.ORDO_STATE = state;
  if (state.registryLocked && /\/workspace\//i.test(location.pathname)) {
    document.documentElement.classList.add("registry-read-only");
    const lifecycle = state.registryLifecycle;
    const lockControls = () => {
      document.querySelectorAll("button,input,select,textarea,[contenteditable='true'],[role='button'],a[download],a[href^='#'],a[href^='javascript:']").forEach((control) => {
        if (!control.disabled) control.disabled = true;
        control.setAttribute("aria-disabled", "true");
        if (control.matches("a")) control.setAttribute("tabindex", "-1");
      });
    };
    document.addEventListener("click", (event) => {
      if (event.target.closest("button,input,select,textarea,[contenteditable='true'],[role='button'],a[download],a[href^='#'],a[href^='javascript:']")) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
    }, true);
    document.addEventListener("submit", (event) => {
      event.preventDefault();
      event.stopImmediatePropagation();
    }, true);
    const banner = document.createElement("div");
    banner.className = `registry-lock-banner ${String(lifecycle.status).toLowerCase()}`;
    banner.innerHTML = `<div><strong>Project ${lifecycle.status}</strong><span>This project is read-only. Restore it from All Projects before making changes or performing actions.</span><small>${lifecycle.reason ? `Reason: ${lifecycle.reason}` : "Reason not recorded"} · ${lifecycle.user || "User not recorded"} · ${lifecycle.at ? new Date(lifecycle.at).toLocaleString() : "Time not recorded"}</small></div><a href="../index.html">Open All Projects</a>`;
    const main = document.querySelector("main.main, main, .main");
    if (main) main.insertBefore(banner, main.firstChild);
    else document.body.insertBefore(banner, document.body.firstChild);
    const style = document.createElement("style");
    style.textContent = `.registry-lock-banner{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:12px 18px;padding:14px 16px;border:1px solid #e5b9be;border-radius:10px;background:#fff4f5;color:#7c2932}.registry-lock-banner>div{display:grid;gap:4px}.registry-lock-banner strong{font-size:12px}.registry-lock-banner span{font-size:9px}.registry-lock-banner small{font-size:8px;color:#9a5960}.registry-lock-banner a{padding:8px 11px;border-radius:7px;background:#8f3039;color:#fff;text-decoration:none;font-size:9px;font-weight:800;white-space:nowrap}.registry-read-only button:disabled,.registry-read-only input:disabled,.registry-read-only select:disabled,.registry-read-only textarea:disabled{cursor:not-allowed!important;opacity:.55!important}`;
    document.head.appendChild(style);
    lockControls();
    new MutationObserver(lockControls).observe(document.body, { childList: true, subtree: true });
  }
  function persist() {
    const latest = read();
    if (latest?.version === 5) {
      db.states = { ...latest.states, ...db.states };
    }
    const saved = copy(ORDO_STATE);
    delete saved.projects;
    db.states[project.id] = saved;
    db.projects = ORDO_STATE.projects;
    const row = db.projects.find((p) => p.id === project.id),
      tasks = ORDO_STATE.streams.flatMap((w) => w.tasks);
    row.progress = tasks.length
      ? Math.round(
          (tasks.filter((t) => t.status === "Completed").length /
            tasks.length) *
            100,
        )
      : 0;
    row.stage = state.closed
      ? "Completed"
      : state.transmittal
        ? "Delivery"
        : state.ntpApproved
          ? "Execution"
          : state.sowWorkflow.status === "final"
            ? "NTP"
            : state.sowWorkflow.status === "review"
              ? "Review"
              : state.workOrder?.status === "Completed"
                ? "SOW"
                : "Work Order";
    try {
      localStorage.setItem(KEY, JSON.stringify(db));
    } catch {
      throw new Error(
        "Changes could not be saved. Browser storage may be full or unavailable.",
      );
    }
  }
  window.ORDO_SAVE = persist;
  window.ORDO_RESET = () => {
    Object.keys(localStorage)
      .filter(
        (k) =>
          k === KEY ||
          k.startsWith("ordoSowReviewV2-") ||
          k.startsWith("ordoNtpFunctionalV2-"),
      )
      .forEach((k) => localStorage.removeItem(k));
    location.href = location.pathname.includes("/workspace/")
      ? "../index.html"
      : "index.html";
  };
  window.ORDO_ADD_PROJECT = (p) => {
    p.id = Math.max(...db.projects.map((p) => p.id)) + 1;
    p.ref = "PROJ-" + new Date().getFullYear() + "-" + p.id;
    p.stage = "Work Order";
    p.progress = 0;
    p.myRole = "Lead Consultant";
    p.health = "On Track";
    db.projects.push(p);
    persist();
    return p.id;
  };
  window.ORDO_STOP_OTHER_PROJECTS = () => {
    const latest = read();
    if (!latest) return;
    const stop = (timer, task) => {
      if (timer?.state === "running" && timer.started) {
        const seconds = Math.max(
          0,
          Math.floor((Date.now() - timer.started) / 1000),
        );
        if (task) task.seconds += seconds;
        else timer.seconds += seconds;
        timer.state = "stopped";
        timer.started = null;
      }
    };
    for (const [id, s] of Object.entries(latest.states)) {
      if (Number(id) === project.id) continue;
      stop(s.projectTimer);
      Object.entries(s.taskTimers || {}).forEach(([id, t]) =>
        stop(
          t,
          s.streams.flatMap((w) => w.tasks).find((t) => t.id === id),
        ),
      );
      db.states[id] = s;
    }
  };
  addEventListener("storage", (e) => {
    if (e.key === KEY && !document.querySelector("input:focus,textarea:focus"))
      location.reload();
  });
  persist();
})();
