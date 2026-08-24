# 🛠️ PANDUAN LENGKAP MAINTENANCE & TESTING BERKALA
**Project:** Seal Online - Boss Respawn Countdown Timer & Discord Live Sync  
**Versi Sistem:** 3.1.0 (High-Precision Autonomous Multi-Slot & Multi-Server Engine)  
**File:** `plan_architecture/PANDUAN_MAINTENANCE_BERKALA.md`  
**Sasaran:** Prosedur Operasional Standar (SOP) Pemeliharaan, Health Check & Pengujian Rutin  
**Terakhir Diperbarui:** 24 Agustus 2026  

---

## 🎯 1. Pendahuluan & Tujuan Dokumen

Sistem pelacak boss Seal Online ini beroperasi secara otonom dengan mengandalkan integrasi Discord self-bot, parsing teks regex, kalkulasi timestamp UTC epoch, dan sinkronisasi real-time via WebSocket serta Database MySQL.

Dokumen ini disusun sebagai **Buku Panduan Maintenance Berkala (Maintenance SOP)** yang dapat digunakan kapan saja untuk:
1. **Memastikan Akurasi 100% Tetap Terjaga**: Memverifikasi bahwa data waktu respawn dan timer countdown tidak mengalami anomali (termasuk isolasi boss kembar di Clements Mine).
2. **Menjamin Kelancaran Koneksi Discord**: Mencegah dan menangani token kedaluwarsa (*expired token*).
3. **Mengelola Siklus Pasca-Maintenance Game**: Menangani reset siklus spawn setelah game server Seal Online melakukan update/maintenance mingguan.
4. **Merawat Kesehatan Database & Config**: Memastikan integritas data MySQL, `boss_configs`, `boss_states`, dan `boss-config.json`.

---

## 📅 2. Jadwal & Matriks Frekuensi Maintenance

```
┌───────────────────────────────────────────────────────────────────────────────────────┐
│                        JADWAL PEMELIHARAAN BERKALA (MAINTENANCE)                      │
├───────────────────────┬──────────────────────┬────────────────────────────────────────┤
│ Frekuensi             │ Estimasi Durasi      │ Fokus Utama Pengecekan                 │
├───────────────────────┼──────────────────────┼────────────────────────────────────────┤
│ 🟢 Harian (Daily)     │ ~1 - 2 Menit         │ Status Discord Online & Tes Alarm Web  │
│ 🟡 Pasca-MT Game      │ ~3 - 5 Menit         │ Reset Timer, Scan Riwayat, Slot Boss   │
│ 🔵 Mingguan (Weekly)  │ ~5 Menit             │ Run Test Suite & Backup Config JSON    │
│ 🟣 Bulanan (Monthly)  │ ~10 Menit            │ Audit Data Config & Peremajaan Token   │
└───────────────────────┴──────────────────────┴────────────────────────────────────────┘
```

---

## 📋 3. Checklist Lengkap Pengujian Berkala (Step-by-Step Testing)

Lakukan pengecekan berurutan sesuai 8 poin checklist di bawah ini:

---

### ✅ CHECKLIST 1: Eksekusi Automated Test Suite Regression (Wajib)
Jalankan test runner otomatis untuk memastikan tidak ada logika kode yang regresi atau rusak:

* **Perintah Terminal:**
  ```bash
  node plan_architecture/test_suite_runner.js
  ```
* **Kriteria Kelulusan (Expected Result):**
  - [x] SUITE 1 (Regex & Name Normalization) $\to$ **PASSED**
  - [x] SUITE 2 (Multi-Slot Twin Boss Matching) $\to$ **PASSED**
  - [x] SUITE 3 (Multi-Location & Oldest Spawn Resolution) $\to$ **PASSED**
  - [x] SUITE 4 (Interval Learning & >=10m Filter) $\to$ **PASSED**
  - [x] SUITE 5 (Timezone-Immune Epoch Countdown) $\to$ **PASSED**
  - [x] SUITE 6 (Multi-Seal Scalability & Server Isolation) $\to$ **PASSED**
  - [x] SUITE 7 (Player Trajectory & Twin-Slot Rapid Kill Affinity) $\to$ **PASSED**
  - [x] SUITE 8 (Canonical Name Resolver & Truncation Merge) $\to$ **PASSED**
  - [x] SUITE 9 (Latest-Valid-Kill Pairing Anti-Cross Snapping) $\to$ **PASSED**
  - [x] SUITE 10 (Siklus Penuh Boss Kembar Clements Mine Slot-Isolated) $\to$ **PASSED**
  - [x] Pesan Akhir: `🎉 ALL 10/10 TEST SUITES PASSED (100% ACCURACY VERIFIED)!`

---

### ✅ CHECKLIST 2: Pemeriksaan Status Koneksi Discord & Token
Memastikan bot Discord server terhubung secara live ke channel `#boss`:

