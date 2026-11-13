# PROPOSED ONLY: insert into Forge migration section before build and release activation.
# Requires all 14 reviewed migration files in database/migrations and a verified staging rehearsal.
# This fragment is not installed in Forge and must not be run against production without approval.

$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000001_add_transmittal_v2_columns.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000002_create_transmittal_recipients_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000004_create_transmittal_documents_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000005_create_proof_of_transmissions_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000006_create_approval_authority_codes_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000007_create_transmittal_histories_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000008_create_external_acknowledgments_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000009_create_transmittal_upload_sessions_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_09_000010_create_transmittal_workspace_items_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_11_000001_add_workflow_version_to_transmittals_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_12_000001_create_transmittal_email_dispatches_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php --force || exit 1
$FORGE_PHP artisan migrate --path=database/migrations/2026_11_12_000003_create_transmittal_provider_events_table.php --force || exit 1
