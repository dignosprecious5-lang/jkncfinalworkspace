<?php

declare(strict_types=1);

// Use C:\Server\php\php.exe with -c tests/Support/transmittal-v1.ini.
// Only the fixed suite may run; allow an explicit discovery-only mode.
$requestedArguments = array_slice($_SERVER['argv'] ?? [], 1);
if (PHP_SAPI !== 'cli'
    || ($_SERVER['argc'] ?? 0) !== count($requestedArguments) + 1
    || ! in_array($requestedArguments, [[], ['--list-tests']], true)) {
    throw new RuntimeException('The isolated V1 runner accepts only no arguments or --list-tests.');
}
$listTests = $requestedArguments === ['--list-tests'];
define('TRANSMITTAL_V1_LAUNCHER', true);
require __DIR__.'/transmittal-v1-bootstrap.php';
$root = dirname(__DIR__, 2);
$runner = $root.'/vendor/pestphp/pest/bin/pest';
if (! is_file($runner)) {
    throw new RuntimeException('The locked Pest dependency is missing.');
}
$argv = ['pest', '--configuration', $root.'/phpunit.transmittal-v1.xml'];
if ($listTests) {
    $argv[] = '--list-tests';
}
$argc = count($argv);
$_SERVER['argv'] = $GLOBALS['argv'] = $argv;
$_SERVER['argc'] = $GLOBALS['argc'] = $argc;
require $runner;
