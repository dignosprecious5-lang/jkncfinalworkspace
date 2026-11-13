<?php

declare(strict_types=1);

// Source inspection only: no Composer/Laravel bootstrap, database, storage or network.
$root = dirname(__DIR__, 2);
$checks = 0;
$check = static function (bool $condition, string $description) use (&$checks): void {
    if (! $condition) {
        throw new RuntimeException($description);
    }
    $checks++;
};
$read = static fn (string $file): string => str_replace("\r\n", "\n", file_get_contents($root.'/'.$file));
$method = static function (string $source, string $name): string {
    // Tokenization prevents braces in strings/comments from ending a method early.
    $tokens = token_get_all($source);
    $capture = false;
    $body = '';
    $depth = 0;
    foreach ($tokens as $index => $token) {
        if (! $capture && is_array($token) && $token[0] === T_FUNCTION) {
            $next = $index + 1;
            while (isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) {
                $next++;
            }
            $capture = isset($tokens[$next]) && is_array($tokens[$next]) && $tokens[$next][1] === $name;
        }
        if (! $capture) {
            continue;
        }
        $body .= is_array($token) ? $token[1] : $token;
        if ($token === '{' || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
            $depth++;
        } elseif ($token === '}' && --$depth === 0) {
            return $body;
        }
    }
    throw new RuntimeException('Method not found: '.$name);
};

$boundary = $read('app/Services/TransmittalWorkflowBoundary.php');
$check(str_contains($method($boundary, 'v2Query'), "where('transmittals.workflow_version', 'v2')"), 'V2 requires exact classification.');
$legacy = $method($boundary, 'v1Query');
$check(str_contains($legacy, "hasColumn('transmittals', 'workflow_version')"), 'Legacy reads preserve pre-classification-schema compatibility.');
$check(str_contains($legacy, "whereNull('transmittals.workflow_version')") && str_contains($legacy, "orWhere('transmittals.workflow_version', 'v1')"), 'V1 preserves NULL/v1 in one grouped predicate.');
$check(str_contains($method($boundary, 'isV2'), "getRawOriginal('workflow_version') === 'v2'") && str_contains($method($boundary, 'isV2'), '->exists'), 'Partial, forged and unsaved parents fail closed.');
$check(str_contains($method($boundary, 'requireV2'), 'self::v2Query()->findOrFail'), 'Bound parents are reloaded through scoped query.');
$check(str_contains($method($boundary, 'v2Children'), "whereHas('transmittal'"), 'Child lookups constrain parent workflow.');
$childWrite = $method($boundary, 'assertV2ChildWrite');
$check(str_contains($childWrite, 'self::v2Query()->whereKey($parentId)->exists()') && str_contains($childWrite, "getRawOriginal('transmittal_id')"), 'Child writes check persisted parent and forbid reassignment.');
$check(str_contains($childWrite, "where('transmittal_id', $".'parentId)') && str_contains($childWrite, "where('recipient_id'"), 'Document/recipient/receipt associations share parent and recipient.');

$controller = $read('app/Http/Controllers/TransmittalV2/TransmittalController.php');
$check(! preg_match('/Transmittal::(?!createForWorkflowVersion\b)\w+\(/', $controller), 'Every V2 parent query is scoped; creators remain server-classified.');
foreach (['workspace', 'edit', 'update', 'show', 'approve', 'reject', 'recall', 'cancel',
    'revokeRecipientLink', 'transmit', 'uploadProof', 'updateProof', 'download', 'pdf', 'receiptPdf',
    'submitForApproval', 'dashboardApprove', 'dashboardRevise', 'dashboardReject'] as $name) {
    $body = $method($controller, $name);
    $check(str_contains(substr($body, 0, strpos($body, '{') + 160), 'TransmittalWorkflowBoundary::requireV2($transmittal)'), $name.' checks workflow before access.');
}
foreach (['update', 'recall', 'cancel', 'revokeRecipientLink', 'transmit', 'uploadProof', 'updateProof'] as $name) {
    $body = $method($controller, $name);
    $check(str_contains($body, 'v2Query()->whereKey($transmittal->getKey())->lockForUpdate()->firstOrFail()'), $name.' repeats classification under lock.');
}
// STEP 186 excludes these unsupported actions instead of scoping deletion queries.
foreach (['destroy', 'restore', 'forceDelete'] as $name) {
    $check(! str_contains($controller, 'public function '.$name.'('), $name.' is excluded from the minimum V2 controller.');
}
foreach (['canEditDraft', 'canManageLifecycle', 'canViewPrivateFiles', 'canViewPrivateAttachments'] as $name) {
    $check(str_contains($method($controller, $name), 'TransmittalWorkflowBoundary::isV2'), $name.' denies non-V2 before ownership/token alternatives.');
}
$check(str_contains($method($controller, 'data'), '$this->visibleTransmittalQuery($request)'), 'Data endpoint preserves record visibility.');
$check(str_contains($method($controller, 'dashboard'), 'canApproveTransmittals($request->user())'), 'Administrative register retains role boundary.');
$check(str_contains($method($controller, 'recipientForToken'), 'v2Children(TransmittalRecipient::class)'), 'Recipient download token is V2-only.');
foreach (['streamDocument', 'renderReceiptPdf', 'verifyDocumentIntegrity'] as $name) {
    $check(str_contains($method($controller, $name), 'TransmittalWorkflowBoundary::assertChild'), $name.' checks parent/child identity.');
}
$check(str_contains($method($controller, 'storeDocument'), 'TransmittalWorkflowBoundary::requireV2'), 'Document persistence requires V2 parent.');
$check(str_contains($method($controller, 'indexRouteFor'), "return 'transmittals.index';"), 'V2 does not dispatch into V1 register.');

