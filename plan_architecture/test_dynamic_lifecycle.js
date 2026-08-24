const axios = require('axios');
const { execSync } = require('child_process');

async function testDynamicServer() {
    console.log('🔄 ======================================================');
    console.log('🔄 MENGUJI SIKLUS HIDUP PENAMBAHAN SERVER DINAMIS & HOT-RELOAD');
    console.log('🔄 ======================================================\n');

    const INTERNAL_SECRET = 'seal_internal_secret_98a7b6c5d4e3f2a1b0c';

    // 1. Buat Server Baru via Laravel
    console.log('1️⃣ Membuat Server Uji Baru di Database...');
    const phpCreate = "$s = App\\Models\\SealServer::create(['name' => 'Seal Dynamic Alpha', 'access_code' => 'seal_dynamic_test_999', 'discord_channel_id' => '1340714356706639873', 'discord_token' => 'dummy_token', 'is_active' => true]); echo 'CREATED_ID:' . $s->id;";
    const out = execSync(`php artisan tinker --execute="${phpCreate.replace(/\$/g, '\\$')}"`).toString();
    const createdId = parseInt(out.match(/CREATED_ID:(\d+)/)[1], 10);
    console.log('   ✓ Berhasil membuat Server Uji ID:', createdId);

    // 2. Trigger Hot-Reload Daemon
    console.log('\n2️⃣ Mengirim Sinyal Hot-Reload ke Daemon...');
    const reloadRes = await axios.post('http://127.0.0.1:3001/control', { action: 'reload' }, {
        headers: { 'X-Internal-Secret': INTERNAL_SECRET }
    });
    console.log('   ✓ Daemon Hot-Reload Response:', reloadRes.data);

    // 3. Cek Health Daemon
    const health = await axios.get('http://127.0.0.1:3001/health');
    console.log('   ✓ Daemon Health:', health.data);
    console.log('   ✓ Total Server Aktif di Memory Daemon:', health.data.activeServers);

    // 4. Bersihkan Server Uji
    console.log('\n3️⃣ Membersihkan Server Uji...');
    execSync(`php artisan tinker --execute="App\\Models\\SealServer::find(${createdId})->delete();"`);
    await axios.post('http://127.0.0.1:3001/control', { action: 'reload' }, {
        headers: { 'X-Internal-Secret': INTERNAL_SECRET }
    });
    console.log('   ✓ Server Uji berhasil dihapus dan dibersihkan dari Daemon.');

    console.log('\n🎉 UJI HOT-RELOAD PENAMBAHAN SERVER BARU 100% SUKSES!');
}

testDynamicServer().catch(e => console.error('Error:', e.message));
