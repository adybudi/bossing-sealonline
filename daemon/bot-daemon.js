/**
 * Seal Online - Multi-Tenant Discord Self-Bot Real-time Daemon
 * Version: 2.0.0 (Pure System Brain Logic V2.0 - High-Precision Autonomous Engine)
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const { WebSocketServer } = require('ws');
const axios = require('axios');
const { Client } = require('discord.js-selfbot-v13');
require('dotenv').config({ path: path.join(__dirname, '../.env') });

const WEBSOCKET_PORT = parseInt(process.env.PORT || process.env.WEBSOCKET_PORT || '3001', 10);
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://127.0.0.1:8000';
const INTERNAL_SECRET = process.env.DAEMON_INTERNAL_SECRET || 'seal_internal_secret_change_me_in_env';

// Load baseline config from boss-config.json
let baseBossConfig = {};
let resolvedConfigPath = path.join(__dirname, 'boss-config.json');
if (!fs.existsSync(resolvedConfigPath)) {
    resolvedConfigPath = path.join(__dirname, '../boss-config.json');
}
try {
    if (fs.existsSync(resolvedConfigPath)) {
        baseBossConfig = JSON.parse(fs.readFileSync(resolvedConfigPath, 'utf8'));
        console.log(`[Config] Berhasil memuat ${Object.keys(baseBossConfig).length} boss baseline dari ${resolvedConfigPath}`);
    }
} catch (e) {
    console.error('[Config] Gagal memuat boss-config.json:', e.message);
}

function saveBaseBossConfig() {
    try {
        fs.writeFileSync(resolvedConfigPath, JSON.stringify(baseBossConfig, null, 2), 'utf8');
    } catch (e) {}
}

// Standard Seal Online respawn cycles (in minutes) - V2.0 Engine 4
const STANDARD_INTERVALS = [15, 20, 25, 30, 45, 60, 75, 90, 105, 120, 150, 180, 210, 240, 300, 360, 420, 480, 720];
const MIN_RESPAWN_MINUTES = 10;

// =========================================================================
// ENGINE 1: Text Sanitizer, Map Trimmer & Universal Prefix Shield
// =========================================================================
function cleanLocation(raw) {
    if (!raw) return 'Lokasi Unknown';
    let s = raw.trim();
    if (s.startsWith('[') && s.endsWith(']')) {
        s = s.slice(1, -1).trim();
    }
    return s.replace(/\s+/g, ' ').trim();
}

function cleanBossName(raw) {
    if (!raw) return '';
    let s = raw.trim();
    while (s.startsWith('[[') && (s.endsWith(']') || s.endsWith(']]'))) {
        s = s.slice(1);
        if (s.endsWith(']')) s = s.slice(0, -1);
        s = s.trim();
    }
    if (s.startsWith('[') && s.endsWith(']')) {
        const firstClose = s.indexOf(']');
        if (firstClose === s.length - 1) {
            const inner = s.slice(1, -1).trim();
            if (!inner.includes('[') && !inner.includes(']')) {
                s = inner;
            }
        }
    }
    s = s.replace(/\[Wolrd Boss\]/gi, '[World Boss]');
    s = s.replace(/^\[([A-Za-z0-9\s_-]+)\](?=[^\s\]])/i, '[$1] ');
    return s.replace(/\s+/g, ' ').trim();
}

// =========================================================================
// ENGINE 2: Dynamic Multi-Slot & Max Slot Cap
// =========================================================================
function getMaxSlotsForBoss(bossName, location) {
    const loc = (location || '').toLowerCase();
    // Clements Mine is the twin boss map in Seal Online (DK Yami & Titan Skull)
    if (loc.includes('clements') || loc.includes('clement')) {
        return 2;
    }
    return 1;
}

function snapToStandardInterval(rawMinutes) {
    if (rawMinutes < 5) return Math.round(rawMinutes);
    let closest = STANDARD_INTERVALS[0];
    let minDiff = Math.abs(rawMinutes - closest);
    for (const std of STANDARD_INTERVALS) {
        const diff = Math.abs(rawMinutes - std);
        if (diff < minDiff) {
            minDiff = diff;
            closest = std;
        }
    }
    if (minDiff <= 8 || (minDiff / closest) <= 0.08) {
        return closest;
    }
    return Math.round(rawMinutes);
}

// -------------------------------------------------------------
// Multi-Tenant Server Session Class (Pure Brain Logic V2)
// -------------------------------------------------------------
class SealServerSession {
    constructor(serverData, daemon) {
        this.id = serverData.id;
        this.name = serverData.name;
        this.accessCode = serverData.access_code;
        this.discordToken = serverData.discord_token;
        this.channelId = serverData.discord_channel_id;
        this.daemon = daemon;

        this.client = null;
        this.botStatus = 'STOPPED';
        this.lastError = null;

        // In-memory data structures per server (ENGINE 6 V2)
        this.bossConfigs = new Map(); // key: 'bossname' or 'bossname @ map' -> { durationMinutes, autoLearned, location }
        this.activeBossMap = new Map(); // key: slotUniqueKey -> boss state object
        this.killQueueMap = new Map(); // key: bossName.toLowerCase() -> Array of { timestamp, location, killer } (ENGINE 4)
        this.bossSlotsMap = new Map(); // key: 'bossname__location' -> Array of slot objects (ENGINE 2)
        this.lastPlayerKills = new Map(); // key: 'killer__bossName.toLowerCase()' -> { timestamp, baseKey, location, slotNumber } (ENGINE 3)

        // 1. Seed from baseBossConfig baseline
        for (const [key, cfg] of Object.entries(baseBossConfig)) {
            const cleanKey = cleanBossName(key);
            const duration = cfg.durationMinutes || 60;
            const loc = cfg.location || (cleanKey.includes(' @ ') ? cleanKey.split(' @ ')[1] : '');
            const bName = cfg.bossName || cleanBossName(cleanKey.split(' @ ')[0]);

            this.bossConfigs.set(cleanKey, {
                bossName: bName,
                location: loc,
                durationMinutes: duration,
                autoLearned: !!cfg.autoLearned
            });
        }

        // 2. Overlay from Laravel DB configs
        if (Array.isArray(serverData.configs)) {
            serverData.configs.forEach(cfg => {
                const cleanBName = cleanBossName(cfg.boss_name);
                const key = cfg.map_name ? `${cleanBName} @ ${cfg.map_name}` : cleanBName;
                this.bossConfigs.set(key, {
                    bossName: cleanBName,
                    location: cfg.map_name || '',
                    durationMinutes: cfg.interval_minutes,
                    autoLearned: !!cfg.is_auto_learned
                });
            });
        }
    }

    clearStateForRescan() {
        this.activeBossMap.clear();
        this.killQueueMap.clear();
        this.bossSlotsMap.clear();
        this.lastPlayerKills.clear();
    }

    getBossMeta(name, fallbackLocation = '') {
        const cleanName = cleanBossName(name);
        const keyWithLoc = fallbackLocation ? `${cleanName} @ ${fallbackLocation}` : '';

        // 1. Exact match with location
        if (keyWithLoc && this.bossConfigs.has(keyWithLoc)) {
            return this.bossConfigs.get(keyWithLoc);
        }
        // 2. Exact match generic name
        if (this.bossConfigs.has(cleanName)) {
            return this.bossConfigs.get(cleanName);
        }
        // 3. Case-insensitive match check with location
        if (fallbackLocation) {
            for (const [key, cfg] of this.bossConfigs.entries()) {
                if (cfg.location && cfg.location.toLowerCase() === fallbackLocation.toLowerCase() && cfg.bossName.toLowerCase() === cleanName.toLowerCase()) {
                    return cfg;
                }
            }
        }
        // 4. Case-insensitive match check generic
        for (const [key, cfg] of this.bossConfigs.entries()) {
            if (cfg.bossName.toLowerCase() === cleanName.toLowerCase()) {
                return cfg;
            }
        }
        // Default fallback
        return {
            bossName: cleanName,
            durationMinutes: 60,
            location: fallbackLocation || 'Lokasi Unknown',
            autoLearned: false
        };
    }

    getSlotList(bossName, location) {
        const key = `${bossName}__${location || 'Lokasi Unknown'}`.toLowerCase().replace(/[^a-z0-9]/g, '_');
        if (!this.bossSlotsMap.has(key)) {
            this.bossSlotsMap.set(key, []);
        }
        return { key, list: this.bossSlotsMap.get(key) };
    }

    // =========================================================================
    // ENGINE 4: FIFO Kill Queue, >=10m Filter & Multi-Scale Snapping
    // =========================================================================
    checkAndLearnRespawnTime(bossName, spawnTimestamp, location = '') {
        const cleanName = cleanBossName(bossName);
        const bossKey = cleanName.toLowerCase();
        const configKey = location ? `${cleanName} @ ${location}` : cleanName;

        const queue = this.killQueueMap.get(bossKey) || [];
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
                    break; // FIFO Match
                }
            }
        }

        if (matchedKill) {
            queue.splice(matchedKillIndex, 1); // Consume the matched kill

            const rawDiffMs = spawnTimestamp - matchedKill.timestamp;
            const rawMinutes = rawDiffMs / 60000;
            const diffMinutes = snapToStandardInterval(rawMinutes);

            console.log(`\n🧠 [AUTO-LEARN][${this.name}] Boss "${cleanName}" di "${location || matchedKill.location}":`);
            console.log(`   - Waktu Mati : ${new Date(matchedKill.timestamp).toLocaleTimeString()}`);
            console.log(`   - Waktu Spawn: ${new Date(spawnTimestamp).toLocaleTimeString()}`);
            console.log(`   ➔ Durasi Respawn Terdeteksi: ${diffMinutes} Menit [Raw: ${rawMinutes.toFixed(1)}m]\n`);

            this.bossConfigs.set(configKey, {
                bossName: cleanName,
                location: location || matchedKill.location || 'Lokasi Unknown',
                durationMinutes: diffMinutes,
                autoLearned: true
            });

            if (!this.bossConfigs.has(cleanName) || !this.bossConfigs.get(cleanName).autoLearned) {
                this.bossConfigs.set(cleanName, {
                    bossName: cleanName,
                    location: location || matchedKill.location || 'Lokasi Unknown',
                    durationMinutes: diffMinutes,
                    autoLearned: true
                });
            }

            // Also persist to baseBossConfig
            baseBossConfig[configKey] = this.bossConfigs.get(configKey);
            saveBaseBossConfig();

            this.syncWithLaravel();
        }
    }

    processDiscordLog(rawText, msgCreatedTimestamp = null) {
        if (!rawText) return;

        const spawnRegex = /\[Monster\]::\s*(?<nameRaw>(?:(?!\[Monster\]::)[^\r\n])+?)\s+muncul di\s+\[(?<loc>[^\]]+)\](?:\s*\[(?<time>\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2})\])?/gi;
        const killRegex = /\[Monster\]::\s*(?<nameRaw>(?:(?!\[Monster\]::)[^\r\n])+?)\s+dikalahkan oleh\s+\[(?<killer>[^\]]+)\](?:\s*\[(?<time>\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2})\])?/gi;

        function parseTimestamp(timeStr) {
            if (!timeStr) return Date.now();
            const [datePart, timePart] = timeStr.split(' ');
            const [d, m, y] = datePart.split('-');
            const parsed = new Date(`${y}-${m}-${d}T${timePart}`);
            return isNaN(parsed.getTime()) ? Date.now() : parsed.getTime();
        }

        let match;

        // 1. Match Spawns (ENGINE 2)
        while ((match = spawnRegex.exec(rawText)) !== null) {
            const bossName = cleanBossName(match.groups.nameRaw);
            const location = cleanLocation(match.groups.loc);
            const spawnTimestamp = msgCreatedTimestamp || parseTimestamp(match.groups.time);

            // Auto-learn respawn time
            this.checkAndLearnRespawnTime(bossName, spawnTimestamp, location);

            const meta = this.getBossMeta(bossName, location);
            const { key: baseKey, list: slotList } = this.getSlotList(bossName, location);
            const maxSlots = getMaxSlotsForBoss(bossName, location);

            let targetSlot = slotList.find(s => s.status !== 'spawned');
            let slotNum = 1;

            if (!targetSlot) {
                if (slotList.length < maxSlots) {
                    slotNum = slotList.length + 1;
                    const displayName = (maxSlots > 1 && slotNum > 1) ? `${bossName} #${slotNum}` : bossName;
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

                    // Retroactive renaming: when 2nd instance appears on twin map, rename slot 1 to "#1"
                    if (maxSlots > 1 && slotList.length === 2 && !slotList[0].displayName.includes('#')) {
                        slotList[0].displayName = `${bossName} #1`;
                        const slot1Key = `${baseKey}_slot_1`;
                        if (this.activeBossMap.has(slot1Key)) {
                            this.activeBossMap.get(slot1Key).bossName = `${bossName} #1`;
                        }
                    }
                } else {
                    // Max slot capacity reached: Recycle oldest spawn
                    slotList.sort((a, b) => (a.lastSpawnTime || 0) - (b.lastSpawnTime || 0));
                    targetSlot = slotList[0];
                    slotNum = targetSlot.slotNumber;
                    targetSlot.status = 'spawned';
                    targetSlot.lastSpawnTime = spawnTimestamp;
                }
            } else {
                slotNum = targetSlot.slotNumber;
                targetSlot.status = 'spawned';
                targetSlot.lastSpawnTime = spawnTimestamp;
            }

            const slotUniqueKey = `${baseKey}_slot_${slotNum}`;
            const bossObj = {
                key: slotUniqueKey,
                bossName: targetSlot.displayName,
                location: location || meta.location || 'Lokasi Unknown',
                slot: slotNum,
                status: 'SPAWNED',
                killedAt: null,
                targetEndTime: spawnTimestamp,
                durationMinutes: meta.durationMinutes || 60
            };

            this.activeBossMap.set(slotUniqueKey, bossObj);
            this.daemon.broadcastToRoom(this.accessCode, { type: 'BOSS_UPDATE', boss: bossObj });
        }

        // 2. Match Kills (ENGINE 3 & 5)
        while ((match = killRegex.exec(rawText)) !== null) {
            const bossName = cleanBossName(match.groups.nameRaw);
            const killer = match.groups.killer ? match.groups.killer.trim() : '';
            const killTimestamp = msgCreatedTimestamp || parseTimestamp(match.groups.time);

            let matchedSlot = null;
            let oldestSpawnTime = Infinity;
            let matchedBaseKey = '';

            // Prioritas 1: Player Trajectory Rapid Double-Kill (<= 180s on twin map)
            if (killer) {
                const lastKill = this.lastPlayerKills.get(`${killer}__${bossName.toLowerCase()}`);
                if (lastKill && (killTimestamp - lastKill.timestamp) <= 180000 && lastKill.baseKey) {
                    const slotList = this.bossSlotsMap.get(lastKill.baseKey);
                    if (slotList) {
                        const twinSlot = slotList.find(s => s.status === 'spawned' && s.slotNumber !== lastKill.slotNumber);
                        if (twinSlot) {
                            matchedSlot = twinSlot;
                            matchedBaseKey = lastKill.baseKey;
                        }
                    }
                }
            }

            // Prioritas 2: Map dengan >= 2 slot hidup bersamaan (contoh Clements Mine)
            if (!matchedSlot) {
                for (const [key, slotList] of this.bossSlotsMap.entries()) {
                    if (key.startsWith(bossName.toLowerCase().replace(/[^a-z0-9]/g, '_'))) {
                        const spawnedSlots = slotList.filter(s => s.status === 'spawned');
                        if (spawnedSlots.length >= 2) {
                            spawnedSlots.sort((a, b) => (a.lastSpawnTime || 0) - (b.lastSpawnTime || 0));
                            matchedSlot = spawnedSlots[0];
                            matchedBaseKey = key;
                            break;
                        }
                    }
                }
            }

            // Prioritas 3: Oldest spawned slot across all locations
            if (!matchedSlot) {
                for (const [key, slotList] of this.bossSlotsMap.entries()) {
                    if (key.startsWith(bossName.toLowerCase().replace(/[^a-z0-9]/g, '_'))) {
                        for (const s of slotList) {
                            if (s.status === 'spawned' && s.lastSpawnTime < oldestSpawnTime) {
                                oldestSpawnTime = s.lastSpawnTime;
                                matchedSlot = s;
                                matchedBaseKey = key;
                            }
                        }
                    }
                }
            }

            // Fallback: Pick slot 1 of known location
            if (!matchedSlot) {
                for (const [key, slotList] of this.bossSlotsMap.entries()) {
                    if (key.startsWith(bossName.toLowerCase().replace(/[^a-z0-9]/g, '_')) && slotList.length > 0) {
                        matchedSlot = slotList[0];
                        matchedBaseKey = key;
                        break;
                    }
                }
            }

            let resolvedLocation = matchedSlot ? matchedSlot.location : this.getBossMeta(bossName).location;
            let displayName = matchedSlot ? matchedSlot.displayName : bossName;
            let slotNum = matchedSlot ? matchedSlot.slotNumber : 1;

            if (matchedSlot) {
                matchedSlot.status = 'running';
                matchedSlot.lastKillTime = killTimestamp;
                matchedSlot.killer = killer;
            }

            const baseKey = matchedBaseKey || `${bossName}__${resolvedLocation}`.toLowerCase().replace(/[^a-z0-9]/g, '_');
            const slotUniqueKey = `${baseKey}_slot_${slotNum}`;

            if (killer && matchedSlot && matchedBaseKey) {
                this.lastPlayerKills.set(`${killer}__${bossName.toLowerCase()}`, {
                    timestamp: killTimestamp,
                    baseKey: matchedBaseKey,
                    location: resolvedLocation,
                    slotNumber: slotNum
                });
            }

            // Add to FIFO kill queue (ENGINE 4)
            const bossKey = bossName.toLowerCase();
            if (!this.killQueueMap.has(bossKey)) {
                this.killQueueMap.set(bossKey, []);
            }
            this.killQueueMap.get(bossKey).push({
                timestamp: killTimestamp,
                location: resolvedLocation,
                killer: killer
            });

            // Calculate Target Respawn (ENGINE 5)
            const meta = this.getBossMeta(bossName, resolvedLocation);
            const durationMinutes = meta.durationMinutes || 60;
            const targetEndTime = killTimestamp + (durationMinutes * 60 * 1000);
            const remainingSeconds = Math.max(0, Math.ceil((targetEndTime - Date.now()) / 1000));
            const status = remainingSeconds <= 0 ? 'SPAWNED' : 'COUNTDOWN';

            const bossObj = {
                key: slotUniqueKey,
                bossName: displayName,
                location: resolvedLocation || meta.location || 'Lokasi Unknown',
                slot: slotNum,
                status: status,
                killedAt: killTimestamp,
                targetEndTime: targetEndTime,
                durationMinutes: durationMinutes
            };

            this.activeBossMap.set(slotUniqueKey, bossObj);
            this.daemon.broadcastToRoom(this.accessCode, { type: 'BOSS_UPDATE', boss: bossObj });
        }
    }

    async scanDiscordHistory() {
        if (!this.client || !this.channelId) return;

        try {
            console.log(`🔄 [${this.name}] Membaca 100 riwayat chat terakhir di channel ${this.channelId}...`);
            const channel = await this.client.channels.fetch(this.channelId);
            if (!channel) {
                throw new Error(`Channel ID ${this.channelId} tidak ditemukan.`);
            }

            const messages = await channel.messages.fetch({ limit: 100 });
            const chronological = Array.from(messages.values()).reverse();

            // Clear active states before re-screening (ENGINE 6 V2)
            this.clearStateForRescan();

            for (const msg of chronological) {
                if (msg.content && msg.content.includes('[Monster]::')) {
                    this.processDiscordLog(msg.content, msg.createdTimestamp);
                }
            }

            console.log(`✅ [${this.name}] Screening selesai! ${this.activeBossMap.size} boss aktif terdeteksi.`);

            // Report screened timestamp to Laravel
            this.daemon.updateServerStatus(this.id, 'RUNNING', null, new Date().toISOString());
            this.syncWithLaravel();

            // Broadcast initial sync to viewers
            this.daemon.broadcastToRoom(this.accessCode, {
                type: 'INITIAL_SYNC',
                bosses: Array.from(this.activeBossMap.values())
            });

        } catch (err) {
            console.error(`❌ [${this.name}] Gagal screening riwayat:`, err.message);
            this.daemon.updateServerStatus(this.id, 'ERROR', err.message);
        }
    }

    async start() {
        if (!this.discordToken) {
            console.warn(`⚠️ [${this.name}] Discord Token kosong. Melewati koneksi bot.`);
            this.botStatus = 'STOPPED';
            return;
        }

        if (!this.channelId) {
            console.warn(`⚠️ [${this.name}] Discord Channel ID kosong.`);
            this.botStatus = 'STOPPED';
            return;
        }

        this.botStatus = 'STARTING';
        this.daemon.updateServerStatus(this.id, 'STARTING');

        try {
            this.client = new Client({ checkUpdate: false });

            this.client.on('ready', async () => {
                console.log(`🟢 [${this.name}] Bot terhubung sebagai ${this.client.user.tag}`);
                this.botStatus = 'RUNNING';
                this.daemon.updateServerStatus(this.id, 'RUNNING');
                await this.scanDiscordHistory();
            });

            this.client.on('messageCreate', (msg) => {
                if (msg.channelId === this.channelId) {
                    if (msg.content && msg.content.includes('[Monster]::')) {
                        this.processDiscordLog(msg.content, msg.createdTimestamp);
                        this.syncWithLaravel();
                    }
                }
            });

            this.client.on('error', (err) => {
                console.error(`🔴 [${this.name}] Bot Error:`, err.message);
                this.lastError = err.message;
                this.botStatus = 'ERROR';
                this.daemon.updateServerStatus(this.id, 'ERROR', err.message);
            });

            await this.client.login(this.discordToken);

        } catch (err) {
            console.error(`❌ [${this.name}] Gagal login Discord:`, err.message);
            this.botStatus = 'ERROR';
            this.lastError = err.message;
            this.daemon.updateServerStatus(this.id, 'ERROR', err.message);
        }
    }

    async stop() {
        console.log(`⏹️ [${this.name}] Menghentikan bot session.`);
        if (this.client) {
            try { this.client.destroy(); } catch (e) {}
            this.client = null;
        }
        this.botStatus = 'STOPPED';
        this.daemon.updateServerStatus(this.id, 'STOPPED');
    }

    syncWithLaravel() {
        const states = Array.from(this.activeBossMap.values()).map(b => ({
            boss_key: b.key,
            boss_name: b.bossName,
            map_name: b.location,
            slot_index: b.slot || 1,
            status: b.status,
            killed_at: b.killedAt,
            target_respawn_at: b.targetEndTime,
            interval_minutes: b.durationMinutes || 30
        }));

        const configs = Array.from(this.bossConfigs.values()).map(c => ({
            boss_name: c.bossName,
            map_name: c.location,
            interval_minutes: c.durationMinutes,
            is_auto_learned: c.autoLearned
        }));

        this.daemon.syncStatesToLaravel(this.id, states, configs);
    }
}

// -------------------------------------------------------------
// Daemon Coordinator Hub
// -------------------------------------------------------------
class MultiServerBotDaemon {
    constructor() {
        this.sessions = new Map(); // key: serverId -> SealServerSession
        this.server = null;
        this.wss = null;
        this.clientSockets = new Set();
    }

    async init() {
        console.log('⚔️  ======================================================');
        console.log('⚔️  Seal Online Multi-Server Bot Daemon v2.0.0 (Pure V2)');
        console.log(`⚔️  WebSocket & Control Server Port: ${WEBSOCKET_PORT}`);
        console.log(`⚔️  Laravel Bridge API: ${LARAVEL_API_URL}`);
        console.log('⚔️  ======================================================\n');

        this.setupHttpAndWebSocket();
        await this.loadAndStartServers();
    }

    setupHttpAndWebSocket() {
        this.server = http.createServer((req, res) => {
            res.setHeader('Access-Control-Allow-Origin', '*');
            res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            res.setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Internal-Secret');

            if (req.method === 'OPTIONS') {
                res.writeHead(200);
                res.end();
                return;
            }

            if (reqUrl === '' || reqUrl === '/health' || reqUrl === '/daemon' || reqUrl === '/daemon/health') {
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({
                    status: 'OK',
                    service: 'Seal Online Boss Tracker Daemon Engine V2.0',
                    activeServers: this.sessions.size,
                    connectedClients: this.clientSockets.size,
                    uptimeSeconds: Math.floor(process.uptime())
                }));
                return;
            }

            const clientSecret = req.headers['x-internal-secret'];
            if (clientSecret !== INTERNAL_SECRET) {
                res.writeHead(403, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ error: 'Unauthorized internal secret' }));
                return;
            }

            let body = '';
            req.on('data', chunk => { body += chunk; });
            req.on('end', async () => {
                try {
                    const data = body ? JSON.parse(body) : {};

                    if (req.method === 'POST' && (reqUrl === '/control-server' || reqUrl === '/control')) {
                        const { server_id, action } = data;
                        await this.handleServerControl(server_id, action);
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: true, message: `Action ${action} executed.` }));
                    } else if (req.method === 'POST' && reqUrl === '/update-interval') {
                        const { server_id, boss_key, interval_minutes } = data;
                        this.handleUpdateInterval(server_id, boss_key, interval_minutes);
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: true }));
                    } else if (req.method === 'POST' && reqUrl === '/manual-event') {
                        const { server_id, type, boss_name, location, duration_minutes } = data;
                        this.handleManualEvent(server_id, type, boss_name, location, duration_minutes);
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: true }));
                    } else if (req.method === 'POST' && reqUrl === '/parse-log') {
                        const { server_id, text } = data;
                        const session = this.sessions.get(server_id);
                        if (session) {
                            session.processDiscordLog(text);
                            session.syncWithLaravel();
                            res.writeHead(200, { 'Content-Type': 'application/json' });
                            res.end(JSON.stringify({ success: true, count: session.activeBossMap.size }));
                        } else {
                            res.writeHead(404, { 'Content-Type': 'application/json' });
                            res.end(JSON.stringify({ error: 'Server session not found' }));
                        }
                    } else if (req.method === 'POST' && reqUrl === '/reset-boss') {
                        const { server_id, boss_key } = data;
                        this.handleResetBoss(server_id, boss_key);
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: true }));
                    } else if (req.method === 'POST' && reqUrl === '/delete-boss') {
                        const { server_id, boss_key } = data;
                        this.handleDeleteBoss(server_id, boss_key);
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: true }));
                    } else if (req.method === 'POST' && reqUrl === '/lockdown') {
                        this.broadcastGlobal({ type: 'GLOBAL_LOCKDOWN' });
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: true, message: 'Global lockdown broadcasted' }));
                    } else if (req.method === 'POST' && reqUrl === '/kick-session') {
                        const { user_access_key, active_session_token, server_access_code } = data;
                        console.log(`🔒 [Single-Device Guard] Session Revoked event broadcasted for Key: ${user_access_key}`);
                        this.broadcastGlobal({
                            type: 'SESSION_REVOKED',
                            userAccessKey: user_access_key,
                            activeSessionToken: active_session_token
                        });
                        res.writeHead(200, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ success: true }));
                    } else {
                        res.writeHead(404, { 'Content-Type': 'application/json' });
                        res.end(JSON.stringify({ error: 'Not found' }));
                    }
                } catch (e) {
                    res.writeHead(500, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({ error: e.message }));
                }
            });
        });

        this.wss = new WebSocketServer({ server: this.server });

        this.wss.on('connection', (ws, req) => {
            this.clientSockets.add(ws);
            ws.subscribedRooms = new Set();

            ws.on('message', (messageRaw) => {
                try {
                    const msg = JSON.parse(messageRaw);
                    const actionType = (msg.type || msg.action || '').toUpperCase();
                    const roomCode = msg.accessCode || msg.server_access_code;

                    if ((actionType === 'SUBSCRIBE' || actionType === 'JOIN') && roomCode) {
                        ws.subscribedRooms.add(roomCode);

                        // Find matching server session
                        for (const session of this.sessions.values()) {
                            if (session.accessCode === roomCode) {
                                ws.send(JSON.stringify({
                                    type: 'INITIAL_SYNC',
                                    serverName: session.name,
                                    botStatus: session.botStatus,
                                    bosses: Array.from(session.activeBossMap.values())
                                }));
                                break;
                            }
                        }
                    } else if ((actionType === 'RESCAN') && roomCode) {
                        for (const session of this.sessions.values()) {
                            if (session.accessCode === roomCode) {
                                session.scanDiscordHistory();
                                break;
                            }
                        }
                    }
                } catch (e) {}
            });

            ws.on('close', () => {
                this.clientSockets.delete(ws);
            });
        });

        this.server.listen(WEBSOCKET_PORT, () => {
            console.log(`🌐 Daemon Server listening on port ${WEBSOCKET_PORT}`);
        });
    }

    broadcastToRoom(roomCode, payload) {
        const raw = JSON.stringify(payload);
        this.clientSockets.forEach(ws => {
            if (ws.readyState === 1 && ws.subscribedRooms && ws.subscribedRooms.has(roomCode)) {
                ws.send(raw);
            }
        });
    }

    broadcastGlobal(payload) {
        const raw = JSON.stringify(payload);
        this.clientSockets.forEach(ws => {
            if (ws.readyState === 1) {
                ws.send(raw);
            }
        });
    }

    async loadAndStartServers() {
        try {
            console.log('📡 Menghubungi Laravel Internal API untuk memuat daftar server aktif...');
            const res = await axios.get(`${LARAVEL_API_URL}/api/internal/servers`, {
                headers: { 'X-Internal-Secret': INTERNAL_SECRET },
                timeout: 5000
            });

            if (res.data && res.data.success && Array.isArray(res.data.data)) {
                const serverList = res.data.data;
                console.log(`📋 Ditemukan ${serverList.length} server aktif di database.`);

                for (const srv of serverList) {
                    const session = new SealServerSession(srv, this);
                    this.sessions.set(srv.id, session);
                    await session.start();
                }
            }
        } catch (err) {
            console.error('❌ Gagal memuat server dari Laravel API:', err.message);
            console.log('   Pastikan server Laravel berjalan di', LARAVEL_API_URL);
        }
    }

    async handleServerControl(serverId, action) {
        let session = this.sessions.get(serverId);
        const act = (action || '').toUpperCase();

        if (act === 'START') {
            if (!session) {
                const res = await axios.get(`${LARAVEL_API_URL}/api/internal/servers`, {
                    headers: { 'X-Internal-Secret': INTERNAL_SECRET }
                });
                const srvData = res.data.data.find(s => s.id === serverId);
                if (srvData) {
                    session = new SealServerSession(srvData, this);
                    this.sessions.set(serverId, session);
                }
            }
            if (session) await session.start();
        } else if (act === 'STOP') {
            if (session) await session.stop();
        } else if (act === 'RESTART') {
            if (session) {
                await session.stop();
                await session.start();
            }
        } else if (act === 'RESCAN') {
            if (session) await session.scanDiscordHistory();
        }
    }

    handleUpdateInterval(serverId, bossKey, intervalMinutes) {
        const session = this.sessions.get(serverId);
        if (!session) return;

        const minutes = parseInt(intervalMinutes, 10);
        if (session.activeBossMap.has(bossKey)) {
            const b = session.activeBossMap.get(bossKey);
            b.durationMinutes = minutes;
            if (b.status === 'COUNTDOWN' && b.killedAt) {
                b.targetEndTime = b.killedAt + (minutes * 60 * 1000);
            }
            this.broadcastToRoom(session.accessCode, { type: 'BOSS_UPDATE', boss: b });
        }

        session.bossConfigs.set(bossKey, {
            bossName: bossKey.split('__')[0],
            durationMinutes: minutes,
            autoLearned: false
        });

        session.syncWithLaravel();
    }

    handleManualEvent(serverId, type, bossName, location, durationMinutes) {
        const session = this.sessions.get(serverId);
        if (!session) return;

        const dur = durationMinutes || 30;
        const now = Date.now();
        const baseKey = `${bossName}__${location || 'Lokasi Unknown'}`.toLowerCase().replace(/[^a-z0-9]/g, '_');
        const slotKey = `${baseKey}_slot_1`;

        if (type === 'SPAWN') {
            const bossObj = {
                key: slotKey,
                bossName: bossName,
                location: location || 'Lokasi Unknown',
                slot: 1,
                status: 'SPAWNED',
                killedAt: null,
                targetEndTime: now,
                durationMinutes: dur
            };
            session.activeBossMap.set(slotKey, bossObj);
            this.broadcastToRoom(session.accessCode, { type: 'BOSS_UPDATE', boss: bossObj });
        } else if (type === 'KILL') {
            const targetEndTime = now + (dur * 60 * 1000);
            const bossObj = {
                key: slotKey,
                bossName: bossName,
                location: location || 'Lokasi Unknown',
                slot: 1,
                status: 'COUNTDOWN',
                killedAt: now,
                targetEndTime: targetEndTime,
                durationMinutes: dur
            };
            session.activeBossMap.set(slotKey, bossObj);
            this.broadcastToRoom(session.accessCode, { type: 'BOSS_UPDATE', boss: bossObj });
        }

        session.syncWithLaravel();
    }

    handleResetBoss(serverId, bossKey) {
        const session = this.sessions.get(serverId);
        if (!session || !session.activeBossMap.has(bossKey)) return;

        const b = session.activeBossMap.get(bossKey);
        b.status = 'SPAWNED';
        b.killedAt = null;
        b.targetEndTime = Date.now();

        this.broadcastToRoom(session.accessCode, { type: 'BOSS_UPDATE', boss: b });
        session.syncWithLaravel();
    }

    handleDeleteBoss(serverId, bossKey) {
        const session = this.sessions.get(serverId);
        if (!session) return;

        session.activeBossMap.delete(bossKey);
        this.broadcastToRoom(session.accessCode, {
            type: 'INITIAL_SYNC',
            bosses: Array.from(session.activeBossMap.values())
        });
        session.syncWithLaravel();
    }

    async updateServerStatus(serverId, status, errorMsg = null, lastScreenedAt = null) {
        try {
            await axios.post(`${LARAVEL_API_URL}/api/internal/servers/${serverId}/status`, {
                bot_status: status,
                last_error: errorMsg,
                last_screened_at: lastScreenedAt
            }, {
                headers: { 'X-Internal-Secret': INTERNAL_SECRET },
                timeout: 3000
            });
        } catch (e) {}
    }

    async syncStatesToLaravel(serverId, states, configs) {
        try {
            await axios.post(`${LARAVEL_API_URL}/api/internal/servers/${serverId}/sync`, {
                states: states,
                configs: configs
            }, {
                headers: { 'X-Internal-Secret': INTERNAL_SECRET },
                timeout: 5000
            });
        } catch (e) {}
    }
}

// -------------------------------------------------------------
// Initialize & Launch
// -------------------------------------------------------------
const daemon = new MultiServerBotDaemon();
daemon.init();

module.exports = { MultiServerBotDaemon, SealServerSession };