1. Buka browser di `http://127.0.0.1:8001/admin` atau `http://localhost:3000`.
2. Periksa badge status di header:
   * **Target Normal:** **`🟢 Live Discord: Online (NamaAkunAnda)`**.
   * **Jika Muncul `🔴 Live Discord: Offline`:** Sesi token telah kedaluwarsa. Ikuti panduan di [SOP 1](#-sop-1-peremajaan-token-discord-token-expired).
3. Buka channel `#boss` di Discord, pastikan bot dapat menerima pesan baru secara real-time saat ada boss mati/spawn.

---

### ✅ CHECKLIST 3: Uji Screening Otomatis Riwayat Chat (`🔄 Scan Riwayat`)
Menguji kemampuan sistem membaca 100 pesan riwayat terakhir secara bersih tanpa duplikasi:

1. Pada navigasi web, klik tombol **`🔄 Scan Riwayat`** atau **`🔄 Rescan Channel`**.
2. Periksa jendela Terminal backend:
   * Harus muncul log: `🔍 [Screening] Membaca 100 riwayat chat channel...`
   * Muncul log: `✨ [Screening Selesai] X status boss aktif & durasi spawn terdeteksi!`
3. Periksa UI web:
   * Seluruh kartu boss aktif harus termuat otomatis.
   * Boss yang siap diburu berstatus **`SPAWN / READY`** otomatis melompat ke urutan teratas (#1) dengan aksen border merah berkedip (*pulsing alert*).

---

### ✅ CHECKLIST 4: Validasi Multi-Slot Boss Kembar (*Clements Mine*)
Memastikan map dengan 2 boss kembar terisolasi sempurna:

1. Cari kartu boss **Death Knight Yami** dan **Titan Skull** di map *Clements Mine*.
2. **Kriteria Validasi:**
   - [x] Muncul kartu terpisah bertanda **`Death Knight Yami #1`** dan **`Death Knight Yami #2`**.
   - [x] **TIDAK PERNAH** muncul slot berlebih seperti `#3` atau `#4` (dibatasi oleh `getMaxSlotsForBoss`).
   - [x] Saat salah satu dibunuh, hanya slot bersangkutan yang masuk countdown, slot kembarannya tetap pada status aslinya.
   - [x] Interval kedua slot terkunci murni **30 Menit** (tidak tertimpa 45m).

---

### ✅ CHECKLIST 5: Validasi Pelacakan Multi-Lokasi (*Same-Name Boss Across Maps*)
Memastikan boss dengan nama sama di map berbeda tidak saling menimpa data:

1. Cari kartu boss **`Knight of All-Evil`** di *Nerais* dan di *Dungeon Silon-Aleph*, atau **`Titan Skull`** di *Clements Mine* dan di *Dungeon Crude*.
2. **Kriteria Validasi:**
   - [x] Masing-masing map memiliki kartu mandiri terpisah.
   - [x] Durasi countdown dan status masing-masing map berjalan independen.

---

### ✅ CHECKLIST 6: Uji Ketahanan Timer Anti-Drift (Tab Minimize & Sleep Test)
Memastikan timer tidak melambat saat browser tidak aktif:

1. Catat sisa detik countdown pada salah satu kartu boss (misal sisa `20:00`).
2. Minimize jendela browser atau switch ke aplikasi lain selama 3 menit.
3. Buka kembali browser:
   * **Kriteria Validasi:** Sisa waktu langsung berkurang tepat menjadi `17:00` (tidak melambat atau nge-freeze karena mengacu ke `targetEndTime - Date.now()`).

---

### ✅ CHECKLIST 7: Uji Audio Web API & Push Notification Desktop
Memastikan suara alarm dan notifikasi visual bekerja saat boss spawn:

1. Di header navigasi web, klik tombol **`🔊 Tes Alarm`**.
2. **Kriteria Validasi:**
   - [x] Terdengar suara melodi 4 nada Web Audio API (*Fanfare Chords* C5-E5-G5-C6) tanpa error.
   - [x] Browser tidak memerlukan file audio eksternal.
3. Saat salah satu timer countdown mencapai `00:00:00`:
   - [x] Modal popup darurat merah muncul dengan teks `🚨 [Nama Boss] TELAH SPAWN!`.
   - [x] Alarm berbunyi loop berulang setiap 1.5 detik sampai tombol **`Hentikan Alarm / OK`** diklik.
   - [x] Desktop Push Notification muncul jika izin notifikasi aktif.

---

### ✅ CHECKLIST 8: Uji Fitur Cadangan (*Mode Paste Log Manual*)
Memastikan sistem tetap dapat digunakan meskipun koneksi Discord sedang bermasalah:

1. Klik tombol **`📋 Paste Log Discord`** di header.
2. Tempelkan contoh log teks berikut ke dalam kotak dialog:
   ```text
   [Monster]::[Ohm] muncul di [Cross Forest] [24-08-2026 14:00:00]
   [Monster]::[Ohm] dikalahkan oleh [Ady] [24-08-2026 14:30:00]
   ```
3. Klik **`Proses Log`**.
4. **Kriteria Validasi:**
   - [x] Muncul feedback hijau: `🎉 Berhasil memproses & mengupdate 2 event boss!`.
   - [x] Kartu `Ohm` di `Cross Forest` langsung aktif dengan countdown yang sesuai.

---

## 🛠️ 4. Prosedur Operasional Standar (SOP Penanganan Masalah)

---

### 🔑 SOP 1: Peremajaan Token Discord (*Token Expired / 401 Unauthorized*)

> **Gejala:** Status di web berubah menjadi `🔴 Live Discord: Offline` atau log terminal menampilkan `❌ [Discord Login Gagal]`.

**Langkah Perbaikan:**
1. Buka browser dan login ke [discord.com/app](https://discord.com/app).
2. Tekan `F12` atau `⌥ + ⌘ + I` (Mac) untuk membuka **Developer Tools**.
3. Buka tab **Network** ➔ Ketik `messages` di filter bar.
4. Klik sembarang channel Discord ➔ Klik salah satu request baris jaringan yang muncul.
5. Pada tab **Headers** di panel kanan ➔ Scroll ke **Request Headers** ➔ Salin nilai panjang di baris **`Authorization:`**.
6. Buka file [`.env`](file:///Users/adybudi/Project/count-boss-seal-with-dashboard/.env) di root folder proyek:
   ```env
   DISCORD_TOKEN=paste_token_baru_anda_disini
   ```
7. Restart daemon di terminal atau via perintah artisan.

---

### 🔄 SOP 2: Prosedur Pasca-Maintenance Game Server Seal Online

> **Kondisi:** Server game Seal Online baru selesai maintenance mingguan / server reboot. Di game Seal Online, saat server baru menyala biasanya seluruh field boss langsung spawn atau siklusnya reset ke awal.

**Langkah Penyelarasan:**
1. Buka dashboard web `http://127.0.0.1:8001/admin`.
2. Klik tombol **`🔄 Rescan / Sync`** pada server yang bersangkutan untuk membaca log spawn pertama yang dikirim bot server pasca-maintenance.
3. Jika seluruh boss terkonfirmasi spawn bersamaan, status boss otomatis tertata menjadi `SPAWN / READY`.

---

### 💾 SOP 3: Backup & Audit Database `boss-config.json` & MySQL

> **Tujuan:** Mengamankan database interval hasil pembelajaran otomatis (*auto-learned intervals*) agar tidak hilang atau tertimpa durasi yang salah.

**Langkah Backup Rutin Mingguan:**
```bash
# Salin konfigurasi dengan stempel tanggal
cp boss-config.json boss-config.backup.$(date +%Y%m%d).json
```

**Cara Memeriksa Anomali Data di Database:**
```bash
php artisan tinker --execute="foreach(App\Models\BossConfig::all() as \$c) { echo \$c->seal_server_id . ' | ' . \$c->boss_name . ' @ ' . \$c->map_name . ' : ' . \$c->interval_minutes . 'm' . PHP_EOL; }"
```

---

### 🔊 SOP 4: Mengatasi Audio Alarm Tidak Berbunyi di Browser

> **Penyebab:** Kebijakan keamanan browser modern (*Autoplay Policy*) memblokir audio bersuara sebelum ada klik pengguna pada halaman.

**Langkah Solusi:**
1. Klik di mana saja pada halaman web minimal 1 kali setelah membuka atau me-refresh browser.
2. Atau klik tombol **`🔊 Tes Alarm`** di header. Ini akan mengaktifkan izin Web Audio Context di browser Anda.

---

### 🔌 SOP 5: Mengatasi Error Port Bentrok (`EADDRINUSE`)

> **Gejala:** Muncul error `listen EADDRINUSE: address already in use :::3001` atau `:::8001`.

**Langkah Solusi:**
```bash
# Temukan PID proses yang memakai port
lsof -i :3001
# Matikan proses tersebut
kill -9 <PID>
```

---

## ⚡ 5. Rangkuman Perintah Cepat (*CLI Quick Reference*)

| Perintah | Deskripsi |
| :--- | :--- |
| `php artisan serve --port=8001` | Menjalankan Laravel Web Application. |
| `node daemon/bot-daemon.js` | Menjalankan Discord Self-Bot & WebSocket Server. |
| `node plan_architecture/test_suite_runner.js` | Menjalankan seluruh 10 automated test suites. |
| `cp boss-config.json boss-config.bak.json` | Melakukan backup cepat database boss config. |

---

## 📝 6. Lembar Log Catatan Maintenance (Audit Record)

Gunakan tabel di bawah ini untuk mencatat riwayat maintenance berkala:

| Tanggal | Jenis Pemeriksaan | Status Test Suite | Status Discord | Catatan / Tindakan yang Diambil | Paraf |
| :---: | :---: | :---: | :---: | :--- | :---: |
| *24/08/2026* | *Upgrade V3.1 & Regression Test* | *10/10 PASSED* | *Online (adybudi)* | *Skeleton matching aktif, interval Clements Mine 30m terverifikasi.* | *AI Agent & Ady* |
| | | | | | |
| | | | | | |
| | | | | | |

---

*Dokumen ini merupakan panduan resmi pemeliharaan sistem. Simpan dan perbarui berkas ini setiap kali ada perubahan arsitektur atau aturan main baru.*
