# Proposed Forge deployment sequence for Transmittal V2

**Status: NON-EXECUTABLE REVIEW DRAFT. Do not paste this document into Forge or run it.**

This is a guarded deployment sequence, not a replacement for the current Forge script. It uses only the supplied Forge release macros (`$CREATE_RELEASE()` and `$ACTIVATE_RELEASE()`) as named concepts. The exact Forge script syntax, shell commands, paths, and triggers have not been inspected. Do not implement this sequence until the local candidate is reconciled to deployed commit `b99ab03`, the complete current script is reviewed, and staging/backup/migration gates pass.

## Required gates before creating a release

1. Confirm the site/server/repository and deployment branch in the authorized target; capture the deployed commit and migration ledger. The local checkout currently lacks commit `b99ab03` and branch `release/deals-only-clean-20261109`.
2. Reconcile all production changes since baseline `3b3d99a` into a reviewed candidate without overwriting them. Verify the release artifact is immutable and contains no accidental V1 or unrelated-module changes.
3. Review the entire exact Forge script and every explicit migration command, trigger and side effect. Confirm whether each named migration is already recorded as applied. The production script names a company-activities drop migration and five Deals migrations; their exact deployed source and names are not all available locally. Do not run any pending item until its effect, source hash, data impact, backup and rehearsal are approved.
4. Confirm a consistent database backup and matching shared/private storage backup, restore both to an isolated target, validate representative V1 row relationships and file hashes, and rehearse the approved migration set against the exact MySQL 8.4 schema.
5. Keep `ORDO_TRANSMITTAL_V2_ENABLED=false`, `ORDO_TRANSMITTAL_V2_DISPATCH_ENABLED=false`, and `ORDO_TRANSMITTAL_V2_BREVO_OUTBOX_ENABLED=false`. Do not provision a live worker or send email as part of this deployment sequence.
6. Stop if commit, branch, migration history, schema, backup, storage, or script differs from reviewed evidence.

## Proposed ordering after all gates pass

1. Create a new release using the verified Forge `$CREATE_RELEASE()` mechanism.
2. Install PHP dependencies from the reviewed lock file and build frontend assets using the reviewed production build command. Never run `composer setup`, `key:generate`, project-data import, or `migrate:fresh` on the existing site. Preserve the current `APP_KEY`.
3. Run only the exact existing migrations proven pending, safe, and separately approved. Do not infer migration execution from the script's `--force` option. In particular, inspect the deployed source and ledger for `2026_09_04_135548_drop_company_activities_table.php`; the local baseline copy is a no-op that preserves rows, but this does not establish the source in commit `b99ab03` or its production application state. Review the five Deals-only commands by exact filename and source before running them.
4. Convert the reviewed V2 `.candidate` files into separately reviewed executable migrations only after schema rehearsal and migration approval. Apply them in dependency order below, before activating code that writes `workflow_version`. These 14 candidate files are not currently runnable Laravel migrations.

| Order | Candidate | Dependency/release gate |
|---:|---|---|
| 1 | `2026_11_09_000001_add_transmittal_v2_columns` | Existing `transmittals` and `users`; fail closed if overlapping columns exist. |
| 2 | `2026_11_09_000002_create_transmittal_recipients_table` | Candidate 1 and existing transmittals. |
| 3 | `2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table` | Candidate 2 and existing receipts; rehearse nullable unique semantics. |
| 4 | `2026_11_09_000004_create_transmittal_documents_table` | Existing transmittals/users. |
| 5 | `2026_11_09_000005_create_proof_of_transmissions_table` | Candidates 2 and 4. |
| 6 | `2026_11_09_000006_create_approval_authority_codes_table` | Existing transmittals/users. |
| 7 | `2026_11_09_000007_create_transmittal_histories_table` | Existing transmittals/users. |
| 8 | `2026_11_09_000008_create_external_acknowledgments_table` | Candidates 2–3 and existing transmittals/receipts. |
| 9 | `2026_11_09_000009_create_transmittal_upload_sessions_table` | Independent; confirm shared cache/session behavior. |
| 10 | `2026_11_09_000010_create_transmittal_workspace_items_table` | Candidate 4; preserves legacy `transmittal_items`. |
| 11 | `2026_11_11_000001_add_workflow_version_to_transmittals_table` | Add nullable classifier; no backfill. Must be applied before new code writes `v1`/`v2`. Historical NULL remains V1. |
| 12 | `2026_11_12_000001_create_transmittal_email_dispatches_table` | Candidate 11 and recipients; email-schema gate only. |
| 13 | `2026_11_12_000002_create_transmittal_email_dispatch_attempts_table` | Candidate 12; email-schema gate only. |
| 14 | `2026_11_12_000003_create_transmittal_provider_events_table` | Candidate 12; email-schema gate only. |

The sequence is not approval to execute. Candidates 12–14 are unnecessary for a manual-only V2 release; if omitted, keep dispatch, Brevo, and email-facing operations unavailable. The November 4 synchronization/backfill migration is excluded. Candidate rollback is preservation-oriented and may refuse to reverse; do not use migration rollback as data recovery.

5. Link the existing shared storage using the verified script operation. Do not replace or clear shared storage. Confirm attachment paths remain on shared storage across releases.
6. Clear and rebuild optimization caches using only the exact, reviewed commands. Confirm route/config caches preserve the default-off V2 and dispatch flags.
7. Activate the release with the verified `$ACTIVATE_RELEASE()` mechanism only after migration and build checks succeed. If a migration or check fails, stop before activation; do not improvise rollback or rerun.
8. Restart queue and Reverb only after confirming the impact on other ORDO modules, worker ownership, pending jobs, and recovery. Do not activate a V2 worker or scheduler in this sequence.
9. Verify release commit, health endpoint, V1 routes/permissions, representative V1 records and attachment hashes, and that all V2/dispatch flags remain off. Review redacted logs and cross-module queue/Reverb health.

## Recovery rule

If a check fails, stop and preserve the old release, shared storage, database and logs. Code-release rollback does not reverse schema changes, data writes, messages, files, acknowledgments, or worker effects. Do not restore over newer data or run `migrate:rollback` without a separate reviewed, approved recovery plan. No production action is authorized by this proposal.
