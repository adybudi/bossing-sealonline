const axios = require('axios');
const { execSync } = require('child_process');
const assert = require('assert');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8001';

async function testRefreshLockKickout() {
    console.log('🧪 ====================================================================');
    console.log('🧪 PENGUJIAN KETAT: HTTP REFRESH KICKOUT SAAT TOGGLE DIKUNCI');
    console.log('🧪 ====================================================================\n');

    // 1. Ambil kode master server
    const serverCode = execSync('php artisan tinker --execute="echo App\\Models\\SealServer::first()->access_code;"').toString().trim();
    console.log(`📌 Master Code Server: ${serverCode}`);

    // 2. Set mode to Public Free (require_access_code = false)
    console.log('\n1️⃣ Mode Publik Bebas: Pengguna membuka tracker server...');
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', false);"');
    
    const freeRes = await axios.get(`${BASE_URL}/tracker/${serverCode}`);
    assert.strictEqual(freeRes.status, 200, 'Saat mode bebas harus 200 OK');
    console.log('   ✓ Halaman tracker berhasil dimuat saat mode bebas.');

    // 3. Admin Mengunci Portal (require_access_code = true)
    console.log('\n2️⃣ Admin Mengunci Portal (require_access_code = true)...');
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', true);"');

    // 4. Pengguna Merefresh Halaman (F5) pada URL yang sama
    console.log('\n3️⃣ Pengguna menekan REFRESH (F5) pada URL tracker...');
    try {
        const refreshRes = await axios.get(`${BASE_URL}/tracker/${serverCode}`, {
            maxRedirects: 0,
            validateStatus: status => status === 302 || status === 200
        });

        assert.strictEqual(refreshRes.status, 302, 'HTTP request saat terkunci HARUS me-redirect (302) ke landing page!');
        assert(refreshRes.headers.location === `${BASE_URL}` || refreshRes.headers.location.endsWith('/'), 'Redirect harus menuju landing page!');
        console.log(`   🚨 [HTTP KICK] Pengguna langsung DITENDANG (302 Redirect ke Landing Page: ${refreshRes.headers.location})!`);
        console.log('   ✓ Celah refresh telah ditutup 100%! Pengguna tidak bisa masuk lagi tanpa kode lisensi.');
    } catch (e) {
        throw e;
    }

    // Kembalikan ke mode bebas (default)
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', false);"');

    console.log('\n====================================================================');
    console.log('🎉 PENGUJIAN HTTP REFRESH KICKOUT LULUS 100% (ZERO CELAH)!');
    console.log('====================================================================\n');
}

testRefreshLockKickout().catch(e => {
    console.error('❌ Test Failed:', e.message);
    process.exit(1);
});
