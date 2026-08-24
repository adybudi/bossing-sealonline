/**
 * Seal Online - Boss Respawn Countdown Timer
 * Pure JavaScript with LocalStorage (JSON) persistence and Web Audio API Alarm
 */

// Key for LocalStorage
const STORAGE_KEY = 'seal_boss_timers_json';
const VIEW_MODE_KEY = 'seal_boss_view_mode';
const SORT_MODE_KEY = 'seal_boss_sort_mode';

// State
let bosses = [];
let timerInterval = null;
let audioCtx = null;
let alarmInterval = null;
let currentViewMode = localStorage.getItem(VIEW_MODE_KEY) || 'card';
let currentSortMode = localStorage.getItem(SORT_MODE_KEY) || 'spawn_desc';

// DOM Elements
const bossForm = document.getElementById('bossForm');
const bossNameInput = document.getElementById('bossName');
const bossLocationInput = document.getElementById('bossLocation');
const spawnHoursInput = document.getElementById('spawnHours');
const spawnMinutesInput = document.getElementById('spawnMinutes');
const spawnSecondsInput = document.getElementById('spawnSeconds');
const bossListContainer = document.getElementById('bossList');
const emptyState = document.getElementById('emptyState');
const bossCountBadge = document.getElementById('bossCount');
const listControls = document.getElementById('listControls');

// Discord Live Status and Paste Elements
const discordStatusBadge = document.getElementById('discordStatusBadge');
const discordStatusText = document.getElementById('discordStatusText');
const btnOpenPasteModal = document.getElementById('btnOpenPasteModal');
const pasteModal = document.getElementById('pasteModal');
const btnClosePasteModal = document.getElementById('btnClosePasteModal');
const btnCancelPaste = document.getElementById('btnCancelPaste');
const btnProcessPaste = document.getElementById('btnProcessPaste');
const discordPasteInput = document.getElementById('discordPasteInput');
const pasteFeedback = document.getElementById('pasteFeedback');

// View mode and Sort controls
const btnViewCard = document.getElementById('btnViewCard');
const btnViewList = document.getElementById('btnViewList');
const sortSelect = document.getElementById('sortSelect');

// Action all buttons
const btnStartAll = document.getElementById('btnStartAll');
const btnPauseAll = document.getElementById('btnPauseAll');
const btnResetAll = document.getElementById('btnResetAll');
const btnClearAll = document.getElementById('btnClearAll');
const btnSoundTest = document.getElementById('btnSoundTest');

// Alarm modal
const alarmOverlay = document.getElementById('alarmOverlay');
const alarmBossName = document.getElementById('alarmBossName');
const alarmBossLocation = document.getElementById('alarmBossLocation');
const btnDismissAlarm = document.getElementById('btnDismissAlarm');

// Preset buttons
const presetButtons = document.querySelectorAll('.btn-preset');

// Initialize Web Audio API for offline alarm sound
function getAudioContext() {
    if (!audioCtx) {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (AudioContext) {
            audioCtx = new AudioContext();
        }
    }
    if (audioCtx && audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    return audioCtx;
}

// Play notification sound (Beep / Melody)
function playBossAlarmSound() {
    try {
        const ctx = getAudioContext();
        if (!ctx) return;

        const now = ctx.currentTime;
        const notes = [523.25, 659.25, 783.99, 1046.5]; // C5, E5, G5, C6 (Fanfare chord)

        notes.forEach((freq, index) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'triangle';
            osc.frequency.setValueAtTime(freq, now + index * 0.12);

            gain.gain.setValueAtTime(0, now + index * 0.12);
            gain.gain.linearRampToValueAtTime(0.3, now + index * 0.12 + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.001, now + index * 0.12 + 0.35);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now + index * 0.12);
            osc.stop(now + index * 0.12 + 0.4);
        });
    } catch (e) {
        console.warn('Audio playback error:', e);
    }
}

// Start continuous alarm sequence until stopped
function triggerContinuousAlarm(boss) {
    playBossAlarmSound();
    
    // Clear any previous alarm interval
    if (alarmInterval) clearInterval(alarmInterval);

    // Repeat alarm every 1.5s
    alarmInterval = setInterval(() => {
        playBossAlarmSound();
    }, 1500);

    // Show modal alert
    alarmBossName.textContent = `🚨 ${boss.name} TELAH SPAWN!`;
    alarmBossLocation.textContent = `📍 Lokasi: ${boss.location}`;
    alarmOverlay.style.display = 'flex';

    // Show desktop notification if granted
    if ('Notification' in window && Notification.permission === 'granted') {
        try {
            new Notification(`[Seal Online] ${boss.name} Muncul!`, {
                body: `Lokasi: ${boss.location} - Waktu respawn telah tiba!`,
                icon: '⚔️'
            });
        } catch (e) {
            console.log('Desktop notification error:', e);
        }
    }
}

