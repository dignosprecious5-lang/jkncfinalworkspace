# V2 Feature Completion Checklist

Use the same 100-point denominator for every development report. Each of the ten feature areas is weighted 10 points. Award points only when **all** acceptance evidence in that row is available and independently reviewed. File presence and source-only checks do not satisfy runtime, browser, database, or preservation criteria. Report partial implementation separately; do not assign fractional points.

| Feature area (10 points each) | Acceptance evidence required | Current evidence and status |
|---|---|---|
| V1 compatibility and data preservation | V1 regression evidence, schema diff review, attachment/receipt/history preservation | Pending. Phase 10 did not change V1 files; full regression and data-preservation evidence remains outstanding. |
| V2 primary workspace and lifecycle | Controller/view integration; owner, guest-token, and admin authorization tests | Inactive candidates prepared. Phase 9/10 source contracts pass; Laravel runtime authorization remains pending. |
| Upload and resumable transfer | Browser tests for chunk/retry/resume, limits, integrity, and incomplete-upload rejection | Implemented/prepared; browser and runtime verification pending. |
| OCR analysis | OCR tests, safe executable checks, and role/CSRF/rate-limit integration tests | Implemented; executable and HTTP integration verification pending. |
| Items and document relationships | MySQL migration/relationship tests and preservation tests against synthetic legacy schema | Blocked on a verified disposable MySQL instance. |
| Approval and authority workflow | Synthetic workflow tests for role, self-approval, rejection, history, and authority-code handling | Namespaced V2 authorization prepared; application runtime and MySQL verification pending. |
| External acknowledgment and receipts | Token expiry/revocation, duplicate/concurrency, unique receipt, and mail behavior tests | Implemented/prepared; MySQL concurrency and mail-sandbox verification pending. |
| Proofs and attachments | Private access, scoped document lookup, failed-save retention, and file-integrity tests | Source checks prepared; filesystem/browser/runtime verification pending. |
| Interface, assets, and accessibility | Vite build, browser flows, mobile layout, dependency fallback, and authorization UI tests | V2 helpers pass syntax checks; Vite build and browser/accessibility checks pending. |
| Security, release, and recovery | Full route/CSRF/security review, disposable MySQL regression, release/rollback evidence, and operator sign-off | 33-endpoint source-contract audit passed. Runtime, MySQL, release, and recovery checks remain pending. |

## Verified evidence recorded for Phase 9/10 (2026-11-10)

- Standalone PHPUnit source-contract suite: 11 tests, 380 assertions, exit code 0.
- PHP syntax checks: seven touched PHP files passed.
- JavaScript syntax checks: both V2-only helper files passed.
- Inactive route manifest: all 33 entries passed source-contract checks for controller-method references, `web`/CSRF coverage, authentication/authorization descriptions, and throttling on state-changing requests.
- V2-only namespaced authorization abilities are prepared. The shared `Transmittal` model has no V2 global policy mapping; the provider and routes remain unregistered. V1 authorization code was not changed.

## Verified evidence recorded for Phase 11 (2026-11-10)

- Local tools: Node.js `22.12.0`; npm `10.9.0`.
- Locked frontend tools: Vite `7.3.1`, Laravel Vite plugin `2.1.0`, and Tailwind Vite `4.2.1`.
- Locked frontend tools: Vite `7.3.1`, Laravel Vite plugin `2.1.0`, and Tailwind Vite `4.2.1`.
- Combined Phase 9/10/11 source-contract suite: 17 tests, 439 assertions, exit code 0.
- Phase 11 source-contract tests: 6 tests, 59 assertions, exit code 0.
- PHP lint passed for the new Phase 11 test (exit code 0).
- Both V2 JavaScript helper modules passed Node syntax checks (exit code 0).
- Browser and Laravel runtime verification remain pending. MySQL schema, transaction, and concurrency verification remain blocked on an isolated disposable MySQL environment.

These are source-only checks. They do not establish browser behavior, Laravel HTTP middleware/Gate runtime behavior, MySQL compatibility, concurrency behavior, or production readiness. No acceptance points are awarded for untested browser, runtime, or MySQL functionality.

## Verified evidence recorded for Phase 12 (2026-11-10)

- The Phase 12 runtime isolation plan requires a disposable VM/container with independently verified network isolation, no host credential or business-data mounts, and disabled UNC/SMB access. It explicitly prohibits using the existing MySQL96 service and requires a separate ephemeral MySQL instance before any MySQL verification. No such environment was started or used.
- Standalone Phase 12 source-contract suite: 4 tests, 36 assertions, exit code 0.
- Combined Phase 9/10/11/12 source-contract suite: 21 tests, 475 assertions, exit code 0.
- PHP lint passed for `tests/Unit/TransmittalV2Phase12ContractTest.php` (exit code 0).
- The Phase 12 source checks confirm the proposed frontend dependency pins are documented but absent from package manifests, optional PDF/ZIP functions fail clearly while unavailable, and the V2 view references only its isolated helper while routes/providers and active Vite inputs remain inactive/unchanged.
- These results are source-contract validation only. They do not establish Laravel runtime, browser, dependency build, MySQL, or production behavior. No feature acceptance points are awarded for those unverified areas.

## Verified evidence recorded for Phase 13/14 review (2026-11-10)

- The local integration checkout is on `transmittal-v2-integration` at `3b3d99ae57c565069a1aa0b8c22e361898bca039`. The worktree is dirty with modified tracked files and numerous untracked integration artifacts; this checkout is not a clean immutable release candidate.
- The documented production branch name `activities-production-release` has no local branch ref in this checkout. No current deployed commit or independently verified Forge inventory is available, so a complete candidate-to-production tree comparison remains pending. Server `jkandc`, site `ordo.jknc.io`, and the target/production branch names are user-provided, not independently verified in Forge.
- Phase 13 read-only checks found the WSL launcher, but not Docker, Podman, VirtualBox, VMware `vmrun`, Hyper-V `Get-VM`, `vmms`, or `vmcompute`. Windows virtualization-feature/hardware status and WSL distribution suitability remain unknown. No test environment was created.
- Official Forge documentation confirms that Forge supports separate sites, site-specific environment variables, repository branch selection, and site isolation. This establishes product capability only; it does not verify capacity, enabled isolation, databases, branches, triggers, or a staging site on `jkandc`. A same-host staging site would still share the server and service failure domain.
- No new acceptance points are awarded. Development progress remains unverified; browser/Laravel runtime checks, frontend dependency/build integration, MySQL verification, full V1 preservation evidence, and deployment/recovery review remain incomplete.

## Scoring and current percentage

Scoring rule: `10 points × number of feature areas with all acceptance evidence passed`, out of 100. No feature area currently has all required acceptance evidence, so **0/100 feature areas are fully verified**. This is a verified-acceptance score, not a measure of code already written. An overall development percentage is **unverified** because browser, Laravel runtime, isolated MySQL, and final V1 compatibility acceptance remain incomplete. The previously stated approximately 75% remains a user-provided provisional estimate only.

## Remaining cross-feature blockers

- No verified disposable MySQL environment is available.
- Browser/Vite integration has not been tested; active Vite inputs remain unchanged.
- `pdf-lib` and `fflate` are absent. PDF compression and ZIP extraction remain unavailable; package installation requires separate dependency review and approval.
- The V2 provider and routes remain inactive; do not activate them without separate approval and runtime/security verification.

## Verified evidence recorded for Phase 15 (2026-11-10)

- The local checkout is `transmittal-v2-integration` at `3b3d99ae57c565069a1aa0b8c22e361898bca039`, with modified tracked files and numerous untracked V2/OCR/attachment artifacts. The `activities-production-release` branch ref and current deployed commit are unavailable locally, so a complete candidate-to-production comparison remains pending.
- Inventory confirms three namespaced V2 controllers, five V2 services, isolated V2 Blade/email views, ten `.php.candidate` migration files outside normal migration discovery, and a 33-entry inactive route manifest. Prior Phase 9–12 source-contract evidence remains 21 tests, 475 assertions, exit code 0; no runtime/database tests were run in Phase 15.
- The Smart Upload Blade view references `resources/js/transmittal-v2-file-tools.js` through `@vite`, but active `vite.config.js` still includes only `resources/css/app.css` and `resources/js/app.js`. V2 asset input/build integration is required before rendering that view.
- `pdf-lib` and `fflate` remain absent; PDF compression and ZIP extraction are unavailable pending separate dependency approval.
- Inspected V2 upload validation applies a 50-file default independently to multiple upload arrays and a 52,428,800-byte per-file default. A unified 50-file / 104,857,600-byte combined request limit across categories and nested item attachments was not verified and must be reconciled before activation.
- The V2 route manifest and authorization provider remain inactive. The staged controller candidate and autoloadable namespaced V2 controller are both present; their provenance and exact correspondence require reconciliation before release assembly. Shared Transmittal models are modified and require full V1 regression/data-preservation evidence.
- No acceptance points are added. Laravel/browser runtime behavior, MySQL migration/receipt concurrency, V1 preservation, and backup/restore/rollback remain unverified or blocked.

## Verified evidence recorded for Phase 16B (2026-11-10)

- Scope: local integration worktree `jknc_transmittal_integration`, branch `transmittal-v2-integration`. No production, Forge, database, migration, dependency installation, activation, commit, reset, or destructive action was performed.
- The installed Laravel validation exception was located at `vendor/laravel/framework/src/Illuminate/Validation/ValidationException.php`. No dependency files were modified.
- Added the reusable V2-only `TransmittalUploadBudget`: maximum 50 files, 104,857,600 combined bytes, and 52,428,800 bytes per file. Limits use actual temporary-file sizes and include nested/category file arrays and verified assembled resumable files. Repeated references to one temporary path count once; independent uploads with identical names/content remain separate. Chunk requests are not submission-budget entries.
- Budget validation precedes create/update transactions, Smart Upload claims/consumption and record writes, proof storage, and OCR analysis. Resumable start/completion also enforce the per-file ceiling. Validation failures in the budget occur before permanent storage or business-record writes; existing temporary resumable files remain available.
- Added 17 standalone tests covering 49/50/51 files; exact and one-byte-over combined and individual byte limits; category/nested combinations; direct plus assembled resumable files; duplicate-reference prevention; source-order checks before persistence; and V1/activation-file preservation. Boundary sizes use test doubles, not large generated uploads.
- Initial standalone run: 17 tests, 83 assertions, exit code 0, with PHPUnit notices about size-only mock objects. Those test doubles were changed to stubs. Final combined Phase 9/10/11/12/16B run: **38 tests, 558 assertions, exit code 0**, with no notices or warnings.
- Test command: `php vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap vendor/autoload.php --do-not-cache-result --display-phpunit-notices tests/Unit/TransmittalV2Phase9ContractTest.php tests/Unit/TransmittalV2Phase10ContractTest.php tests/Unit/TransmittalV2Phase11ContractTest.php tests/Unit/TransmittalV2Phase12ContractTest.php tests/Unit/TransmittalV2UploadBudgetTest.php`. This loads standalone components only; it does not bootstrap the Laravel application or use the database isolation launcher.
- PHP syntax checks passed for all five changed/new PHP files (each exit code 0); the final test-file revision also passed lint (exit code 0).
- SHA-256 preservation assertions passed for ten pre-change files: the V1 controller and view; shared Transmittal, TransmittalItem, and TransmittalReceipt models; active routes, providers, and Vite configuration; shared OCR service; and staged controller. Existing dirty V1/shared work was preserved, not reverted. The autoloadable V2 controller retains its feature gate and namespaced authorization protections.
- Source/standalone evidence does not establish Laravel HTTP behavior, filesystem/transaction rollback behavior, concurrent resumable import safety, browser behavior, or V1 runtime/data preservation. Those checks remain pending or blocked. No feature acceptance points are awarded.

### Production Deployment Readiness Tracker after Phase 16B

