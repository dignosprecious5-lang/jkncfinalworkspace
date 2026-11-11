<?php

namespace Tests;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

class DealsMysqlIsolationProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app['config'];

        if (
            $config->get('app.env') !== 'testing' ||
            getenv('APP_KEY') === false ||
            $config->get('app.key') !== getenv('APP_KEY') ||
            $config->get('database.default') !== 'mysql' ||
            ! empty($config->get('database.connections.mysql.url')) ||
            ! empty($config->get('database.connections.mysql.unix_socket')) ||
            $config->get('database.connections.mysql.host') !== '127.0.0.1' ||
            (int) $config->get('database.connections.mysql.port') !== 13321 ||
            $config->get('database.connections.mysql.database') !== 'ordo_deals_regression_20261109_a1' ||
            $config->get('database.connections.mysql.username') !== 'ordo_deals_test_20261109_a1'
        ) {
            throw new RuntimeException('STOP: Deals regression database configuration is not the approved local test target.');
        }

        $config->set('queue.connections.null', ['driver' => 'null']);
        $config->set('queue.default', 'null');
        $config->set('queue.failed.driver', 'null');
        $config->set('mail.default', 'array');
        $config->set('broadcasting.default', 'null');
    }
}