// Stop alarm
function stopAlarm() {
    if (alarmInterval) {
        clearInterval(alarmInterval);
        alarmInterval = null;
    }
    alarmOverlay.style.display = 'none';
}

// Request notification permission if supported
function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

// Format seconds into HH:MM:SS
function formatTime(totalSeconds) {
    const s = Math.max(0, Math.floor(totalSeconds));
    const hours = Math.floor(s / 3600);
    const minutes = Math.floor((s % 3600) / 60);
    const seconds = s % 60;

    const pad = (num) => String(num).padStart(2, '0');
    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
}

// Load data from LocalStorage (JSON)
function loadBossesFromJSON() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
        try {
            bosses = JSON.parse(saved);
            
            // Adjust running bosses based on timestamps
            const now = Date.now();
            bosses.forEach(boss => {
                if (boss.status === 'running' && boss.targetEndTime) {
                    const remainingMs = boss.targetEndTime - now;
                    if (remainingMs <= 0) {
                        boss.remainingSeconds = 0;
                        boss.status = 'spawned';
                    } else {
                        boss.remainingSeconds = Math.ceil(remainingMs / 1000);
                    }
                }
            });
        } catch (e) {
            console.error('Error parsing JSON from localStorage', e);
            bosses = [];
        }
    } else {
        bosses = [];
    }
}

// Save data to LocalStorage (JSON)
function saveBossesToJSON() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(bosses));
}

// Get Sorted Boss List based on active sort mode
function getSortedBosses() {
    const list = [...bosses];

    list.sort((a, b) => {
        const aSpawned = a.status === 'spawned' || a.remainingSeconds <= 0;
        const bSpawned = b.status === 'spawned' || b.remainingSeconds <= 0;

        // Prioritas 1: Boss yang sudah spawn selalu ditaruh di paling atas
        if (currentSortMode === 'spawn_desc' || currentSortMode === 'spawn_asc') {
            if (aSpawned && !bSpawned) return -1;
            if (!aSpawned && bSpawned) return 1;
        }

        switch (currentSortMode) {
            case 'spawn_desc':
                // Sesuai request: Dari countdown terbesar ke terkecil
                return b.remainingSeconds - a.remainingSeconds;

            case 'spawn_asc':
                // Dari countdown terkecil ke terbesar (segera spawn)
                return a.remainingSeconds - b.remainingSeconds;

            case 'name':
                // Urutan alfabet Nama Boss (A-Z)
                return a.name.localeCompare(b.name, 'id');

            default:
                return b.remainingSeconds - a.remainingSeconds;
        }
    });

    return list;
}

