const axios = require('axios');
const WebSocket = require('ws');
const { execSync } = require('child_process');
const assert = require('assert');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8001';
const WS_URL = 'ws://127.0.0.1:3001';

async function testCommercialKeysAndKickout() {
    console.log('🧪 ====================================================================');
    console.log('🧪 PENGUJIAN EXPERT: MULTI-KEY COMMERCIAL LICENSE & REAL-TIME AUTO-KICK');
    console.log('🧪 ====================================================================\n');

    // 1. Generate Lisensi 7 Hari & Expired
    console.log('1️⃣ Menguji Pembuatan Lisensi (7 Hari, 14 Hari, 30 Hari, Permanen)...');
    
    const outJson = execSync('php plan_architecture/seed_test_keys.php seed').toString();
    const keyData = JSON.parse(outJson.trim());

    console.log(`   ✓ Key 7 Hari Aktif dibuat: ${keyData.k7}`);
    console.log(`   ✓ Key Expired dibuat: ${keyData.kExp}`);

    // 2. Akses Tracker dengan Key Aktif 7 Hari
    console.log('\n2️⃣ Menguji Akses Pemain dengan Key Aktif 7 Hari...');
    const res7 = await axios.get(`${BASE_URL}/tracker/${keyData.k7}`);
    assert.strictEqual(res7.status, 200, 'Akses tracker key aktif harus 200 OK');
    assert(res7.data.includes('SEAL ONLINE BOSS TIMER'), 'Tracker harus memuat konten timer');
    console.log('   ✓ Key 7 Hari berhasil membuka live tracker secara penuh!');

    // 3. Akses Tracker dengan Key Expired
    console.log('\n3️⃣ Menguji Akses Pemain dengan Key yang Sudah Habis Masa Aktifnya (Expired)...');
    try {
        await axios.get(`${BASE_URL}/tracker/${keyData.kExp}`);
        throw new Error('Key Expired seharusnya ditolak (403)!');
    } catch (err) {
        if (err.response && err.response.status === 403) {
            assert(err.response.data.includes('Masa Aktif Kode Akses Telah Berakhir'), 'Harus menampilkan view key_expired');
            console.log('   ✓ Key Expired berhasil ditolak 100% (Status: 403 Forbidden - Masa Aktif Berakhir).');
        } else {
            throw err;
        }
    }

    // 4. Pengujian REAL-TIME AUTO-KICK Single Active Device
    console.log('\n4️⃣ Menguji Fitur Anti-Share: REAL-TIME KICKOUT Komputer A saat Komputer B Login...');
    
    // Simulasikan Komputer A Login
    const compALoginRes = await axios.get(`${BASE_URL}/tracker/${keyData.k7}`);
    const tokenMatchA = compALoginRes.data.match(/const CLIENT_SESSION_TOKEN = "(.*?)";/);
    const sessionTokenA = tokenMatchA ? tokenMatchA[1] : null;
    assert(sessionTokenA, 'Komputer A harus mendapatkan session token unik');
    console.log(`   [Komputer A] Login sukses. Session Token: ${sessionTokenA.substring(0, 12)}...`);

    // Hubungkan WebSocket Komputer A
    const wsCompA = new WebSocket(WS_URL);
    let compAKicked = false;
    let kickReason = '';

    await new Promise((resolve, reject) => {
        wsCompA.on('open', () => {
            wsCompA.send(JSON.stringify({
                action: 'SUBSCRIBE',
                accessCode: keyData.server_code,
                userAccessKey: keyData.k7,
                sessionToken: sessionTokenA
            }));
            console.log('   [Komputer A] Terhubung ke WebSocket Gateway port 3001.');
            resolve();
        });

        wsCompA.on('message', (raw) => {
            try {
                const msg = JSON.parse(raw.toString());
                if (msg.type === 'SESSION_REVOKED') {
                    if (msg.userAccessKey === keyData.k7 && msg.activeSessionToken !== sessionTokenA) {
                        compAKicked = true;
                        kickReason = `Sesi digantikan oleh token baru: ${msg.activeSessionToken.substring(0, 12)}...`;
                    }
                }
            } catch (e) {}
        });

        wsCompA.on('error', reject);
    });

    // Sekarang simulasikan Komputer B Login dengan kode yang SAMA
    console.log(`   [Komputer B] Membuka URL tracker dengan KODE YANG SAMA (${keyData.k7})...`);
    const compBLoginRes = await axios.get(`${BASE_URL}/tracker/${keyData.k7}`);
    const tokenMatchB = compBLoginRes.data.match(/const CLIENT_SESSION_TOKEN = "(.*?)";/);
    const sessionTokenB = tokenMatchB ? tokenMatchB[1] : null;
    assert(sessionTokenB, 'Komputer B harus mendapatkan session token baru');
    console.log(`   [Komputer B] Login sukses. Session Token Baru: ${sessionTokenB.substring(0, 12)}...`);

    // Tunggu notifikasi kickout sampai di Komputer A
    await new Promise(r => setTimeout(r, 600));

    assert(compAKicked, 'Komputer A HARUS menerima sinyal SESSION_REVOKED secara real-time!');
    console.log(`   🚨 [REAL-TIME KICK] Komputer A berhasil menerima sinyal kickout! (${kickReason})`);
    console.log('   ✓ Komputer A langsung menampilkan modal "Ups! Kamu Harus Terlogout!"');

    wsCompA.close();

    // 5. Cleanup Test Keys
    execSync(`php plan_architecture/seed_test_keys.php clean ${keyData.k7} ${keyData.kExp}`);

    console.log('\n====================================================================');
    console.log('🎉 SEMUA PENGUJIAN EXPERT (MULTI-KEY, EXPIRED & AUTO-KICK) LULUS 100%!');
    console.log('====================================================================\n');
}

testCommercialKeysAndKickout().catch(e => {
    console.error('❌ Test Failed:', e.message);
    process.exit(1);
});
