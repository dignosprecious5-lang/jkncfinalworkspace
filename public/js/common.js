window.ORDO = {
  stages: [
    "Work Order",
    "Plan", "Review", "NTP", "Execution",
  ],
  lifecyclePercentages: ["0%", "25%", "50%", "75%", "100%"],
  esc(s) {
    return String(s ?? "").replace(/[&<>"']/g, function (c) {
      if (c === "&") return "&amp;";
      if (c === "<") return "&lt;";
      if (c === ">") return "&gt;";
      if (c === '"') return "&quot;";
      return "&#39;";
    });
  },
  badge(s) {
    const c =
      s === "Completed" || s === "Ready"
        ? "green"
        : s === "Pending" || s === "Pending Client"
          ? "amber"
          : s === "In Progress"
            ? "purple"
            : s === "At Risk" || s === "Needs Attention"
              ? "red"
              : "blue";
    return `<span class=\"badge ${c}\">${this.esc(s)}</span>`;
  },
  dur(sec) {
    sec = Math.max(0, Math.floor(Number(sec) || 0));
    const h = Math.floor(sec / 3600),
      m = Math.floor((sec % 3600) / 60);
    return h ? `${h}h ${m}m` : `${m}m`;
  },
  fmt(sec) {
    sec = Math.max(0, Math.floor(Number(sec) || 0));
    const h = Math.floor(sec / 3600),
      m = Math.floor((sec % 3600) / 60),
      s = sec % 60;
    return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
  },
  allTasks() {
    return ORDO_STATE.streams.flatMap((w) =>
      w.tasks.map((t) => ({ task: t, stream: w })),
    );
  },
  findTask(id) {
    return this.allTasks().find((x) => x.task.id === id)?.task;
  },
  findStream(id) {
    return ORDO_STATE.streams.find((x) => x.id === id);
  },
  timerFor(id) {
    ORDO_STATE.taskTimers[id] = ORDO_STATE.taskTimers[id] || {
      state: "stopped",
      started: null,
    };
    return ORDO_STATE.taskTimers[id];
  },
  taskSeconds(t) {
    const x = this.timerFor(t.id);
    return (
      Number(t.seconds || 0) +
      (x.state === "running" && x.started
        ? Math.floor((Date.now() - x.started) / 1000)
        : 0)
    );
  },
  projectSeconds() {
    const x = ORDO_STATE.projectTimer;
    return (
      Number(x.seconds || 0) +
      (x.state === "running" && x.started
        ? Math.floor((Date.now() - x.started) / 1000)
        : 0)
    );
  },
  totalHandling() {
    return (
      this.projectSeconds() +
      this.allTasks().reduce((s, x) => s + this.taskSeconds(x.task), 0)
    );
  },
  doneCount() {
    return this.allTasks().filter((x) => x.task.status === "Completed").length;
  },
  save() {
    ORDO_SAVE();
  },
  log(action, details = {}) {
    const page = location.pathname.split("/").pop()?.replace(".html", "") || "project";
    const moduleNames = {"project-dashboard":"Regular Dashboard","work-order":"Work Order","scope-of-work":"RSAT",review:"Review",ntp:"NTP",execution:"Execution","sow-report":"RSAT Report","delivery-completion":"Delivery & Completion",attachment:"Attachment",history:"History"};
    const timestamp = new Date();
    ORDO_STATE.history.unshift({
      id: details.id || `AUDIT-${ORDO_PROJECT_ID}-${timestamp.getTime()}`,
      action,
      at: timestamp.toISOString(),
      user: details.user || ORDO_STATE.viewer || "Current User",
      module: details.module || moduleNames[page] || page,
      type: details.type || "User Action",
      project: (typeof ORDO_PROJECT !== "undefined" && ORDO_PROJECT?.ref) || `REG-${ORDO_PROJECT_ID}`,
      details: details.details || null,
    });
    this.save();
  },
  stopRunning() {
    const p = ORDO_STATE.projectTimer;
    if (p.state === "running" && p.started) {
      p.seconds += Math.floor((Date.now() - p.started) / 1000);
      p.state = "stopped";
      p.started = null;
    }
    Object.entries(ORDO_STATE.taskTimers).forEach(([id, x]) => {
      if (x.state === "running" && x.started) {
        const t = this.findTask(id);
        if (t) t.seconds += Math.floor((Date.now() - x.started) / 1000);
        x.state = "stopped";
        x.started = null;
      }
    });
    this.save();
  },
  startProject() {
    if (ORDO_STATE.closed) return;
    if (
      location.pathname.endsWith("/execution.html") &&
      !ORDO_STATE.ntpApproved
    )
      return alert("Approve NTP first.");
    ORDO_STOP_OTHER_PROJECTS();
    this.stopRunning();
    ORDO_STATE.projectTimer.state = "running";
    ORDO_STATE.projectTimer.started = Date.now();
    this.log("Regular direct timer started");
  },
  pauseProject() {
    const x = ORDO_STATE.projectTimer;
    if (x.state !== "running") return;
    x.seconds += Math.floor((Date.now() - x.started) / 1000);
    x.state = "paused";
    x.started = null;
    this.log("Regular direct timer paused");
  },
  stopProject() {
    const x = ORDO_STATE.projectTimer;
    if (x.state === "running" && x.started)
      x.seconds += Math.floor((Date.now() - x.started) / 1000);
    x.state = "stopped";
    x.started = null;
    this.log("Regular direct timer stopped");
  },
  startTask(id) {
    if (
      ORDO_STATE.closed ||
      !ORDO_STATE.ntpApproved ||
      ORDO_STATE.sowWorkflow.status !== "final"
    )
      return alert("Approve RSAT and NTP before execution.");
    ORDO_STOP_OTHER_PROJECTS();
    const t = this.findTask(id);
    if (!t || t.status === "Completed") return;
    this.stopRunning();
    const x = this.timerFor(id);
    x.state = "running";
    x.started = Date.now();
    t.status = "In Progress";
    this.log("Timer started: " + t.title);
  },
  pauseTask(id) {
    const t = this.findTask(id),
      x = this.timerFor(id);
    if (!t || x.state !== "running") return;
    t.seconds += Math.floor((Date.now() - x.started) / 1000);
    x.state = "paused";
    x.started = null;
    this.log("Timer paused: " + t.title);
  },
  stopTask(id) {
    const t = this.findTask(id),
      x = this.timerFor(id);
    if (!t) return;
    if (x.state === "running" && x.started)
      t.seconds += Math.floor((Date.now() - x.started) / 1000);
    x.state = "stopped";
    x.started = null;
    this.log("Timer stopped: " + t.title);
  },
  doneTask(id) {
    if (ORDO_STATE.closed || ORDO_STATE.transmittal || !ORDO_STATE.ntpApproved)
      return alert("Execution is locked.");
    const t = this.findTask(id);
    if (!t) return;
    this.stopTask(id);
    t.status = "Completed";
    this.log("Task completed: " + t.title);
    this.save();
  },
  duplicateTask(id) {
    if (ORDO_STATE.closed || ORDO_STATE.transmittal) return;
    const item = this.allTasks().find((x) => x.task.id === id);
    if (!item) return;
    item.stream.tasks.push({
      ...item.task,
      id: "t" + Date.now(),
      title: item.task.title + " (Copy)",
      status: "Ready",
      seconds: 0,
    });
    this.log("Task duplicated: " + item.task.title);
  },
  duplicateStream(id) {
    if (ORDO_STATE.closed || ORDO_STATE.transmittal) return;
    const w = this.findStream(id);
    if (!w) return;
    ORDO_STATE.streams.push({
      id: "ws" + Date.now(),
      name: w.name + " (Copy)",
      tasks: w.tasks.map((t) => ({
        ...t,
        id: "t" + Math.random().toString(36).slice(2, 8),
        status: "Ready",
        seconds: 0,
      })),
    });
    this.log("Workstream duplicated: " + w.name);
  },
  quickTask(ws, title) {
    const w = this.findStream(ws) || ORDO_STATE.streams[0];
    if (!title) return;
    w.tasks.push({
      id: "t" + Date.now(),
      title,
      assignee: "Unassigned",
      est: "1h",
      status: "Ready",
      seconds: 0,
      report: true,
    });
    this.log("Quick Task added: " + title);
  },
};