| Status | Evidence or remaining work |
|---|---|
| Verified | Prior Phase 9-12 source-only validation, Phase 15 checklist, Phase 16 controller comparison; Phase 16B unified V2 upload-budget implementation, standalone boundary/source tests, PHP lint, and recorded V1/activation-file hash preservation. |
| Pending | Isolated runtime acceptance of upload-budget rejection/persistence behavior; PDF/ZIP dependencies; Vite integration; production baseline comparison; backup/restore/rollback verification; final V1 runtime/data-preservation evidence. |
| Blocked | Isolated Laravel/browser/MySQL runtime testing until the approved isolation environment is available. |
| Overall | **NOT READY**. No deployment or next development phase authorized. |

## Verified evidence recorded for Phase 18 (2026-11-10)

- Local scope: `jknc_transmittal_integration`, branch `transmittal-v2-integration`. This phase changes only the V2 Smart Upload queue module, V2 file-tools bridge, V2 Smart Upload Blade view, its new standalone frontend tests, and this checklist.
- Added `resources/js/transmittal-v2-upload-queue.js` with stable per-File identities, serial asynchronous additions, atomic batch admission, and fixed ceilings of 50 files / 104,857,600 combined bytes / 52,428,800 bytes per file. Separate File objects with identical names, sizes, and timestamps remain distinct; repeated references to one File object are counted once.
- Smart Upload cancels native submission synchronously, waits for pending additions and OCR, validates the complete queue, prepares resumable files, verifies the queue snapshot, and only then constructs the final submission. Direct files and resumable IDs are mutually exclusive; categories/OCR tokens and item document indices follow the backend direct-first/resumable-second ordering. Failed validation/preparation does not submit a valid subset. Controls, generated inputs, and remapped indices are restored on preparation/commit failure.
- OCR requests carry stable file/request tickets. Removed/replaced files and superseded requests cannot receive results; manual category changes are preserved. Resumable cache keys now include queue identity to avoid collisions between same-metadata files; cross-page resume behavior remains subject to isolated acceptance testing.
- Browser selection, drag/drop, camera admission, and the future ZIP-admission hook await asynchronous additions before success. ZIP extraction and PDF compression remain explicitly unavailable; no PDF/ZIP packages or extraction/compression implementation were added.
- Reused the previously prepared 29 frontend tests. The pre-write ES-module checks used `node --input-type=module --check` with source through stdin; all three JS files passed. Inline Blade JavaScript compiled with `vm.Script`. The prepared changes passed a no-project-write dry run before application, and final applied contents match the validated plan.
- Initial `node --test tests/Frontend/transmittal-v2-smart-upload.test.js` launch in the filesystem sandbox exited 1 with EPERM while resolving the parent directory, before the 29 test bodies ran. The permission-approved retry outside the sandbox passed **29/29 tests, exit code 0**. These execute synthetic queue data and the actual Blade submit handler with synthetic DOM objects; no browser, app, real network, or database is used.
- Existing Phase 9/10/11/12 and 16B standalone PHP suite rerun: **38 tests, 558 assertions, exit code 0**, no notices/warnings. Command: `php vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap vendor/autoload.php --do-not-cache-result --display-phpunit-notices tests/Unit/TransmittalV2Phase9ContractTest.php tests/Unit/TransmittalV2Phase10ContractTest.php tests/Unit/TransmittalV2Phase11ContractTest.php tests/Unit/TransmittalV2Phase12ContractTest.php tests/Unit/TransmittalV2UploadBudgetTest.php`.
- Final module syntax checks: three files, each exit code 0. Inline-script syntax and preservation/plan verification command: exit code 0.
- SHA-256 preservation checks passed for 15 files: V1 controller/view, shared app.js and workspace transmittal.js, routes, providers, Vite config, package manifests/lockfile, V2 controller and resumable service, existing image-compression helper, and three shared Transmittal models. Tests also verify the Document Management handler is unchanged and the resumable protocol body is unchanged apart from the identity-based cache key. TXT/resumable policy and workspace persistence were not changed.
- No packages were installed; no routes/providers/Vite inputs were activated; no Laravel bootstrap, database/migration, Forge/production, commit/reset, or destructive action occurred. Existing modified/untracked work was preserved.
- Source-only evidence does not establish real-browser FileList/DataTransfer behavior, network/session expiry and reload behavior, filesystem/database persistence, or V1 runtime compatibility. Browser/Laravel/MySQL acceptance remains blocked on the approved isolation environment. No additional feature acceptance points are awarded.

### Production Deployment Readiness Tracker after Phase 18

| Status | Evidence or remaining work |
|---|---|
| Verified | Phases 9-12 source validation; Phase 15 checklist; Phase 16 controller comparison; Phase 16B backend upload-budget implementation; Phase 17 assessment; Phase 18 Smart Upload source implementation, 29 passing frontend tests, 38 passing PHP tests / 558 assertions, syntax checks and preservation evidence. |
| Pending | PDF/ZIP dependency and functionality approvals; Vite installation/build/input integration; separate decisions on Document Management, TXT/resumable policy, and workspace resumable persistence; production baseline comparison; runtime acceptance; V1 runtime preservation; backup/restore/rollback verification. |
| Blocked | Isolated Laravel/browser/MySQL runtime testing until the approved isolation environment is verified. |
| Overall | **NOT READY**. Phase 19 and deployment have not been started or authorized. |

## Verified evidence recorded for Phase 19B source verification (2026-11-10)

- **Phase 19B dependency source verification is complete. Security acceptance remains pending.** This entry records previously completed commands; the checklist-finalization task does not rerun tests or start application tooling.
- Verified integration workspace: `C:\Users\Admin\Documents\original\jknc_transmittal_integration`; branch: `transmittal-v2-integration`.
- Frontend standalone suites: **35 tests passed, 0 failed, exit code 0** (29 existing Smart Upload tests plus six dependency tests). Node does not report an aggregate assertion count.
- PHP Phase 9-12 and 16B source-only suites: **38 tests, 566 assertions passed, exit code 0**, with no notices or warnings.
- Module-aware JavaScript syntax checks passed for all three changed/new JS test/helper files. PHP syntax checks passed for all three changed PHP contract files. Each check exited 0.
- `@zip.js/zip.js@2.24.0` remains installed. Its exact version, registry integrity, and default/native ES-module imports were verified without constructing an archive reader or extracting files. No dependencies were changed during source verification or checklist finalization.
- All **162 existing non-root dependency records** were preserved, including versions, integrity values, resolution URLs, and metadata. The immutable pre-install fixture retains the original state and permits only the exact approved zip.js declaration/record and the explicitly approved lockfile-name exception.
- Exact metadata exception: top-level `package-lock.json.name` may change only from `jknc_project` to `jknc_transmittal_integration`, matching the integration folder. `package.json` has no name field in either snapshot and was not renamed. No other manifest or lockfile fields are exempted.
- All **16 protected-file SHA-256 checks passed**. Existing package manifests were unchanged during the source-verification batch. Frontend contracts retain the Admin/V1-related route/controller/view protections and the excluded Document Management/resumable-policy checks.
- The saved audit's **11 existing flagged package entries remain unresolved**: 2 critical, 7 high, 1 moderate, 1 low. zip.js was not listed in that saved report; this is not a guarantee of vulnerability freedom. No security remediation, audit fix, or new security clearance is recorded.
- ZIP extraction and PDF compression remain explicitly disabled; fflate and pdf-lib remain absent. Active Vite inputs, routes, and providers were not changed.
- The existing Admin Transmittal Dashboard at `/admin/transmittal-dashboard` was not modified: no changes to its routes, controller, views, permissions, approval logic, or database operations were made. V1 and other ORDO modules were not modified.
- Checklist finalization is append-only. The pre-existing checklist prefix is preserved byte-for-byte; all other Git-listed tracked/untracked file contents are checked against hashes captured immediately before this append. Existing modified/untracked work is preserved.
- No Vite/concurrently/development server, Laravel runtime, database service, migration, Forge/production access, commit, reset, or deployment was started. Browser/Laravel/MySQL acceptance and full V1 runtime verification remain pending or blocked.

### Production Deployment Readiness Tracker after Phase 19B source verification

| Status | Evidence or remaining work |
|---|---|
| Verified | Prior phases; zip.js 2.24.0 installation/import; strict immutable baseline and exact name exception; 35 frontend tests; 38 PHP tests / 566 assertions; syntax checks; 162 original dependency records and 16 protected-file hashes; checklist evidence finalized. |
| Pending | Separately approved security remediation and fresh audit; bounded ZIP extraction; PDF safety decision; Vite integration/build; excluded workflow decisions; runtime acceptance; production baseline comparison; V1 runtime preservation; backup/restore/rollback verification. |
| Blocked | Security audit acceptance; isolated Laravel/browser/MySQL testing until an approved environment is verified. |
| Overall | **NOT READY**. Source verification is not security clearance. No security remediation, next implementation phase, or deployment has started. |


## STEP 184 — V1/V2 workflow separation (2026-11-12)

Scope: local source repair only, production baseline 3b3d99ae57c565069a1aa0b8c22e361898bca039. No routes/provider/feature activation, schema or migration changes, database connections, Laravel bootstrap, production/Forge access, build, deployment, commit or push.

- Added explicit TransmittalWorkflowBoundary queries. V2 requires exact v2; V1 groups historical NULL and v1. Legacy reads retain a metadata-checked fallback before the existing classification migration; this does not resolve the pre-existing classified-creation schema prerequisite. No global model scope or historical reclassification is introduced.
- V2 parent register/admin/visibility, duplicate matching, typed record actions, token lookups and locked reloads are scoped. Existing owner, session-token, role and Gate checks remain; checks are repeated on locked parents in mutation callbacks. V2 navigation remains in the plural register.
- V1 controller queries, legacy receipt lookup and only the Transmittal resolver/reload in CorporateApprovalController exclude V2. Other corporate module branches are unchanged.
- Approval, workspace-item and Brevo operations require V2 parents. Seven V2-only child models now require V2 parents on model saves/deletes, disallow parent reassignment, and validate linked document/recipient/receipt parent identity. Query-builder/bulk SQL and privileged external writes bypass model events; this is not a database constraint or complete concurrency guarantee. Shared V1 parent/item/receipt model files are unchanged in this step.
- Added tests/Static/transmittal-workflow-boundaries.php: standalone source parsing only, no Composer/Laravel bootstrap, database, storage or network operations. Initial execution: 90 source-only checks passed, exit 0. Added synthetic policy cases for NULL/v1/invalid/forged/unsaved parents; PHPUnit policy tests were not run.
- Existing dependency fixtures, prior checklist content, routes, provider registration, Vite config, dependencies, migration sources, and unrelated existing dirty work are preserved. Final syntax and preservation evidence is recorded below after verification.

### Production Deployment Readiness Tracker after STEP 184

| Status | Evidence or remaining work |
|---|---|
| Verified — source only | Explicit workflow predicates, scoped V2 parent/child lookups and locked reloads, V1 NULL/v1 filters, namespaced policy checks and V2 child write guards; 90 standalone source checks passed. |
| Pending | Isolated HTTP, model-event/relationship, V1 preservation, transaction/concurrency and production-engine acceptance; updated synthetic fixtures and regression contracts where classification is now mandatory. No functional acceptance is claimed. |
| Blocked | Existing workspaceItems integration, unsupported soft-delete/lifecycle calls, missing role/manager schema prerequisites, OCR contract, send/database uncertainty, PDF/dependency/security acceptance, Vite build/browser acceptance, actual production baseline/script/ledger evidence and tested backup/recovery. |
| Overall | **NOT READY**. V2 remains inactive. STEP 184 does not authorize the next repair group or deployment. |

Final STEP 184 verification: PHP syntax checks passed for all 21 changed/new PHP files (exit 0); the 90 source-only boundary checks passed again (exit 0); git diff --check passed (exit 0). Of the 141 pre-step file hashes captured, 123 remain identical and the other 18 are the intended existing-file edits; CorporateApprovalController was separately captured before editing and its diff is limited to the Transmittal import/resolver/reload. Three source/checker files were added. The complete prior tracker prefix is preserved byte-for-byte. Historical dependency fixtures, shared Transmittal/TransmittalItem/TransmittalReceipt models, routes/provider/Vite configuration, dependency files and migration sources were not changed by STEP 184. No PHPUnit, Laravel HTTP, browser, engine, transaction or concurrency acceptance ran.


