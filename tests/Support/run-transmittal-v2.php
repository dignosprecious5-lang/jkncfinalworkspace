<?php

declare(strict_types=1);

$arguments = array_slice($_SERVER['argv'] ?? [], 1);
if (PHP_SAPI !== 'cli' || ($_SERVER['argc'] ?? 0) !== count($arguments) + 1
    || ! in_array($arguments, [[], ['--list-tests']], true)) {
    throw new RuntimeException('The V2 launcher accepts only no arguments or --list-tests.');
}

define('TRANSMITTAL_V2_LAUNCHER', true);
require_once __DIR__.'/TransmittalV2Isolation.php';

$root = dirname(__DIR__, 2);
\Tests\Support\TransmittalV2Isolation::prepare($root);

$autoload = $root.'/vendor/autoload.php';
if (! is_file($autoload)) {
    throw new RuntimeException('The V2 isolated test dependencies are missing.');
}
$loader = require $autoload;
if (! is_object($loader) || ! method_exists($loader, 'addClassMap')) {
    throw new RuntimeException('Composer autoload could not be verified.');
}
$loader->addClassMap([
    'Tests\\TestCase' => __DIR__.'/TransmittalV2TestCase.php',
]);
define('TRANSMITTAL_V2_BOOTSTRAPPED', true);

$runner = $root.'/vendor/phpunit/phpunit/phpunit';
if (! is_file($runner)) {
    throw new RuntimeException('The installed PHPUnit runner is missing.');
}

$argv = ['phpunit', '--configuration', $root.'/phpunit.transmittal-v2.xml'];
if ($arguments === ['--list-tests']) {
    $argv[] = '--list-tests';
}
$_SERVER['argv'] = $GLOBALS['argv'] = $argv;
$_SERVER['argc'] = $GLOBALS['argc'] = count($argv);
require $runner;
