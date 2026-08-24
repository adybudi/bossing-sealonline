# 🧠 MEMORY & DOKUMENTASI ARSITEKTUR SISTEM
**Project:** Seal Online Multi-Server Boss Tracker & Admin Dashboard  
**Versi:** 2.0.0 (Laravel 12 + MySQL + Multi-Tenant Node Daemon + Access Token)  
**Terakhir Diperbarui:** 22 Agustus 2026

---

## 📌 1. Ikhtisar Proyek (Project Overview)
Aplikasi web pelacak spawn boss real-time untuk game **Seal Online** multi-server. Sistem menggabungkan dashboard administrasi modern berbasis **Laravel 12 & MySQL** dengan engine listener real-time berbasis **Node.js Multi-Tenant Daemon (`discord.js-selfbot-v13`)**.

Pemain masuk ke halaman hitung mundur menggunakan **Kode Akses Unik (Access Code)** dalam mode **Pure Read-Only**, sehingga aman dari gangguan pihak luar dan hanya menampilkan hitung mundur presisi, visual progress bar, serta alarm audio/notifikasi web.

---

## 🏗️ 2. Arsitektur & Komponen Utama

```
┌────────────────────────────────────────────────────────┐
│                   DISCORD PLATFORM                     │
│     Channel #boss Server A     Channel #boss Server B  │
└───────────────┬────────────────────────┬───────────────┘
                │ (Gateway WebSocket)
                ▼
┌────────────────────────────────────────────────────────┐
│         NODE.JS MULTI-BOT DAEMON SERVICE               │
│  - daemon/bot-daemon.js (Port 3001)                    │
│  - Dynamic Worker Pool (discord.js-selfbot-v13)        │
│  - Screening 100 Pesan Riwayat Discord saat Startup    │
│  - FIFO Kill Queue & Multi-Slot Tracker (#1, #2)       │
│  - WebSocket Server (Room-Isolated by Access Code)     │
└───────────────▲────────────────────────┬───────────────┘
                │ Internal API Sync      │ ws:// (Live Broadcast)
                │ (X-Internal-Secret)    │
┌───────────────▼────────────────────────▼───────────────┐
│              LARAVEL 12 BACKEND (MYSQL)                │
│  - Admin Panel (/admin) & Server Management            │
│  - Database MySQL (seal_servers, boss_states, configs) │
│  - Discord Token Enkripsi (AES-256)                    │
│  - Public Tracker Viewer (/tracker/{access_code})      │
│  - Access Code Generator & Verification Middleware     │
└────────────────────────────────────────────────────────┘
```

---

## 🧩 3. Logika & Alur Kerja Mendalam (Deep Logic)

### A. Alur Screening Otomatis Riwayat Chat (Startup History Screening)
1. Saat bot server terhubung ke Discord, bot membaca hingga 100 pesan riwayat terakhir di channel `#boss` yang ditentukan.
2. Pesan diproses secara kronologis (*oldest to newest*) menggunakan timestamp UTC server Discord (`msg.createdTimestamp`).
3. Sistem membangun status setiap boss, menghitung selisih waktu respawn, dan menentukan sisa waktu countdown aktif.
4. Server mem-broadcast `INITIAL_SYNC` ke room browser klien yang terdaftar.

### B. Solusi Kasus Boss Sama di Map Berbeda (Multi-Location Tracking)
- Boss dengan nama sama (misal `Knight of All-Evil`) di `Nerais` dan `Dungeon Silon-Aleph` dipisahkan secara independen melalui antrean instans hidup (*Alive Queue FIFO*).

### C. Solusi Kasus Boss Kembar di Map yang Sama (Multi-Slot Twin Bosses)
- Di map seperti *Clements Mine*, ada **2 Death Knight Yami** dan **2 Titan Skull** yang hidup bersamaan.
- Menggunakan **Dynamic Multi-Slot Tracker** (`#1`, `#2`) sehingga timer masing-masing boss berjalan mandiri tanpa menimpa satu sama lain.

### D. Penjaminan Akurasi Interval 100% (Anti-Meleset)
1. **FIFO Kill Queue (`killQueueMap`)**: Mencegah penimpaan kematian antar boss kembar.
2. **Filter Ambang Batas Minimal ($\ge 10$ Menit)**: Menolak selisih durasi palsu akibat boss kembar yang mati berdekatan.
3. **Penyelarasan Siklus Game Standar (*Interval Snapping*)**: Menormalkan selisih ke menit bulat standar (30m, 120m, dll).
4. **Anti-Drift Timer Absolut**: Web menghitung sisa waktu berdasarkan timestamp absolut target (`targetEndTime - Date.now()`), countdown tidak melambat walau tab browser diminimize.

---

## 📁 4. Struktur File Proyek

| File / Folder | Deskripsi & Fungsi |
| :--- | :--- |
| [`daemon/bot-daemon.js`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/daemon/bot-daemon.js) | Layanan background Node.js multi-tenant, worker pool Discord selfbot, WebSocket server per room, dan sinkronisasi ke Laravel. |
| [`app/Models/SealServer.php`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/app/Models/SealServer.php) | Model Eloquent server Seal, enkripsi token Discord, dan generator kode akses unik. |
| [`app/Models/BossConfig.php`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/app/Models/BossConfig.php) | Model persistensi interval respawn boss per server. |
| [`app/Models/BossState.php`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/app/Models/BossState.php) | Model snapshot status boss terkini di database MySQL. |
| [`app/Http/Controllers/Admin/`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/app/Http/Controllers/Admin) | Controller admin (Auth, Dashboard, Server CRUD, Start/Stop Bot, Boss Intervals). |
| [`app/Http/Controllers/Api/InternalApiController.php`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/app/Http/Controllers/Api/InternalApiController.php) | Bridge API terproteksi `X-Internal-Secret` untuk komunikasi Daemon $\leftrightarrow$ Laravel. |
| [`app/Http/Controllers/TrackerController.php`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/app/Http/Controllers/TrackerController.php) | Controller publik untuk validasi Access Code dan render Tracker View. |
| [`resources/views/landing.blade.php`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/resources/views/landing.blade.php) | Portal landing page input Access Code pemain. |
| [`resources/views/tracker.blade.php`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/resources/views/tracker.blade.php) | Dashboard Tracker **Pure Read-Only**, Web Audio API alarm synthesizer, progress bar, dan real-time WebSocket. |
| [`resources/views/admin/`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/resources/views/admin) | Antarmuka dashboard admin lengkap (Login, List Server, Form Tambah/Edit, Konfigurasi Interval). |
| [`ecosystem.config.js`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/ecosystem.config.js) | Konfigurasi PM2 Process Manager untuk menjalankan Laravel & Daemon di VPS. |
| [`.env.example`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/.env.example) | Template konfigurasi environment (MySQL, Daemon secret, Port). |
| [`README.md`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/README.md) | Panduan instalasi dan deployment lengkap. |
