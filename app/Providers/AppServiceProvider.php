<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        $registerJsonUnquote = function ($connection) {
            if ($connection->getDriverName() === 'sqlite') {
                try {
                    $pdo = $connection->getPdo();
                    if (is_object($pdo) && method_exists($pdo, 'sqliteCreateFunction')) {
                        $pdo->sqliteCreateFunction('JSON_UNQUOTE', function ($val) {
                            if ($val === null) {
                                return null;
                            }
                            $s = (string) $val;
                            if (str_starts_with($s, '"') && str_ends_with($s, '"')) {
                                return substr($s, 1, -1);
                            }
                            return $s;
                        });
                    }
                } catch (\Throwable $e) {
                    // Ignore if not supported or during early bootstrap
                }
            }
        };

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Database\Events\ConnectionEstablished::class, function ($event) use ($registerJsonUnquote) {
            $registerJsonUnquote($event->connection);
        });

        try {
            $registerJsonUnquote(\Illuminate\Support\Facades\DB::connection());
        } catch (\Throwable $e) {
            // Ignore during early bootstrap
        }
    }
}