// Render boss list in DOM
function renderBossList() {
    bossListContainer.innerHTML = '';
    const total = bosses.length;

    bossCountBadge.textContent = `${total} Boss`;

    if (total === 0) {
        emptyState.style.display = 'block';
        listControls.style.display = 'none';
        return;
    }

    emptyState.style.display = 'none';
    listControls.style.display = 'flex';

    const sortedList = getSortedBosses();

    sortedList.forEach((boss) => {
        const card = document.createElement('div');
        card.className = `boss-card status-${boss.status}`;
        card.id = `boss-card-${boss.id}`;

        // Badge text & status
        let statusBadgeText = 'Standby';
        let statusBadgeClass = 'badge-idle';
        if (boss.status === 'running') {
            statusBadgeText = 'Berjalan';
            statusBadgeClass = 'badge-running';
        } else if (boss.status === 'paused') {
            statusBadgeText = 'Berhenti (Pause)';
            statusBadgeClass = 'badge-paused';
        } else if (boss.status === 'spawned') {
            statusBadgeText = 'SPAWN / READY';
            statusBadgeClass = 'badge-spawned';
        }

        // Calculate progress percentage
        const progressPercent = boss.totalSeconds > 0 
            ? ((boss.remainingSeconds / boss.totalSeconds) * 100).toFixed(1) 
            : 0;

        card.innerHTML = `
            <div class="boss-card-top">
                <div class="boss-info">
                    <h3 class="boss-title" title="${escapeHtml(boss.name)}">👾 ${escapeHtml(boss.name)}</h3>
                    <div class="boss-location">📍 ${escapeHtml(boss.location)}</div>
                </div>
                <span class="boss-badge ${statusBadgeClass}">${statusBadgeText}</span>
            </div>

            <div class="timer-progress-wrap">
                <div class="timer-progress-bar" style="width: ${progressPercent}%"></div>
            </div>

            <div class="boss-timer-body">
                <div class="timer-countdown" id="timer-display-${boss.id}">${formatTime(boss.remainingSeconds)}</div>
                <div class="timer-subtext" onclick="editBossInterval('${boss.id}')" title="Klik untuk mengubah interval menit" style="cursor: pointer; user-select: none;">Interval: ${formatTime(boss.totalSeconds)} ✏️</div>
            </div>

            <div class="boss-actions">
                ${boss.status === 'running' 
                    ? `<button class="btn btn-warning" onclick="pauseBoss('${boss.id}')" title="Jeda countdown">⏸ Berhenti</button>` 
                    : `<button class="btn btn-success" onclick="startBoss('${boss.id}')" title="Mulai countdown">▶ Start</button>`
                }
                <button class="btn btn-info" onclick="resetBoss('${boss.id}')" title="Reset waktu ke awal">🔄 Reset</button>
                <button class="btn btn-delete-icon" onclick="deleteBoss('${boss.id}')" title="Hapus Boss">🗑</button>
            </div>
        `;

        bossListContainer.appendChild(card);
    });
}

// Sanitize HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Timer Loop
function startMainLoop() {
    if (timerInterval) clearInterval(timerInterval);

    timerInterval = setInterval(() => {
        let hasChanges = false;
        const now = Date.now();

        bosses.forEach((boss) => {
            if (boss.status === 'running') {
                if (boss.targetEndTime) {
                    const remainingMs = boss.targetEndTime - now;
                    const newRemaining = Math.max(0, Math.ceil(remainingMs / 1000));

                    if (newRemaining !== boss.remainingSeconds) {
                        boss.remainingSeconds = newRemaining;
                        hasChanges = true;

                        // Check if finished
                        if (boss.remainingSeconds <= 0) {
                            boss.status = 'spawned';
                            boss.targetEndTime = null;
                            triggerContinuousAlarm(boss);
                            renderBossList();
                        } else {
                            // Update just the text & progress bar for high performance
                            updateCardDisplay(boss);
                        }
                    }
                }
            }
        });

        if (hasChanges) {
            saveBossesToJSON();
        }
    }, 500);
}

// Quick DOM update without full re-render
function updateCardDisplay(boss) {
    const displayEl = document.getElementById(`timer-display-${boss.id}`);
    if (displayEl) {
        displayEl.textContent = formatTime(boss.remainingSeconds);
    }
    const cardEl = document.getElementById(`boss-card-${boss.id}`);
    if (cardEl) {
        const progressBar = cardEl.querySelector('.timer-progress-bar');
        if (progressBar && boss.totalSeconds > 0) {
            const pct = ((boss.remainingSeconds / boss.totalSeconds) * 100).toFixed(1);
            progressBar.style.width = `${pct}%`;
        }
    }
}

// Boss Actions
window.startBoss = function(id) {
    getAudioContext();
    requestNotificationPermission();

    const boss = bosses.find(b => b.id === id);
    if (!boss) return;

    // If already at 0, reset before starting
    if (boss.remainingSeconds <= 0) {
        boss.remainingSeconds = boss.totalSeconds;
    }

    boss.status = 'running';
    boss.targetEndTime = Date.now() + (boss.remainingSeconds * 1000);

    saveBossesToJSON();
    renderBossList();
};

window.pauseBoss = function(id) {
    const boss = bosses.find(b => b.id === id);
    if (!boss) return;

    if (boss.status === 'running') {
        const now = Date.now();
        if (boss.targetEndTime) {
            boss.remainingSeconds = Math.max(0, Math.ceil((boss.targetEndTime - now) / 1000));
        }
        boss.status = 'paused';
        boss.targetEndTime = null;

        saveBossesToJSON();
        renderBossList();
    }
};

window.resetBoss = function(id) {
    const boss = bosses.find(b => b.id === id);
    if (!boss) return;

    boss.status = 'idle';
    boss.remainingSeconds = boss.totalSeconds;
    boss.targetEndTime = null;

    saveBossesToJSON();
    renderBossList();
};

