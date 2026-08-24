# 🏛️ Perencanaan Arsitektur: Migrasi ke Laravel 12 + Filament Admin + MySQL + Node.js Real-time Daemon

**Dokumen Versi:** 2.0.0 (Laravel 12 + Filament + MySQL Multi-Server)  
**Tanggal:** 22 Agustus 2026  
**Status:** Disetujui & Siap Dieksekusi

---

## 🎯 1. Tujuan & Kebutuhan Sistem

1. **Multi-Server Seal Support**:
   - Mendukung banyak server game Seal secara dinamis melalui dashboard admin (tidak lagi *hardcoded* di `.env`).
2. **Dashboard Admin Modern (Laravel 12 + Filament Admin)**:
   - Mengelola data server Seal (Nama Server, Discord User Token terenkripsi, Channel ID, Interval Standar).
   - Men-generate **Kode Akses Unik (Access Code)** yang panjang dan acak per server Seal (dilengkapi tombol Copy).
   - Mengontrol status bot per server secara langsung: **Start Bot**, **Stop Bot**, **Restart Bot**, dan **Trigger Re-scan 100 Messages**.
   - Mengatur/mengedit interval respawn boss per server.
3. **Landing Page & Viewer Client (Pure Read-Only)**:
   - Pengguna/pemain masuk dengan memasukkan **Kode Akses Unik** pada portal landing page (atau link langsung `domain.com/tracker/{unique_code}`).
   - **Pure Read-Only**: Tidak ada tombol edit interval, tidak ada tombol scan ulang, tidak ada input log manual, tidak bisa mereset timer.
   - **Real-Time Countdown & Notifikasi**: Menerima pembaruan hitung mundur boss, status *SPAWN/READY*, progress bar visual, dan alarm audio/browser push notif.
4. **Keamanan & Hosting-Ready**:
   - Database **MySQL** dengan relasi tabel yang rapi.
   - Discord Token disimpan dengan enkripsi bawaan Laravel (`encrypted` cast / AES-256).
   - Socket WebSocket diisolasi per room sesuai `access_code`.

---

## 🏗️ 2. Arsitektur Hybrid & Alur Data

```
┌────────────────────────────────────────────────────────┐
│                   DISCORD PLATFORM                     │
│     Channel #boss Server A     Channel #boss Server B  │
└───────────────┬────────────────────────┬───────────────┘
                │                        │
                ▼                        ▼
┌────────────────────────────────────────────────────────┐
│         NODE.JS MULTI-BOT DAEMON SERVICE               │
│  - Dynamic Worker Pool (discord.js-selfbot-v13)        │
│  - Screening & Calculation Engine (FIFO, Multi-Slot)   │
│  - WebSocket Server (Port 3001, Rooms by Access Code)  │
└───────────────▲────────────────────────┬───────────────┘
                │ Internal API Sync      │ ws:// (Real-time Broadcast)
                │ (X-Internal-Secret)    │
┌───────────────▼────────────────────────▼───────────────┐
│              LARAVEL 12 BACKEND (MYSQL)                │
│  - Filament Admin Dashboard (/admin)                   │
│  - Database MySQL (servers, tokens, states, configs)   │
│  - Access Code Generator & Verification Middleware     │
│  - Client Dashboard View (/tracker/{access_code})      │
└────────────────────────────────────────────────────────┘
```

---

## 🗄️ 3. Skema Database (MySQL)

### 1. Tabel `seal_servers`
- `id` (BIGINT, PK, Auto Increment)
- `name` (VARCHAR(100)) - Contoh: "Seal BOD Classic"
- `slug` (VARCHAR(100), Unique)
- `access_code` (VARCHAR(64), Unique, Indexed) - Token akses unik pemain
- `discord_token` (TEXT) - Discord User Token (Terenkripsi AES-256)
- `discord_channel_id` (VARCHAR(64)) - ID Channel Discord `#boss`
- `is_active` (BOOLEAN, Default: true)
- `bot_status` (ENUM: `STOPPED`, `STARTING`, `RUNNING`, `ERROR`)
- `last_error` (TEXT, Nullable)
- `last_screened_at` (TIMESTAMP, Nullable)
- `created_at`, `updated_at` (TIMESTAMP)

### 2. Tabel `boss_configs`
- `id` (BIGINT, PK)
- `seal_server_id` (BIGINT, FK -> `seal_servers.id`, On Delete Cascade)
- `boss_name` (VARCHAR(150))
- `map_name` (VARCHAR(100), Nullable)
- `interval_minutes` (INT)
- `is_auto_learned` (BOOLEAN, Default: true)
- `created_at`, `updated_at` (TIMESTAMP)

### 3. Tabel `boss_states`
- `id` (BIGINT, PK)
- `seal_server_id` (BIGINT, FK -> `seal_servers.id`, On Delete Cascade)
- `boss_key` (VARCHAR(150), Indexed)
- `boss_name` (VARCHAR(150))
- `map_name` (VARCHAR(100))
- `slot_index` (INT, Default: 1)
- `status` (ENUM: `COUNTDOWN`, `SPAWNED`, `UNKNOWN`)
- `killed_at` (BIGINT, Nullable) - UTC timestamp ms
- `target_respawn_at` (BIGINT, Nullable) - UTC timestamp ms
- `interval_minutes` (INT)
- `updated_at` (TIMESTAMP)

---

## 🚀 4. Tahapan Pengerjaan

1. **Setup Laravel 12 & Filament v3/v4**:
   - Inisialisasi project Laravel 12 dengan konfigurasi database MySQL.
   - Instalasi Filament Admin Panel & Autentikasi Admin.
   - Buat Migration, Model, dan Factory untuk `seal_servers`, `boss_configs`, dan `boss_states`.

2. **Pengembangan Filament Admin Resource**:
   - CRUD Server Seal: Input nama, token Discord, channel ID.
   - Action generator untuk Access Code Unik.
   - Tombol kontrol bot: Start, Stop, Rescan.
   - Manajemen interval boss per server.

3. **Node.js Bot Daemon Service**:
   - Layanan multi-tenant yang membaca konfigurasi aktif dari API internal Laravel.
   - Inisialisasi client `discord.js-selfbot-v13` untuk setiap server aktif.
   - Integrasi WebSocket Server dengan sistem room berbasis `access_code`.
   - Sinkronisasi snapshot status terkini ke database MySQL.

4. **Landing Page & Pure Read-Only Tracker UI**:
   - Form input Access Code pada halaman depan (`/`).
   - Tampilan Tracker Dark Theme RPG di `/tracker/{access_code}` yang responsif, visual progress bar, alarm suara, dan notifikasi push.
   - Perlindungan anti-ubah: semua tombol mutasi dihilangkan untuk viewer umum.

5. **Deployment & Process Manager (PM2 / Nginx)**:
   - Setup `ecosystem.config.js` untuk menjalankan Laravel dan Node Daemon bersamaan.
   - Template konfigurasi Nginx Reverse Proxy untuk HTTP & WebSocket.
