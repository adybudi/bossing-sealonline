<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class StartAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start 
                            {--host=127.0.0.1 : Host interface to bind} 
                            {--port=8001 : Port for Laravel Web Server} 
                            {--daemon-port=3001 : Port for Node.js WebSocket Daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jalankan seluruh ekosistem Seal Tracker (Laravel Web Server di Port 8001 + Node.js Bot Daemon) sekaligus dengan satu perintah.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $daemonPort = $this->option('daemon-port');

        $this->output->writeln('');
        $this->output->writeln('<fg=cyan;options=bold>====================================================================</>');
        $this->output->writeln('<fg=yellow;options=bold>⚔️  SEAL ONLINE BOSS TRACKER - ALL-IN-ONE RUNNER v2.0.0 ⚔️</>');
        $this->output->writeln('<fg=cyan;options=bold>====================================================================</>');
        $this->output->writeln("<fg=green>🌐 Laravel Web App      :</> <fg=white;options=bold>http://{$host}:{$port}</>");
        $this->output->writeln("<fg=green>🛡️  Admin Dashboard     :</> <fg=white;options=bold>http://{$host}:{$port}/admin</>");
        $this->output->writeln("<fg=green>⚡ Node Bot & WebSocket :</> <fg=white;options=bold>http://{$host}:{$daemonPort} & ws://{$host}:{$daemonPort}</>");
        $this->output->writeln('<fg=gray>Tekan CTRL+C kapan saja untuk menghentikan seluruh layanan secara bersih.</>');
        $this->output->writeln('<fg=cyan;options=bold>====================================================================</>');
        $this->output->writeln('');

        // 1. Inisialisasi Process Laravel Serve
        $laravelProcess = new Process([PHP_BINARY, 'artisan', 'serve', "--host={$host}", "--port={$port}"]);
        $laravelProcess->setTimeout(null);

        // 2. Inisialisasi Process Node.js Bot Daemon
        $nodeBin = $this->findNodeBinary();
        $daemonScript = base_path('daemon/bot-daemon.js');
        $daemonProcess = new Process(
            [$nodeBin, $daemonScript],
            base_path(),
            [
                'LARAVEL_API_URL' => "http://{$host}:{$port}",
                'WEBSOCKET_PORT' => $daemonPort,
            ]
        );
        $daemonProcess->setTimeout(null);

        // Start Laravel
        $laravelProcess->start(function ($type, $buffer) {
            $lines = explode("\n", trim($buffer));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $this->output->writeln("<fg=cyan>[Laravel]</> {$line}");
                }
            }
        });

        // Start Node.js Daemon
        $daemonProcess->start(function ($type, $buffer) {
            $lines = explode("\n", trim($buffer));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $this->output->writeln("<fg=green>[Bot-Daemon]</> {$line}");
                }
            }
        });

        // Handle Graceful Termination (CTRL+C)
        if (function_exists('pcntl_signal')) {
            declare(ticks = 1);
            $cleanup = function () use ($laravelProcess, $daemonProcess) {
                $this->output->writeln('');
                $this->output->writeln('<fg=yellow>⏹️  Menghentikan seluruh proses Seal Tracker...</>');
                $laravelProcess->stop(2);
                $daemonProcess->stop(2);
                $this->output->writeln('<fg=green>✓ Seluruh proses telah berhenti dengan aman.</>');
                exit(0);
            };

            pcntl_signal(SIGINT, $cleanup);
            pcntl_signal(SIGTERM, $cleanup);
        }

        // Loop monitoring
        while ($laravelProcess->isRunning() || $daemonProcess->isRunning()) {
            usleep(250000); // 250ms
        }

        return 0;
    }

    /**
     * Find node binary path
     */
    protected function findNodeBinary(): string
    {
        $commonPaths = [
            '/usr/local/bin/node',
            '/opt/homebrew/bin/node',
            '/usr/bin/node',
            'node',
        ];

        foreach ($commonPaths as $path) {
            if ($path === 'node' || file_exists($path)) {
                return $path;
            }
        }

        return 'node';
    }
}