window.deleteBoss = function(id) {
    bosses = bosses.filter(b => b.id !== id);
    saveBossesToJSON();
    renderBossList();
};

// Global Batch Actions
btnStartAll.addEventListener('click', () => {
    getAudioContext();
    requestNotificationPermission();

    const now = Date.now();
    bosses.forEach(boss => {
        if (boss.status !== 'running') {
            if (boss.remainingSeconds <= 0) {
                boss.remainingSeconds = boss.totalSeconds;
            }
            boss.status = 'running';
            boss.targetEndTime = now + (boss.remainingSeconds * 1000);
        }
    });
    saveBossesToJSON();
    renderBossList();
});

btnPauseAll.addEventListener('click', () => {
    const now = Date.now();
    bosses.forEach(boss => {
        if (boss.status === 'running') {
            if (boss.targetEndTime) {
                boss.remainingSeconds = Math.max(0, Math.ceil((boss.targetEndTime - now) / 1000));
            }
            boss.status = 'paused';
            boss.targetEndTime = null;
        }
    });
    saveBossesToJSON();
    renderBossList();
});

btnResetAll.addEventListener('click', () => {
    bosses.forEach(boss => {
        boss.status = 'idle';
        boss.remainingSeconds = boss.totalSeconds;
        boss.targetEndTime = null;
    });
    saveBossesToJSON();
    renderBossList();
});

btnClearAll.addEventListener('click', () => {
    if (bosses.length === 0) return;
    if (confirm('Apakah Anda yakin ingin menghapus semua data boss?')) {
        bosses = [];
        saveBossesToJSON();
        renderBossList();
    }
});

// Form Submission
bossForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const name = bossNameInput.value.trim();
    const location = bossLocationInput.value.trim();
    const hours = parseInt(spawnHoursInput.value, 10) || 0;
    const minutes = parseInt(spawnMinutesInput.value, 10) || 0;
    const seconds = parseInt(spawnSecondsInput.value, 10) || 0;

    const totalSeconds = (hours * 3600) + (minutes * 60) + seconds;

    if (!name) {
        alert('Silakan masukkan nama boss!');
        bossNameInput.focus();
        return;
    }

    if (!location) {
        alert('Silakan masukkan lokasi boss!');
        bossLocationInput.focus();
        return;
    }

    if (totalSeconds <= 0) {
        alert('Durasi waktu spawn harus lebih dari 0 detik!');
        spawnMinutesInput.focus();
        return;
    }

    // Create new boss entry (JSON structure)
    const newBoss = {
        id: 'boss_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
        name: name,
        location: location,
        totalSeconds: totalSeconds,
        remainingSeconds: totalSeconds,
        status: 'idle',
        targetEndTime: null
    };

    bosses.unshift(newBoss); // Add to beginning of list
    saveBossesToJSON();
    renderBossList();

    // Reset inputs
    bossNameInput.value = '';
    bossLocationInput.value = '';
    bossNameInput.focus();

    // User gesture initializes audio
    getAudioContext();
    requestNotificationPermission();
});

// View Mode Switcher
function setViewMode(mode) {
    currentViewMode = mode;
    localStorage.setItem(VIEW_MODE_KEY, mode);

    if (mode === 'list') {
        bossListContainer.classList.add('boss-view-list');
        btnViewList.classList.add('active');
        btnViewCard.classList.remove('active');
    } else {
        bossListContainer.classList.remove('boss-view-list');
        btnViewCard.classList.add('active');
        btnViewList.classList.remove('active');
    }
}

btnViewCard.addEventListener('click', () => {
    setViewMode('card');
});

btnViewList.addEventListener('click', () => {
    setViewMode('list');
});

// Sort mode change listener
if (sortSelect) {
    sortSelect.addEventListener('change', (e) => {
        currentSortMode = e.target.value;
        localStorage.setItem(SORT_MODE_KEY, currentSortMode);
        renderBossList();
    });
}

// Preset button handlers
presetButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const h = btn.getAttribute('data-h') || '0';
        const m = btn.getAttribute('data-m') || '0';
        const s = btn.getAttribute('data-s') || '0';

        spawnHoursInput.value = h;
        spawnMinutesInput.value = m;
        spawnSecondsInput.value = s;
    });
});

// Test Alarm Button
btnSoundTest.addEventListener('click', () => {
    getAudioContext();
    playBossAlarmSound();
});