## STEP 185 — V2 workspace-item integration (2026-11-12)

Scope: only V2 workspace-item integration on the preserved STEP 184 worktree. No migration/schema changes, V2 route/provider activation, database tests/connections, production/Forge access, dependency installation, build, deployment, merge, commit or push.

- V2 controller create, Smart Upload create and update now delegate item persistence through an explicit adapter to TransmittalWorkspaceItemSyncService. All V2 item relationships, eager loads, snapshots, forms, counts, payloads, private attachment references and PDF consumers use workspaceItems. Paginator items() remains the paginator API. The old filename/position-based legacy preview association helper was removed; no historical rows or files were backfilled or moved.
- Missing items returns without replacing the list; null/malformed items is rejected before controller transaction entry and by the sync service. Only an explicit array replaces the editable V2 list; explicit [] clears that list. New Smart Upload drafts generate default workspace rows only when the items key is omitted. Invalid/missing document indices and conflicting references fail rather than falling back to another file.
- Item/document IDs are checked against the current V2 parent before per-item file storage and rechecked by the locked sync service. Sparse upload field indices remain mapped to their submitted rows. Cancelled/unpersisted model saves abort before omission deletion. Legacy transmittal_items is never used by V2 item persistence.
- Quantity remains bounded text, including zero, decimal and unit-bearing values; V2 forms use text quantity inputs. Payloads carry workspace IDs, document IDs, line_number and quantity. Saved rows survive malformed old form input. Approval snapshots record ordered workspace items and their document identity; approved item display takes precedence over old form input. Existing frozen snapshots are not rewritten.
- Existing evidence/approval guards run before V2 update writes and again in item synchronization. Direct workspace-item model saves/deletes now also check the V2 parent and retained evidence. Approval validates workspace/document ownership under its parent lock and refuses replacement of frozen approval evidence. Model events do not constrain raw query-builder/SQL bypasses and are not a database constraint or proof of concurrency safety.
- V2 preview/PDF/email item rendering no longer calls V1 item templates. The V2 PDF template supplies the receipt package through V2 partials. Private document associations replace legacy item attachment_path use. PDF merger dependencies, end-to-end attachment completeness, rendering and sending remain separate unresolved acceptance gates.

Verification performed in STEP 185:
- PHP lint passed for five changed/new PHP source/checker files, each exit 0.
- Standalone source checker passed: 66 workspace-item checks plus the unchanged 90 STEP 184 checks, 156 total, exit 0. It also parses embedded PHP blocks in all seven changed V2 Blade files without booting Blade/Laravel. An initial checker expectation had a quoting typo and was corrected before the final passing run.
- JavaScript syntax check for resources/js/transmittal.js passed, exit 0. The first sandbox attempt failed on workspace directory resolution before parsing; the separately permission-approved syntax-only retry outside the sandbox succeeded.
- git diff --check passed, exit 0. No PHPUnit application tests, browser tests, PDF rendering, database tests, migrations or production checks ran. Runtime/data-preservation acceptance is not claimed.

### Production Deployment Readiness Tracker after STEP 185

| Status | Evidence or remaining work |
|---|---|
| Verified — source only | STEP 184 boundaries remain checked; V2 workspace-item service integration, ownership and omission/null safeguards, textual quantities, frozen evidence guards, V2 form/count/export consumers; 156 source checks, PHP lint, JS syntax and tracked diff checks passed. |
| Pending | Isolated V1/V2 HTTP/model-event, workspace persistence/replacement, rollback/concurrency, quantity and attachment preservation, actual database-engine/schema acceptance, browser forms and rendered PDF acceptance. Historical dependency fixtures remain unchanged; earlier database/synthetic fixtures require review for the STEP 184 classification prerequisites before any execution. |
| Blocked | Existing schema/role/manager prerequisites; unsupported lifecycle/soft-delete functionality; OCR contract; send/database uncertainty and attachment completeness; PDF/dependency/security acceptance; Vite build; verified production baseline/full Forge script/migration ledger and rehearsed backup/recovery. |
| Overall | **NOT READY**. V2 remains inactive. STOP after STEP 185; no next repair group or deployment is authorized by this record. |

Preservation verification: of 145 captured pre-STEP-185 file hashes, 132 are identical and exactly the intended 13 existing files changed; only tests/Static/transmittal-workspace-items.php was added. All other STEP 184 files, V1 controllers/models/views, historical dependency fixtures, migration candidates, dependency and activation files remain unchanged. The complete prior readiness-tracker prefix is preserved byte-for-byte.


## STEP 186 — Minimum V2 lifecycle compatibility (2026-11-12)

Scope: exclude unsupported parent-record deletion/restoration from the minimum V2 release only. No files, database rows or attachments were deleted. No V1 lifecycle, shared model, schema/migration, unrelated module, dependency, Vite, active route/provider, feature flag or JavaScript changes. No database/application bootstrap, production/Forge access, merge, commit, push or deployment.

- Removed the V2 destroy, restore and forceDelete action implementations from the current inactive controller; no unsupported onlyTrashed/withTrashed/trashed calls, lifecycle-archive writes or permanent file cleanup remain in it. The register whitelist excludes deleted; its deletedCount and deleted-register query are removed. Unsupported filters are rejected by validation rather than reaching an unsupported query.
- Removed the V2 delete form and obsolete deleted-filter/display conditions. Cancellation/Archived remains a business status, not soft deletion. Existing draft queue/file removal actions in JavaScript are unrelated to parent deletion and remain unchanged.
- Excluded transmittals.destroy, transmittals.restore and transmittals.force-delete from the proposed route array. It now contains 30 endpoints, stays inactive, and explicitly records the exclusions. The minimum source points to the repaired app controller; staged historical implementation is reference-only and preserved unchanged. No historical implementation file was deleted.
- Verified 11 retained controller sections unchanged: cancel, recall, approve, reject, submit, submitForApproval, transmit, revokeRecipientLink, canManageLifecycle, canEditDraft and syncItems. Of the 30 retained endpoint entries, 29 are identical; index changes only its obsolete deleted-filter description. Supported endpoint authentication, authorization and middleware remain intact.
- Updated four source-contract test files for the 30-endpoint proposal and explicit deletion exclusions. The STEP 184 source checker now asserts unsupported handlers are absent: 89 checks replace the previous 90 because four deletion-specific positive checks became three exclusion checks. All remaining workflow guards are retained. The STEP 185 checker and its 66 checks are unchanged.

Final static verification:
- PHP lint passed on all seven changed/new PHP files (exit 0); focused checker execution also parses the final PHP source. No PHPUnit application/HTTP tests were run.
- tests/Static/transmittal-lifecycle-exclusions.php passed: 270 lifecycle checks plus 89 STEP 184 workflow checks and 66 STEP 185 workspace checks = 425 source-only checks, exit 0. It checks all 30 proposed routes, active V2 source/assets for unsupported lifecycle calls, retained transitions, and embedded PHP/conditional pairs in changed views. Two initial checker assumptions about removed actions/control placement were corrected before the final passing run.
- git diff --check passed (exit 0). No JavaScript changed, so no new JS syntax execution was required.
- This is source-level exclusion/preservation evidence only. HTTP routing, rendered UI/PDFs, real database/model events/locking/concurrency, data preservation and production operation remain unverified.

### Production Deployment Readiness Tracker after STEP 186

| Status | Evidence or remaining work |
|---|---|
| Verified — source only | STEPS 184/185 workflow and workspace protections retained; unsupported V2 parent deletion/restoration/archive/query/UI/routes excluded; 30 proposed endpoints remain inactive; 425 source checks, PHP lint and diff checks passed. |
| Resolved for minimum scope | Unsupported SoftDeletes/lifecycle-archive dependency removed from minimum runtime workflow. Historical implementation and candidate migrations remain preserved; their presence does not approve future lifecycle activation or schema changes. |
| Pending | Isolated HTTP/register/filter/route and rendered-UI acceptance; V1/V2 business-transition and workspace/model-event regression; real-engine migration/transaction/concurrency and record/file preservation; browser/PDF verification. |
| Blocked | Existing classification/V2 schema and role/manager prerequisites; OCR contract; send/database uncertainty and attachment completeness; PDF/dependency/security acceptance; Vite build; independently verified production baseline, full Forge script, migration ledger, consistent backups and rehearsed lossless recovery. |
| Overall | **NOT READY**. V2 remains inactive. STOP after STEP 186; no subsequent repair or production action is authorized by this record. |

Preservation verification: 146 pre-step hashes captured; 136 files remain byte-identical and exactly the intended 10 existing files changed. Only tests/Static/transmittal-lifecycle-exclusions.php was added. V1/shared models/controllers/views, STEPS 184/185 helper/service/model repairs and workspace checker, JavaScript, historical staged implementation, dependency fixtures, migration candidates, dependencies and activation files remain unchanged except the explicitly listed lifecycle controller/test adjustments. The complete prior tracker prefix is preserved byte-for-byte.


## STEP 187 — V2 schema and role compatibility (2026-11-12)

Scope: reconcile the ten non-executable V2 schema candidates and missing role/signatory prerequisites. No migration was run, candidate was not moved into Laravel's migration directory, no routes/providers were activated, and no database, production or Forge access occurred.

- `operations_manager` is a V2 document signatory name selected or entered in the separate Operations Manager form field and included in approval snapshots. It is distinct from `approved_by` / `approved_at` system approval audit values and from the existing V1 `approved_by_name` / `approved_position` written-signatory fields. The minimum column candidate now adds only nullable `string(160) operations_manager`; it does not replace, copy into, or backfill the V1 fields. The first candidate's unsupported `deleted_at` field was removed to match STEP 186's minimum lifecycle exclusions.
- V2 no longer reads `users.transmittal_role`. Existing `User::isAdmin()` and `isSuperAdmin()` support approval checks. The signatory selector reads only `id` and `name`. Existing `access_transmittal` role/user permission fields remain available for module access; no roles are assigned or privileges broadened. No users schema candidate is required. A focused PHPUnit contract fixture was updated, but PHPUnit was not run.
- The candidates are still `.php.candidate` files outside Laravel migration discovery. They perform schema additions/table creation only, abort on existing-column/table conflicts, retain data in `down()`, and contain no historical updates, deletes, file operations, or cascading/nullifying foreign keys. Foreign keys use `restrictOnDelete`.

### V2 candidate dependency and preservation matrix

