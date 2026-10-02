ORDO REGULAR - MODULAR WORKSPACE
================================

Lifecycle: Work Order -> Plan -> Review -> NTP -> Execution.
Plan is the RSAT section. Reporting and delivery are supporting cycle records.

NAVIGATION
Regular Dashboard | Work Order | RSAT | Review | NTP | Execution |
RSAT Report | Delivery & Completion | Attachment | History

RUN
Static prototype: node scripts/serve.cjs -> http://127.0.0.1:8080
Laravel: laravel-app/START_ORDO.bat -> http://127.0.0.1:8000

REGULAR FUNCTIONS
- Work Order retains approval, assignment and acknowledgment controls in the prototype.
- RSAT planning adds activity frequency, reminder lead time, deadline and reporting period.
- Internal Review and client NTP approval are required before execution.
- Periodic RSAT reports include completed, ongoing and pending activities.
- Report snapshots are retained with cycle and period.
- Transmittal evidence completes the service cycle without closing the engagement.
- Start Next Cycle retains the previous cycle record, carries planning forward and requires fresh Review/NTP.
- Engagement closure/suspension are separate from cycle completion.
- A Certificate of Completion is not required by the Regular cycle workflow.

COMPATIBILITY
Existing filenames, project IDs, route names and internal sow keys are retained.
New Regular browser state uses ordoRegularV1; old ordoModularV5 data is copied on first use, not deleted.
Existing record references and sample service descriptions are retained.
Static browser data and Laravel database data remain separate.
The prototype records local demo actions; it does not actually send email.
Laravel remains a local integration baseline without authentication or role authorization.

FILES
index.html: Regular registry
workspace/: active screens
assets/js/regular-cycle.js: cycle state, report snapshots, archive and rollover
assets/js/regular-ui.js: period, RSAT schedule and archived-cycle UI
assets/js/delivery-completion.js: transmittal and cycle completion
laravel-app/: persistent Laravel implementation
.regular-backup/: original source files before the Regular adaptation

CHECKS
node tests/static-checks.cjs
cd laravel-app && composer test
