# Laravel workspace mapping

The latest supplied files supersede the older README. Preserve the V10 SOW, V2 Review, and V2 NTP source pages when extending the design.

| Source | Canonical static URL | Laravel section |
| --- | --- | --- |
| `workspace/_sources/scope-of-work-builder-v10-functional-modern-fixed.html` | `workspace/scope-of-work.html` | `/projects/{id}/scope-of-work` |
| `workspace/_sources/project-review-v2-percentage-lifecycle.html` | `workspace/review.html` | `/projects/{id}/review` |
| `workspace/_sources/project-ntp-v2-change-package-sop-functional.html` | `workspace/ntp.html` | `/projects/{id}/ntp` |
| `project-dashboard.html` | `workspace/project-dashboard.html` | `/projects/{id}` |
| Other canonical workspace pages | `workspace/{section}.html` | `/projects/{id}/{section}` |
| `index.html` and portfolio modules | `index.html`, `modules/*.html` | `/`, `/portfolio/{module}` |

Run `node scripts/sync-workspace.cjs` after changing the versioned source files to refresh the canonical copies. The `reference/` folder retains the earlier single-file mockups for comparison.

## Implementation

- `Project` is an Eloquent model with a JSON record payload. The migration creates its table; the seeder imports sample projects once and preserves existing edits.
- `ProjectController` loads the selected record, validates commands, handles uploads/downloads, preferences, and CSV exports.
- `ProjectWorkflow` owns lifecycle transitions, timer accounting, task state, and delivery gates within a database transaction.
- `projects/index.blade.php` renders the registry, filtered portfolios, export, preferences, and project creation.
- `projects/workspace.blade.php` provides the common workspace and working section forms.
- `projects/action.blade.php` provides CSRF-protected action forms.
- `layouts/app.blade.php` and `public/css/ordo.css` provide the shared layout.

## Feature translation

The Laravel forms support Within/Out of Scope text, five required reviewer acceptances with percentage progress, original/supplemental NTP with change-package reference, task timers, attachments, saved reports, client actions, deliverables, transmittal, COC, and closure. Laravel also provides a workstream outline-to-task builder and stored reviewer response timestamps. The original richer static layouts remain available for design comparison.

## Integration boundary

This repository does not include the host application's authentication, authorization policies, Task Manager V2 models, email delivery, or Records Management services. The local Laravel baseline records approval actions entered by its operator; it does not authenticate the named reviewer. Before integrating into a shared production system, replace that operator flow with the host identity/policy system, and route task timing and records through its existing services. The timer lock currently applies across this local application's projects.

Static browser storage and the Laravel database are separate. There is no automatic synchronization between them. Existing prototype data is retained in its old storage key; the new project-scoped prototype uses a versioned key.