// Dismiss Alarm Button
btnDismissAlarm.addEventListener('click', () => {
    stopAlarm();
});

// -------------------------------------------------------------
// Client Boss Config & Parser (Populated dynamically from Discord)
// -------------------------------------------------------------
let clientBossConfig = {};

// Try fetching remote config if served via server
function loadRemoteBossConfig() {
    fetch('/boss-config.json')
        .then(res => res.json())
        .then(data => { clientBossConfig = Object.assign(clientBossConfig, data); })
        .catch(() => {});
}

function getClientBossMeta(name, fallbackLocation = '') {
    const cleanName = name.trim();
    if (clientBossConfig[cleanName]) return clientBossConfig[cleanName];
    for (const key of Object.keys(clientBossConfig)) {
        if (key.toLowerCase() === cleanName.toLowerCase()) return clientBossConfig[key];
    }
    return { durationMinutes: 60, location: fallbackLocation || 'Lokasi Unknown', category: 'besar' };
}

function cleanBossName(raw) {
    if (!raw) return '';
    let s = raw.trim();
    if (s.startsWith('[') && s.endsWith(']')) {
        s = s.slice(1, -1).trim();
    }
    return s;
}

const clientBossSlotsMap = new Map();
const clientKillQueueMap = new Map();

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
    if (minDiff <= 8 || (minDiff / closest) <= 0.08) {
        return closest;
    }
    return Math.round(rawMinutes);
}

