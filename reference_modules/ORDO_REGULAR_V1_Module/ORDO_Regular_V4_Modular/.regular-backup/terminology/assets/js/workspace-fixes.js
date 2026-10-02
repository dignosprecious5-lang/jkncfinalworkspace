(function () {
  "use strict";
  const p = ORDO_STATE.projects.find((p) => p.id === ORDO_PROJECT_ID),
    isWorkspace = location.pathname.includes("/workspace/");
  function links() {
    document.querySelectorAll("a[href]").forEach((a) => {
      const url = new URL(a.getAttribute("href"), location.href);
      if (
        url.pathname.includes("/workspace/") &&
        url.pathname.endsWith(".html")
      ) {
        if (!url.searchParams.has("project"))
          url.searchParams.set("project", p.id);
        a.href = url.href;
      }
    });
  }
  links();
  const workOrderIncomplete = ORDO_STATE.workOrder && ORDO_STATE.workOrder.status !== "Completed";
  if (workOrderIncomplete) {
    document.querySelectorAll('a[href*="scope-of-work.html"]').forEach((link) => {
      link.setAttribute("aria-disabled", "true");
      link.title = "Complete the Work Order and all required acknowledgments first.";
      link.addEventListener("click", (event) => {
        event.preventDefault();
        alert("RSAT is locked until the Work Order is completed and all required assigned persons have acknowledged.");
      });
    });
  }
  document.querySelectorAll(".quick-link[href]").forEach((link) => {
    const linkPath = new URL(link.getAttribute("href"), location.href).pathname;
    const active = linkPath === location.pathname;
    link.classList.toggle("active", active);
    if (active) link.setAttribute("aria-current", "page");
  });
  document.addEventListener(
    "click",
    (e) => {
      const a = e.target.closest("a[href]");
      if (a) links();
    },
    true,
  );
  if (isWorkspace) {
    const evidenceSave =
      document.getElementById("save") || document.getElementById("fsave");
    if (
      evidenceSave &&
      (location.pathname.endsWith("/attachment.html") ||
        location.pathname.endsWith("/execution.html"))
    ) {
      const upload = document.createElement("input");
      upload.type = "file";
      upload.setAttribute("aria-label", "Evidence attachment (up to 2 MB)");
      evidenceSave.parentElement.before(upload);
      evidenceSave.onclick = () => {
        const file = upload.files[0];
        if (!file) return alert("Choose an evidence file first.");
        if (file.size > 2 * 1024 * 1024)
          return alert("The browser demo supports files up to 2 MB.");
        const reader = new FileReader();
        reader.onload = () => {
          const entry = {
            name: file.name,
            class: (
              document.getElementById("class") ||
              document.getElementById("fclass")
            ).value,
            stage: document.getElementById("stage")?.value || "Execution",
            report: (
              document.getElementById("report") ||
              document.getElementById("freport")
            ).checked,
            record: "REC-" + Date.now(),
            data: reader.result,
          };
          ORDO_STATE.files.push(entry);
          try {
            ORDO.log("Evidence uploaded: " + file.name);
            location.reload();
          } catch (error) {
            ORDO_STATE.files.pop();
            alert(error.message);
          }
        };
        reader.onerror = () => alert("The file could not be read.");
        reader.readAsDataURL(file);
      };
    }
    document.addEventListener("click", (e) => {
      if (e.target.matches(".file-row .btn")) {
        const index = [...document.querySelectorAll(".file-row")].indexOf(
          e.target.closest(".file-row"),
        );
        const file = ORDO_STATE.files[index];
        if (!file?.data)
          return alert(
            "This is sample metadata. Upload the actual file to download its contents.",
          );
        const a = document.createElement("a");
        a.href = file.data;
        a.download = file.name;
        a.click();
      }
    });
    document
      .querySelectorAll(".workspace-title")
      .forEach((el) => (el.textContent = p.title));
    document
      .querySelectorAll(".breadcrumb strong")
      .forEach((el) => (el.textContent = p.ref));
    document
      .querySelectorAll(".refline")
      .forEach((el) => (el.textContent = p.ref + " · " + p.deal));
    document.querySelectorAll(".chips .chip").forEach((el) => {
      const b = el.querySelector("strong");
      if (!b) return;
      const label = el.textContent;
      if (label.startsWith("Business")) b.textContent = p.business;
      if (label.startsWith("Client")) b.textContent = p.client || "—";
      if (label.startsWith("Target")) b.textContent = p.target;
    });
    document
      .querySelectorAll(".relations")
      .forEach(
        (el) =>
          (el.innerHTML = `<span>Deal: ${ORDO.esc(p.deal)}</span><span>Company: ${ORDO.esc(p.business)}</span><span>Regular manager: ${ORDO.esc(p.lead)}</span>`),
      );
    document.querySelectorAll(".statuspill").forEach((el, i) => {
      if (i === 0) el.textContent = "RSAT: " + ORDO_STATE.sowWorkflow.status;
      if (i === 1)
        el.textContent =
          "NTP: " + (ORDO_STATE.ntpApproved ? "Approved" : "Pending");
      if (i === 2)
        el.textContent = ORDO_STATE.closed ? "Regular closed" : p.stage;
      if (i === 3)
        el.textContent = "Cycle: " + (ORDO_STATE.regular?.status || "Active");
    });
    const management = ORDO_STATE.stageManagement;
    const stageIndexFromQuery = new URLSearchParams(location.search).get("stage");
    if (management) {
      const requestedStage = stageIndexFromQuery === null ? null : Number(stageIndexFromQuery);
      const pageStages = {
        "/work-order.html": 0,
        "/scope-of-work.html": 1,
        "/review.html": 2,
        "/ntp.html": 3,
        "/execution.html": 4,
        "/sow-report.html": 4,
        "/delivery-completion.html": 4,
        "/history.html": 4,
        "/attachment.html": 4,
      };
      const pageStage = Object.entries(pageStages).find(([page]) =>
        location.pathname.endsWith(page),
      )?.[1];
      if (Number.isInteger(requestedStage) && requestedStage >= 0 && requestedStage < ORDO.stages.length)
        management.selected = requestedStage;
      else if (pageStage !== undefined)
        management.selected = pageStage;
      management.selected = Math.max(0, Math.min(ORDO.stages.length - 1, Number(management.selected) || 0));
      const selectedRecord = management.stages[management.selected];
      const stageLabel = ORDO.stages[management.selected];
      const stageWords = {
        "Work Order": "Work Order",
        Plan: "RSAT Planning",
        Review: "Review",
        NTP: "NTP Preparation",
        Execution: "Execution",
        Reporting: "Reporting",
        Presentation: "Presentation",
        Delivery: "Delivery",
        Completion: "Completion",
      };
      const formatDate = (value) => (value ? new Date(value).toLocaleString() : "—");
      const elapsed = (seconds, startedAt, activeState) =>
        Number(seconds || 0) + (activeState && startedAt ? Math.floor((Date.now() - new Date(startedAt).getTime()) / 1000) : 0);
      const formatSeconds = (seconds) => {
        const total = Math.max(0, Math.floor(seconds || 0));
        return `${String(Math.floor(total / 3600)).padStart(2, "0")}:${String(Math.floor((total % 3600) / 60)).padStart(2, "0")}:${String(total % 60).padStart(2, "0")}`;
      };
      const stageActions = {
        "Work Order": "Work Order",
        RSAT: "RSAT",
        Review: "Review",
        NTP: "NTP",
        Execution: "Execution",
        Reporting: "Reporting",
        Presentation: "Presentation",
        Delivery: "Delivery",
        Completion: "Completion",
      };
      const panelHost = location.pathname.endsWith("/execution.html") ? null : document.querySelector(".sidecard");
      if (panelHost) {
        if ([
          "/work-order.html",
          "/execution.html",
          "/attachment.html",
          "/sow-report.html",
          "/delivery-completion.html",
          "/history.html",
        ].some((page) => location.pathname.endsWith(page))) {
          panelHost.classList.add("stage-management-only");
          panelHost.querySelectorAll(":scope > .side-section").forEach((section) => {
            section.hidden = true;
          });
        }
        let panel = panelHost.querySelector(".stage-management-panel");
        if (!panel) {
          panel = document.createElement("div");
          panel.className = "stage-management-panel";
          const title = panelHost.querySelector(".side-title");
          if (title) title.after(panel);
          else panelHost.prepend(panel);
        }
        const label = stageActions[stageLabel] || stageLabel;
        const status = selectedRecord.status;
        const inProgress = elapsed(selectedRecord.inProgressSeconds, selectedRecord.inProgressStartedAt, status === "In Progress");
        const onHold = elapsed(selectedRecord.onHoldSeconds, selectedRecord.onHoldStartedAt, status === "On Hold");
        const totalHandling = inProgress + onHold;
        const actions = status === "Not Started"
          ? `<button class="stage-control primary" data-stage-action="start">Start ${label}</button>`
          : status === "In Progress"
            ? `<button class="stage-control" data-stage-action="hold">Put ${label} On Hold</button><button class="stage-control primary" data-stage-action="complete">Complete ${label}</button><button class="stage-control danger" data-stage-action="cancel">Cancel ${label}</button>`
            : status === "On Hold"
              ? `<button class="stage-control primary" data-stage-action="resume">Resume ${label}</button><button class="stage-control" data-stage-action="complete">Complete ${label}</button><button class="stage-control danger" data-stage-action="cancel">Cancel ${label}</button>`
              : `<button class="stage-control" data-stage-action="reopen">Reopen ${label}</button>`;
        const historySection = ["/work-order.html", "/scope-of-work.html"].some((page) => location.pathname.endsWith(page))
          ? ""
          : `<section><h4>STAGE HISTORY</h4><div class="stage-history">${(selectedRecord.history || []).slice(-4).reverse().map((item) => `<div><strong>${ORDO.esc(item.action)}</strong><small>${formatDate(item.at)}</small></div>`).join("") || "<small>No stage activity yet.</small>"}</div></section>`;
        panel.innerHTML = `<section><h4>STAGE STATUS</h4><div class="stage-status-value ${status.toLowerCase().replaceAll(" ", "-")} ">${status}</div></section><section><h4>STAGE HANDLING</h4><div class="stage-total"><span>Total Stage Handling Time</span><strong>${formatSeconds(totalHandling)}</strong></div><dl class="stage-handling"><div><dt>In Progress</dt><dd>${formatSeconds(inProgress)}</dd></div><div><dt>On Hold</dt><dd>${formatSeconds(onHold)}</dd></div><div><dt>Started</dt><dd>${formatDate(selectedRecord.startedAt)}</dd></div><div><dt>Completed</dt><dd>${formatDate(selectedRecord.completedAt)}</dd></div><div><dt>Canceled</dt><dd>${formatDate(selectedRecord.canceledAt)}</dd></div></dl></section><section><h4>STAGE CONTROLS</h4><div class="stage-controls">${actions}</div></section>${historySection}`;
        if (location.pathname.endsWith("/scope-of-work.html")) {
          const legacyControls = [...panelHost.querySelectorAll(":scope > .side-section")].find((section) => section.querySelector("#saveDraftBtn"));
          const genericControls = panel.querySelector(".stage-controls");
          if (legacyControls && genericControls) {
            genericControls.hidden = true;
            const specificControls = document.createElement("div");
            specificControls.className = "stage-specific-controls";
            [...legacyControls.children]
              .filter((child) => !child.matches("h4, .demo-reset, .workflow-note"))
              .forEach((child) => specificControls.append(child));
            genericControls.after(specificControls);
            panelHost.querySelectorAll(":scope > .side-section").forEach((section) => {
              section.hidden = true;
            });
          }
        }
        panel.querySelectorAll("[data-stage-action]").forEach((button) => {
          button.onclick = () => {
            const action = button.dataset.stageAction;
            const now = new Date().toISOString();
            const record = management.stages[management.selected];
            const finishActiveTimer = () => {
              if (record.inProgressStartedAt) {
                record.inProgressSeconds += Math.max(0, Math.floor((Date.now() - new Date(record.inProgressStartedAt).getTime()) / 1000));
                record.inProgressStartedAt = null;
              }
              if (record.onHoldStartedAt) {
                record.onHoldSeconds += Math.max(0, Math.floor((Date.now() - new Date(record.onHoldStartedAt).getTime()) / 1000));
                record.onHoldStartedAt = null;
              }
            };
            if (action === "start" || action === "resume") {
              if (record.onHoldStartedAt) {
                record.onHoldSeconds += Math.max(0, Math.floor((Date.now() - new Date(record.onHoldStartedAt).getTime()) / 1000));
                record.onHoldStartedAt = null;
              }
              record.status = "In Progress";
              record.startedAt ||= now;
              record.inProgressStartedAt ||= now;
            } else if (action === "hold") {
              if (record.inProgressStartedAt) {
                record.inProgressSeconds += Math.max(0, Math.floor((Date.now() - new Date(record.inProgressStartedAt).getTime()) / 1000));
                record.inProgressStartedAt = null;
              }
              record.status = "On Hold";
              record.onHoldStartedAt ||= now;
              record.startedAt ||= now;
            } else if (action === "complete" || action === "cancel") {
              finishActiveTimer();
              record.status = action === "complete" ? "Completed" : "Canceled";
              record.completedAt = action === "complete" ? now : null;
              record.canceledAt = action === "cancel" ? now : null;
            } else if (action === "reopen") {
              record.status = "In Progress";
              record.completedAt = null;
              record.canceledAt = null;
              record.inProgressStartedAt = now;
              record.startedAt ||= now;
            }
            record.history ||= [];
            record.history.push({ action: `${action} ${stageLabel}`, at: now });
            ORDO_SAVE();
            location.reload();
          };
        });
      }
      document.querySelectorAll("#life .stage").forEach((link, index) => {
        const url = new URL(link.href, location.href);
        url.searchParams.set("stage", index);
        link.href = url.href;
        link.classList.toggle("selected", index === management.selected);
      });
    }
    const current =
      p.stage === "Completed" ? 4 : Math.max(0, ORDO.stages.indexOf(p.stage));
    const life = document.getElementById("life");
    if (life) {
      const lifecycle = life.closest(".lifecycle");
      let shell = lifecycle?.querySelector(".life-shell");
      if (lifecycle && !shell) {
        shell = document.createElement("div");
        shell.className = "life-shell";
        shell.appendChild(life);
        const panel = document.createElement("aside");
        panel.className = "life-status";
        shell.appendChild(panel);
        lifecycle.appendChild(shell);
      }
      const percentage = ORDO.lifecyclePercentages[current] || "0%";
      const stage = ORDO.stages[current] || ORDO.stages[0];
      const status = p.health || "On Track";
      const panel = shell?.querySelector(".life-status");
      if (panel) {
        panel.innerHTML = `<span class="life-status-kicker">CURRENT STAGE</span><strong>${ORDO.esc(stage)}</strong><b>${percentage} Complete</b><dl><div><dt>Status</dt><dd>${ORDO.esc(status)}</dd></div></dl>`;
      }
      const routes = [
        "work-order",
        "scope-of-work",
        "review",
        "ntp",
        "execution",
        "sow-report",
        "attachment",
        "delivery-completion",
        "history",
      ];
      life.innerHTML = ORDO.stages
        .map(
          (s, i) => {
            const state = `${i < current ? "done" : i === current ? "current" : "upcoming"}${i === management.selected ? " selected" : ""}`;
            const node = i < current ? "✓" : "";
            return `<a class="stage ${state}" href="${routes[i]}.html?project=${p.id}&stage=${i}" aria-current="${i === current ? "step" : "false"}"><span class="stage-node" aria-hidden="true">${node}</span><span class="stage-copy"><small>${s}</small></span></a>`;
          },
        )
        .join("");
    }
    document.querySelectorAll(".ntp-info td").forEach((td) => {
      const label = td.querySelector("strong")?.textContent;
      const values = {
        "Regular No.:": p.ref,
        "Condeal Reference No.:": p.deal,
        "Client Name:": p.client || "?",
        "Business Name:": p.business,
      };
      if (values[label]) {
        const strong = td.querySelector("strong");
        td.replaceChildren(
          strong,
          document.createTextNode(" " + values[label]),
        );
      }
    });
    if (location.pathname.endsWith("/delivery-completion.html")) {
      const panel = document.createElement("div");
      panel.className = "card card-pad";
      panel.innerHTML = "<h3>Deliverable readiness</h3>";
      ORDO_STATE.deliverables.forEach((d, i) => {
        const label = document.createElement("label");
        label.className = "checkline";
        const box = document.createElement("input");
        box.type = "checkbox";
        box.checked = d.status === "Ready";
        box.disabled =
          !!ORDO_STATE.transmittal ||
          d.name === "Final RSAT Report" ||
          !ORDO_STATE.ntpApproved;
        box.onchange = () => {
          d.status = box.checked ? "Ready" : "Pending";
          ORDO.log("Deliverable updated: " + d.name);
          location.reload();
        };
        label.append(box, document.createTextNode(d.name));
        panel.append(label);
      });
      document.querySelector(".content").prepend(panel);
    }
    if (location.pathname.endsWith("/sow-report.html")) {
      const draft = ORDO_STATE.reportDraft || {};
      ["issues", "recommendations", "way"].forEach((id) => {
        const el = document.getElementById(id);
        el.value = draft[id] || "";
        el.addEventListener("input", () => {
          ORDO_STATE.reportDraft[id] = el.value;
          ORDO_STATE.deliverables.find(
            (d) => d.name === "Final RSAT Report",
          ).status = "Pending";
          ORDO.save();
        });
      });
      document.getElementById("items").addEventListener("change", (e) => {
        if (e.target.matches("input")) {
          const task = ORDO.findTask(e.target.dataset.task);
          if (task) {
            task.report = e.target.checked;
            ORDO.save();
            document.getElementById("refresh").click();
          }
        }
      });
      const old = document.getElementById("refresh").onclick;
      document.getElementById("refresh").onclick = () => {
        old();
        document
          .querySelectorAll("#items input")
          .forEach((el, i) => (el.dataset.index = i));
        const preview = document.getElementById("preview");
        if (preview) {
          preview.innerHTML = preview.innerHTML
            .replace(
              "Transfer of Share From Dany and Ronald to X10",
              ORDO.esc(p.title),
            )
            .replace("PROJ-2026-120", ORDO.esc(p.ref));
        }
      };
      document.getElementById("refresh").click();
      const approve = document.getElementById("approve");
      approve.onclick = () => {
        if (
          !ORDO_STATE.ntpApproved ||
          !ORDO.allTasks().length
        )
          return alert("Approve NTP and add report activities first.");
        if (!ORDO.allTasks().some((x) => x.task.report))
          return alert("Select report tasks in Execution first.");
        ORDO_STATE.deliverables.find(
          (d) => d.name === "Final RSAT Report",
        ).status = "Ready";
        ORDO_REGULAR.snapshotReport();
        const sentAt = new Date().toISOString();
        const project = ORDO_STATE.projects.find((item) => item.id === ORDO_PROJECT_ID) || {};
        ORDO_STATE.clientReportCommunications ||= [];
        ORDO_STATE.clientReportCommunications.unshift({
          id: `CLIENT-FINAL-REPORT-${ORDO_PROJECT_ID}-${Date.now()}`,
          type: "Final RSAT Report",
          taskTitle: "Approved Regular / RSAT Report",
          recipient: project.clientEmail || ORDO_STATE.clientContact?.email || "Client email on file",
          client: project.client || "Client",
          text: document.getElementById("way")?.value || "Final RSAT Report",
          files: [], sentAt, lastSentAt: sentAt, status: "Sent to Client", attempts: 1,
          proofId: `FINAL-REPORT-${ORDO_PROJECT_ID}-${Date.now()}`,
        });
        ORDO.log("Saved report approved");
        ORDO.save();
        document.getElementById("refresh")?.click();
        alert("Report approved and sent to the client email on file.");
      };
    }
    if (
      ORDO_STATE.transmittal &&
      location.pathname.endsWith("/sow-report.html")
    )
      document
        .querySelectorAll("input,textarea,#approve")
        .forEach((el) => (el.disabled = true));
    if (ORDO_STATE.closed) {
      document
        .querySelectorAll("button,input,textarea,select")
        .forEach((el) => (el.disabled = true));
    }
  } else {
    document.querySelectorAll(".project-submenu").forEach((menu) => {
      if (menu.querySelector('[data-portfolio-dashboard]')) return;
      const allProjects = [...menu.querySelectorAll("a")].find((link) => link.textContent.trim() === "All Regular Services");
      if (!allProjects) return;
      const dashboard = document.createElement("a");
      dashboard.dataset.portfolioDashboard = "true";
      dashboard.href = location.pathname.endsWith("/index.html") || !location.pathname.includes("/modules/") ? "modules/project-dashboard.html" : "../modules/project-dashboard.html";
      dashboard.textContent = "Regular Dashboard";
      if (location.pathname.endsWith("/modules/project-dashboard.html")) dashboard.className = "active";
      allProjects.before(dashboard);
    });
    document.querySelectorAll(".project-parent").forEach(
      (b) =>
        (b.onclick = () => {
          const menu = document.querySelector(".project-submenu");
          menu.classList.toggle("open");
          b.setAttribute("aria-expanded", menu.classList.contains("open"));
        }),
    );
    document
      .querySelectorAll(".nav-row,.child-row:not(.project-parent)")
      .forEach((b) => {
        b.disabled = true;
        b.title = "Outside the Regular module";
      });
    document.querySelectorAll(".kpi strong").forEach((el, i) => {
      const all = ORDO_STATE.projects;
      el.textContent =
        [
          all.length,
          all.filter((p) => p.stage === "Plan").length,
          all.filter((p) => p.stage === "Execution").length,
          all.filter((p) => p.stage !== "Completed").length,
          all.filter((p) => p.stage === "Completed").length,
        ][i] ?? el.textContent;
    });
    const create = [...document.querySelectorAll("button")].find(
      (b) => b.textContent.trim() === "Create Regular",
    );
    if (create) {
      const dialog = document.createElement("dialog");
      dialog.innerHTML = `<form><h2>Create project</h2>${[
        ["title", "Regular title"],
        ["business", "Company"],
        ["lead", "Owner"],
        ["workOrder", "Work order / Service memo"],
        ["target", "Target completion"],
      ]
        .map(
          ([name, label]) =>
            `<label>${label}<input required name="${name}" ${name === "target" ? 'type="date"' : ""}></label>`,
        )
        .join(
          "",
        )}<button class="btn primary">Create</button> <button type="button" class="btn" data-cancel>Cancel</button></form>`;
      document.body.append(dialog);
      create.onclick = () => dialog.showModal();
      dialog.querySelector("[data-cancel]").onclick = () => dialog.close();
      dialog.querySelector("form").onsubmit = (e) => {
        e.preventDefault();
        const input = Object.fromEntries(new FormData(e.target));
        const id = ORDO_ADD_PROJECT({
          ...input,
          deal: input.workOrder,
          client: "",
          handling: "0m",
          aht: "0m",
        });
        location.href = "workspace/scope-of-work.html?project=" + id;
      };
    }
    if (location.pathname.endsWith("/settings.html")) {
      const box = document.createElement("div");
      box.className = "card card-pad";
      box.innerHTML =
        '<h3>Personal preferences</h3><label>My name <input id="viewerName" class="field"></label><button class="btn" id="saveViewer">Save preferences</button>';
      document.querySelector(".main").append(box);
      box.querySelector("input").value = ORDO_DB.viewer;
      box.querySelector("button").onclick = () => {
        const name = box.querySelector("input").value.trim();
        if (!name) return;
        ORDO_DB.viewer = name;
        ORDO.save();
        alert("Preferences saved.");
      };
    }
    if (location.pathname.endsWith("/reports.html")) {
      const cards = document.getElementById("cards");
      cards.innerHTML =
        '<div class="card card-pad"><h2>Regular registry report</h2><p>Current project stages, owners and target dates.</p><div class="table-wrap"><table><thead><tr><th>Reference</th><th>Regular</th><th>Owner</th><th>Phase</th><th>Target</th></tr></thead><tbody>' +
        ORDO_STATE.projects
          .map(
            (p) =>
              "<tr>" +
              [p.ref, p.title, p.lead, p.stage, p.target]
                .map((v) => "<td>" + ORDO.esc(v) + "</td>")
                .join("") +
              "</tr>",
          )
          .join("") +
        '</tbody></table></div><button class="btn" id="exportRegistry">Download CSV</button></div>';
      document.getElementById("exportRegistry").onclick = () => {
        const cell = (v) =>
          '"' +
          String(/^[=+@\-\t\r]/.test(v) ? "'" + v : v).replaceAll('"', '""') +
          '"';
        const csv = [
          ["Reference", "Regular", "Owner", "Phase", "Target"],
          ...ORDO_STATE.projects.map((p) => [
            p.ref,
            p.title,
            p.lead,
            p.stage,
            p.target,
          ]),
        ]
          .map((r) => r.map(cell).join(","))
          .join("\r\n");
        const url = URL.createObjectURL(
          new Blob([csv], { type: "text/csv;charset=utf-8" }),
        );
        const a = document.createElement("a");
        a.href = url;
        a.download = "project-registry.csv";
        a.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
      };
    }
  }
  if (isWorkspace && ["/review.html", "/ntp.html"].some((page) => location.pathname.endsWith(page))) {
    const side = document.querySelector(".sidecard");
    side?.querySelector(":scope > .stage-management-panel")?.remove();
    side?.classList.remove("stage-management-only");
    side?.querySelectorAll(":scope > .side-section").forEach((section) => (section.hidden = false));
    const sections = side ? [...side.querySelectorAll(":scope > .side-section")] : [];
    const statusSection = sections.find((section) => section.querySelector("h4")?.textContent.trim() === "STAGE STATUS");
    const handlingSection = sections.find((section) => section.querySelector("h4")?.textContent.trim() === "STAGE HANDLING");
    if (side && statusSection && handlingSection) {
      handlingSection.before(statusSection);
      const isReview = location.pathname.endsWith("/review.html");
      const workOrderLocked = ORDO_STATE.workOrder?.status !== "Completed";
      const label = workOrderLocked
        ? "Locked"
        : isReview
          ? ORDO_STATE.sowWorkflow.status === "final" ? "Completed" : ORDO_STATE.sowWorkflow.revisionApprovalStatus === "pending" ? "Returned for Revision" : ORDO_STATE.sowWorkflow.status === "review" ? "For Review" : "Not Started"
          : ORDO_STATE.sowWorkflow.status !== "final" ? "Locked" : ORDO_STATE.ntpApproved ? "Completed" : "In Progress";
      let summary = statusSection.querySelector(".dedicated-stage-status");
      if (!summary) {
        summary = document.createElement("div");
        summary.className = "stage-status-value dedicated-stage-status";
        statusSection.querySelector("h4")?.after(summary);
      }
      summary.textContent = label;
      summary.className = `stage-status-value dedicated-stage-status ${label.toLowerCase().replaceAll(" ", "-")}`;
      const index = isReview ? 2 : 3;
      const record = ORDO_STATE.stageManagement.stages[index];
      const running = (base, started) => Number(base || 0) + (started ? Math.max(0, Math.floor((Date.now() - new Date(started).getTime()) / 1000)) : 0);
      const clock = (seconds) => { const value=Math.max(0,Math.floor(seconds||0)); return `${String(Math.floor(value/3600)).padStart(2,"0")}:${String(Math.floor((value%3600)/60)).padStart(2,"0")}:${String(value%60).padStart(2,"0")}`; };
      let total = handlingSection.querySelector(".dedicated-stage-total");
      if (!total) {
        total = document.createElement("div");
        total.className = "stage-total dedicated-stage-total";
        handlingSection.querySelector("h4")?.after(total);
      }
      total.innerHTML = `<span>Total Stage Handling Time</span><strong>${clock(running(record.inProgressSeconds, record.inProgressStartedAt) + running(record.onHoldSeconds, record.onHoldStartedAt))}</strong>`;
      if (label === "Locked") {
        side.querySelectorAll("button,input,textarea,select").forEach((control) => (control.disabled = true));
        const controls = sections.find((section) => section.querySelector("h4")?.textContent.trim() === "STAGE CONTROLS");
        if (controls && !controls.querySelector(".stage-lock-note")) {
          const note = document.createElement("div");
          note.className = "workflow-note stage-lock-note";
          note.textContent = workOrderLocked ? "Complete the Work Order before this stage becomes available." : "Complete and approve the RSAT before NTP becomes available.";
          controls.prepend(note);
        }
      }
    }
  }
  document.querySelectorAll("input[placeholder]").forEach((el) => {
    if (!el.labels?.length) el.setAttribute("aria-label", el.placeholder);
  });
})();
