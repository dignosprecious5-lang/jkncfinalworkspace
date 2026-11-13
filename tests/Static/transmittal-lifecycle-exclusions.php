<?php

declare(strict_types=1);

// Source inspection only: no Composer/Laravel bootstrap, database or network.
require __DIR__.'/transmittal-workspace-items.php';
$priorChecks = $checks;
$controller = $read('app/Http/Controllers/TransmittalV2/TransmittalController.php');
$manifest = json_decode($read('patches/transmittal-v2/transmittal-v2-route-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$routes = array_column($manifest['routes'], null, 'name');

$check($manifest['status'] === 'inactive', 'Proposed routes remain inactive.');
$check(count($routes) === 32 && count($manifest['routes']) === 32, 'Exactly 32 unique proposed minimum-release endpoints remain.');
$check($manifest['endpoint_security_review']['reviewed_endpoint_count'] === 32, 'Manifest metadata matches remaining endpoint count.');
foreach (['destroy', 'restore', 'forceDelete'] as $action) {
    $check(! preg_match('/public\s+function\s+'.preg_quote($action, '/').'\s*\(/', $controller), $action.' has no active V2 handler.');
}
foreach (['transmittals.destroy', 'transmittals.restore', 'transmittals.force-delete'] as $name) {
    $check(! isset($routes[$name]), $name.' is excluded from proposed routes.');
    $check(in_array($name, $manifest['minimum_release_lifecycle']['excluded_route_names'], true), $name.' exclusion is explicitly documented.');
}
foreach ($manifest['routes'] as $route) {
    $check(! in_array($route['method'], ['DELETE'], true), $route['name'].' exposes no record-deletion endpoint.');
    $check(! preg_match('/@(destroy|restore|forceDelete)$/', $route['controller']), $route['name'].' targets a supported action.');
    [$class, $name] = explode('@', $route['controller'], 2);
    $file = 'app/'.str_replace('\\', '/', substr($class, 4)).'.php';
    $check(str_contains($read($file), 'function '.$name.'('), $route['name'].' has a remaining controller method.');
    $check(in_array('web', $route['middleware'], true), $route['name'].' retains web/CSRF middleware.');
    if (in_array($route['method'], ['POST', 'PUT', 'PATCH'], true)) {
        $check(count(array_filter($route['middleware'], static fn ($middleware): bool => str_starts_with($middleware, 'throttle:'))) > 0, $route['name'].' retains write throttling.');
    }
}
$index = $method($controller, 'index');
$check(! str_contains($index, "'deleted'") && ! str_contains($index, 'deletedCount'), 'Register accepts no deleted filter and computes no deleted count.');
$check(str_contains($index, "'archived' => ['cancelled']"), 'Archived filter remains a cancellation business state.');
$check(str_contains($index, "Rule::in(['all', 'uploaded', 'submitted', 'accepted', 'reverted', 'archived'])"), 'Unsupported filter is rejected by the explicit whitelist.');

$files = array_merge(
    glob($root.'/app/Http/Controllers/TransmittalV2/*.php'),
    glob($root.'/app/Services/TransmittalV2/*.php'),
    glob($root.'/resources/views/transmittals/*.blade.php'),
    glob($root.'/resources/views/transmittal-v2/acknowledgments/*.blade.php'),
    glob($root.'/resources/js/transmittal*.js')
);
foreach ($files as $file) {
    $source = file_get_contents($file);
    $label = str_replace($root.'/', '', str_replace('\\', '/', $file));
    $check(! preg_match('/\b(?:onlyTrashed|withTrashed|trashed|forceDelete|restore)\s*\(/', $source), $label.' has no unsupported lifecycle call.');
    $check(! str_contains($source, 'transmittal_lifecycle_archives'), $label.' has no lifecycle archive operation.');
    $check(! preg_match('/transmittals\.(?:destroy|restore|force-delete)/', $source), $label.' references no excluded record action.');
}
$check(! str_contains($controller, '$record->delete()') && ! str_contains($controller, '$transmittal->delete()'), 'Controller has no parent-record deletion.');
$check(! str_contains($controller, '$disk->delete('), 'Permanent-deletion file cleanup is absent.');

foreach (['cancel', 'recall', 'approve', 'reject', 'submit', 'submitForApproval', 'transmit', 'revokeRecipientLink'] as $name) {
    $body = $method($controller, $name);
    $check(str_contains($body, 'TransmittalWorkflowBoundary::'), $name.' retains workflow separation.');
    $check(! preg_match('/->(?:delete|restore|forceDelete)\s*\(/', $body), $name.' remains a non-deletion business action.');
}
foreach (['cancel', 'recall', 'approve', 'reject', 'submit-for-approval', 'transmit', 'recipients.revoke-link'] as $name) {
    $check(isset($routes['transmittals.'.$name]), $name.' remains in the proposed workflow.');
}
$cancel = $method($controller, 'cancel');
$check(str_contains($cancel, "'status' => 'cancelled'") && str_contains($cancel, "'workflow_status' => 'Archived'"), 'Cancellation changes business state rather than deleting records.');
$check(str_contains($cancel, '$record->histories()->create(') && str_contains($cancel, '$this->canManageLifecycle($request, $transmittal)'), 'Cancellation retains ownership and audit checks.');
$check(str_contains($method($controller, 'recall'), "'status' => 'draft'"), 'Recall retains its draft transition.');
$check(str_contains($method($controller, 'reject'), '$approval->reject('), 'Rejection retains approval-service authorization/state handling.');
$show = $read('resources/views/transmittals/show.blade.php');
$controls = $show.$read('resources/views/transmittals/_action-center.blade.php');
$check(str_contains($controls, "route('transmittals.cancel'") && str_contains($controls, "route('transmittals.recall'") && str_contains($controls, "route('transmittals.reject'"), 'Supported business controls remain.');
$view = $read('resources/views/transmittals/index.blade.php');
$check(! str_contains($view, "'deleted'") && ! str_contains($view, 'No deleted records found.'), 'Register has no deletion filter/display.');
$check(str_contains($view, "'archived' => 'Archived'") && str_contains($view, "$"."summary['cancelled']"), 'Cancellation archive display is preserved.');
$shared = $read('app/Models/Transmittal.php');
$check(! str_contains($shared, 'SoftDeletes') && ! str_contains($shared, 'addGlobalScope'), 'Shared V1 model acquires no deletion/global scope behavior.');
$check(str_contains($manifest['v2_controller_candidate']['source'], 'app/Http/Controllers/TransmittalV2/'), 'Minimum-release source points to repaired controller.');
$check(is_file($root.'/'.$manifest['v2_controller_candidate']['historical_reference_only']), 'Historical staged implementation file is preserved separately.');

// Validate embedded PHP syntax without compiling Blade or booting the application.
foreach (['index', 'show'] as $name) {
    $source = $read('resources/views/transmittals/'.$name.'.blade.php');
    preg_match_all('/@php\s*\n(.*?)@endphp/s', $source, $blocks);
    foreach ($blocks[1] as $block) {
        token_get_all('<?php '.$block, TOKEN_PARSE);
    }
    $check(true, $name.' embedded PHP blocks parse.');
    $check(preg_match_all('/@if\b/', $source) === preg_match_all('/@endif\b/', $source), $name.' keeps conditional directive pairs after control removal.');
}
echo 'PASS: '.($checks - $priorChecks).' lifecycle source checks; '.$checks." total across STEPS 184–186. No runtime/database acceptance.\n";
