const axios = require('axios');
const assert = require('assert');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8001';

async function testSecurityLockdown() {
    console.log('🛡️ ====================================================================');
    console.log('🛡️ AUDIT KEAMANAN KETAT: ISOLASI ROUTE USER & PROTEKSI ADMIN');
    console.log('🛡️ ====================================================================\n');

    // 1. Uji Proteksi Admin Routes (Unauthenticated User Blocked)
    console.log('1️⃣ Menguji Proteksi Seluruh Route Admin dari Akses Publik Tanpa Login...');
    const protectedAdminUrls = [
        '/admin',
        '/admin/keys',
        '/admin/servers/create',
        '/admin/profile',
        '/admin/servers/1/edit',
        '/admin/servers/1/tracker',
        '/admin/servers/1/configs'
    ];

    for (const path of protectedAdminUrls) {
        const res = await axios.get(`${BASE_URL}${path}`, {
            maxRedirects: 0,
            validateStatus: s => s === 302 || s === 403 || s === 401
        });
        assert(res.status === 302 || res.status === 403 || res.status === 401, `Path ${path} harus ditolak untuk publik!`);
        console.log(`   ✓ ${path.padEnd(28)}: DITOLAK (Status: ${res.status}, Dialihkan ke login/landing)`);
    }

    // 2. Uji Proteksi Mutasi Data Admin (POST / PUT / DELETE)
    console.log('\n2️⃣ Menguji Penolakan Aksi Modifikasi Data (POST/DELETE) Tanpa Login...');
    const mutationTests = [
        { method: 'post', url: '/admin/servers' },
        { method: 'post', url: '/admin/keys' },
        { method: 'post', url: '/admin/settings/toggle-access-code' },
        { method: 'delete', url: '/admin/keys/999' }
    ];

    for (const t of mutationTests) {
        const res = await axios.request({
            method: t.method,
            url: `${BASE_URL}${t.url}`,
            maxRedirects: 0,
            validateStatus: s => [302, 401, 403, 404, 419, 405].includes(s)
        });
        assert([302, 401, 403, 404, 419, 405].includes(res.status), `Aksi ${t.method.toUpperCase()} ${t.url} harus diblokir!`);
        console.log(`   ✓ [${t.method.toUpperCase().padEnd(6)}] ${t.url.padEnd(36)}: DIBLOKIR TOTAL (Status: ${res.status})`);
    }

    // 3. Uji Proteksi Internal API Daemon (/api/internal/*)
    console.log('\n3️⃣ Menguji Proteksi Internal Daemon API Bridge...');
    const apiRes = await axios.get(`${BASE_URL}/api/internal/servers`, {
        validateStatus: s => s === 401 || s === 403
    });
    assert.strictEqual(apiRes.status, 401, 'Internal API tanpa secret header WAJIB 401 Unauthorized!');
    console.log('   ✓ /api/internal/servers       : DITOLAK (Status: 401 Unauthorized - Anti Tampering)');

    // 4. Uji Fallback Anti-Probing (Zero Information Leakage)
    console.log('\n4️⃣ Menguji Penanganan URL Acak / Probing (Anti-Scanning)...');
    const randomPaths = ['/wp-admin', '/phpmyadmin', '/api/secret-dump', '/admin/fake-exploit'];
    for (const rPath of randomPaths) {
        const res = await axios.get(`${BASE_URL}${rPath}`, {
            maxRedirects: 0,
            validateStatus: s => s === 302 || s === 404
        });
        assert(res.status === 302 || res.status === 404, 'URL tidak dikenal harus diarahkan kembali!');
        console.log(`   ✓ Probing ${rPath.padEnd(25)}: AMAN (Dialihkan ke Landing Page)`);
    }

    // 5. Uji Halaman Publik Bebas dari Bocoran Link Admin
    console.log('\n5️⃣ Menguji Bahwa Halaman Publik Bebas dari Tautan/Bocoran Admin...');
    const landingHtml = (await axios.get(`${BASE_URL}/`)).data;
    assert(!landingHtml.includes('/admin/login'), 'Landing page TIDAK BOLEH mengandung tautan login admin');
    assert(!landingHtml.includes('Admin Dashboard'), 'Landing page TIDAK BOLEH mengandung teks Admin Dashboard');
    console.log('   ✓ Halaman Landing Page bersih 100% dari tautan internal admin.');

    console.log('\n====================================================================');
    console.log('🎉 AUDIT KEAMANAN LENGKAP: 100% ROUTE USER TERISOLASI & TERLINDUNGI!');
    console.log('====================================================================\n');
}

testSecurityLockdown().catch(e => {
    console.error('❌ Test Failed:', e.message);
    process.exit(1);
});
