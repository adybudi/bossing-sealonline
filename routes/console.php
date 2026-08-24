<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('start {--host=127.0.0.1} {--port=8001} {--daemon-port=3001}', function () {
    return $this->call(\App\Console\Commands\StartAllCommand::class, [
        '--host' => $this->option('host'),
        '--port' => $this->option('port'),
        '--daemon-port' => $this->option('daemon-port'),
    ]);
})->purpose('Jalankan seluruh ekosistem Seal Tracker (Laravel di Port 8001 + Node Daemon) sekaligus.');