function parseDiscordLogTextClient(rawText) {
    if (!rawText) return [];

    const spawnRegex = /\[Monster\]::\s*(?<nameRaw>[\s\S]+?)\s+muncul di\s+\[(?<loc>[^\]]+)\](?:[\s\S]*?\[(?<time>\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2})\])?/gi;
    const killRegex = /\[Monster\]::\s*(?<nameRaw>[\s\S]+?)\s+dikalahkan oleh\s+\[(?<killer>[^\]]+)\](?:[\s\S]*?\[(?<time>\d{2}-\d{2}-\d{4}\s+\d{2}:\d{2}:\d{2})\])?/gi;

    function parseTimestamp(timeStr) {
        if (!timeStr) return Date.now();
        const [datePart, timePart] = timeStr.split(' ');
        const [d, m, y] = datePart.split('-');
        const parsed = new Date(`${y}-${m}-${d}T${timePart}`);
        return isNaN(parsed.getTime()) ? Date.now() : parsed.getTime();
    }

    const allEvents = [];
    let match;

    while ((match = spawnRegex.exec(rawText)) !== null) {
        allEvents.push({
            type: 'SPAWN',
            name: cleanBossName(match.groups.nameRaw),
            location: match.groups.loc.trim(),
            timeStr: match.groups.time,
            timestamp: parseTimestamp(match.groups.time),
            index: match.index
        });
    }

    while ((match = killRegex.exec(rawText)) !== null) {
        allEvents.push({
            type: 'KILLED',
            name: cleanBossName(match.groups.nameRaw),
            killer: match.groups.killer ? match.groups.killer.trim() : '',
            timeStr: match.groups.time,
            timestamp: parseTimestamp(match.groups.time),
            index: match.index
        });
    }

    // Sort chronologically
    allEvents.sort((a, b) => a.timestamp - b.timestamp || a.index - b.index);

    let learnedCount = 0;
    const finalResults = [];

    allEvents.forEach(ev => {
        const bossKey = ev.name.toLowerCase();

        if (ev.type === 'SPAWN') {
            const { key: baseKey, list: slotList } = getClientSlotList(ev.name, ev.location);

            let targetSlot = slotList.find(s => s.status !== 'spawned');
            let slotNum = 1;

            if (!targetSlot) {
                slotNum = slotList.length + 1;
                const displayName = slotNum > 1 ? `${ev.name} #${slotNum}` : ev.name;
                targetSlot = {
                    slotNumber: slotNum,
                    name: ev.name,
                    displayName: displayName,
                    location: ev.location,
                    status: 'spawned',
                    lastSpawnTime: ev.timestamp,
                    lastKillTime: null
                };
                slotList.push(targetSlot);

                if (slotList.length === 2 && !slotList[0].displayName.includes('#')) {
                    slotList[0].displayName = `${ev.name} #1`;
                }
            } else {
                slotNum = targetSlot.slotNumber;
                targetSlot.status = 'spawned';
                targetSlot.lastSpawnTime = ev.timestamp;
            }

            // Find matching kill in FIFO queue (filter out false 5-7m pairings between twin bosses)
            const queue = clientKillQueueMap.get(bossKey) || [];
            let matchedKillIndex = -1;
            let matchedKill = null;

            for (let i = 0; i < queue.length; i++) {
                const k = queue[i];
                if (k.timestamp < ev.timestamp) {
                    const diffMs = ev.timestamp - k.timestamp;
                    const diffMins = Math.round(diffMs / 60000);
                    if (diffMins >= 10 && diffMins <= 720) {
                        matchedKillIndex = i;
                        matchedKill = k;
                        break;
                    }
                }
            }

            if (matchedKill) {
                queue.splice(matchedKillIndex, 1);
                const rawDiffMs = ev.timestamp - matchedKill.timestamp;
                const diffMinutes = snapToStandardInterval(rawDiffMs / 60000);

                if (diffMinutes >= 10 && diffMinutes <= 720) {
                    learnedCount++;
                    clientBossConfig[ev.name] = {
                        durationMinutes: diffMinutes,
                        location: ev.location || matchedKill.location,
                        autoLearned: true
                    };
                }
            }

            const meta = getClientBossMeta(ev.name, ev.location);

            finalResults.push({
                action: 'SPAWN',
                name: targetSlot.displayName,
                baseName: ev.name,
                location: ev.location || meta.location,
                durationSeconds: meta.durationMinutes * 60,
                remainingSeconds: 0,
                status: 'spawned',
                autoLearned: !!meta.autoLearned,
                timestamp: ev.timeStr || new Date(ev.timestamp).toISOString()
            });

        } else if (ev.type === 'KILLED') {
            let matchedSlot = null;
            let oldestSpawnTime = Infinity;
            let matchedBaseKey = '';

            for (const [key, slotList] of clientBossSlotsMap.entries()) {
                if (key.startsWith(ev.name.toLowerCase().replace(/[^a-z0-9]/g, '_'))) {
                    for (const slot of slotList) {
                        if (slot.status === 'spawned' && slot.lastSpawnTime < oldestSpawnTime) {
                            oldestSpawnTime = slot.lastSpawnTime;
                            matchedSlot = slot;
                            matchedBaseKey = key;
                        }
                    }
                }
            }

            if (!matchedSlot) {
                for (const [key, slotList] of clientBossSlotsMap.entries()) {
                    if (key.startsWith(ev.name.toLowerCase().replace(/[^a-z0-9]/g, '_')) && slotList.length > 0) {
                        matchedSlot = slotList[0];
                        matchedBaseKey = key;
                        break;
                    }
                }
            }

            let resolvedLocation = matchedSlot ? matchedSlot.location : getClientBossMeta(ev.name).location;
            let displayName = matchedSlot ? matchedSlot.displayName : ev.name;
            let slotNum = matchedSlot ? matchedSlot.slotNumber : 1;

            if (matchedSlot) {
                matchedSlot.status = 'running';
                matchedSlot.lastKillTime = ev.timestamp;
                matchedSlot.killer = ev.killer;
            }

            const baseKey = matchedBaseKey || `${ev.name}__${resolvedLocation}`.toLowerCase().replace(/[^a-z0-9]/g, '_');
            const slotUniqueKey = `${baseKey}_slot_${slotNum}`;

            const killRecord = {
                timestamp: ev.timestamp,
                location: resolvedLocation,
                killer: ev.killer
            };
            clientLastKilledMap.set(slotUniqueKey, killRecord);
            clientLastKilledMap.set(baseKey, killRecord);
            clientLastKilledMap.set(ev.name.toLowerCase(), killRecord);

            if (!clientKillQueueMap.has(bossKey)) clientKillQueueMap.set(bossKey, []);
            clientKillQueueMap.get(bossKey).push(killRecord);

            const meta = getClientBossMeta(ev.name, resolvedLocation);
            const durationSeconds = meta.durationMinutes * 60;
            const targetEndTime = ev.timestamp + (durationSeconds * 1000);
            const remainingSeconds = Math.max(0, Math.ceil((targetEndTime - Date.now()) / 1000));
            const status = remainingSeconds <= 0 ? 'spawned' : 'running';

            finalResults.push({
                action: 'KILLED',
                name: displayName,
                baseName: ev.name,
                location: resolvedLocation,
                durationSeconds: durationSeconds,
                remainingSeconds: remainingSeconds,
                targetEndTime: status === 'running' ? targetEndTime : null,
                status: status,
                killer: ev.killer,
                autoLearned: !!meta.autoLearned,
                timestamp: ev.timeStr || new Date(ev.timestamp).toISOString()
            });
        }
    });

    finalResults.learnedCount = learnedCount;
    return finalResults;
}