| Candidate | Schema effects / prerequisites | Ordering and preservation notes | Minimum V2 |
|---|---|---|---|
| `2026_11_09_000001_add_transmittal_v2_columns.php.candidate` | Nullable V2 parent metadata, status, rejection/cancellation, manager name, processing/confidentiality, approval snapshot/code and token hash; user FKs for preparer/rejector/canceller | Existing `transmittals` and `users`; aborts on any conflicting column; no defaults/backfill; no lifecycle `deleted_at`; rollback retains columns | Yes, before child tables |
| `2026_11_09_000002_create_transmittal_recipients_table.php.candidate` | Recipient names/emails, unique nullable token hash, expiry/revocation/send timestamps; transmittal FK | After parent columns; table collision aborts; restrictive FK; rollback retains rows | Yes |
| `2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php.candidate` | Nullable unique recipient association, signed name/comments/code/snapshot/IP/user-agent | Requires existing receipt table and recipients; V1 `receipt_no` unique index and historical receipt columns untouched; nullable unique semantics require target-engine verification | Yes |
| `2026_11_09_000004_create_transmittal_documents_table.php.candidate` | V2 document metadata, unique storage path, nullable uploader/OCR fields; transmittal/user FKs | Requires existing transmittals/users; metadata only, no stored-file operations; rollback retains rows/files | Yes |
| `2026_11_09_000005_create_proof_of_transmissions_table.php.candidate` | Transmission evidence, nullable recipient/document/sender links, provider metadata | After recipients and documents; restrictive FKs preserve evidence; rollback retains rows | Yes for proof workflow |
| `2026_11_09_000006_create_approval_authority_codes_table.php.candidate` | One authority row per transmittal, unique code, approver, frozen snapshot/hash/time | Requires transmittals/users; unique parent and code constraints; rollback retains frozen evidence | Yes for approval |
| `2026_11_09_000007_create_transmittal_histories_table.php.candidate` | Audit action, description, nullable actor, JSON metadata | Requires transmittals/users; parent/time index; rollback retains history | Yes |
| `2026_11_09_000008_create_external_acknowledgments_table.php.candidate` | Acknowledgment events and optional receipt association | After recipients and existing receipt table; restrictive FKs and recipient/time index; rollback retains evidence | Yes for external acknowledgments |
| `2026_11_09_000009_create_transmittal_upload_sessions_table.php.candidate` | UUID/token/session hashes, chunk metadata, expiry and upload status | Independent of transmittal rows; unique upload UUID and supporting indexes; rollback retains session/file evidence | Only if resumable upload is included; confirm minimum feature scope |
| `2026_11_09_000010_create_transmittal_workspace_items_table.php.candidate` | Separate workspace rows, nullable document association, order/line numbers and textual quantity | After documents; restrictive parent/document FKs; preserves historical `transmittal_items` untouched | Yes; STEP 185 uses this separate relation |
| `2026_11_11_000001_add_workflow_version_to_transmittals_table.php.candidate` | Nullable two-character `workflow_version` on `transmittals` | Must be reviewed against existing production column/history; no backfill; rollback retains the column; now outside executable migration discovery | Yes, before V1/V2 write behavior depending on classification |

Shared schema observations: candidate additions reference existing tables and use restrictive FKs. The current source-only baseline migration defines V1 `transmittal_items.transmittal_id` and `transmittal_receipts.transmittal_id` with cascading deletes; those historical migrations are untouched. Production FK/index state and engine semantics are not independently verified. The candidate sequence is a dependency proposal, not an approved execution order. The nullable workflow-version migration is kept outside executable discovery as `2026_11_11_000001_add_workflow_version_to_transmittals_table.php.candidate`; production applicability remains unknown. The irreversible `2026_11_04_142800_sync_transmittal_v1_and_v2_columns.php` remains excluded.

### Production Deployment Readiness Tracker after STEP 187

| Status | Evidence or remaining work |
|---|---|
| Verified — source only | STEP 184–186 boundary/workspace/lifecycle source checks; ten candidate definitions; separate nullable operations-manager field; existing Admin/SuperAdmin authorization; no custom role-column dependency; V1 item/receipt tables untouched; 114 schema checks plus prior 425 checks pass. |
| Pending | Confirm whether resumable upload session table is part of the approved minimum feature set; review manager/signatory labels with business owner; review all ten candidates against actual production migration history/schema and target-engine FK/index/nullable-unique behavior; isolated V1/V2 database/HTTP/concurrency/file-preservation acceptance. |
| Blocked | `workflow_version` migration is in executable discovery and must be reconciled with the Forge forced-migration script before any release; existing V2 parent/child/receipt/document columns and migration history are not production-verified; schema candidate execution/rollback requires a separate approval and tested backup/recovery. OCR contract, outbound-send/database uncertainty, attachment completeness, PDF/dependency/security acceptance, Vite build, actual production baseline/script/ledger, consistent backup and rehearsed lossless recovery remain unresolved. The November 4 irreversible synchronization migration remains excluded. |
| Overall | **NOT READY.** V2 remains inactive. No migration, database operation, production/Forge action, deployment, commit, push or merge occurred. |

Final STEP 187 verification:
- PHP syntax passed for all six changed PHP source/candidate files (each `php -l` exited 0).
- `tests/Static/transmittal-schema-contracts.php` passed 114 source-only checks. `tests/Static/transmittal-lifecycle-exclusions.php` passed 425 total source-only checks, including the STEP 184/185/186 check sets.
- `git diff --check` passed. Of 146 pre-STEP-186 file hashes, 132 remain byte-identical; the 14 differences are the exact intended STEP 186/187 edits. One new STEP 187 static checker was added. No other captured existing file changed.
- No PHPUnit suite, Laravel bootstrap, database/schema inspection, migration execution, production/Forge access, or runtime behavior verification occurred. The schema findings describe repository source only.


## STEP 188 — V2 approval, receipt and transmission integrity (2026-11-12)

Scope: source-only integrity repairs for the inactive V2 workflow. No migration/schema changes, route/provider activation, database operations, email/API calls, production/Forge access, or V1 workflow edits.

- Approval still requires an exact V2 parent, Admin/SuperAdmin authorization, and separation from the preparer. Under the parent lock, approval authorization and status are rechecked. Approval now refuses any pre-existing snapshot, authority row/code, approver, approval timestamp, or approved status. Authority code, digest, frozen snapshot, parent approval fields and history remain in one database transaction. Rejection rechecks its role after locking and refuses conflicting frozen approval evidence. Recall and cancellation now refuse inconsistent frozen approval evidence.
- Transmission policy now permits only approved or already-sent V2 parents. Unsent recipient emails are validated as a complete preflight before the first send; empty recipient sets and invalid addresses fail visibly instead of being skipped. Per-recipient token, sent timestamp, proof, history, and the approved-to-sent parent transition are written together in a locked transaction after provider success. This does not make the external email atomic with the database.
- Fixed acknowledgment expiry to read `transmittal_v2.acknowledgment.hours` from the existing config file. Acknowledgment token lookup and locked recheck continue to validate token hash, sent state, expiration and revocation. Under the existing parent/recipient locks, a pre-existing acknowledgment event without its receipt now blocks another acknowledgment. Receipt, acknowledgment, any acknowledgment proof, history, and final parent acknowledgment status remain in one transaction.
- Existing unique `transmittal_receipts.receipt_no` is the receipt-number arbiter; the non-executable candidate adds nullable unique `recipient_id` (one V2 receipt per recipient while existing V1 NULL associations remain possible). Candidate recipient token hashes and authority parent/code are unique. `receipt_code` currently has no unique index and no lookup/verification endpoint was found; if it is intended as a public lookup credential, that requires a reviewed schema decision. No candidate was changed or executed.
- Receipt PDF access now checks owner/admin authorization on every authenticated path, including the optional-receipt branch; rendering validates the receipt's V2 parent and recipient ownership. V2 register payloads expose each V2 receipt through the proposed V2 PDF route instead of the V1 receipt route. The V1 receipt controller/relationship remains unchanged.
- Brevo acknowledgment document attachments now require readable files whose SHA-256 matches metadata. Direct V2 email attachment handling fails closed on any missing/unreadable associated file rather than silently omitting it. An ambiguous Brevo failure no longer falls back to a second mail transport. V1 receipt/transmission paths and shared V1 models were not changed.

### Remaining consistency limits

The provider call still occurs before the transaction that records `sent_at`, token hash, proof and history. A provider may accept an email and the process/database may fail before the transaction commits; a retry could send a duplicate. The cache lock is not durable delivery evidence and its cross-instance configuration is unverified. Preventing this crash/concurrency window requires a durable outbox or dispatch-attempt state plus isolated engine/provider recovery tests; that requires a separately reviewed schema/worker architecture and is not implemented here. No exactly-once delivery claim is made. MySQL lock behavior, unique-index behavior, transaction participation of all models, and rollback after provider acceptance remain unverified.

The current V2 public acknowledgment receipt path records electronic acknowledgment. Physical/manual evidence uses proof records; no separate V2 physical receipt-creation path was found in the proposed minimum route manifest. Existing V1 receipt generation remains scoped to V1. The candidate uniqueness rules have not been tested against a real schema or historical rows.

### Production Deployment Readiness Tracker after STEP 188

| Status | Evidence or remaining work |
|---|---|
| Verified — source only | STEPS 184–187 retained; role/state/frozen-evidence checks strengthened; pending approval cannot transmit; acknowledgment expiry config aligned; invalid recipient and missing/corrupt attachments fail closed; V2 receipt/event writes stay transaction-scoped; V1 files unchanged. Source checks and lint results recorded below. |
| Pending | Isolated V1/V2 approval/rejection/recall/cancel, token replay/revocation/expiry, duplicate receipt/event, physical proof, attachment completeness and provider failure tests; target-engine lock/index/rollback verification; decide whether `receipt_code` requires uniqueness/public verification. |
| Blocked | Durable email dispatch/outbox and crash-safe retry/duplicate handling require architectural/schema review; external email cannot be atomic with DB. Production schema/history and unique indexes are unverified; actual Forge forced-migration script/ledger, Vite/PDF/security/OCR acceptance, attachment backup, tested restore and lossless rollback remain unresolved. The November 4 irreversible sync migration remains excluded. |
| Overall | **NOT READY.** V2 remains inactive. No database operation, migration, external send/API call, production/Forge action, deployment, commit, push or merge occurred. |

Final STEP 188 verification:
- PHP lint passed for all six changed/new PHP files (each `php -l` exited 0).
- `tests/Static/transmittal-integrity-contracts.php` passed 474 additional checks and reran the STEP 184–186 suites (425 total source checks, 899 combined). `tests/Static/transmittal-schema-contracts.php` passed all 114 STEP 187 checks.
- `git diff --check` passed. SHA-256 comparison against the pre-STEP-186 capture found 128 of 146 captured files byte-identical; the other 18 are exactly the intended STEP 186–188 edits. The STEP 186, STEP 187 and STEP 188 static checkers are present.
- No PHPUnit/application tests, database access, migration execution, provider request, production/Forge access, or runtime behavior verification occurred. This source review does not prove MySQL locks, multi-instance cache locks, email delivery, or transaction durability.


## STEP 189 — V2 durable email dispatch design and safety review (2026-11-12)

Scope: documentation-only design review. No application sources, migrations, configuration or tests were changed. No email/API call, database operation, production/Forge access, migration, route/provider activation, deployment, commit, push or merge occurred. V2 remains inactive.

- Current path is synchronous: controller authorizes and cache-locks, preflights recipients, calls Brevo, then commits the acknowledgment token hash/expiry, `sent_at`, proof, history and parent transition. The provider side effect is outside the DB transaction. The request uses a new `X-Entity-Ref-ID` per attempt and no `IdempotencyKey`; `messageId` may be empty. The cache lock is not durable evidence and its cross-instance configuration is unknown.
- Recommended durable outbox is described in `durable-email-dispatch-design.md`: transactional dispatch intent plus token hash/ciphertext and immutable attachment manifest; stable dispatch/provider idempotency IDs; append-only attempts and provider events; leased V2 worker plus poller; conservative `unknown` state and authorized reconciliation; no blind retry/new key after uncertain acceptance. Keep V1 synchronous and unchanged.
- Official Brevo docs describe `IdempotencyKey`, provider `messageId`, event webhooks and lookup APIs, but documentation differs between 15-minute and 30-minute idempotency TTL and does not establish exact behavior for this one-recipient endpoint. Treat it as bounded protection, not exactly-once. Confirm endpoint semantics and correlation/event behavior with Brevo and controlled staging evidence before automated resend.
- Local Laravel 12 source has a database queue-table migration, but queue defaults to `sync`, configured queue connections show `after_commit=false`, and local development uses `queue:listen --tries=1`. Repository search did not find a Forge deployment script or worker supervision configuration. Production queue tables, worker, cache topology, storage sharing, deployment branch/script and migration ledger are not independently verified.

### Production Deployment Readiness Tracker after STEP 189

