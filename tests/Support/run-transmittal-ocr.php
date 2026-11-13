<?php

declare(strict_types=1);

// Creation is not execution approval. Future approved runs must use the dedicated INI.
$arguments = array_slice($_SERVER['argv'] ?? [], 1);
if (PHP_SAPI !== 'cli' || ($_SERVER['argc'] ?? 0) !== count($arguments) + 1
    || ! in_array($arguments, [[], ['--list-tests']], true)) {
    throw new RuntimeException('The mocked OCR launcher accepts only no arguments or --list-tests.');
}
define('TRANSMITTAL_OCR_MOCKED_LAUNCHER', true);
require __DIR__.'/transmittal-ocr-bootstrap.php';
$root = dirname(__DIR__, 2);
$runner = $root.'/vendor/phpunit/phpunit/phpunit';
if (! is_file($runner)) {
    throw new RuntimeException('The installed PHPUnit runner is missing.');
}
$argv = ['phpunit', '--configuration', $root.'/phpunit.transmittal-ocr.xml'];
if ($arguments === ['--list-tests']) {
    $argv[] = '--list-tests';
}
$_SERVER['argv'] = $GLOBALS['argv'] = $argv;
$_SERVER['argc'] = $GLOBALS['argc'] = count($argv);
require $runner;
