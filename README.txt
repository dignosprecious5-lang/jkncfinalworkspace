ORDO PROJECT - MODULAR WORKSPACE
==============================

This folder contains the clickable static prototype and a Laravel implementation.
The files on disk, including the newer versioned pages, are the source of truth.
The previous README and mapping described an older version of the project.

STATIC PROTOTYPE
----------------
Run: node scripts/serve.cjs
Open: http://127.0.0.1:8080
You can also open index.html directly, but a local server gives consistent storage.

index.html                 Project registry and project creation
modules/for-me.html         Projects owned by the selected demo user
modules/all-projects.html   Complete registry
modules/reports.html        Registry report and CSV export
modules/settings.html       Project settings and policy reference

CURRENT WORKSPACE VERSIONS
--------------------------
workspace/scope-of-work.html
  Canonical copy of _sources/scope-of-work-builder-v10-functional-modern-fixed.html.
  V10 Within/Out of Scope builder, outline editing, planning timer and review flow.
workspace/review.html
  Canonical copy of _sources/project-review-v2-percentage-lifecycle.html.
  V2 reviewer acceptance percentages and lifecycle gate.
workspace/ntp.html
  Canonical copy of _sources/project-ntp-v2-change-package-sop-functional.html.
  V2 original/supplemental NTP, change packages and SOP context.

Other active pages: project-dashboard, work-order, execution, files-evidence,
sow-report, history-updates, delivery-completion.
All workspace links carry ?project=ID. Use canonical names for new links.
Run node scripts/sync-workspace.cjs after updating the versioned source pages.

STATE AND RULES
---------------
assets/js/mock-data.js supplies sample data.
assets/js/project-session.js stores separate state for each project.
assets/js/common.js supplies shared timers/tasks.
assets/js/workspace-fixes.js connects the common UI and completion controls.
State is stored in this browser. Reset Demo clears the current prototype dataset.
The static version is a demonstration; use Laravel for database persistence.

LARAVEL
-------
Read laravel-app/README.md and LARAVEL_MAPPING.md.
Run laravel-app/START_ORDO.bat. This checkout includes portable QA tools;
on another computer, install PHP 8.4+ and Composer 2 first.
The application uses SQLite by default, migrations, an idempotent seeder,
validated POST actions, CSRF protection, private uploads and server-side gates.
It is a local integration baseline, with no login/role system supplied yet.

CHECKS
------
node tests/static-checks.cjs
cd laravel-app
composer test

reference/ keeps the previous single-file designs for comparison.