| Status | Evidence or remaining work |
|---|---|
| Verified — source/docs only | Current controller → Brevo → post-provider DB transaction order; fresh provider reference/no idempotency header; optional message ID; attachment preflight/hash validation; local Laravel 12 queue configuration and jobs-table migration source; Brevo idempotency docs conflict on TTL and describe correlation/event lookup; design document records proposed outbox, state machine, recovery, privacy, approvals, and test boundaries. |
| Pending | Decide state semantics for queued/accepted/delivered and existing `sent_at`/proof/parent values; approve outbox schema, constraints, token encryption/retention, manual resend authority and audit policy; confirm target engine; reconcile additive schema with existing V2 candidates; obtain provider answers and perform isolated fake-HTTP/database tests plus controlled staging idempotency, event, timeout and worker-recovery tests. |
| Blocked | External email and database commit cannot be made atomic; current V2 is not durable and can duplicate or orphan an accepted send. No durable outbox/worker/event ingestion exists. Brevo TTL/one-message semantics remain unresolved. Forge worker supervision, deployment script, shared queue/cache/storage, production migration history and live settings are unverified and were not accessed. Backup/restore rehearsal, migration approval, V1 regression, Vite/PDF/OCR/security acceptance and all production release gates remain open. |
| Overall | **NOT READY.** V2 stays inactive. No source/runtime/test behavior beyond the reviewed code/docs is claimed. |

STEP 189 verification: read-only source/config/migration inspection and official documentation review only. No tests, PHP lint, database, migration, API call, production or Forge access occurred. The pre-existing working files were preserved; only this new design document and this appended tracker section are authorized documentation additions for this step.


## STEP 191 — Finalize V2 durable email dispatch specification (2026-11-12)

Scope: documentation-only. Read and reconciled the full STEP 189 design and STEP 190 findings against local V2 send source, models, candidates, route manifest, and queue configuration. No application code/tests/configuration/migrations changed; no migration, DB operation, email/API call, production/Forge access, V2 activation, commit, push, merge, reset, clean, or deployment occurred.

- Added `step-191-durable-email-dispatch-specification.md` with a decision/approval matrix, verified facts versus recommended defaults, request/worker transaction boundaries, proposed additive dispatch/attempt/event candidates, source/config dependencies, six phased implementation gates and their acceptance criteria.
- Conservative requirements: unknown outcome never auto-resends; provider acceptance is not delivery; V1 stays unchanged; dispatch/attempt/event evidence is auditable; bearer tokens are excluded from logs and provider-event records; manual resend requires explicit authorization.
- Unresolved approvals include V2 `sent_at`/parent status semantics, exact dedupe and engine constraints, retry classes/limits, byte-equivalent payload retention, encryption/key lifecycle and retention period, worker/queue/poller operations, webhook authentication/replay, manual reconciliation/resend roles, target DB and staging proof.
- Brevo's current API reference documents an idempotency header, but current batch documentation says 30 minutes while the older transactional-email changelog says 15 minutes. Single-recipient semantics remain unconfirmed. Until verified in controlled staging, automatic retry of uncertain or ambiguous provider outcomes stays prohibited.

### Production Deployment Readiness Tracker after STEP 191

| Status | Evidence / work |
|---|---|
| Verified — source/docs only | Existing synchronous send path and queue defaults; no V2 outbox/attempt/event source; route manifest inactive; Laravel jobs-table migration source only; Brevo documentation conflict; STEP 191 specification prepared. |
| Recommended defaults — not approved | V2-only transactional outbox, append-only evidence, stable dispatch/provider IDs, unknown hold, no V1/global queue change, exact payload/attachment hash validation, authenticated replay-safe event handling. |
| Pending | Management/security/technical approval of status semantics, retry/authorization/retention/key/worker/webhook/DB choices; provider confirmation; isolated tests; controlled staging evidence. |
| Blocked | No approved complete runtime design or implementation; provider idempotency behavior unresolved for this endpoint; production/Forge schema, deployment script, worker and shared services unverified; migration, backup/restore, V1 regression and release gates open. |
| Overall | **NOT READY.** V2 remains inactive. |

STEP 191 verification: documentation review only. No tests or runtime checks executed. Only the new STEP 191 specification and this tracker append are authorized documentation changes; all pre-existing modified and untracked application work was preserved.


## STEP 192 — V2 outbox foundation implementation gate (2026-11-12)

Scope: approval reconciliation and source-only verification. No outbox model, migration candidate, service, job, test, configuration, route, or provider was added. No existing application/test files were changed. No database/migration, email/API, production/Forge access, V2 activation, commit, push, merge, reset, clean, or deployment occurred.

### Approval gate results

- **Explicitly approved policy constraints from STEPS 191–192:** unknown provider outcomes must never auto-resend; provider acceptance is not delivery; preserve V1 behavior; keep V2 dispatch evidence auditable; bearer tokens must not enter logs or provider-event records; manual resend requires explicit authorization. These settle policy constraints only, not a specific implementation schema or runtime configuration.
- **Not explicitly approved:** exact dispatch/attempt/event schemas and status graph; recipient and parent state semantics; logical dedupe and database uniqueness scopes; retry classifications, counts and backoff; byte-equivalent payload storage; encryption key source/rotation and sensitive-data retention/purge period; target database locking/FK/index behavior; queue connection/name, worker count/timeouts/lease and poller cadence; webhook authentication/secret lifecycle/event key; reconciliation evidence threshold and authorized roles/dual control.
- **Result:** no component met the STEP 192 requirement that its necessary design decisions already be explicitly approved. No implementation was performed. In particular, dispatch models/state definitions and candidates cannot safely be prepared until their exact states, constraints, sensitive fields and rollback protections are approved. No active workers, retry logic, webhook ingestion, sending, or production integration were prepared.

### Production Deployment Readiness Tracker after STEP 192

| Status | Evidence / work |
|---|---|
| Verified — source-only | Full Step 189/191 design and Step 191 tracker read; current local V2 source confirms synchronous provider-before-DB writes, no outbox models/candidates/jobs/event ingestion, inactive V2 route manifest, and local queue default/config limitations. STEP 184 workflow-boundary, STEP 185 workspace-item, STEP 186 lifecycle, STEP 187 schema, and STEP 188 integrity source-only checks all passed in this turn. |
| Explicit policy approval | Unknown never auto-resends; acceptance is distinct from delivery; V1 stays unchanged; evidence remains auditable; no bearer token in logs/provider events; manual resend requires explicit authorization. |
| Pending approval/evidence | Exact schema/state/uniqueness; retry behavior; immutable payload strategy; encryption/key/retention; database engine concurrency; worker/queue/poller operations; webhook auth/replay; manual reconciliation roles/evidence; provider endpoint-specific idempotency confirmation and staging proof. |
| Blocked | No foundation component has all implementation decisions approved. No outbox schema/models/attempts/events exist. Production schema/history, Forge queue worker, shared queue/cache/storage, deployment script, backup/restore, staging acceptance, and release approval remain unverified/open. |
| Overall | **NOT READY.** V2 remains inactive. |

STEP 192 verification: five existing PHP source-only check scripts passed: workflow boundaries (89), workspace items (155 cumulative), lifecycle exclusions (425 cumulative), schema contracts (114), and integrity contracts (899 cumulative, including STEPS 184–188 checks). No PHP files changed, so PHP lint was not applicable. No database, migration, Laravel bootstrap, PHPUnit/application runtime test, external request, production or Forge access occurred. Preservation audit and `git diff --check` results are recorded in the STEP 192 response.


## STEP 193 — Safe local V2 email outbox foundation (2026-11-12)

Scope: local, inactive V2 foundation only. No V1 controller/model/route, existing production migration, shared/global queue configuration, unrelated ORDO module, V2 route/provider registration, active job/worker/scheduler, or outbound transport was changed. No database, migration, email/API, production/Forge access, commit, push, merge, reset, clean, or deployment occurred.

### Files added

- `app/Enums/TransmittalEmailDispatchState.php`
- `app/Enums/TransmittalEmailDispatchAttemptEvent.php`
- `app/Models/TransmittalEmailDispatch.php`
- `app/Models/TransmittalEmailDispatchAttempt.php`
- `app/Services/TransmittalV2/TransmittalEmailDispatchIdentity.php`
- `app/Services/TransmittalV2/TransmittalEmailDispatchService.php`
- `app/Services/TransmittalV2/TransmittalEmailDispatchAttemptRecorder.php`
- `app/Services/TransmittalV2/TransmittalProviderEventContract.php`
- `patches/transmittal-v2/migrations/2026_11_12_000001_create_transmittal_email_dispatches_table.php.candidate` (non-executable)
- `patches/transmittal-v2/migrations/2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php.candidate` (non-executable)
- `tests/Static/transmittal-v2-email-outbox-contracts.php`

Pre-existing files changed: `tests/Static/transmittal-schema-contracts.php` was updated to require the ten previously reviewed candidates plus the two new non-executable outbox candidates, while retaining the original candidate-name/order assertions; this checklist received the tracker append. All other pre-existing modified/untracked files were preserved.

### Implemented and explicitly blocked

- Dispatch identity is deterministic per V2 parent, recipient, purpose and SHA-256 immutable transmission-version fingerprint. Dispatch UUID is a stable UUIDv5 derived from that identity. Service performs sequential duplicate lookup and rejects a payload/manifest fingerprint conflict. **Database unique constraints are omitted and marked BLOCKED pending target-engine evidence; no concurrent/multi-instance duplicate-prevention guarantee is claimed.**
- Dispatch and attempt candidates are additive table-creation candidates only, outside `database/migrations`. Their `down()` methods refuse to drop audit evidence. They contain scalar association IDs but deliberately omit engine-dependent indexes and foreign keys; exact types, ownership constraints and uniqueness must be reviewed against the authorized engine before they can be considered executable.
- Dispatch states are `pending`, `processing`, `accepted`, `delivered`, `failed`, and `unknown`. Ordinary transitions are pending→processing and processing→accepted/failed/unknown. Accepted→delivered requires verified provider evidence in the pure state contract, but the model blocks persistence of that transition because event authentication/ingestion is not implemented. Failed, delivered and unknown have no ordinary outgoing transitions. Unknown never triggers a retry or resend.
- Dispatch creation validates V2 parent/recipient ownership and existing transmit authorization. It writes only identifiers, state and fingerprints; no token, recipient email, rendered body, attachment path or file content is persisted. Attempt evidence is append-only and limited to sanitized event/status/message-ID/error-code/fingerprint fields. The provider-event value object is non-persistent and explicitly untrusted; no provider-event table/candidate was prepared because authentication and event-uniqueness contracts remain blocked.
- No active provider call, queue job, worker, scheduler, retry, webhook endpoint, route integration or V2 activation was added. Payload encryption/retention, actual attachment manifest persistence, engine-specific uniqueness/FKs, provider idempotency behavior, delivery-event projection, worker configuration and manual reconciliation remain blocked.

### Production Deployment Readiness Tracker after STEP 193

| Status | Evidence / work |
|---|---|
| Verified — local/source-only | New enum, identity helper, V2 dispatch/attempt model contracts, intent and attempt recorder, non-persistent provider-event value object, two non-executable candidates and static checker are present. Existing V2 routes/provider remain inactive; V1 sender and shared queue config are unchanged. PHP lint and static checks are recorded in the STEP 193 response. |
| Pending | Approve target DB engine and verify type/index/FK/unique constraints; decide encrypted payload/token design, key lifecycle and retention before persisting sendable payload; define provider-event authentication/uniqueness and verified delivery transition; verify actual version/attachment manifest production and transmission semantics; isolated DB concurrency and V1 preservation tests. |
| Blocked | Unique dispatch/attempt constraints and parent-recipient FK are omitted; current intent lookup is only sequential and cannot guarantee concurrent deduplication. Provider-event persistence, delivery updates, payload/token storage, workers, retries and sending remain unimplemented. Brevo idempotency endpoint semantics/TTL, production migration history, Forge queue worker, shared queue/cache/storage, backup/restore and release gates remain unverified. |
| Overall | **NOT READY.** V2 remains inactive; no migration or email was executed. |

