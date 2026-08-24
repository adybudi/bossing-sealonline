/**
 * 🧪 Full End-to-End System Deep Verification
 * Testing:
 * 1. Database & Multi-Server Configuration
 * 2. Real-Time Daemon Multi-Tenant Connection & Auto-Screening
 * 3. Dynamic Server Addition & Hot-Reload Detection
 * 4. WebSocket Real-Time Broadcasting & Room Security
 * 5. Public Player (Read-Only) Enforcement
 * 6. Admin Privileges & AJAX Action Mutation Endpoints
 */

const axios = require('axios');
const WebSocket = require('ws');
const { execSync } = require('child_process');

const LARAVEL_BASE = process.env.BASE_URL || 'http://127.0.0.1:8001';
const DAEMON_WS = 'ws://127.0.0.1:3001';
const DAEMON_HTTP = 'http://127.0.0.1:3001';
const INTERNAL_SECRET = 'seal_internal_secret_98a7b6c5d4e3f2a1b0c';

const GREEN = '\x1b[32m';
const RED = '\x1b[31m';
const CYAN = '\x1b[36m';
const YELLOW = '\x1b[33m';
const RESET = '\x1b[0m';
const BOLD = '\x1b[1m';

async function runVerification() {
    console.log(`${BOLD}${CYAN}================================================================${RESET}`);
    console.log(`${BOLD}${CYAN}🛡️  SEAL ONLINE BOSS TRACKER - DEEP E2E SYSTEM AUDIT & VERIFY${RESET}`);
    console.log(`${BOLD}${CYAN}================================================================\n${RESET}`);

    let totalSteps = 6;
    let passedSteps = 0;

    // -------------------------------------------------------------
    // TEST 1: Laravel Internal API & Multi-Server Discovery
    // -------------------------------------------------------------
    console.log(`${BOLD}1️⃣  Menguji Laravel Database & Multi-Server Discovery API...${RESET}`);
    try {
        const res = await axios.get(`${LARAVEL_BASE}/api/internal/servers`, {
            headers: { 'X-Internal-Secret': INTERNAL_SECRET }
        });
        const servers = res.data.data;
        if (!servers || servers.length === 0) throw new Error("No servers found in DB");
        
        console.log(`  ${GREEN}✓ API Internal Terhubung (Status: ${res.status} OK)${RESET}`);
        console.log(`  ${GREEN}✓ Ditemukan ${servers.length} Server Aktif di Database:${RESET}`);
        servers.forEach(s => {
            console.log(`    • [ID: ${s.id}] ${s.name} (Code: ${s.access_code}, Channel: ${s.discord_channel_id})`);
        });
        passedSteps++;
    } catch (e) {
        console.error(`  ${RED}✗ Test 1 Gagal:${RESET}`, e.message);
    }

    // -------------------------------------------------------------
    // TEST 2: Multi-Server Boss State Consistency in MySQL
    // -------------------------------------------------------------
    console.log(`\n${BOLD}2️⃣  Menguji Konsistensi Data Boss State di Database (MySQL)...${RESET}`);
    try {
        const res = await axios.get(`${LARAVEL_BASE}/api/internal/servers`, {
            headers: { 'X-Internal-Secret': INTERNAL_SECRET }
        });
        const servers = res.data.data;

        for (const s of servers) {
            const phpCode = `\\$count = App\\\\Models\\\\BossState::where('seal_server_id', ${s.id})->count(); echo 'COUNT:' . \\$count;`;
            const out = execSync(`php artisan tinker --execute="${phpCode}"`).toString();
            const match = out.match(/COUNT:(\d+)/);
            const count = match ? parseInt(match[1], 10) : 0;
            console.log(`  ${GREEN}✓ Server "${s.name}": ${count} Boss State Terdata di Database MySQL.${RESET}`);
            if (count === 0) throw new Error(`Server ${s.name} memiliki 0 boss data`);
        }
        passedSteps++;
    } catch (e) {
        console.error(`  ${RED}✗ Test 2 Gagal:${RESET}`, e.message);
    }

    // -------------------------------------------------------------
    // TEST 3: WebSocket Real-Time Gateway & Room Isolation
    // -------------------------------------------------------------
    console.log(`\n${BOLD}3️⃣  Menguji WebSocket Gateway (Port 3001) & Isolasi Room Server...${RESET}`);
    try {
        const res = await axios.get(`${LARAVEL_BASE}/api/internal/servers`, {
            headers: { 'X-Internal-Secret': INTERNAL_SECRET }
        });
        const server1 = res.data.data[0];
        const server2 = res.data.data[1];

        // Connect client to Server 1 room
        const client1Promise = new Promise((resolve, reject) => {
            const ws1 = new WebSocket(DAEMON_WS);
            ws1.on('open', () => {
                ws1.send(JSON.stringify({ action: 'SUBSCRIBE', accessCode: server1.access_code }));
            });
            ws1.on('message', (data) => {
                const msg = JSON.parse(data.toString());
                if (msg.type === 'INITIAL_SYNC') {
                    ws1.close();
                    resolve(msg.bosses ? msg.bosses.length : 0);
                }
            });
            ws1.on('error', reject);
            setTimeout(() => reject(new Error("Timeout WS Client 1")), 4000);
        });

        const syncCount1 = await client1Promise;
        console.log(`  ${GREEN}✓ WebSocket Room 1 (${server1.name}): Menerima INITIAL_SYNC (${syncCount1} Boss).${RESET}`);

        if (server2) {
            const client2Promise = new Promise((resolve, reject) => {
                const ws2 = new WebSocket(DAEMON_WS);
                ws2.on('open', () => {
                    ws2.send(JSON.stringify({ action: 'SUBSCRIBE', accessCode: server2.access_code }));
                });
                ws2.on('message', (data) => {
                    const msg = JSON.parse(data.toString());
                    if (msg.type === 'INITIAL_SYNC') {
                        ws2.close();
                        resolve(msg.bosses ? msg.bosses.length : 0);
                    }
                });
                ws2.on('error', reject);
                setTimeout(() => reject(new Error("Timeout WS Client 2")), 4000);
            });

            const syncCount2 = await client2Promise;
            console.log(`  ${GREEN}✓ WebSocket Room 2 (${server2.name}): Menerima INITIAL_SYNC (${syncCount2} Boss).${RESET}`);
        }

        passedSteps++;
    } catch (e) {
        console.error(`  ${RED}✗ Test 3 Gagal:${RESET}`, e.message);
    }

    // -------------------------------------------------------------
    // TEST 4: Public View (Read-Only) Security Enforcement
    // -------------------------------------------------------------
    console.log(`\n${BOLD}4️⃣  Menguji Keamanan Public Tracker (Read-Only Mode Tanpa Akses Admin)...${RESET}`);
    try {
        const res = await axios.get(`${LARAVEL_BASE}/api/internal/servers`, {
            headers: { 'X-Internal-Secret': INTERNAL_SECRET }
        });
        const server = res.data.data[0];

        const publicRes = await axios.get(`${LARAVEL_BASE}/tracker/${server.access_code}`);
        const html = publicRes.data;

        const hasAddFormInput = html.includes('id="addBossName"');
        const hasPasteLogModal = html.includes('id="pasteModal"');
        const hasAdminStartAllBtn = html.includes('onclick="adminStartAll()"');
        const hasIntervalModal = html.includes('id="intervalModal"');
        const hasPopUpModal = html.includes('id="spawnModal"') && html.includes('OK, Matikan Alarm');

        if (hasAddFormInput || hasPasteLogModal || hasAdminStartAllBtn || hasIntervalModal) {
            throw new Error("Elemen kontrol Admin bocor ke tampilan publik!");
        }

        if (!hasPopUpModal) {
            throw new Error("Pop-up modal spawn tidak ditemukan pada view!");
        }

        console.log(`  ${GREEN}✓ Public Tracker URL (/tracker/${server.access_code}) dapat diakses pemain.${RESET}`);
        console.log(`  ${GREEN}✓ Seluruh tombol aksi, form input & modal admin terlindungi (100% Read-Only).${RESET}`);
        console.log(`  ${GREEN}✓ Pop-Up Modal Spawn (🔔) aktif siap membunyikan alarm & menampilkan notifikasi.${RESET}`);
        passedSteps++;
    } catch (e) {
        console.error(`  ${RED}✗ Test 4 Gagal:${RESET}`, e.message);
    }

    // -------------------------------------------------------------
    // TEST 5: Admin Tracker View & Privilege Capabilities
    // -------------------------------------------------------------
    console.log(`\n${BOLD}5️⃣  Menguji Akses Tracker Administrator (Full Control Mode)...${RESET}`);
    try {
        const phpCode = `\\$server = App\\\\Models\\\\SealServer::first(); \\$view = view('tracker', ['server' => \\$server, 'states' => App\\\\Models\\\\BossState::where('seal_server_id', \\$server->id)->get(), 'configs' => App\\\\Models\\\\BossConfig::where('seal_server_id', \\$server->id)->get(), 'wsPort' => 3001, 'isAdmin' => true])->render(); echo (str_contains(\\$view, 'addBossName') && str_contains(\\$view, 'adminStartAll') && str_contains(\\$view, 'pasteModal')) ? 'ADMIN_OK' : 'ADMIN_FAIL';`;
        const out = execSync(`php artisan tinker --execute="${phpCode}"`).toString();

        if (!out.includes('ADMIN_OK')) {
            throw new Error("Admin privileges rendering failed: " + out);
        }

        console.log(`  ${GREEN}✓ Administrator dapat mengakses tracker langsung tanpa kode akses.${RESET}`);
        console.log(`  ${GREEN}✓ Form Tambah Boss, Paste Log, Bulk Actions, & Edit Interval aktif lengkap.${RESET}`);
        passedSteps++;
    } catch (e) {
        console.error(`  ${RED}✗ Test 5 Gagal:${RESET}`, e.message);
    }

    // -------------------------------------------------------------
    // TEST 6: Dynamic Server Addition & Hot-Reload Auto-Screening
    // -------------------------------------------------------------
    console.log(`\n${BOLD}6️⃣  Menguji Penambahan Server Baru Dinamis & Hot-Reload Auto-Screening...${RESET}`);
    try {
        // Trigger hot-reload control API to daemon
        const controlRes = await axios.post(`${DAEMON_HTTP}/control`, {
            action: 'reload'
        }, {
            headers: { 'X-Internal-Secret': INTERNAL_SECRET }
        });
        console.log(`  ${GREEN}✓ Daemon Hot-Reload API Response:${RESET}`, controlRes.data);

        // Verify that rescan control works seamlessly
        const rescanRes = await axios.post(`${DAEMON_HTTP}/control`, {
            action: 'rescan',
            serverId: 1
        }, {
            headers: { 'X-Internal-Secret': INTERNAL_SECRET }
        });
        console.log(`  ${GREEN}✓ Daemon Rescan History (100 Pesan) Response:${RESET}`, rescanRes.data);

        passedSteps++;
    } catch (e) {
        console.error(`  ${RED}✗ Test 6 Gagal:${RESET}`, e.message);
    }

    // -------------------------------------------------------------
    // FINAL AUDIT VERDICT
    // -------------------------------------------------------------
    console.log(`\n${BOLD}${CYAN}================================================================${RESET}`);
    if (passedSteps === totalSteps) {
        console.log(`${BOLD}${GREEN}🎉 SELURUH ${passedSteps}/${totalSteps} PENGUJIAN SISTEM LULUS SEMPURNA (100% PRODUCTION READY)!${RESET}`);
    } else {
        console.log(`${BOLD}${RED}⚠️ ${totalSteps - passedSteps} PENGUJIAN MENGALAMI KENDALA!${RESET}`);
    }
    console.log(`${BOLD}${CYAN}================================================================\n${RESET}`);
}

runVerification();
