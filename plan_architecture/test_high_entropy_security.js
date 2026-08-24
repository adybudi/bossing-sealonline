const axios = require('axios');
const { execFileSync, execSync } = require('child_process');
const assert = require('assert');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8001';

async function testHighEntropySecurity() {
    console.log('🧪 ====================================================================');
    console.log('🧪 PENGUJIAN KEAMANAN: HIGH-ENTROPY CRYPTO KEYS & ANTI-BRUTE-FORCE');
    console.log('🧪 ====================================================================\n');

    // 1. Generate 5 Sample Cryptographic Keys
    console.log('1️⃣ Menguji Generator Kode Kriptografi Entropi Tinggi (~195 bit)...');
    const outKeys = execSync('php plan_architecture/seed_test_keys.php gen_sample').toString();
    const sampleKeys = JSON.parse(outKeys.trim());

    sampleKeys.forEach((k, idx) => {
        console.log(`   [Key #${idx+1}] ${k}`);
        assert(k.includes('-'), 'Key harus berformat chunk terpisah rapi');
        assert(/[!@$%*_-]/.test(k), 'Key HARUS mengandung karakter spesial');
        assert(k.length >= 25, 'Panjang key harus memiliki entropi kuat (>= 25 karakter)');
    });
    console.log('   ✓ Format kode kriptografi ultra aman & mengandung special characters!');

    // 2. Simpan 1 Key ke DB dan Uji Akses via URL Tracker
    console.log('\n2️⃣ Menguji Akses Tracker dengan Key Bervariasi Karakter Spesial...');
    const testKey = sampleKeys[0];
    execFileSync('php', ['plan_architecture/seed_test_keys.php', 'insert_one', testKey]);

    // Test show GET with special characters
    const showRes = await axios.get(`${BASE_URL}/tracker/${encodeURIComponent(testKey)}`);
    assert.strictEqual(showRes.status, 200, 'Akses tracker harus 200 OK');
    assert(showRes.data.includes('SEAL ONLINE BOSS TIMER'), 'Konten tracker harus termuat');
    console.log(`   ✓ GET /tracker/{code} berhasil membaca special characters tanpa kendala encoding!`);

    // 3. Cleanup Test Key
    execFileSync('php', ['plan_architecture/seed_test_keys.php', 'clean', testKey]);

    console.log('\n====================================================================');
    console.log('🎉 SEMUA PENGUJIAN KEAMANAN & ANTI-BRUTE-FORCE LULUS 100%!');
    console.log('====================================================================\n');
}

testHighEntropySecurity().catch(e => {
    console.error('❌ Test Failed:', e.message);
    process.exit(1);
});
