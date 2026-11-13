# ORDO Transmittal V2 release candidate

This local Git release branch is based on production commit `b99ab0307aef1861812ca929657ac0329e4e0ac1`. It consolidates the approved V2 application source, V1 compatibility changes, corrected outbox FK migrations, and focused contracts. It is not pushed or deployed.

The approved source snapshot used for integration had manifest SHA-256 `37112C63AA4EBFDD545B1B10F3328C074507C46B17E93ED98389B057455754D6`. Its complete local code and package scope was reconciled against the intended `transmittal-v2-integration` worktree; no controller, route file, view, service, config, or test file was missing. The b99 `routes/web.php` production Deals routes are preserved.

All 14 reviewed V2 migrations are now in Laravel's standard `database/migrations/` directory. Their candidate originals remain under `patches/transmittal-v2/migrations/`; the unsafe historical synchronization migration is excluded. The four files in this directory document the proposed Forge additions, order, manifest, staging gates, and recovery limitations.

The synthetic attachment fixture discrepancy was a metadata error (42 declared bytes versus 40 fixture bytes), corrected in the disposable rehearsal only. It was not a production or application-file defect.

Local migration rehearsal and test results are recorded in the release conversation. Production and staging schemas, backups, and restore behavior remain unverified. Keep V2 and dispatch feature flags off until separately approved.