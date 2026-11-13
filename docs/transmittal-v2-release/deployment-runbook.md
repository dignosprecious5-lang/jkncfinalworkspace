# Staging and Production Release Procedure (Proposal)

## Release state

Production is **NO-GO** until staging exists, backup/restore is proven, target migration history is reconciled, and the operator separately approves production migrations and deployment. Keep all V2 and dispatch flags off during code deployment.

## Exact migration order

The proposed Forge fragment runs these 14 files individually, after the existing Forge migration commands and before build/release activation:

1. `2026_11_09_000001_add_transmittal_v2_columns.php`
2. `2026_11_09_000002_create_transmittal_recipients_table.php`
3. `2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php`
4. `2026_11_09_000004_create_transmittal_documents_table.php`
5. `2026_11_09_000005_create_proof_of_transmissions_table.php`
6. `2026_11_09_000006_create_approval_authority_codes_table.php`
7. `2026_11_09_000007_create_transmittal_histories_table.php`
8. `2026_11_09_000008_create_external_acknowledgments_table.php`
9. `2026_11_09_000009_create_transmittal_upload_sessions_table.php`
10. `2026_11_09_000010_create_transmittal_workspace_items_table.php`
11. `2026_11_11_000001_add_workflow_version_to_transmittals_table.php`
12. `2026_11_12_000001_create_transmittal_email_dispatches_table.php`
13. `2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php`
14. `2026_11_12_000003_create_transmittal_provider_events_table.php`

The fragment is `proposed-forge-v2-migration-additions.sh`. It contains one fail-fast command per file. It assumes these files have been integrated into `database/migrations/`; do not run it from this temporary package as-is. Confirm that none of these migrations were previously applied under another filename/source. Exclude the unsafe historical synchronization migration.

## Required backup and restore evidence

Before staging migration rehearsal, an authorized operator must:

1. Capture a consistent full database backup of the staging target, including schema and Laravel migration ledger. Record engine/version, capture time, backup identifier, and checksum.
2. Snapshot the exact shared storage backing the site, including V1 attachment paths and V2 private/public document locations. Preserve ownership, permissions, symlink target, and file metadata; record checksums for representative/all referenced files as practical.
3. Restore both database and storage together into an isolated staging rehearsal target. Do not restore over a live database or shared storage.
4. Verify V1 transmittal/item/receipt/attachment/acknowledgment/history counts and relationships, NULL workflow classifications, and file existence/checksums before and after migrations. Check unrelated module smoke tests.
5. Record successful application startup and recovery evidence. A backup without a successful isolated restore is not a verified backup.

Actual production database/storage backup locations, schedule, retention, and restore proof are not available in this local candidate.

## Environment and activation sequence

1. Verify Forge repository, release branch, deployed base commit, complete current script, existing migration ledger, PHP/MySQL versions, storage links, queue/cache/session and worker supervision on staging.
2. Stage the code and 14 migrations with flags off. Configuration defaults must remain: `ORDO_TRANSMITTAL_V2_ENABLED=false`, `ORDO_TRANSMITTAL_V2_DISPATCH_ENABLED=false`, `ORDO_TRANSMITTAL_V2_BREVO_OUTBOX_ENABLED=false`, `ORDO_TRANSMITTAL_V2_DISPATCH_PROVIDER=disabled`, OCR and Smart Upload false.
3. Run the additive migrations on staging only after the restored backup passes checks. Verify V1 and unrelated module behavior before activation.
4. With separate staging approval, enable V2 application routes while dispatch remains disabled. Test V1/V2 authorization, registration, documents, approvals, receipts, acknowledgments and PDFs.
5. Keep dispatch disabled until the separate environment-managed encryption key, recovery/rotation procedure, queue supervision, Brevo configuration, authenticated webhook secret and monitoring are verified. Then stage mock/provider-specific sending checks; any real staging email needs separate authorization. Never retry unknown outcomes automatically.
6. Production activation and production sends require separate explicit approval. Do not enable feature flags as part of an unreviewed code deployment.

## Verification checklist

- Confirm the active release commit and migration ledger before/after.
- Confirm 14 V2 migration rows and no unexpected historical migration/backfill.
- Confirm historical V1 rows retain values and NULL workflow classification.
- Confirm receipt numbers, acknowledgments, histories, quantities, attachment associations, storage paths, file hashes, and authorized downloads.
- Confirm V1 workflows and unrelated ORDO/Deals modules pass their approved smoke checks.
- Confirm V2 routes are inaccessible while the gate is off, then test only the approved staged activation.
- Confirm queue worker supervision/restart, failure monitoring, encrypted outbox handling, authenticated event checks, and bounded retry behavior; unknown outcome must not be resent automatically.
- Confirm production feature flags are still off unless separately approved.

## Rollback and recovery

These V2 evidence migrations intentionally refuse destructive `down()` rollback. Do not drop tables/columns, delete dispatch evidence, or run rollback/reset commands. If a migration or release fails, stop activation, capture logs and migration ledger, keep V2 flags off, and use the platform's reviewed previous-release activation procedure only after confirming compatibility. A code rollback leaves additive schema in place.

Restoring a database/storage backup can discard writes made after its capture. It requires an approved outage/write-freeze or an explicitly reviewed reconciliation plan, a successful restore rehearsal, and separate authorization. Never restore over production as an automatic rollback. Preserve all post-backup V1 receipts, acknowledgments, histories, attachments, and V2 evidence.

## Current access blockers

No staging site is identified; the only known Forge site is production. There is no authorized Forge dashboard/configuration access or production backup/restore evidence in this local environment. The actual production database migration ledger and shared storage paths have not been inspected. Production rollout therefore remains blocked.