<?php

declare(strict_types=1);

// Source-only candidate review. This script never boots Laravel or connects to a database.
$root = dirname(__DIR__, 2);
$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (! $condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};
$read = static function (string $relative) use ($root, $check): string {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $source = file_get_contents($path);
    $check(is_string($source), "source exists: {$relative}");
    return $source;
};

$directory = $root.'/patches/transmittal-v2/migrations';
$files = glob($directory.'/*.php.candidate') ?: [];
$check(count($files) === 14, 'ten V2, one workflow-version, and three outbox/event candidates remain non-executable');
$candidateSources = [];
foreach ($files as $file) {
    $candidateSources[basename($file)] = file_get_contents($file);
    $check(is_string($candidateSources[basename($file)]), 'candidate source is readable: '.basename($file));
}
$all = implode("\n", $candidateSources);
$check(! str_contains($all, '2026_11_04_142800_sync_transmittal_v1_and_v2_columns'), 'dangerous synchronization migration is not a candidate dependency');
$check(! str_contains($all, 'transmittal_role'), 'no custom users.transmittal_role schema prerequisite');
$check(! str_contains($all, "Schema::table('transmittal_items'"), 'legacy V1 item schema is untouched');
$check(str_contains($all, "Schema::table('transmittal_receipts'"), 'V2 receipt additions target the existing receipt table');

$addColumns = $candidateSources['2026_11_09_000001_add_transmittal_v2_columns.php.candidate'] ?? '';
$check(str_contains($addColumns, "'operations_manager'") && str_contains($addColumns, "\$table->string('operations_manager', 160)->nullable()"), 'operations_manager is separately nullable V2 signatory text');
$check(! str_contains($addColumns, "'deleted_at'") && ! str_contains($addColumns, "\$table->timestamp('deleted_at')"), 'unsupported V2 deletion lifecycle has no schema field');
$check(str_contains($addColumns, "Schema::hasColumn('transmittals', \$column)"), 'existing proposed-column collision aborts before alterations');
$check(! preg_match('/\b(?:update|delete|insert|truncate)\s*\(/i', $addColumns), 'parent candidate has no historical row writes');
$check(! str_contains($addColumns, '->default('), 'nullable parent additions do not populate historical rows by default');
$check(str_contains($addColumns, 'Intentionally retain additive columns'), 'parent candidate rollback does not drop existing data');

$v1Parent = $read('database/migrations/2026_04_10_165905_create_transmittals_table.php');
$check(str_contains($v1Parent, "\$table->string('approved_by_name')->nullable()"), 'existing V1 written manager name remains in baseline');
$check(str_contains($v1Parent, "\$table->string('approved_position')->nullable()"), 'existing V1 written manager position remains in baseline');
$check(! str_contains($addColumns, "\$table->string('approved_by_name'") && ! str_contains($addColumns, "\$table->string('approved_position'"), 'V2 addition does not replace V1 signatory columns');
$check(! str_contains($v1Parent, "\$table->string('operations_manager'"), 'V2 signatory addition does not alter historical V1 migration');

$authorization = $read('app/Services/TransmittalV2Authorization.php');
$controller = $read('app/Http/Controllers/TransmittalV2/TransmittalController.php');
$check(str_contains($authorization, '$user->isAdmin()') && str_contains($authorization, '$user->isSuperAdmin()'), 'existing application administrator roles authorize V2 approval');
$check(! str_contains($authorization.$controller, 'transmittal_role'), 'V2 active authorization does not read a missing custom role column');
$check(str_contains($controller, "get(['id', 'name'])"), 'signatory selector reads only established user fields');
$check(str_contains($controller, "'operations_manager' => \$operationsManager"), 'V2 controller writes its separate manager signatory');
$check(str_contains($controller, "'approved_by_name' => \$this->resolveSignatoryName"), 'V2 continues to preserve existing written approval-name field semantics');

$user = $read('app/Models/User.php');
$check(str_contains($user, "'role'") && str_contains($user, 'function isAdmin()') && str_contains($user, 'function isSuperAdmin()'), 'existing User role APIs remain the authorization source');
$check(! str_contains($user, 'transmittal_role'), 'shared User model gains no custom role field');
$userPermission = $read('app/Models/UserPermission.php');
$rolePermission = $read('app/Models/RolePermission.php');
$check(str_contains($userPermission, "'access_transmittal'") && str_contains($rolePermission, "'access_transmittal'") && str_contains($user, 'function hasPermission('), 'existing module permission remains available without role grants');

