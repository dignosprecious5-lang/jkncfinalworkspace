<?php

declare(strict_types=1);

$arguments = array_slice($_SERVER['argv'] ?? [], 1);
if (PHP_SAPI !== 'cli' || $arguments !== [] || ($_SERVER['argc'] ?? 0) !== 1) {
    throw new RuntimeException('The attachment launcher accepts no arguments.');
}

define('TRANSMITTAL_ATTACHMENT_LAUNCHER', true);
require_once __DIR__.'/transmittal-attachment-bootstrap.php';

$root = dirname(__DIR__, 2);
if (realpath($root) !== realpath('C:/Users/Admin/Documents/original/jknc_transmittal_integration')
    || ! is_file($root.'/phpunit.transmittal-attachments.xml')) {
    throw new RuntimeException('The attachment suite root could not be verified.');
}

$runner = $root.'/vendor/phpunit/phpunit/phpunit';
if (! is_file($runner)) {
    throw new RuntimeException('The locked PHPUnit runner is missing.');
}

$argv = ['phpunit', '--configuration', $root.'/phpunit.transmittal-attachments.xml'];
$_SERVER['argv'] = $GLOBALS['argv'] = $argv;
$_SERVER['argc'] = $GLOBALS['argc'] = count($argv);
require $runner;
