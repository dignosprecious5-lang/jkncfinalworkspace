<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ! defined('TRANSMITTAL_OCR_MOCKED_LAUNCHER')
    || TRANSMITTAL_OCR_MOCKED_LAUNCHER !== true
    || realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) !== realpath(__DIR__.'/run-transmittal-ocr.php')) {
    throw new RuntimeException('Use only the dedicated mocked OCR launcher and INI.');
}
require_once __DIR__.'/TransmittalOcrIsolation.php';
\Tests\Support\TransmittalOcrIsolation::prepare(dirname(__DIR__, 2));
if (defined('TRANSMITTAL_OCR_MOCKED_BOOTSTRAPPED')) {
    \Tests\Support\TransmittalOcrIsolation::assertDouble();
    return;
}
// Do not replace, alias, unload, or patch an already loaded vendor class.
if (class_exists('Symfony\\Component\\Process\\Process', false)) {
    throw new RuntimeException('Symfony Process was loaded before OCR isolation; substitution refused.');
}
$loader = require dirname(__DIR__, 2).'/vendor/autoload.php';
if (class_exists('Symfony\\Component\\Process\\Process', false)) {
    throw new RuntimeException('Composer startup loaded Symfony Process; use a separately reviewed seam.');
}
$loader->addClassMap([
    'Symfony\\Component\\Process\\Process' => __DIR__.'/TransmittalOcrProcessDouble.php',
    'Tests\\Support\\TransmittalOcrTestCase' => __DIR__.'/TransmittalOcrTestCase.php',
    'Tests\\Support\\TransmittalOcrFixtures' => __DIR__.'/TransmittalOcrFixtures.php',
]);
require __DIR__.'/TransmittalOcrProcessDouble.php';
\Tests\Support\TransmittalOcrIsolation::assertDouble();
define('TRANSMITTAL_OCR_MOCKED_BOOTSTRAPPED', true);
