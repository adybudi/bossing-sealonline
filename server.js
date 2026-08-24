/**
 * Seal Online - Boss Respawn Real-time Discord Listener & Web Server
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const { WebSocketServer } = require('ws');
require('dotenv').config();

const PORT = process.env.PORT || 3000;
const DISCORD_TOKEN = process.env.DISCORD_TOKEN || '';
const DISCORD_CHANNEL_ID = process.env.DISCORD_CHANNEL_ID || '';

// Load boss config mapping
let bossConfig = {};
function loadBossConfig() {
    try {
        const configPath = path.join(__dirname, 'boss-config.json');
        if (fs.existsSync(configPath)) {
            bossConfig = JSON.parse(fs.readFileSync(configPath, 'utf8'));
            console.log(`[Config] Berhasil memuat ${Object.keys(bossConfig).length} boss dari boss-config.json`);
        }
    } catch (e) {
        console.error('[Config] Gagal memuat boss-config.json:', e.message);
    }
}
loadBossConfig();

// Helper to look up boss info
function getBossMeta(name, fallbackLocation = '') {
    const cleanName = name.trim();
    if (bossConfig[cleanName]) {
        return bossConfig[cleanName];
    }
    // Case-insensitive match check
    for (const key of Object.keys(bossConfig)) {
        if (key.toLowerCase() === cleanName.toLowerCase()) {
            return bossConfig[key];
        }
    }
    // Default fallback
    return {
        durationMinutes: 60,
        location: fallbackLocation || 'Lokasi Unknown',
        category: 'besar'
    };
}

// -------------------------------------------------------------
// 1. HTTP Server for Frontend Assets
// -------------------------------------------------------------
const mimeTypes = {
    '.html': 'text/html',
    '.css': 'text/css',
    '.js': 'text/javascript',
    '.json': 'application/json',
    '.png': 'image/png',
    '.svg': 'image/svg+xml'
};

const server = http.createServer((req, res) => {
    let reqUrl = req.url.split('?')[0];
    if (reqUrl === '/') reqUrl = '/index.html';

    const filePath = path.join(__dirname, reqUrl);
    const ext = path.extname(filePath).toLowerCase();

    // API endpoint to parse raw pasted text
    if (req.method === 'POST' && reqUrl === '/api/parse-log') {
        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            try {
                const { text } = JSON.parse(body);
                const results = parseDiscordLogText(text);
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true, count: results.length, data: results }));
            } catch (e) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, error: e.message }));
            }
        });
        return;
    }

    fs.readFile(filePath, (err, content) => {
        if (err) {
            if (err.code === 'ENOENT') {
                res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
                res.end('404 Not Found');
            } else {
                res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
                res.end('500 Server Error');
            }
        } else {
            res.writeHead(200, { 'Content-Type': mimeTypes[ext] || 'text/plain' });
            res.end(content);
        }
    });
});

// -------------------------------------------------------------
// 2. WebSocket Server for Live Browser Sync
// -------------------------------------------------------------
const wss = new WebSocketServer({ server });
let isDiscordConnected = false;
let discordUsername = '';
let discordClientInstance = null;

// In-memory active boss states
const activeBossMap = new Map();
const lastKilledMap = new Map();

function broadcast(data) {
    const payload = JSON.stringify(data);
    wss.clients.forEach(client => {
        if (client.readyState === 1) { // OPEN
            client.send(payload);
        }
    });
}

wss.on('connection', ws => {
    console.log('[WebSocket] Klien browser terhubung.');

    // Send initial status and all screened active bosses immediately
    ws.send(JSON.stringify({
        type: 'INITIAL_SYNC',
        connected: isDiscordConnected,
        username: discordUsername,
        channelId: DISCORD_CHANNEL_ID,
        bosses: Array.from(activeBossMap.values()),
        bossConfig: bossConfig
    }));

    ws.on('message', data => {
        try {
            const msg = JSON.parse(data);
            if (msg.type === 'RESCAN_HISTORY' && discordClientInstance) {
                console.log('🔄 [Request] Klien meminta scan ulang riwayat Discord...');
                scanDiscordHistory(discordClientInstance);
            }
        } catch (e) {}
    });
});

const aliveSpawnQueueMap = new Map(); // key: bossName.toLowerCase() -> Array of { location, spawnTimestamp }

function cleanBossName(raw) {
    if (!raw) return '';
    let s = raw.trim();
    if (s.startsWith('[') && s.endsWith(']')) {
        s = s.slice(1, -1).trim();
    }
    return s;
}

function saveBossConfig() {
    try {
        const configPath = path.join(__dirname, 'boss-config.json');
        fs.writeFileSync(configPath, JSON.stringify(bossConfig, null, 2), 'utf8');
        console.log('[Config] Berhasil menyimpan pembaruan waktu spawn ke boss-config.json');
    } catch (e) {
        console.error('[Config] Gagal menyimpan boss-config.json:', e.message);
    }
}

// FIFO Kill Queue to accurately pair multiple kills with spawns without overwriting
const killQueueMap = new Map(); // key: bossName.toLowerCase() -> Array of { timestamp, location, killer }

// Standard Seal Online respawn intervals (in minutes)
const standardIntervals = [15, 20, 30, 45, 60, 75, 90, 105, 120, 150, 180, 210, 240, 300, 360, 420, 480, 720];

function snapToStandardInterval(rawMinutes) {
    if (rawMinutes < 5) return Math.round(rawMinutes);
    let closest = standardIntervals[0];
    let minDiff = Math.abs(rawMinutes - closest);
    for (const std of standardIntervals) {
        const diff = Math.abs(rawMinutes - std);
        if (diff < minDiff) {
            minDiff = diff;
            closest = std;
        }
    }
    // Snap to standard game cycle if within 8 minutes or 8% tolerance
    if (minDiff <= 8 || (minDiff / closest) <= 0.08) {
        return closest;
    }
    return Math.round(rawMinutes);
}

const MIN_RESPAWN_MINUTES = 10; // Seal Online bosses minimum respawn (filters out false twin-boss 5-7 min pairings)

// Auto-learn boss respawn duration from a kill -> spawn cycle using FIFO Queue
function checkAndLearnRespawnTime(bossName, spawnTimestamp, location = '') {
    const cleanName = cleanBossName(bossName);
    const bossKey = cleanName.toLowerCase();
    const configKey = location ? `${cleanName} @ ${location}` : cleanName;

    // Search kill queue for the oldest kill prior to this spawn that matches realistic respawn time
    const queue = killQueueMap.get(bossKey) || [];
    let matchedKillIndex = -1;
    let matchedKill = null;

    for (let i = 0; i < queue.length; i++) {
        const k = queue[i];
        if (k.timestamp < spawnTimestamp) {
            const diffMs = spawnTimestamp - k.timestamp;
            const diffMinutes = Math.round(diffMs / 60000);
            if (diffMinutes >= MIN_RESPAWN_MINUTES && diffMinutes <= 720) {
                matchedKillIndex = i;
                matchedKill = k;
                break; // Found the true corresponding kill (FIFO)
            }
        }
    }

    if (matchedKill) {
        queue.splice(matchedKillIndex, 1); // Consume the matched kill

        const rawDiffMs = spawnTimestamp - matchedKill.timestamp;
        const rawMinutes = rawDiffMs / 60000;
        const diffMinutes = snapToStandardInterval(rawMinutes);

        console.log(`\n🧠 [SCREENING AUTO-DETECT] Boss "${cleanName}" di "${location || matchedKill.location}":`);
        console.log(`   - Waktu Mati : ${new Date(matchedKill.timestamp).toLocaleTimeString()}`);
        console.log(`   - Waktu Spawn: ${new Date(spawnTimestamp).toLocaleTimeString()}`);
        console.log(`   ➔ Durasi Respawn Terdeteksi: ${diffMinutes} Menit (${(diffMinutes / 60).toFixed(1)} Jam) [Raw: ${rawMinutes.toFixed(1)}m]!\n`);

        bossConfig[configKey] = {
            bossName: cleanName,
            location: location || matchedKill.location || 'Lokasi Unknown',
            durationMinutes: diffMinutes,
            autoLearned: true
        };

        if (!bossConfig[cleanName] || !bossConfig[cleanName].autoLearned) {
            bossConfig[cleanName] = {
                bossName: cleanName,
                location: location || matchedKill.location || 'Lokasi Unknown',
                durationMinutes: diffMinutes,
                autoLearned: true
            };
        }

        saveBossConfig();

        broadcast({
            type: 'BOSS_CONFIG_UPDATED',
            bossName: cleanName,
            configKey: configKey,
            data: bossConfig[configKey]
        });
    }
}

// Multi-Slot Tracker: Allows multiple instances of the same boss in the same location (e.g. 2 DK Yami in Clements Mine)
const bossSlotsMap = new Map(); // key: 'bossname__location' -> Array of slot objects

function getSlotList(bossName, location) {
    const key = `${bossName}__${location}`.toLowerCase().replace(/[^a-z0-9]/g, '_');
    if (!bossSlotsMap.has(key)) {
        bossSlotsMap.set(key, []);
    }
    return { key, list: bossSlotsMap.get(key) };
}

// -------------------------------------------------------------
// 3. Discord Log Parser Logic (with Multi-Slot & Multi-Location)
// -------------------------------------------------------------
function parseDiscordLogText(rawText, isLive = false, msgCreatedTimestamp = null) {
    const results = [];
    if (!rawText) return results;

    const spawnRegex = /\[Monster\]::\s*(?<nameRaw>[\s\S]+?)\s+muncul di\s+\[(?<loc>[^\]]+)\](?:[\s\S]*?\[(?<time>\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2})\])?/gi;
    const killRegex = /\[Monster\]::\s*(?<nameRaw>[\s\S]+?)\s+dikalahkan oleh\s+\[(?<killer>[^\]]+)\](?:[\s\S]*?\[(?<time>\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2})\])?/gi;

    let match;

    // Helper to parse date string "DD-MM-YYYY HH:MM:SS"
    function parseTimestamp(timeStr) {
        if (!timeStr) return Date.now();
        const [datePart, timePart] = timeStr.split(' ');
        const [d, m, y] = datePart.split('-');
        const parsed = new Date(`${y}-${m}-${d}T${timePart}`);
        return isNaN(parsed.getTime()) ? Date.now() : parsed.getTime();
    }

    // Match Spawns
    while ((match = spawnRegex.exec(rawText)) !== null) {
        const bossName = cleanBossName(match.groups.nameRaw);
        const location = match.groups.loc.trim();
        // Prefer global Discord UTC epoch timestamp for 100% timezone-immune precision
        const spawnTimestamp = msgCreatedTimestamp || parseTimestamp(match.groups.time);

        // Auto-learn respawn time if previous kill is recorded
        checkAndLearnRespawnTime(bossName, spawnTimestamp, location);

        const meta = getBossMeta(bossName, location);
        const { key: baseKey, list: slotList } = getSlotList(bossName, location);

        // Find a slot that is NOT currently spawned, or allocate a new slot (#1, #2, etc.)
        let targetSlot = slotList.find(s => s.status !== 'spawned');
        let slotNum = 1;

        if (!targetSlot) {
            slotNum = slotList.length + 1;
            const displayName = slotNum > 1 ? `${bossName} #${slotNum}` : bossName;
            targetSlot = {
                slotNumber: slotNum,
                name: bossName,
                displayName: displayName,
                location: location,
                status: 'spawned',
                lastSpawnTime: spawnTimestamp,
                lastKillTime: null
            };
            slotList.push(targetSlot);

            // If 2nd instance just appeared, rename slot 1 to '#1' for clarity
            if (slotList.length === 2 && !slotList[0].displayName.includes('#')) {
                slotList[0].displayName = `${bossName} #1`;
                const slot1UniqueKey = `${baseKey}_slot_1`;
                if (activeBossMap.has(slot1UniqueKey)) {
                    activeBossMap.get(slot1UniqueKey).name = `${bossName} #1`;
                }
            }
        } else {
            slotNum = targetSlot.slotNumber;
            targetSlot.status = 'spawned';
            targetSlot.lastSpawnTime = spawnTimestamp;
        }

        const slotUniqueKey = `${baseKey}_slot_${slotNum}`;

        const item = {
            action: 'SPAWN',
            name: targetSlot.displayName,
            baseName: bossName,
            location: location || meta.location,
            durationSeconds: meta.durationMinutes * 60,
            remainingSeconds: 0,
            status: 'spawned',
            autoLearned: !!meta.autoLearned,
            timestamp: match.groups.time || new Date(spawnTimestamp).toISOString()
        };

        // Update active boss map
        activeBossMap.set(slotUniqueKey, {
            id: 'boss_' + slotUniqueKey,
            ...item,
            totalSeconds: item.durationSeconds,
            targetEndTime: null
        });

        results.push(item);
    }

    // Match Kills
    while ((match = killRegex.exec(rawText)) !== null) {
        const bossName = cleanBossName(match.groups.nameRaw);
        const killer = match.groups.killer ? match.groups.killer.trim() : '';
        // Prefer global Discord UTC epoch timestamp for 100% timezone-immune precision
        const killTimestamp = msgCreatedTimestamp || parseTimestamp(match.groups.time);

        // Find the oldest currently spawned slot across all locations for this boss
        let matchedSlot = null;
        let oldestSpawnTime = Infinity;
        let matchedBaseKey = '';

        for (const [key, slotList] of bossSlotsMap.entries()) {
            if (key.startsWith(bossName.toLowerCase().replace(/[^a-z0-9]/g, '_'))) {
                for (const slot of slotList) {
                    if (slot.status === 'spawned' && slot.lastSpawnTime < oldestSpawnTime) {
                        oldestSpawnTime = slot.lastSpawnTime;
                        matchedSlot = slot;
                        matchedBaseKey = key;
                    }
                }
            }
        }

        // If no slot was currently in 'spawned' status, pick slot 1 of the last known location
        if (!matchedSlot) {
            for (const [key, slotList] of bossSlotsMap.entries()) {
                if (key.startsWith(bossName.toLowerCase().replace(/[^a-z0-9]/g, '_')) && slotList.length > 0) {
                    matchedSlot = slotList[0];
                    matchedBaseKey = key;
                    break;
                }
            }
        }

        let resolvedLocation = matchedSlot ? matchedSlot.location : getBossMeta(bossName).location;
        let displayName = matchedSlot ? matchedSlot.displayName : bossName;
        let slotNum = matchedSlot ? matchedSlot.slotNumber : 1;

        if (matchedSlot) {
            matchedSlot.status = 'running';
            matchedSlot.lastKillTime = killTimestamp;
            matchedSlot.killer = killer;
        }

        const baseKey = matchedBaseKey || `${bossName}__${resolvedLocation}`.toLowerCase().replace(/[^a-z0-9]/g, '_');
        const slotUniqueKey = `${baseKey}_slot_${slotNum}`;

        // Store kill record for screening and FIFO queue
        const killRecord = {
            timestamp: killTimestamp,
            location: resolvedLocation,
            killer: killer
        };
        lastKilledMap.set(slotUniqueKey, killRecord);
        lastKilledMap.set(baseKey, killRecord);
        lastKilledMap.set(bossName.toLowerCase(), killRecord);

        // Add to FIFO kill queue
        const bossKey = bossName.toLowerCase();
        if (!killQueueMap.has(bossKey)) {
            killQueueMap.set(bossKey, []);
        }
        killQueueMap.get(bossKey).push(killRecord);

        const meta = getBossMeta(bossName, resolvedLocation);
        const durationSeconds = meta.durationMinutes * 60;

        let targetEndTime, remainingSeconds, status;

        if (isLive) {
            // Live event: Boss was just killed right now! Start full countdown immediately
            targetEndTime = Date.now() + (durationSeconds * 1000);
            remainingSeconds = durationSeconds;
            status = 'running';
        } else {
            // History event: Calculate based on time elapsed
            targetEndTime = killTimestamp + (durationSeconds * 1000);
            remainingSeconds = Math.max(0, Math.ceil((targetEndTime - Date.now()) / 1000));
            status = remainingSeconds <= 0 ? 'spawned' : 'running';
        }

        const item = {
            action: 'KILLED',
            name: displayName,
            baseName: bossName,
            location: resolvedLocation,
            durationSeconds: durationSeconds,
            remainingSeconds: remainingSeconds,
            targetEndTime: status === 'running' ? targetEndTime : null,
            status: status,
            killer: killer,
            autoLearned: !!meta.autoLearned,
            timestamp: match.groups.time || new Date().toISOString()
        };

        // Update active boss map
        activeBossMap.set(slotUniqueKey, {
            id: 'boss_' + slotUniqueKey,
            ...item,
            totalSeconds: item.durationSeconds
        });

        results.push(item);
    }

    return results;
}

// -------------------------------------------------------------
// 4. Discord Self-Bot Real-time Listener & History Screening
// -------------------------------------------------------------
async function scanDiscordHistory(client) {
    if (!DISCORD_CHANNEL_ID) return;

    try {
        console.log(`🔍 [Screening] Membaca 100 riwayat chat channel #${DISCORD_CHANNEL_ID}...`);
        const channel = await client.channels.fetch(DISCORD_CHANNEL_ID);
        if (channel && channel.isText()) {
            const messages = await channel.messages.fetch({ limit: 100 });
            const messageList = Array.from(messages.values()).reverse(); // Oldest to newest

            console.log(`📥 [Screening] Menganalisis ${messageList.length} pesan riwayat...`);

            let processedCount = 0;
            for (const msg of messageList) {
                if (msg.content && msg.content.includes('[Monster]::')) {
                    const events = parseDiscordLogText(msg.content, false, msg.createdTimestamp);
                    if (events.length > 0) {
                        processedCount += events.length;
                    }
                }
            }

            console.log(`✨ [Screening Selesai] ${activeBossMap.size} status boss aktif & durasi spawn terdeteksi!`);

            // Broadcast initial sync with all screened bosses to all connected clients
            broadcast({
                type: 'INITIAL_SYNC',
                connected: true,
                username: discordUsername,
                channelId: DISCORD_CHANNEL_ID,
                bosses: Array.from(activeBossMap.values()),
                bossConfig: bossConfig,
                message: `Berhasil screening ${processedCount} riwayat log Discord!`
            });
        }
    } catch (err) {
        console.warn('[Screening Warning] Tidak dapat mengambil riwayat pesan:', err.message);
    }
}

function startDiscordListener() {
    if (!DISCORD_TOKEN || DISCORD_TOKEN === 'MASUKKAN_TOKEN_DISCORD_ANDA_DISINI') {
        console.log('\n⚠️  [Discord Listener] DISCORD_TOKEN belum diisi di file .env');
        console.log('   Web server tetap berjalan di http://localhost:' + PORT);
        console.log('   Anda dapat menggunakan fitur manual atau "Paste Log Discord" langsung di web.\n');
        return;
    }

    try {
        const { Client } = require('discord.js-selfbot-v13');
        const client = new Client({ checkUpdate: false });
        discordClientInstance = client;

        client.on('ready', async () => {
            isDiscordConnected = true;
            discordUsername = client.user.tag;
            console.log(`\n✅ [Discord Listener] Berhasil login sebagai: ${discordUsername}`);

            broadcast({
                type: 'DISCORD_STATUS',
                connected: true,
                username: discordUsername,
                channelId: DISCORD_CHANNEL_ID
            });

            // Perform automatic screening on channel history (last 100 messages)
            await scanDiscordHistory(client);
        });

        client.on('messageCreate', (message) => {
            if (DISCORD_CHANNEL_ID && message.channelId !== DISCORD_CHANNEL_ID) {
                return;
            }

            const rawContent = message.content || '';
            if (!rawContent.includes('[Monster]::')) {
                return;
            }

            console.log(`[Discord Log Baru] ${rawContent.replace(/\n/g, ' ')}`);

            const parsedEvents = parseDiscordLogText(rawContent, true, message.createdTimestamp);
            if (parsedEvents.length > 0) {
                parsedEvents.forEach(event => {
                    console.log(`⚡ [Auto-Sync] Boss ${event.name} (${event.action}) -> Broadcast ke Web`);
                    broadcast({
                        type: 'BOSS_EVENT',
                        data: event
                    });
                });
            }
        });

        client.on('error', (err) => {
            console.error('[Discord Error]', err.message);
            isDiscordConnected = false;
            broadcast({ type: 'DISCORD_STATUS', connected: false, error: err.message });
        });

        client.login(DISCORD_TOKEN).catch(err => {
            console.error('❌ [Discord Login Gagal]', err.message);
            isDiscordConnected = false;
        });

    } catch (e) {
        console.warn('⚠️ Module discord.js-selfbot-v13 belum terinstall. Jalankan "npm install" terlebih dahulu.');
    }
}

// -------------------------------------------------------------
// 5. Start Server
// -------------------------------------------------------------
server.listen(PORT, () => {
    console.log(`\n======================================================`);
    console.log(`⚔️  SEAL ONLINE BOSS TIMER & DISCORD LISTENER`);
    console.log(`======================================================`);
    console.log(`🌐 Buka di browser: http://localhost:${PORT}`);
    console.log(`======================================================\n`);

    startDiscordListener();
});