$candidateNames = array_keys($candidateSources);
sort($candidateNames, SORT_STRING);
$expectedOrder = [
    '2026_11_09_000001_add_transmittal_v2_columns.php.candidate',
    '2026_11_09_000002_create_transmittal_recipients_table.php.candidate',
    '2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php.candidate',
    '2026_11_09_000004_create_transmittal_documents_table.php.candidate',
    '2026_11_09_000005_create_proof_of_transmissions_table.php.candidate',
    '2026_11_09_000006_create_approval_authority_codes_table.php.candidate',
    '2026_11_09_000007_create_transmittal_histories_table.php.candidate',
    '2026_11_09_000008_create_external_acknowledgments_table.php.candidate',
    '2026_11_09_000009_create_transmittal_upload_sessions_table.php.candidate',
    '2026_11_09_000010_create_transmittal_workspace_items_table.php.candidate',
    '2026_11_11_000001_add_workflow_version_to_transmittals_table.php.candidate',
    '2026_11_12_000001_create_transmittal_email_dispatches_table.php.candidate',
    '2026_11_12_000002_create_transmittal_email_dispatch_attempts_table.php.candidate',
    '2026_11_12_000003_create_transmittal_provider_events_table.php.candidate',
];
$check($candidateNames === $expectedOrder, 'candidate set retains the reviewed dependency ordering and appends outbox dependencies');

$expectedParents = [
    '000002' => ['transmittals'],
    '000003' => ['transmittal_recipients'],
    '000004' => ['transmittals', 'users'],
    '000005' => ['transmittal_recipients', 'transmittal_documents'],
    '000006' => ['transmittals', 'users'],
    '000007' => ['transmittals', 'users'],
    '000008' => ['transmittal_recipients', 'transmittal_receipts'],
    '000010' => ['transmittal_documents'],
];
foreach ($expectedParents as $suffix => $parents) {
    $candidate = '';
    foreach ($candidateSources as $name => $source) {
        if (str_contains($name, $suffix)) {
            $candidate = $source;
            break;
        }
    }
    foreach ($parents as $parent) {
        $check(str_contains($candidate, "Schema::hasTable('{$parent}')"), "{$suffix} checks dependency table {$parent}");
    }
}

foreach ($candidateSources as $name => $source) {
    $check(! str_contains($source, 'Schema::drop'), "{$name} has no destructive down operation");
    $check(! str_contains($source, 'DB::table'), "{$name} does not reclassify or backfill historic rows");
    $check(! str_contains($source, 'Storage::'), "{$name} does not mutate stored attachments");
    $check(! str_contains($source, 'cascadeOnDelete') && ! str_contains($source, 'nullOnDelete'), "{$name} has no cascading or nullifying foreign key");
    if (str_contains($source, '->constrained(')) {
        $check(str_contains($source, '->restrictOnDelete()'), "{$name} foreign keys preserve referenced records");
    }
}

$receipt = $candidateSources['2026_11_09_000003_add_v2_fields_to_transmittal_receipts_table.php.candidate'] ?? '';
$v1Receipt = $read('database/migrations/2026_04_10_173623_create_transmittal_receipts_table.php');
$check(str_contains($receipt, "\$table->foreignId('recipient_id')->nullable()->unique"), 'V2 recipient association is nullable and unique per receipt');
$check(str_contains($v1Receipt, "\$table->string('receipt_no')->unique()") && str_contains($receipt, 'deliberately untouched'), 'existing V1 receipt_no uniqueness is preserved');
$check(str_contains($receipt, "Schema::hasColumn('transmittal_receipts', \$column)"), 'receipt collision requires reconciliation before schema change');

$workspace = $candidateSources['2026_11_09_000010_create_transmittal_workspace_items_table.php.candidate'] ?? '';
$check(str_contains($workspace, "Schema::create('transmittal_workspace_items'"), 'V2 workspace items remain a separate table');
$check(str_contains($workspace, "\$table->foreignId('document_id')->nullable()"), 'workspace document association remains nullable');
$check(! str_contains($workspace, 'transmittal_items'), 'workspace candidate never rewrites V1 item records');

$workflowMigration = $read('patches/transmittal-v2/migrations/2026_11_11_000001_add_workflow_version_to_transmittals_table.php.candidate');
$check(! file_exists($root.'/database/migrations/2026_11_11_000001_add_workflow_version_to_transmittals_table.php'), 'workflow-version candidate is outside executable Laravel migration discovery');
$check(str_contains($workflowMigration, "\$table->string('workflow_version', 2)->nullable()"), 'separate classification migration is additive and nullable');
$check(! preg_match('/\b(?:update|delete|insert|truncate)\s*\(/i', $workflowMigration), 'classification migration has no historical backfill');
$check(str_contains($workflowMigration, 'Destructive rollback is prohibited'), 'classification rollback preserves its column and values');
$check(! str_contains($all, '2026_11_04_142800_sync_transmittal_v1_and_v2_columns'), 'older irreversible sync migration stays excluded');

fwrite(STDOUT, "PASS: {$checks} source-only schema contract checks. No Laravel bootstrap or database access.\n");
