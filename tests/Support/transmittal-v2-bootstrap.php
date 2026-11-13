<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || ! defined('TRANSMITTAL_V2_BOOTSTRAPPED')
    || TRANSMITTAL_V2_BOOTSTRAPPED !== true) {
    throw new RuntimeException('The V2 suite requires its dedicated isolated launcher.');
}

require_once __DIR__.'/TransmittalV2Isolation.php';
\Tests\Support\TransmittalV2Isolation::assertEnvironment();
