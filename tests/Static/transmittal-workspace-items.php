<?php

declare(strict_types=1);

// Reuse the STEP 184 source parser and its unchanged boundary acceptance checks.
// This reads source only. No autoload, Laravel, database, filesystem writes or network.
require __DIR__.'/transmittal-workflow-boundaries.php';
$boundaryChecks = $checks;
$controller = $read('app/Http/Controllers/TransmittalV2/TransmittalController.php');
$service = $read('app/Services/TransmittalWorkspaceItemSyncService.php');
$approval = $read('app/Services/TransmittalV2/TransmittalApprovalService.php');
$model = $read('app/Models/TransmittalWorkspaceItem.php');

$check(! preg_match('/\$(?:transmittal|record|locked|item)->items\b|[\x27\x22]items\.document[\x27\x22]/', $controller.$approval), 'V2 parent consumers never use legacy item relationships.');
$check(! str_contains($controller, 'TransmittalItem::') && ! str_contains($controller, 'associateLegacyItemDocumentsForPreview'), 'No legacy model writes or guessed preview associations.');
$check(str_contains($controller, '$paginator->items()') && ! str_contains($controller, '$paginator->workspaceItems()'), 'Paginator API remains unchanged.');
foreach (['createFromSmartUpload', 'store', 'update'] as $name) {
    $check(str_contains($method($controller, $name), '$this->syncItems('), $name.' uses central item adapter.');
}
$adapter = $method($controller, 'syncItems');
$check(str_contains($adapter, '$sync->synchronize($transmittal, [\'items\' => $items])'), 'Adapter delegates persistence to workspace sync service.');
$check(! str_contains($adapter, '->save(') && ! str_contains($adapter, '->delete(') && ! str_contains($adapter, '->create('), 'Adapter has no independent item writer/deleter.');
$check(strpos($adapter, "array_key_exists('items', $".'validated)') < strpos($adapter, 'requireV2('), 'Missing item payload returns before database work.');
$check(strpos($adapter, "is_array($"."validated['items'])") < strpos($adapter, 'requireV2('), 'Null/malformed item payload rejected before database work.');
$check(str_contains($adapter, "workspaceItems()->whereKey($"."data['id'])->lockForUpdate()->firstOrFail()"), 'Submitted item ID must belong to current workspace.');
$check(str_contains($adapter, "documents()->whereKey($"."data['document_id'])->lockForUpdate()->firstOrFail()"), 'Explicit document ID must belong to current parent.');
$check(str_contains($adapter, 'The selected uploaded document is unavailable.') && str_contains($adapter, 'The selected document references conflict.'), 'Invalid index or conflicting document references fail explicitly.');
$check(str_contains($adapter, 'assertChild($transmittal, $document)'), 'Uploaded document association repeats parent identity check.');
$check(! preg_match('/\(float\)|\(int\).*quantity|is_numeric\(|intval\(/', $adapter), 'Adapter never numerically coerces quantities.');
$check(substr_count($controller, "'items' => ['sometimes', 'array', 'max:50']") === 2, 'Both HTTP creation/form contracts reject null and cap the list.');
$smart = $method($controller, 'createFromSmartUpload');
$check(str_contains($smart, "if (! array_key_exists('items', $".'validated))'), 'New Smart Upload defaults are generated only for an omitted payload.');
$check(! str_contains($smart, "empty($"."itemData['quantity'])") && ! str_contains($smart, '->workspaceItems()->create('), 'Smart Upload neither loses zero quantities nor bypasses the service.');
$update = $method($controller, 'update');
$check(strpos($update, '->assertEditable($record)') < strpos($update, '$record->forceFill('), 'Frozen evidence checked before parent/document edits.');
$sync = $method($service, 'synchronize');
$check(strpos($sync, "array_key_exists('items', $".'payload)') < strpos($sync, 'DB::transaction('), 'Service omission guard precedes transaction.');
$check(strpos($sync, 'The items payload must be an array.') < strpos($sync, 'DB::transaction('), 'Service null rejection precedes transaction.');
$check(str_contains($sync, 'TransmittalWorkflowBoundary::v2Query()') && str_contains($sync, 'lockForUpdate()'), 'Service reloads classified parent under lock.');
$check(str_contains($sync, 'The item does not belong to this transmittal.') && str_contains($sync, 'The document does not belong to this transmittal.'), 'Service rechecks item/document ownership.');
$check(str_contains($sync, 'An item may be submitted only once.'), 'Duplicate item IDs fail.');
$check(str_contains($sync, "'quantity' => $"."this->optionalString($"."data, 'quantity', 40, $".'position)'), 'Quantity is persisted as bounded text.');
$check(str_contains($sync, '$item->save() === false') && str_contains($sync, '$item->getKey() === null'), 'Cancelled/unpersisted item saves cannot trigger list deletion.');
$check(! str_contains($service, '->items()') && ! str_contains($service, 'TransmittalItem::'), 'All service replacements/deletions affect only workspace items.');
$editable = $method($service, 'assertEditable');
foreach (['isV2(', 'approved_snapshot', 'approved_at', 'authorityCode()->exists()', 'proofs()->exists()', 'receipts()->exists()', 'acknowledgments()->exists()'] as $guard) {
    $check(str_contains($editable, $guard), 'Item editability protects '.$guard);
}
$check(str_contains($model, 'static::saving($guard)') && str_contains($model, 'static::deleting($guard)') && str_contains($model, '->assertEditable($parent)'), 'Direct workspace model mutations also guard retained evidence.');
$check(str_contains($approval, "'items' => $".'locked->workspaceItems->map') && str_contains($approval, "'line_number' => $".'item->line_number'), 'Approval snapshots use ordered workspace items and line numbers.');
$check(str_contains($approval, 'Frozen approval evidence cannot be replaced.') && str_contains($approval, 'assertChild($locked, $item)'), 'Approval validates item identity and retains frozen snapshots.');
$check(str_contains($approval, 'documents()->whereKey($item->document_id)->lockForUpdate()->firstOrFail()'), 'Approval validates linked documents under parent lock.');
$check(! preg_match('/(?:loadView|view)\(\x27transmittal\.(?:preview|preview-pdf|package-pdf|index)/', $controller), 'V2 item rendering never calls legacy item templates.');
foreach (['pdf', 'previewPdf', 'renderReceiptPdf', 'sendEmail'] as $name) {
    $body = $method($controller, $name);
    $check(str_contains($body, 'transmittals.pdf') || str_contains($body, '$this->pdf('), $name.' uses V2 PDF renderer.');
}
$check(! str_contains($method($controller, 'sendEmail'), '$item->attachment_path'), 'V2 item email attachments use private document associations.');
$check(! str_contains($method($controller, 'renderReceiptPdf'), '$item->attachment_path'), 'V2 item PDF paths never use legacy attachment_path.');
foreach (['_document-form', '_form-drawer', '_meta-summary-cards', 'show', 'workspace'] as $view) {
    $source = $read('resources/views/transmittals/'.$view.'.blade.php');
    $check(str_contains($source, '->workspaceItems') && ! preg_match('/->items\b/', $source), $view.' reads V2 workspace items.');
}
foreach (['_form-drawer', 'workspace'] as $view) {
    $source = $read('resources/views/transmittals/'.$view.'.blade.php');
    $check(str_contains($source, '][document_id]') && str_contains($source, '][line_number]'), $view.' retains item association/line metadata.');
    $check(str_contains($source, 'if (! is_array($formItems))'), $view.' retains saved items after malformed old input.');
    $check(! preg_match('/type="number"(?:(?!<input).){0,220}name="items\[[^\n]+\]\[quantity\]"/s', $source), $view.' accepts textual and decimal quantities.');
}
$document = $read('resources/views/transmittals/_document-form.blade.php');
$check(str_contains($document, "array_key_exists('items', $".'snapshot)') && strpos($document, "collect($"."snapshot['items'])") < strpos($document, 'elseif (is_array($formItemsInput))'), 'Approved item snapshots take precedence over old form input.');
$check(str_contains($document, "data_get($"."item, 'quantity') ?? ''"), 'PDF/document output retains zero quantity.');
$check(str_contains($read('resources/views/transmittals/pdf.blade.php'), "@include('transmittals._receipt-document'"), 'Receipt package uses V2 document and receipt partials.');

// Parse embedded PHP blocks without compiling or booting Laravel/Blade.
foreach (['_document-form', '_form-drawer', '_meta-summary-cards', 'show', 'workspace', 'smart-upload', 'pdf'] as $view) {
    $source = $read('resources/views/transmittals/'.$view.'.blade.php');
    preg_match_all('/@php\s*\n(.*?)@endphp/s', $source, $blocks);
    foreach ($blocks[1] as $block) {
        token_get_all('<?php '.$block, TOKEN_PARSE);
    }
    $check(true, $view.' embedded PHP blocks parse.');
}
echo 'PASS: '.($checks - $boundaryChecks).' workspace-item source checks; '.$checks." total with STEP 184. No runtime/database acceptance.\n";
