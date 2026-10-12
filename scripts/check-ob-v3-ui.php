<?php

// Standalone regression check: php scripts/check-ob-v3-ui.php
$root = dirname(__DIR__);
$page = file_get_contents($root.'/resources/views/human-capital/obf.blade.php');
$form = file_get_contents($root.'/resources/views/human-capital/partials/obf-create-form.blade.php');
$preview = file_get_contents($root.'/resources/views/human-capital/partials/obf-create-preview.blade.php');

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
    echo "PASS: $message\n";
}

check(str_contains($form, 'Primary Traveler') && str_contains($form, 'preview.full_name || form.employee_name'), 'Primary Traveler name and label remain visible');
check(!str_contains($form, 'x-model="form.employee_id"'), 'V3 form has no Primary Traveler selector');
check(str_contains($page, 'this.form.employee_id = currentEmployee.id;') && str_contains($page, 'this.selectEmployee();'), 'new records assign the current employee');
check(str_contains($page, 'name="employee_id" :value="form.classification ? (form.employee_id ||'), 'V3 employee ID remains submitted');
check(str_contains($page, 'this.form = this.hydrateTrip(trip);') && str_contains($page, 'String(row.employee_id) !== String(trip.employee_id)'), 'record hydration and team exclusion remain intact');
check(str_contains($form, 'openTeamMemberModal()') && str_contains($form, 'teamDraft.employee_id'), 'team member controls remain available');

// Expand the only included preview partial, then inspect the recovered HTML DOM.
$markup = str_replace("@include('human-capital.partials.obf-create-preview')", $preview, $page);
$dom = new DOMDocument();
libxml_use_internal_errors(true);
check($dom->loadHTML($markup), 'preview markup parses into a DOM');
libxml_clear_errors();
$xpath = new DOMXPath($dom);

function visibilityWrapper(DOMNode $node): ?string
{
    for ($parent = $node->parentNode; $parent instanceof DOMElement; $parent = $parent->parentNode) {
        if ($parent->hasAttribute('x-show') && $parent->getAttribute('x-show') !== 'showPanel') {
            return $parent->getAttribute('x-show');
        }
    }
    return null;
}

function hasAncestorClass(DOMNode $node, string $class): bool
{
    for ($parent = $node->parentNode; $parent instanceof DOMElement; $parent = $parent->parentNode) {
        if (in_array($class, preg_split('/\s+/', $parent->getAttribute('class')), true)) {
            return true;
        }
    }
    return false;
}

$attachments = $xpath->query("//*[contains(@class, 'section-title') and normalize-space(.)='Attachments']");
check($attachments->length === 2, 'exactly two preview Attachments sections exist for V3 and legacy records');
$v3 = $attachments->item(0);
$legacy = $attachments->item(1);
check(visibilityWrapper($v3) === 'form.classification', 'V3 Attachments belongs to the classified preview');
check(visibilityWrapper($legacy) === '!form.classification && (isView || isEdit)', 'legacy Attachments stays inside its visibility wrapper');
$next = $legacy->nextSibling;
while ($next && !($next instanceof DOMElement)) {
    $next = $next->nextSibling;
}
check($next instanceof DOMElement && $next->tagName === 'table' && str_contains($next->textContent, 'Attachment Types:'), 'legacy Attachment Types table follows its heading');

$remarks = $xpath->query("//*[contains(@class, 'section-title') and normalize-space(.)='REMARKS']")->item(0);
check($remarks instanceof DOMElement && visibilityWrapper($remarks) === visibilityWrapper($legacy), 'legacy Remarks remains in the same wrapper');
check(hasAncestorClass($v3, 'print-area') && hasAncestorClass($legacy, 'print-area') && hasAncestorClass($remarks, 'print-area'), 'both preview paths and legacy Remarks remain printable');
check(str_contains($form, ':href="legacyFile.url"') && str_contains($form, ':href="attachment.url"'), 'existing V3 and legacy attachment links remain available');

function visible(string $expression, ?string $classification, bool $isView, bool $isEdit): bool
{
    return match ($expression) {
        'form.classification' => (bool) $classification,
        '!form.classification && (isView || isEdit)' => !$classification && ($isView || $isEdit),
        default => throw new RuntimeException("Unexpected preview visibility expression: $expression"),
    };
}

foreach (['client', 'internal', 'social'] as $classification) {
    check(visible(visibilityWrapper($v3), $classification, true, false)
        && !visible(visibilityWrapper($legacy), $classification, true, false), "$classification V3 record shows only V3 Attachments");
}
check(!visible(visibilityWrapper($v3), null, true, false)
    && visible(visibilityWrapper($legacy), null, true, false), 'null-classification legacy view shows legacy Attachments and Remarks');

echo "O.B. V3 UI regression checks passed.\n";
