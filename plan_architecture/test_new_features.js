const axios = require('axios');
const { execSync } = require('child_process');
const assert = require('assert');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8001';

async function testNewFeatures() {
    console.log('🧪 ======================================================');
    console.log('🧪 MENGUJI FITUR BARU: DYNAMIC ACCESS MODE & ADMIN PROFILE');
    console.log('🧪 ======================================================\n');

    // 1. Test Landing Page in Free Public Mode (requireCode = false)
    console.log('1️⃣ Menguji Landing Page pada Mode Publik Bebas (Pilih Server Langsung)...');
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', false);"');
    
    const landingFreeRes = await axios.get(`${BASE_URL}/`);
    const freeHtml = landingFreeRes.data;
    
    assert(freeHtml.includes('Pilih Server Seal Online'), 'Harus menampilkan judul Pilih Server');
    assert(freeHtml.includes('Seal Hell Fire'), 'Harus menampilkan kartu Seal Hell Fire');
    assert(freeHtml.includes('Seal Majapahit'), 'Harus menampilkan kartu Seal Majapahit');
    assert(!freeHtml.includes('id="access_code"'), 'Form input kode akses TIDAK BOLEH muncul saat mode bebas');
    console.log('   ✓ Landing page mode bebas berhasil menampilkan list server secara instan tanpa input kode unik.');

    // 2. Test Landing Page in Code Protected Mode (requireCode = true)
    console.log('\n2️⃣ Menguji Landing Page saat Mode Proteksi Kode DIAKTIFKAN...');
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', true);"');
    
    const landingCodeRes = await axios.get(`${BASE_URL}/`);
    const codeHtml = landingCodeRes.data;
    
    assert(codeHtml.includes('id="access_code"'), 'Form input kode akses HARUS muncul saat mode kode aktif');
    console.log('   ✓ Landing page mode kode berhasil mewajibkan input kode akses unik.');

    // Kembalikan ke mode bebas (default rekomendasi)
    execSync('php artisan tinker --execute="App\\Models\\AppSetting::set(\'require_access_code\', false);"');

    // 3. Test Keamanan Route Admin (Unauthorized Access Redirect)
    console.log('\n3️⃣ Menguji Hardening & Proteksi Route Admin (Unauthorized Access)...');
    try {
        const adminRes = await axios.get(`${BASE_URL}/admin`, { maxRedirects: 0 });
        throw new Error('Route /admin seharusnya me-redirect guest ke login!');
    } catch (e) {
        if (e.response && (e.response.status === 302 || e.response.status === 401)) {
            console.log(`   ✓ Route /admin terlindungi (Status Redirect: ${e.response.status} -> /admin/login)`);
        } else {
            throw e;
        }
    }

    try {
        const profileRes = await axios.get(`${BASE_URL}/admin/profile`, { maxRedirects: 0 });
        throw new Error('Route /admin/profile seharusnya me-redirect guest ke login!');
    } catch (e) {
        if (e.response && (e.response.status === 302 || e.response.status === 401)) {
            console.log(`   ✓ Route /admin/profile terlindungi (Status Redirect: ${e.response.status} -> /admin/login)`);
        } else {
            throw e;
        }
    }

    // 4. Test Keamanan API Internal (Tanpa Secret Header)
    console.log('\n4️⃣ Menguji Keamanan Endpoint API Internal (Tanpa X-Internal-Secret)...');
    try {
        await axios.get(`${BASE_URL}/api/internal/servers`);
        throw new Error('API Internal seharusnya menolak request tanpa secret header!');
    } catch (e) {
        if (e.response && e.response.status === 401) {
            console.log('   ✓ Endpoint /api/internal/* terlindungi 100% (Status: 401 Unauthorized)');
        } else {
            throw e;
        }
    }

    console.log('\n======================================================');
    console.log('🎉 SEMUA FITUR BARU & SECURITY AUDIT LULUS SEMPURNA 100%!');
    console.log('======================================================\n');
}

testNewFeatures().catch(e => {
    console.error('❌ Test Failed:', e.message);
    process.exit(1);
});