// Edit boss interval directly
window.editBossInterval = function(id) {
    const boss = bosses.find(b => b.id === id);
    if (!boss) return;
    const currentMins = Math.round(boss.totalSeconds / 60);
    const input = prompt(`Ubah interval respawn untuk "${boss.name}" (dalam menit):`, currentMins);
    if (input === null) return;
    const newMins = parseInt(input, 10);
    if (isNaN(newMins) || newMins <= 0) {
        alert('Durasi menit tidak valid!');
        return;
    }
    boss.totalSeconds = newMins * 60;
    if (boss.status === 'idle') {
        boss.remainingSeconds = boss.totalSeconds;
    } else if (boss.status === 'running' && boss.targetEndTime) {
        // Recalculate targetEndTime with new duration
        boss.remainingSeconds = Math.min(boss.remainingSeconds, boss.totalSeconds);
    }
    saveBossesToJSON();
    renderBossList();
};

// Apply single boss event to state
function applyBossEvent(event) {
    // Match by name and location (so same boss in different maps has separate cards)
    const existingIndex = bosses.findIndex(b => 
        b.name.toLowerCase() === event.name.toLowerCase() &&
        (!event.location || !b.location || b.location.toLowerCase() === event.location.toLowerCase())
    );

    const isRunning = event.status === 'running';
    const targetEnd = isRunning ? (event.targetEndTime || (Date.now() + (event.remainingSeconds * 1000))) : null;

    if (existingIndex !== -1) {
        const b = bosses[existingIndex];
        b.location = event.location || b.location;
        b.totalSeconds = event.durationSeconds || b.totalSeconds;
        b.remainingSeconds = event.remainingSeconds;
        b.status = event.status;
        b.targetEndTime = targetEnd;

        if (event.status === 'spawned') {
            triggerContinuousAlarm(b);
        } else {
            if (alarmBossName && alarmBossName.textContent.toLowerCase().includes(b.name.toLowerCase())) {
                stopAlarm();
            }
        }
    } else {
        const newBoss = {
            id: 'boss_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
            name: event.name,
            location: event.location,
            totalSeconds: event.durationSeconds,
            remainingSeconds: event.remainingSeconds,
            status: event.status,
            targetEndTime: targetEnd
        };
        bosses.unshift(newBoss);

        if (event.status === 'spawned') {
            triggerContinuousAlarm(newBoss);
        }
    }

    saveBossesToJSON();
    renderBossList();
}

// -------------------------------------------------------------
// WebSocket Real-time Live Connection
// -------------------------------------------------------------
let liveWs = null;
function initWebSocket() {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const host = window.location.host || 'localhost:3000';
    const wsUrl = `${protocol}//${host}`;

    try {
        liveWs = new WebSocket(wsUrl);

        liveWs.onopen = () => {
            console.log('[WebSocket] Terhubung ke server lokal.');
        };

        liveWs.onmessage = (event) => {
            try {
                const msg = JSON.parse(event.data);
                if (msg.type === 'DISCORD_STATUS') {
                    updateDiscordStatusBadge(msg);
                } else if (msg.type === 'INITIAL_SYNC') {
                    updateDiscordStatusBadge(msg);
                    if (msg.bossConfig) {
                        clientBossConfig = Object.assign(clientBossConfig, msg.bossConfig);
                    }
                    if (msg.bosses && msg.bosses.length > 0) {
                        console.log(`📥 [Sync Riwayat] Memuat ${msg.bosses.length} status boss dari Discord history.`);
                        msg.bosses.forEach(b => {
                            applyBossEvent({
                                action: b.status === 'spawned' ? 'SPAWN' : 'KILLED',
                                name: b.name,
                                location: b.location,
                                category: b.category,
                                durationSeconds: b.totalSeconds || b.durationSeconds,
                                remainingSeconds: b.remainingSeconds,
                                targetEndTime: b.targetEndTime,
                                status: b.status,
                                killer: b.killer,
                                autoLearned: b.autoLearned
                            });
                        });
                    }
                } else if (msg.type === 'BOSS_CONFIG_UPDATED') {
                    console.log(`🧠 [Config Terdeteksi] Boss ${msg.bossName}: ${msg.data.durationMinutes}m`);
                    clientBossConfig[msg.bossName] = msg.data;
                } else if (msg.type === 'BOSS_EVENT') {
                    console.log('⚡ [Live Event Diterima]', msg.data);
                    applyBossEvent(msg.data);
                }
            } catch (e) {
                console.error('[WebSocket Error Parse]', e);
            }
        };

        liveWs.onclose = () => {
            updateDiscordStatusBadge({ connected: false });
            // Retry connect every 5 seconds
            setTimeout(initWebSocket, 5000);
        };

        liveWs.onerror = () => {
            updateDiscordStatusBadge({ connected: false });
        };
    } catch (e) {
        updateDiscordStatusBadge({ connected: false });
    }
}

