<?php

declare(strict_types=1);

if (! defined('TRANSMITTAL_V1_LAUNCHER') || TRANSMITTAL_V1_LAUNCHER !== true) {
    throw new RuntimeException('Use the dedicated V1 PHP runner with its isolated ini; direct Pest/Artisan execution is refused.');
}
require_once __DIR__.'/TransmittalV1Isolation.php';
$root = dirname(__DIR__, 2);
\Tests\Support\TransmittalV1Isolation::prepare($root);
if (defined('TRANSMITTAL_V1_BOOTSTRAPPED')) {
    \Tests\Support\TransmittalV1Isolation::assertEnvironment();
    return;
}
$autoload = $root.'/vendor/autoload.php';
if (! is_file($autoload)) {
    throw new RuntimeException('V1 isolation passed, but locked test dependencies are not installed.');
}
$loader = require $autoload;
if (class_exists(\Tests\TestCase::class, false)) {
    throw new RuntimeException('Tests TestCase was loaded before the isolated bootstrap.');
}
// Process-local override only. Preserve the existing shared test files.
$loader->addClassMap(['Tests\\TestCase' => __DIR__.'/TransmittalV1TestCase.php']);
define('TRANSMITTAL_V1_BOOTSTRAPPED', true);