STEP 193 verification:
- PHP lint passed for all 12 changed PHP/PHP-candidate files, including the updated schema contract checker.
- `tests/Static/transmittal-v2-email-outbox-contracts.php` passed 80 source-only checks. Existing checks passed: STEP 184 boundary 89; STEP 185 workspace 155 cumulative; STEP 186 lifecycle 437 cumulative; STEP 187 schema 124; STEP 188 integrity 911 cumulative. These checks do not establish database concurrency, transaction behavior or runtime acceptance.
- `git diff --check` passed. SHA-256 comparison against the pre-STEP-193 snapshot found no pre-existing hash changes except the intentionally updated schema contract checker and this tracker; newly added files are the exact foundation files listed above. No V1/unrelated files changed during this step.
- No PHPUnit/application tests, Laravel bootstrap, database, migrations, external calls, production/Forge access, queue/worker/scheduler execution or runtime behavior verification occurred.


## Accelerated local integration and testing (2026-11-12)

Scope: continued local-only work under the existing STEP 193 authorization. V2 routes/providers/workers remain inactive; no migration, production/Forge access, live email/API, commit, push, merge, reset, clean or deployment occurred. Existing dirty files were preserved.

- **Verified locally:** isolated V2 suite passed 113 tests / 1,519 assertions; isolated V1 regression passed 26 tests / 222 assertions; isolated PHP attachment suite passed 12 tests / 104 assertions; source-only frontend attachment launcher passed 5 tests. PHP lint passed for 63 changed PHP sources and migration candidates. STEP 184-188 source/outbox checks passed (89 boundary; 155 workspace cumulative; 437 lifecycle cumulative; 130 schema; 911 integrity cumulative; 86 outbox). V2 receipt PDF now disables Dompdf PHP and remote-resource execution; renderer output itself remains unverified. `git diff --check` passed.
- **Build:** Vite production build passed (64 modules) with `publicDir` disabled and output routed to a unique temporary directory; no repository `public/build` artifacts or public files were written/copied.
- **Frontend tests:** 33/35 smart-upload/ZIP Node tests passed; two protected-file assertions failed on the existing `resources/js/transmittal.js` and V1 `app/Http/Controllers/TransmittalController.php` hashes. Their contents and baseline hashes were not altered. Separately, all five attachment source-only tests passed via the restricted launcher.
- **Migration safety:** relocated the untracked nullable `workflow_version` migration, preserving its contents, from executable Laravel discovery into `patches/transmittal-v2/migrations/2026_11_11_000001_add_workflow_version_to_transmittals_table.php.candidate`. The static migration contract now requires 13 candidates outside executable discovery and passed 130 checks. No migration was run.
- **Composer/OCR:** manifest validation succeeded with existing unbound-version warnings for four dependencies; `composer check-platform-reqs --no-dev` passed on local PHP 8.5.3. OCR runtime suite was attempted and stopped at the strict extension allowlist because `lexbor` is loaded but unreviewed; no guard was weakened.
- **Outbox state:** intent and attempt recording have isolated SQLite-memory test coverage for stable identity, sequential duplicate prevention, V2/recipient ownership, append-only attempts, accepted/unknown state projection, and refusal to mark delivery without verified evidence. This does not prove target-engine unique constraints, cross-instance concurrency, transaction durability, or delivery. Provider sending remains fail-closed; payload/token encryption and retention, engine-specific constraints, authenticated provider events, reconciliation, workers, and retry remain blocked.

### Production Deployment Readiness Tracker — current

| Status | Evidence / remaining work |
|---|---|
| Verified - local only | Isolated V1, V2, and PHP attachment suites pass; five source-only frontend attachment tests pass; workflow/workspace/lifecycle/schema/integrity/outbox checks pass; Vite builds in isolated temporary output; Composer platform requirements pass; workflow-version migration is outside executable discovery; V2 receipt PDF disables Dompdf PHP and remote resources; 63 PHP sources/candidates lint; diff check passes. No production/live-service verification. |
| Pending | Resolve two protected-file hash mismatches with reviewed provenance; provide a PHP environment satisfying the OCR strict allowlist; verify V2 PDF output and route/authorization behavior in an isolated HTTP harness; test target-engine migration constraints and attachment retention in isolated/staging. |
| Blocked | Durable sendable payload encryption/retention; target DB-specific foreign keys/unique indexes and concurrency proof; provider idempotency semantics and authenticated event/reconciliation; queue worker/scheduler/recovery design. V2 transmission remains intentionally 503/fail-closed. All 13 schema proposals remain non-executable and unrun. V2 routes/provider/workers remain inactive. Forge script, migration ledger, shared storage/queue/cache, backup/restore and production configuration remain independently unverified. |
| Overall | **NOT READY.** No production readiness or V2 transmission functionality is claimed. |


## Accelerated acceptance continuation (2026-11-12)

- Isolated V2 Laravel/PHPUnit suite: **116 tests, 1,584 assertions passed** on PHP 8.5.3 with the dedicated SQLite `:memory:` launcher. Added test-only HTTP coverage for the feature gate, V1/V2 approval separation, role restrictions, duplicate approval refusal, frozen workspace-item snapshot, token-scoped/idempotent acknowledgment, receipt PDF output, and private document token/hash validation. Test routes are created only in the isolated app; production V2 route/provider activation remains off.
- Frontend Node suite: **34/35 passed**. Git inspection confirms the expected `Transmittal.php` hash is the production-baseline blob at `47e8398`; reviewed current additions are explicitly exempted in preservation assertions. The `smart-upload.blade.php` captured hash (`a890…`) does not match the historical `a61c775` version (`440e…`) or a reachable source version found in this worktree; the assertion remains failing and was not refreshed. Other 34 frontend assertions pass, including actual Blade handler, queue, OCR-ticket and JS syntax checks.
- Attachment/V1 acceptance remains as previously verified: **12 attachment tests / 104 assertions** and **26 V1 tests / 222 assertions**; these application sources were not changed during this continuation. Vite build and isolated output checks remain as previously verified.
- OCR remains blocked: the only discovered PHP binary loads `lexbor`, which the dedicated OCR safety allowlist rejects. The guard was not weakened. No supported alternate PHP runtime was found.
- Migration engine verification remains pending. The local machine has MySQL Server 9.6 binaries/service, but the deployed engine/version and safe independent test-database target are not verified. No candidate migration or migration command was executed; all candidates remain outside Laravel migration discovery. The candidate dispatch schema still omits uniqueness constraints, so durable concurrent deduplication remains unproved and blocked.
- `php -l tests/Feature/TransmittalV2HttpAcceptanceTest.php` passed; `git diff --check` passed. No V1 or unrelated ORDO production source was modified in this continuation. No production/Forge access, database migration, external call, live email, worker, deployment, or Git history mutation occurred.

### Current Production Deployment Readiness Tracker

| Status | Evidence / remaining work |
|---|---|
| Verified — isolated local | V2 suite 116/1,584; prior isolated V1 suite 26/222; prior isolated attachment suite 12/104; 34/35 frontend tests; source-level V1/V2 and migration contracts; Vite isolated build; PHP lint and diff check. HTTP controller behavior and PDF bytes verified only in test-only routes with synthetic SQLite data. |
| Pending | Resolve/prove provenance for the captured Smart Upload Blade hash before accepting that file; provide OCR-compatible PHP runtime without relaxing the allowlist; obtain independently verified target DB engine/version and run candidate compatibility/constraint checks on a disposable matching engine; complete deployment-script, backup/restore, shared-storage and migration-history verification. |
| Blocked | One frontend preservation assertion remains failing. Durable dispatch uniqueness/concurrency constraints, authenticated provider-event ingestion, payload encryption/retention, provider reconciliation, worker/recovery configuration, and automated resend controls remain incomplete. V2 routes/provider/workers remain inactive; sending remains fail-closed. Production settings, Forge script, migration history, backup/restore and staging/live verification have not been checked. |
| Overall | **NOT READY.** |

## Fast-track release-candidate continuation (2026-11-12)

- Registered the 30 reviewed V2 routes in `routes/transmittal-v2.php`, included from `routes/web.php`, and registered only the namespaced `TransmittalV2AuthorizationServiceProvider`. The V2 middleware/config flag remains default-off; this source registration does not enable V2 in any environment. V1 routes remain in place.
- Updated isolated HTTP acceptance to use the application-registered routes. It verifies all 30 manifest methods/URIs and feature-gate middleware, confirms `/transmittals` returns 404 when disabled, and exercises actual V2 approval, token acknowledgment, private document download, and recipient receipt PDF handlers against synthetic SQLite data. The actual transmit endpoint returns 503 and issues no HTTP request or send-state mutation.
- Isolated V2: **119 tests / 2,126 assertions passed**. Isolated V1: **26 / 222 passed**. PHP attachment suite: **12 / 104 passed**. Guarded frontend attachment suite: **5 / 5 passed**. Smart Upload/ZIP frontend suite: **34 / 35 passed**; its remaining failure is the pre-install Smart Upload Blade hash (`a890…`) versus current source (`138c…`). No historical source matched the expected hash; neither source nor expected hash was overwritten.
- Re-ran workflow, workspace-item, lifecycle, schema, integrity and outbox source contracts: **90, 156 cumulative, 438 cumulative, 130, 912 cumulative and 90 checks passed**, respectively. Changed PHP lint and `git diff --check` passed. A direct unguarded attachment Node invocation was refused by the test guard; the approved synthetic launcher then passed all five tests.
- Disposable MySQL 9.6 evidence from the preceding continuation remains: 13 non-executable candidates applied in filename order on a fresh temporary data directory; synthetic V1 fields remained unchanged; unique dispatch/attempt keys rejected duplicates; two independent processes deduplicated a concurrent logical intent; a forced mid-transaction failure rolled back atomically. No production or existing database was accessed.
- OCR remains unverified because the strict runner refuses the locally loaded `lexbor` extension. OCR and the separate Smart Upload convenience page are not required for manual entry or document attachment in source (the register supports drafts and the edit form accepts documents), but that exact draft/edit upload path has not received end-to-end runtime acceptance. Treat Smart Upload as deferred only after staging confirms the manual workflow and its file retention.
- The outbox remains a non-sendable intent/attempt ledger. No encrypted sendable payload, retention/purge policy, delivery worker/poller, authenticated provider-event ingestion, provider reconciliation, or authorized resend path was implemented. Unknown outcomes cannot be automatically resent; `accepted` is not `delivered`; route transmission stays fail-closed.

### Current Production Deployment Readiness Tracker

| Status | Evidence / remaining work |
|---|---|
| Verified — isolated local | Actual gated route table: 30/30 manifest routes; default-off 404; V2 119/2,126; V1 26/222; attachment PHP 12/104 and guarded frontend 5/5; 34/35 Smart Upload/ZIP; disposable MySQL 9.6 candidate compatibility and synthetic V1 preservation; uniqueness/concurrency/transaction evidence; all listed source checks, PHP lint, and diff check. No real emails or production service/database access. |
| Pending | Resolve Smart Upload Blade provenance without changing the captured hash, or prove the manual draft/edit attachment path in staging and formally defer Smart Upload; provide an OCR-compatible reviewed PHP runtime if OCR is retained; verify the actual production database engine/version and migration ledger; verify Forge build artifacts/script, shared storage/cache/queue, backup and tested restore. |
| Blocked — release critical | V2 send route returns 503. Durable encrypted payload and retention/key handling need explicit technical/security decisions; workers/poller and recovery supervision need operational approval; provider acceptance reconciliation, authenticated event ingestion, provider idempotency behavior, delivery projection, and manual resend authority are incomplete. The 13 schema candidates remain non-executable; production schema/history compatibility and staging restore are unverified. |
| Activation | V2 routes and namespaced abilities are source-registered but remain unavailable with the default-off feature flag. No production route, provider call, worker, scheduler or email was activated. |
| Overall | **NO-GO — NOT READY.** |


## Critical blocker resolution continuation (2026-11-12)

### Completed local work

