<?php

$projectRoot = dirname(__DIR__);
require $projectRoot.'/vendor/autoload.php';

if (getenv('ORDO_DEALS_ISOLATED_TEST') !== '1' || getenv('APP_ENV') !== 'testing') {
    throw new RuntimeException('STOP: Explicit Deals test isolation markers are required.');
}

require_once __DIR__.'/DealsMysqlIsolationProvider.php';
\Illuminate\Foundation\Bootstrap\RegisterProviders::merge(
    [\Tests\DealsMysqlIsolationProvider::class],
    $projectRoot.'/bootstrap/providers.php'
);
