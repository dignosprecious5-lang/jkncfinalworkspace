<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ! defined('TRANSMITTAL_ATTACHMENT_LAUNCHER')
    || TRANSMITTAL_ATTACHMENT_LAUNCHER !== true) {
    throw new RuntimeException('Attachment tests require their dedicated isolated launcher.');
}

if (! defined('TRANSMITTAL_V1_LAUNCHER')) {
    define('TRANSMITTAL_V1_LAUNCHER', true);
}

require_once __DIR__.'/transmittal-v1-bootstrap.php';

if (! defined('TRANSMITTAL_V1_BOOTSTRAPPED') || TRANSMITTAL_V1_BOOTSTRAPPED !== true) {
    throw new RuntimeException('The isolated V1 fixture bootstrap did not complete.');
}

define('TRANSMITTAL_ATTACHMENT_STAGE_A_TESTS', true);
