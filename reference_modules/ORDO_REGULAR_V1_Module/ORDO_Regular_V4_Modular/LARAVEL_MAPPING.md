# Regular module mapping

| Navigation | Retained static file | Laravel section |
|---|---|---|
| Regular Dashboard | workspace/project-dashboard.html | project-dashboard |
| Work Order | workspace/work-order.html | work-order |
| RSAT (Plan) | workspace/scope-of-work.html | scope-of-work |
| Review | workspace/review.html | review |
| NTP | workspace/ntp.html | ntp |
| Execution | workspace/execution.html | execution |
| RSAT Report | workspace/sow-report.html | sow-report |
| Delivery & Completion | workspace/delivery-completion.html | delivery-completion |
| Attachment | workspace/attachment.html | files-evidence |
| History | workspace/history.html | history-updates |

The lifecycle contains exactly Work Order, Plan, Review, NTP, Execution. Existing internal model, route and field names remain compatible.

Regular cycle state records the cycle number, reporting period, report snapshots and completed-cycle archives. Reports support unfinished activities. Cycle completion requires reporting and transmittal; a COC is not required. Next-cycle creation resets RSAT/NTP approvals and task execution state, while preserving the previous cycle snapshot.

The static prototype has richer assignment/acknowledgment and evidence UI. Laravel supplies local operator forms, server validation and SQLite persistence; it is not a pixel-identical replica. Host authentication, role authorization, actual email and external records integrations remain outside this local baseline.

Versioned planning, Review and NTP source copies include the Regular changes. The synchronization script retains their canonical filenames.
