<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$action = $argv[1] ?? 'seed';

if ($action === 'seed') {
    $server = App\Models\SealServer::first();
    $k7 = App\Models\ServerAccessKey::create([
        'seal_server_id' => $server->id,
        'code' => 'TEST-7D-' . rand(1000, 9999),
        'label' => 'Uji Coba 7 Hari',
        'duration_type' => '7_days',
        'duration_days' => 7,
        'activated_at' => now(),
        'expires_at' => now()->addDays(7),
        'is_active' => true
    ]);

    $kExp = App\Models\ServerAccessKey::create([
        'seal_server_id' => $server->id,
        'code' => 'TEST-EXP-' . rand(1000, 9999),
        'label' => 'Uji Coba Expired',
        'duration_type' => '7_days',
        'duration_days' => 7,
        'activated_at' => now()->subDays(8),
        'expires_at' => now()->subHour(),
        'is_active' => true
    ]);

    echo json_encode([
        'k7' => $k7->code,
        'kExp' => $kExp->code,
        'server_code' => $server->access_code
    ]);
} elseif ($action === 'gen_sample') {
    $server = App\Models\SealServer::first();
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $server->name), 0, 4)) ?: 'SEAL';
    $keys = [];
    for ($i = 0; $i < 5; $i++) {
        $keys[] = App\Models\ServerAccessKey::generateUniqueCode($prefix, '7_days');
    }
    echo json_encode($keys);
} elseif ($action === 'insert_one') {
    $code = $argv[2];
    $server = App\Models\SealServer::first();
    App\Models\ServerAccessKey::create([
        'seal_server_id' => $server->id,
        'code' => $code,
        'label' => 'Uji Coba Entropi Tinggi',
        'duration_type' => '7_days',
        'duration_days' => 7,
        'activated_at' => now(),
        'expires_at' => now()->addDays(7),
        'is_active' => true
    ]);
    echo "INSERTED";
} elseif ($action === 'clean') {
    $k7 = $argv[2] ?? '';
    $kExp = $argv[3] ?? '';
    App\Models\ServerAccessKey::where('code', $k7)->orWhere('code', $kExp)->delete();
    echo "CLEANED";
}