function updateDiscordStatusBadge(status) {
    if (!discordStatusBadge || !discordStatusText) return;

    if (status.connected) {
        discordStatusBadge.className = 'badge-discord badge-discord-on';
        discordStatusText.textContent = `Live Discord: Online (${status.username || 'Aktif'})`;
    } else {
        discordStatusBadge.className = 'badge-discord badge-discord-off';
        discordStatusText.textContent = 'Live Discord: Offline';
    }
}

const btnRescanDiscord = document.getElementById('btnRescanDiscord');
if (btnRescanDiscord) {
    btnRescanDiscord.addEventListener('click', () => {
        if (liveWs && liveWs.readyState === WebSocket.OPEN) {
            btnRescanDiscord.textContent = '⏳ Scanning...';
            liveWs.send(JSON.stringify({ type: 'RESCAN_HISTORY' }));
            setTimeout(() => {
                btnRescanDiscord.textContent = '🔄 Scan Riwayat';
            }, 2500);
        } else {
            alert('Server Discord Listener sedang offline. Pastikan Anda sudah menjalankan "npm start" di terminal!');
        }
    });
}

// -------------------------------------------------------------
// Paste Discord Log Modal Handlers
// -------------------------------------------------------------
if (btnOpenPasteModal) {
    btnOpenPasteModal.addEventListener('click', () => {
        discordPasteInput.value = '';
        pasteFeedback.style.display = 'none';
        pasteModal.style.display = 'flex';
        discordPasteInput.focus();
    });
}

function closePasteModal() {
    if (pasteModal) pasteModal.style.display = 'none';
}

if (btnClosePasteModal) btnClosePasteModal.addEventListener('click', closePasteModal);
if (btnCancelPaste) btnCancelPaste.addEventListener('click', closePasteModal);

// Close on clicking overlay backdrop
if (pasteModal) {
    pasteModal.addEventListener('click', (e) => {
        if (e.target === pasteModal) closePasteModal();
    });
}

if (btnProcessPaste) {
    btnProcessPaste.addEventListener('click', () => {
        getAudioContext();
        requestNotificationPermission();

        const rawText = discordPasteInput.value.trim();
        if (!rawText) {
            pasteFeedback.className = 'paste-feedback error';
            pasteFeedback.textContent = 'Silakan paste teks log chat Discord terlebih dahulu!';
            pasteFeedback.style.display = 'block';
            return;
        }

        const events = parseDiscordLogTextClient(rawText);
        if (events.length === 0) {
            pasteFeedback.className = 'paste-feedback error';
            pasteFeedback.textContent = 'Tidak ditemukan format [Monster]::[...] yang cocok pada teks yang Anda paste!';
            pasteFeedback.style.display = 'block';
            return;
        }

        events.forEach(ev => applyBossEvent(ev));

        pasteFeedback.className = 'paste-feedback success';
        let feedbackMsg = `🎉 Berhasil memproses & mengupdate ${events.length} event boss!`;
        if (events.learnedCount > 0) {
            feedbackMsg += ` (🧠 ${events.learnedCount} waktu respawn boss terdeteksi otomatis dari riwayat)`;
        }
        pasteFeedback.textContent = feedbackMsg;
        pasteFeedback.style.display = 'block';

        setTimeout(() => {
            closePasteModal();
        }, 1600);
    });
}

// Initial startup
document.addEventListener('DOMContentLoaded', () => {
    loadBossesFromJSON();
    loadRemoteBossConfig();
    setViewMode(currentViewMode);
    if (sortSelect) {
        sortSelect.value = currentSortMode;
    }
    renderBossList();
    startMainLoop();
    initWebSocket();
});