- Added database unique indexes to the non-executable V2 dispatch candidates for dispatch UUID, dedupe fingerprint, attempt-event UUID, and `(attempt_uuid, sequence_no, event_type)`. The candidates remain under `patches/` and are not Laravel-discoverable.
- Repaired attempt-ledger event identity: a submission-start row and provider-outcome row for the same attempt/sequence now receive distinct deterministic event UUIDs. A new atomic `beginSubmission()` locks a V2 dispatch, moves only `pending` to `processing`, and appends the start evidence in the same transaction. Unknown/failed/accepted states cannot be automatically re-claimed. Generic append rejects submission-start events. Provider acceptance still does not establish delivery.
- Dispatch intent creation uses parent/recipient/actor locks, unique database keys, and duplicate-key reconciliation by immutable fingerprint. Payload/attachment changes for the same identity are rejected. No provider submission, email, queue job, retry, worker, or route integration was added.

### Disposable MySQL 9.6 evidence

- Ran an independently initialized MySQL 9.6.0 server bound only to `127.0.0.1:63427` with its own temp data directory. The existing Windows `MySQL96` service was not used; after verification, the temporary server (PID 13892) was shut down and its port refused connections, while service PID 4196 remained RUNNING. The synthetic temp data directory was left in place; no cleanup was performed.
- Manually invoked the `up()` methods for the 13 V2 `.candidate` migrations in filename order against only that newly initialized disposable database. The older V1/V2 synchronization migration was not present in the candidate set or executed. All 13 applied successfully on this local MySQL version. A seeded historical V1 parent retained its NULL workflow classification, legacy status/control number, item quantity, receipt number, and attachment path unchanged.
- Verified all four outbox unique indexes on MySQL 9.6; direct duplicate insert attempts for each dispatch/attempt key were rejected with MySQL duplicate-key errors. Two independent PHP processes concurrently requested the same parent/recipient/immutable version while one held the parent transaction lock: both returned the same dispatch UUID and exactly one row existed for that dedupe fingerprint. A synthetic SQL trigger forced failure after attempt insertion and before state update; transaction rollback left the dispatch processing with only its original start event.
- This is engine evidence for local MySQL 9.6 only. Production database engine/version, schema history, and lock behavior remain independently unverified.

### Acceptance results

- Isolated V2 PHP/PHPUnit: **118 tests, 1,601 assertions passed** (PHP 8.5.3; guarded SQLite `:memory:` launcher), including HTTP authorization/approval, acknowledgment, PDF, private storage, dispatch uniqueness contracts, atomic claim/outcome handling, and historical workflow isolation.
- Isolated V1: **26 tests / 222 assertions passed**. Attachments: **12 tests / 104 assertions passed**.
- Frontend: **34/35 Node tests passed**; the only failure is the Smart Upload Blade pre-install hash. Eight reachable historical versions were checked; none matches the captured `a890…` hash. A bounded workspace search found no alternate source copy or captured file body; the current source hash is `138c…`, and the expected hash remains unchanged. The exact older `a61c775` source hashes to `440e…`.
- Source checks: workflow boundary 89; workspace 155 cumulative; lifecycle 437 cumulative; schema 130; integrity 911 cumulative; outbox 90. PHP lint passed for changed PHP and migration-candidate files; `git diff --check` passed.

### Current Production Deployment Readiness Tracker

| Status | Evidence / remaining work |
|---|---|
| Verified — local only | V2 118/1,601; V1 26/222; attachment 12/104; disposable MySQL 9.6 compatibility for 13 candidate migrations; V1 synthetic-record preservation; four outbox unique constraints; concurrent same-identity intent deduplication; atomic attempt/state rollback; 34/35 frontend tests; all source checks; PHP lint and diff check. |
| Pending | Resolve Smart Upload Blade baseline provenance without changing its expected hash; obtain the actual production database engine/version and migration history for compatibility review; run migration and concurrency verification on a protected staging restore of the verified engine; provide an OCR-compatible PHP runtime. |
| Blocked | OCR launcher rejects `lexbor`, which is loaded even under PHP `-n`; bounded runtime discovery found only `C:\Server\php\php.exe`. WSL returned its usage screen rather than an available distro; Docker/Podman were absent. Sendable payload/token encryption and retention, authenticated provider events, provider reconciliation, delivery projection, worker supervision/recovery, and manual resend policy remain incomplete. V2 routes/providers/workers remain inactive; sending remains fail-closed. Production configuration, Forge script, backup/restore and staging/live gates remain unverified. |
| Overall | **NOT READY.** |


## Final deployment readiness execution (2026-11-12)

### Completed and verified in this execution

- Added an isolated HTTP acceptance case for the manual draft-to-edit attachment workflow: V2 draft creation, PDF upload through the edit endpoint, persisted V2 document association, private-storage persistence, authenticated scoped download, and absence of OCR invocation. This uses synthetic SQLite and fake local storage only; it does not prove production storage behavior.
- Final isolated suites: **V2 120 tests / 2,275 assertions passed**; **V1 26 / 222 passed**; **attachments 12 / 104 passed**. V2 coverage includes gated route registration, approval/authorization, acknowledgments, receipt PDF/private document behavior, and fail-closed transmit behavior. No live provider call occurred.
- Re-ran the existing source-only suites: workflow boundary 90; workspace cumulative 156; lifecycle cumulative 438; schema 130; integrity cumulative 912; outbox 90. These do not establish provider delivery, worker recovery, production database compatibility, or concurrency beyond the separately recorded disposable MySQL evidence.
- PHP lint passed for the changed acceptance test; `git diff --check` passed. No production/V1 controller, model, route, migration, or unrelated module was changed by this final acceptance addition. The pre-existing dirty working tree remains preserved.

### Release capability and unresolved blockers

- V2 transmission remains intentionally fail-closed (HTTP 503, no provider request or send-state mutation). The outbox is not a complete sendable durable mail system: encrypted immutable sendable payload/key lifecycle, retention, authenticated provider-event ingestion and replay protection, provider reconciliation, worker lease/recovery/supervision, delivery projection, and explicitly authorized manual resend are not implemented. No automatic retry of `unknown` is allowed; `accepted` is not `delivered`.
- The 13 schema files remain `.candidate` documents outside Laravel migration discovery. Their order by filename is: `2026_11_09_000001_add_transmittal_v2_columns`; `2026_11_09_000002_create_transmittal_recipients_table`; `2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table`; `2026_11_09_000004_create_transmittal_documents_table`; `2026_11_09_000005_create_proof_of_transmissions_table`; `2026_11_09_000006_create_approval_authority_codes_table`; `2026_11_09_000007_create_transmittal_histories_table`; `2026_11_09_000008_create_external_acknowledgments_table`; `2026_11_09_000009_create_transmittal_upload_sessions_table`; `2026_11_09_000010_create_transmittal_workspace_items_table`; `2026_11_11_000001_add_workflow_version_to_transmittals_table`; `2026_11_12_000001_create_transmittal_email_dispatches_table`; `2026_11_12_000002_create_transmittal_email_dispatch_attempts_table` (all `.php.candidate`). The older `2026_11_04_142800_sync_transmittal_v1_and_v2_columns.php` remains excluded. Local disposable MySQL 9.6 candidate/concurrency/V1-preservation evidence is recorded above; no evidence establishes the production engine, live schema/history, or a staged restore. Do not promote/run candidates until those are independently verified and migration approval is given.
- The manual attachment path is now locally accepted, so Smart Upload and OCR can be deferred from the minimum workflow. Smart Upload preservation hash mismatch remains unresolved and its expected hash was not changed. OCR execution remains blocked by the reviewed runtime's loaded `lexbor` extension; its safety guard was not weakened. Keep OCR-dependent paths unavailable if choosing a manual-only release; no production setting has been altered.

### Staging package and deployment gate checklist

The current tree is **not an approved deployable package**: V2 route/provider source registration exists behind the default-off feature gate, the outbox is non-sendable, candidates are not executable migrations, and production/staging configuration and Forge hooks are unverified. Before any staging release:

1. **Artifact and access gate:** review the complete staged commit/diff and exact branch/commit; verify composer/npm lockfiles, PHP 8.5 extensions, Vite production output/manifest and Forge release hook on a staging clone. Confirm all V2 route/provider changes remain disabled by default. Do not include or activate the excluded synchronization migration.
2. **Environment gate:** independently establish staging PHP/runtime, DB engine/version and schema, `migrations` ledger, private shared-storage disk/path and permissions, cache/session/queue drivers, `APP_KEY` provisioning, mail provider credentials/sender/domain, and worker supervision. Keep mail credentials out of logs and reports. Do not enable mail dispatch until the blocked outbox security/operational design is approved and implemented. Manual-only operation must not imply that email transmission works.
3. **Backup and restore gate:** before schema work, take coordinated database and shared/private attachment-storage backups with a recorded timestamp and retention location; preserve backup access controls. Restore both into an isolated staging copy and verify V1 row counts and representative legacy NULL classifications, control numbers/statuses, item quantities, receipts and attachment hashes/paths before using that copy. A backup without a successful restore check is not a rollback plan.
4. **Migration gate:** compare the full candidate set and ordering to the restored staging schema and actual migration ledger; obtain reviewed additive/constraint/lock-impact approval; run only approved migrations against staging copy; verify V1 records/files unchanged and V2 constraints/data contracts. No production migration command is authorized by this readiness work.
5. **Staging acceptance gate:** with manual-only features, test V2 create/edit, private attachments/download authorization, workspace items, approval/rejection, receipt PDFs, recipient acknowledgment/token expiry/revocation, and V1 regression. Test shared-storage behavior across releases. Keep V2 feature activation and any dispatch mechanism separately disabled until approved. Record redacted logs and actual DB/storage evidence.
6. **Rollback/recovery gate:** document the exact Forge release rollback procedure and confirm it on staging. First disable V2 via the verified feature flag if an application defect occurs; then roll back the release using the verified Forge mechanism. Do not reverse/drop additive schema or delete V2 rows/files as routine rollback. For data/storage corruption, restore coordinated DB and storage snapshots only under an approved recovery plan that accounts for writes after the snapshot. Preserve post-release evidence and avoid code-only rollback that strands files or dispatch records.
7. **Post-deploy verification gate (future separately approved production activity):** verify deployed commit, health, default-off/approved flag state, V1 register/actions and attachments, V2 authorized manual workflow, private storage, PDFs and redacted errors. If email is later approved, verify queue/worker health, provider acceptance separately from delivery, authenticated events/reconciliation and unknown-outcome handling. None of these production checks has been performed.

### Updated Production Deployment Readiness Tracker

| Status | Evidence / action required |
|---|---|
| **Verified — isolated local** | V2 120/2,275; V1 26/222; attachments 12/104; manual draft/edit PDF attachment/private download path; source suites 90/156/438/130/912/90; earlier isolated MySQL 9.6 candidate/uniqueness/concurrency/rollback/synthetic V1 preservation evidence; PHP lint and diff check. No runtime production claim. |

### Consolidated implementation and staging-readiness update — 2026-11-12

