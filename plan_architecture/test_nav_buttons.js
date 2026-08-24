const axios = require('axios');
const { execSync } = require('child_process');
const assert = require('assert');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8001';

async function testNavButtons() {
    console.log('🧪 ====================================================================');
    console.log('🧪 PENGUJIAN TOMBOL NAVIGASI: PUBLIC (KEMBALI) VS PRIVATE (LOGOUT)');
    console.log('🧪 ====================================================================\n');

    // 1. Ambil kode master server
    const serverCode = execSync('php artisan tinker --execute="echo App\\Models\\SealServer::first()->access_code;"').toString().trim();
    
    // 2. Mode Public: require_access_code = false
    console.log('1️⃣ Menguji Tampilan Tracker pada Mode Publik...');
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', false);"');
    
    const publicRes = await axios.get(`${BASE_URL}/tracker/${serverCode}`);
    const publicHtml = publicRes.data;
    
    assert(publicHtml.includes('Kembali ke Halaman Utama'), 'Harus menampilkan tombol Kembali ke Halaman Utama saat Public');
    assert(!publicHtml.includes('fa-right-from-bracket'), 'TIDAK BOLEH menampilkan tombol Logout saat Public');
    console.log('   ✓ Mode Publik menampilkan tombol: [ <i class="fa-solid fa-house"></i> Kembali ke Halaman Utama ]');

    // 3. Mode Private: Pengguna dengan Lisensi Khusus Pembeli
    console.log('\n2️⃣ Menguji Tampilan Tracker pada Mode Private (Lisensi Pembeli)...');
    const outKey = 'VIP-NAV-TEST-7D';
    execSync(`php plan_architecture/seed_test_keys.php insert_one "${outKey}"`);

    const privateRes = await axios.get(`${BASE_URL}/tracker/${outKey}`);
    const privateHtml = privateRes.data;

    assert(privateHtml.includes('fa-right-from-bracket') && privateHtml.includes('Logout'), 'HARUS menampilkan tombol Logout saat Private');
    console.log('   ✓ Mode Private (Lisensi) menampilkan tombol: [ <i class="fa-solid fa-right-from-bracket"></i> Logout ]');

    // Cleanup
    execSync(`php plan_architecture/seed_test_keys.php clean "${outKey}"`);

    console.log('\n====================================================================');
    console.log('🎉 PENGUJIAN TOMBOL NAVIGASI LULUS SEMPURNA 100%!');
    console.log('====================================================================\n');
}

testNavButtons().catch(e => {
    console.error('❌ Test Failed:', e.message);
    process.exit(1);
});