foreach (['TransmittalController.php', 'TransmittalUploadController.php', 'ExternalAcknowledgmentController.php'] as $file) {
    $source = $read('app/Http/Controllers/TransmittalV2/'.$file);
    $check(! preg_match('/Transmittal(?:Document|Recipient)::query\(/', $source), $file.' has no unscoped direct child lookup.');
}
$ack = $read('app/Http/Controllers/TransmittalV2/ExternalAcknowledgmentController.php');
$check(str_contains($method($ack, 'store'), 'TransmittalWorkflowBoundary::v2Query()') && str_contains($method($ack, 'store'), 'lockForUpdate()'), 'Acknowledgment rechecks parent under lock.');
$check(str_contains($method($ack, 'recipientFor'), 'workflow_version') && str_contains($method($ack, 'recipientFor'), 'assertChild'), 'Token eager parent includes classification and receipt identity.');
foreach (['app/Services/TransmittalV2/TransmittalApprovalService.php', 'app/Services/TransmittalWorkspaceItemSyncService.php'] as $file) {
    $source = $read($file);
    $check(str_contains($source, 'TransmittalWorkflowBoundary::requireV2') && str_contains($source, 'TransmittalWorkflowBoundary::v2Query()') && str_contains($source, 'lockForUpdate()'), $file.' rechecks V2 parent under lock.');
}
$brevo = $read('app/Services/TransmittalV2/BrevoTransmittalService.php');
foreach (['sendAcknowledgmentRequest', 'sendTransmittalDocument'] as $name) {
    $body = $method($brevo, $name);
    $check(strpos($body, 'TransmittalWorkflowBoundary::requireV2') < strpos($body, '$this->client('), $name.' checks parent before sending.');
}

$v1 = $read('app/Http/Controllers/TransmittalController.php');
$check(! preg_match('/Transmittal::(?!createForWorkflowVersion\b)\w+\(/', $v1), 'V1 parent reads/writes use legacy query.');
$check(str_contains($v1, "createForWorkflowVersion('v1'"), 'V1 creator classification is preserved.');
$check(str_contains($read('app/Http/Controllers/TransmittalReceiptController.php'), 'TransmittalWorkflowBoundary::v1Query()'), 'Legacy receipt route excludes V2 parent.');
$check(str_contains($read('app/Http/Controllers/CorporateApprovalController.php'), "'transmittal' => TransmittalWorkflowBoundary::v1Query()"), 'Legacy corporate decisions exclude V2.');
$policy = $read('app/Policies/TransmittalV2Policy.php');
foreach (['view', 'viewAttachments', 'approve', 'reject', 'transmit', 'uploadProof'] as $name) {
    $check(str_contains($method($policy, $name), 'TransmittalWorkflowBoundary::isV2'), $name.' policy has workflow boundary.');
}
foreach (['ApprovalAuthorityCode', 'ExternalAcknowledgment', 'ProofOfTransmission', 'TransmittalDocument',
    'TransmittalHistory', 'TransmittalRecipient', 'TransmittalWorkspaceItem'] as $model) {
    $source = $read('app/Models/'.$model.'.php');
    $check(str_contains($source, 'use RequiresTransmittalV2Parent;') && str_contains($method($source, 'transmittal'), "where('transmittals.workflow_version', 'v2')"), $model.' guards writes and parent relationship.');
    $check(! str_contains($source, "where('transmittal_id', $".'this->transmittal_id)'), $model.' does not introduce instance-bound eager-load predicates.');
}
foreach (['Transmittal', 'TransmittalItem', 'TransmittalReceipt'] as $model) {
    $source = $read('app/Models/'.$model.'.php');
    $check(! str_contains($source, 'RequiresTransmittalV2Parent') && ! str_contains($source, 'addGlobalScope'), $model.' keeps legacy model behavior.');
}
$check(str_contains($read('routes/web.php'), "require __DIR__.'/transmittal-v2.php';"), 'V2 route file is included independently of V1 routes.');
$check(str_contains($read('routes/transmittal-v2.php'), 'EnsureTransmittalV2Enabled::class'), 'V2 routes are behind the default-disabled gate.');
$check(str_contains($read('bootstrap/providers.php'), 'TransmittalV2AuthorizationServiceProvider'), 'Namespaced V2 abilities are registered.');
$check(str_contains($read('config/transmittal_v2.php'), "env('ORDO_TRANSMITTAL_V2_ENABLED', false)"), 'Feature remains default-disabled.');
echo 'PASS: '.$checks." source-only boundary checks. No Laravel/database/runtime acceptance.\n";