| Status | Evidence / remaining condition |
|---|---|
| **Verified — local V2 runtime** | After outbox integration and final local fixes, isolated V2 suite: **131 tests / 2,615 assertions passed**. Includes default-off routes, synthetic SQLite HTTP creation/approval/transmit, encrypted outbox, local mock acceptance, acknowledgment and receipt PDF; private attachment ownership/hash validation; provider HMAC/replay/reconciliation; bounded safe retry; unknown-outcome recovery; designated manual resend and token rotation; empty-recipient refusal. No real email/API or database migration was run. |
| **Verified — V1/attachment regression** | V1 suite **26 / 222 passed**; PHP attachment suite **12 / 104 passed**; attachment frontend harness **5 / 5 passed**. Current V1 behavior is covered by isolated regressions; this is not production-data/file verification. |
| **Verified — build/static checks** | JavaScript syntax checks passed; isolated Vite 7.3.1 build passed (64 modules) to a new OS temporary directory; PHP lint passed on **265 PHP sources and migration candidates**; source contracts passed: boundary 90, workspace cumulative 156, lifecycle cumulative 478, schema 135, integrity cumulative 952, outbox 106; `git diff --check` passed. |
| **Blocked — Smart Upload hash** | Smart Upload suite: **28 passed / 1 failed** because `resources/js/transmittal.js` actual SHA-256 `76148b500c09ce6f1681261d5ba39a4abf6988c46a8953a1def22bb2776a43bd` differs from the unchanged captured expectation `50420496239955a38137e836c676d500dc61e8019eed096512cd534adf53746b`. Expected hash was not replaced. The production baseline tree has no `resources/js/transmittal.js`; captured expectation provenance remains unresolved. Optional Smart Upload remains default-off. |
| **Deferred — OCR/Smart Upload** | Manual V2 creation/edit/PDF attachment/private-download path passed prior isolated acceptance. OCR and Smart Upload are not required for the safe manual V2 flow; both remain default-off and are not release-accepted. Local OCR runtime guard rejects the present lexbor extension. PDF/ZIP compression/extraction remain unavailable. |
| **Implemented — local mock outbox only** | Envelope-encrypted sendable payload; stable dispatch/attempt identities; immutable attempt and provider-event evidence; V2 parent/recipient lock and unique intent constraints; validated attachment bytes; separate accepted/delivered states; timestamped HMAC mock-event replay handling; unknown never auto-retries; bounded confirmed-safe rejection retry; interrupted processing recovers to unknown; explicit designated-ID manual resend with reason/idempotency audit and acknowledgment token rotation. |
| **Blocked — actual provider and staging operation** | No Brevo adapter is integrated into the outbox; generic HMAC event contract is mock-only and not verified against Brevo. No worker/scheduler recovery command or staging queue supervision was enabled. `QUEUE_CONNECTION=sync` is in `.env.example`. Encryption key provisioning and old-key recovery must be verified in staging. No real send or external API call occurred. |
| **Blocked — migration release** | There are **14 non-executable candidate migrations**. Historical MySQL 9.6 evidence covers the prior 13-candidate state only; current HTTP tests use hand-built SQLite memory schemas. The new provider-event candidate and complete current set have not been rehearsed against the actual staging schema/engine. The November 4 synchronization migration remains excluded. |
| **Repository / preservation state** | `HEAD` remains production baseline `3b3d99ae57c565069a1aa0b8c22e361898bca039`; branch remains `transmittal-v2-integration`. The integration work tree remains dirty with modified and untracked files; no commit, push, merge, reset, clean or deployment occurred. Legacy workflow-boundary source changes from earlier approved steps remain in the candidate and require full diff review. |
| **Forge / deployment script** | No Forge deployment script was present in this local repository to inspect. `composer setup` runs `key:generate` and `migrate --force`; the import command can run `migrate:fresh` with `--fresh` and can force migration/seeding. Neither is a safe existing-production deployment command. The reported Forge script with multiple forced migrations remains unverified and blocks a concrete deployment plan. |
| **Release decision** | **Local complete email release candidate: NO-GO** until provider adapter/event contract and migration rehearsal are complete. Manual V2 flow is ready for authorized isolated staging evaluation. **STAGING: NOT GO** pending actual staging identity/schema/backups/script/config/worker review. **PRODUCTION: NOT AUTHORIZED.** |

Staging review package: [staging-release-package.md](staging-release-package.md). It includes file scope, all 14 candidate migration filenames/order, configuration names without values, key and worker gates, restore/rollback checks, optional-feature deferral and staging verification order.
| **Pending — staging evidence** | Staging deployment artifact/Forge hook; actual staging DB engine/schema/migration ledger; staged restore and shared-storage verification; candidate review on restored schema; browser end-to-end manual-only workflow; exact backup restoration and rollback drill; resolve Smart Upload hash provenance only if retaining that feature. |
| **Blocked — release-critical** | Durable V2 email sending: payload encryption/key lifecycle and retention, authenticated provider events/replay protection, provider reconciliation/idempotency semantics, worker supervision/recovery, delivery-state evidence, and manual resend authority. The transmit endpoint remains 503/fail-closed. Production DB/schema/config/Forge/shared storage and backup/restore remain unverified. |
| **Not accepted** | Smart Upload hash assertion unresolved (not rewritten); OCR runtime blocked by `lexbor`; frontend suite remains 34/35 as previously recorded. These can be deferred only for a clearly manual-only staging scope with V1 preserved. |
| **Activation / overall** | Route/provider source registration is default-off; no production activation, migration, provider call, queue worker, Forge access, or deployment occurred. **NO-GO — NOT READY for production or a full V2 release.** A manual-only staging validation is the next available gate after artifact/config and restore controls are independently established. |

### Latest Production Deployment Readiness Tracker - 2026-11-12

This end-of-file status supersedes earlier tracker rows in this historical checklist.

| Status | Current evidence |
|---|---|
| **Verified - local manual V2 and mock outbox** | V2 **131/2,615**; V1 **26/222**; attachments **12/104**; frontend attachment harness **5/5**; Vite build and PHP lint (**265** files) passed. Current source checks passed: boundary 90, workspace cumulative 156, lifecycle cumulative 478, schema 135, integrity cumulative 952, outbox 106. `git diff --check` passed. All runtime tests were isolated; no live email, API, database or migration was used. |
| **Pending - preservation / optional workflow** | Smart Upload integrity suite **28/29** because actual JS hash differs from the unchanged expected hash; no provenance proof exists. OCR is gated off and the local runtime guard rejects lexbor. Manual file upload/download is the accepted path. |
| **Blocked - complete email release** | The implemented outbox/provider/event adapter is local/testing mock only. Brevo transport semantics, provider-specific event authentication/correlation, worker supervision/recovery command and actual staging key provisioning are unverified. Unknown outcomes never auto-retry; provider acceptance is not delivery. |
| **Blocked - schema release** | 14 migrations remain non-executable candidates. Prior disposable MySQL evidence covered the earlier 13-candidate set; the new provider-event candidate and current complete set are not rehearsed against the actual staging engine/schema. November 4 synchronization migration stays excluded. |
| **Repository state** | Branch `transmittal-v2-integration`, HEAD still `3b3d99ae57c565069a1aa0b8c22e361898bca039`; dirty modified/untracked work is preserved and not an immutable release artifact. No commit, push, merge, reset, clean or deployment. |
| **Staging GO** | **NO** - staging identity, deployment script/hooks, actual schema/history, backup/restore, shared storage, queue supervision and provider configuration lack authorized evidence. |
| **Production GO** | **NOT AUTHORIZED** - no Forge or production access/change was performed or approved. |
| **Overall local release candidate** | **NO-GO for the complete email-enabled release.** Manual V2 workflow is ready for a separately authorized isolated staging evaluation after artifact review and migration/restore gates. |

### Latest Production Deployment Readiness Tracker - 2026-11-13 (Brevo adapter continuation)

This tracker supersedes the earlier local email-provider rows above. Historical test records remain unchanged.

| Status | Current evidence |
|---|---|
| **Verified - local Brevo adapter acceptance** | Existing `BrevoTransmittalService` transport/configuration is reused through a V2-specific adapter. Final V2 suite: **135 tests / 2,665 assertions passed**. Synthetic provider responses covered accepted 201/message ID, correlation tag/reference, 429 unknown outcome/no resend, Bearer-authenticated delivered event, replay idempotency, and documented `hard_bounce`, `soft_bounce`, `invalid_email`, `blocked`, and `deferred` events. The test-specific Guzzle MockHandler avoids initializing native network transports; the runner's deny-by-default protection remains enabled. No live provider call occurred. |
| **Verified - source contracts and manifest** | V2 outbox source-only contracts passed **118 checks**; route manifest parsed as JSON; V2 route/provider remains default-off. Brevo outbox opt-in is separately default-off and reuses existing key/sender config. Changed Brevo PHP files passed syntax checks; final `git diff --check` passed. |
| **Verified - regressions** | V1 suite **26 tests / 222 assertions passed**; attachments **12 tests / 104 assertions passed**. No V1 implementation files or unrelated ORDO module files were changed during the Brevo adapter work in this continuation. |
| **Blocked - provider/staging** | No credentials were read, and no Brevo API/webhook was called or configured. Staging must verify existing key/sender configuration, secured Bearer webhook setup, provider delivery/replay/order semantics, encryption key provisioning/recovery, queue supervision and manual resend designation. Brevo message/tag/reference fields are correlation evidence only; no idempotency guarantee is claimed. |
| **Blocked - schema and recovery operations** | 14 schema candidates remain outside Laravel migration discovery. Historical disposable MySQL evidence covered an earlier 13-candidate set; current complete candidates need rehearsal against the authorized staging engine/schema. No scheduled recovery command or worker supervision is activated. The unsafe November 4 synchronization migration remains excluded. |
| **Repository / preservation** | Dirty integration tree and untracked V2 sources remain preserved. No production access, Git staging/commit/push/merge/reset/clean, migrations, real sends, Forge changes or deployment occurred. |
| **LOCAL RELEASE-CANDIDATE GO** | **NO for deployable release artifact**: local behavior/regressions are green, but all 14 schema files remain non-executable candidates and the complete current migration set has not been rehearsed against the target schema. |
| **STAGING GO** | **NO** - staging identity, migration/schema rehearsal, backup restore, deployment hook, queue operation and provider configuration are unverified. |
| **PRODUCTION GO** | **NOT AUTHORIZED** - no production access or deployment approval. |

### Final release-preparation tracker — 2026-11-13

| Status | Current evidence |
|---|---|
| **Verified — prior local evidence** | V2 **135 tests / 2,665 assertions**, V1 **26 / 222**, attachments **12 / 104**, outbox source contracts **118**; Brevo adapter/webhook exercised with mocks. This read-only audit did not rerun unchanged suites. |
| **Verified — local inventory** | HEAD is `3b3d99ae57c565069a1aa0b8c22e361898bca039` on `transmittal-v2-integration`; dirty tracked/untracked work preserved. The supplied production commit `b99ab03` and branch `release/deals-only-clean-20261109` are absent from local objects/refs; local production remotes still point at `3b3d99a`, so current production delta cannot be compared. Fourteen `.candidate` migrations remain outside Laravel discovery. The November 4 synchronization migration is absent from this checkout. No Forge deployment hook/workflow was found locally. V2/dispatch gates default off. |
| **Verified — V1 boundary** | NULL and exact `v1` classify as V1; V2 uses exact `v2`. Existing `items()`, receipt and attachment relationships remain; V2 workspace items are separate. No global scope or unrelated ORDO module change found in the modified file inventory. |
| **Release blocker — schema/code order** | V1 creation now writes `workflow_version='v1'`, while the nullable column remains candidate 11. Review/rehearse/apply that schema before deploying the writer. Do not backfill historical NULL rows. Candidate 3 and outbox candidates 12–14 require exact target-engine constraint/uniqueness rehearsal. |
| **Verified — local snapshot** | Prepared a temporary archive outside the repository containing the baseline diff, every untracked file and SHA256SUMS; no secret/generated paths were excluded. Exact temporary path/hash are recorded in the final session report. It is a review snapshot, not a signed or deployable artifact. |
| **Pending — staging/Forge evidence** | User-supplied Forge facts identify server/site/repository and MySQL 8.4/PHP 8.5, but the actual script and deployment failure are not locally available. Need complete redacted script, exact five Deals migration filenames/source, staging DB schema/history, database+shared-storage backup and restore evidence, migration rehearsal, key/webhook/queue configuration, and worker supervision. |
| **Deferred — optional features** | Smart Upload expected hash remains unchanged and unresolved; OCR runtime is blocked by `lexbor`. Keep both disabled; manual upload/download is the accepted release scope. |
| **LOCAL RELEASE CANDIDATE** | **NO-GO for deployable artifact** until the current production delta is reconciled, V2 candidates are executable/rehearsed, and workflow_version is safely installed before code. The previous preservation snapshot predates this proposed Forge draft. |
| **STAGING** | **NO-GO** pending exact script review, isolated restore/rehearsal, and staging configuration verification. |
| **PRODUCTION** | **NO-GO / NOT AUTHORIZED**. No production/Forge access, migration, send, worker activation, or deployment occurred. |
