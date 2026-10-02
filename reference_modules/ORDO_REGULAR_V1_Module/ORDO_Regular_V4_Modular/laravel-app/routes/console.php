<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('ordo:about', function () {
    $this->info('ORDO Laravel regular workspace');
})->purpose('Display the ORDO application name');
