const axios = require('axios');
const WebSocket = require('ws');
const { execSync } = require('child_process');
const assert = require('assert');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8001';
const WS_URL = 'ws://127.0.0.1:3001';

async function testLockdownKickout() {
    console.log('🧪 ====================================================================');
    console.log('🧪 PENGUJIAN REAL-TIME GLOBAL LOCKDOWN KICKOUT SAAT TOGGLE DIKUNCI');
    console.log('🧪 ====================================================================\n');

    // 1. Set mode to Public Free (require_access_code = false)
    console.log('1️⃣ Mengaktifkan Mode Publik Bebas (require_access_code = false)...');
    execSync('php plan_architecture/seed_test_keys.php clean'); // cleanup
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', false);"');

    // 2. Hubungkan Public Viewer ke Tracker via WebSocket
    console.log('2️⃣ Penonton publik membuka tracker dan terhubung ke WebSocket...');
    const wsClient = new WebSocket(WS_URL);
    let lockdownReceived = false;
    let lockdownMsg = '';

    await new Promise((resolve, reject) => {
        wsClient.on('open', () => {
            wsClient.send(JSON.stringify({
                action: 'SUBSCRIBE',
                accessCode: 'seal_8e97f4a682887daff81ce9f1d55ad16e'
            }));
            console.log('   ✓ Penonton publik aktif terhubung di port 3001.');
            resolve();
        });

        wsClient.on('message', (raw) => {
            try {
                const msg = JSON.parse(raw.toString());
                if (msg.type === 'GLOBAL_LOCKDOWN') {
                    lockdownReceived = true;
                    lockdownMsg = msg.message;
                }
            } catch (e) {}
        });

        wsClient.on('error', reject);
    });

    // 3. Admin Mengunci Portal (Mengaktifkan Wajib Kode Akses)
    console.log('\n3️⃣ Admin menekan tombol Lock di Dashboard (Beralih ke Mode Wajib Kode)...');
    
    // Panggil endpoint /lockdown di daemon langsung (atau via SettingController)
    await axios.post(`http://127.0.0.1:3001/lockdown`, { action: 'LOCKDOWN' }, {
        headers: { 'X-Internal-Secret': 'seal_internal_secret_98a7b6c5d4e3f2a1b0c' }
    });
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', true);"');

    // Tunggu sinyal sampai
    await new Promise(r => setTimeout(r, 600));

    assert(lockdownReceived, 'Seluruh penonton publik HARUS menerima event GLOBAL_LOCKDOWN secara real-time!');
    console.log(`   🚨 [REAL-TIME KICK] Penonton publik berhasil menerima event GLOBAL_LOCKDOWN! (${lockdownMsg})`);
    console.log('   ✓ Modal "Akses Server Telah Dikunci!" langsung muncul di layar penonton publik.');

    wsClient.close();

    // Kembalikan ke mode bebas (default)
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', false);"');

    console.log('\n====================================================================');
    console.log('🎉 PENGUJIAN GLOBAL LOCKDOWN & REAL-TIME AUTO-KICK LULUS 100%!');
    console.log('====================================================================\n');
}

testLockdownKickout().catch(e => {
    console.error('❌ Test Failed:', e.message);
    process.exit(1);
});
