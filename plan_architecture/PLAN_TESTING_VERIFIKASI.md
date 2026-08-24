# 🧪 PANDUAN PENGUJIAN & VERIFIKASI LOGIKA SISTEM (MULTI-SEAL READY)
**Project:** Seal Online Boss Timer & Auto-Screening Tracker  
**File:** `plan_architecture/PLAN_TESTING_VERIFIKASI.md`  
**Target:** Validasi Akurasi 100% Sesuai `SYSTEM_BRAIN_LOGIC.md`  
**Format:** Siap dibagikan ke AI Model / Reviewer Eksternal  

---

## 🎯 1. Tujuan Pengujian
Dokumen ini disusun sebagai panduan pengujian menyeluruh (*comprehensive verification plan*) untuk memvalidasi bahwa sistem pelacak boss Seal Online beroperasi dengan akurasi 100% matematis dan bebas anomali data, serta mendukung arsitektur **Multiple Seal (Multi-Server Game Dinamis)**.

---

## 📋 2. Skenario Uji 6 Mesin Logika (Test Scenarios)

### 🔬 Test Case 1: Regex & Pembersihan Nama Bersarang (Engine 1)
* **Kasus Uji:**
  * String 1: `[Monster]::[[A] Knight of All-Evil ] muncul di [Nerais] [22-08-2026 15:18:38]`
  * String 2: `[Monster]::[Death Knight Yami] dikalahkan oleh [Uyuru] [22-08-2026 15:21:55]`
  * String 3: `[Monster]::[   Titan Skull   ] muncul di [Clements Mine]`
* **Expected Output:**
  * String 1: Nama = `[A] Knight of All-Evil`, Map = `Nerais`.
  * String 2: Nama = `Death Knight Yami`, Killer = `Uyuru`.
  * String 3: Nama = `Titan Skull`, Map = `Clements Mine`.
* **Kriteria Lulus:** Tidak ada kurung siku ganda `[[...]]` yang tersisa dan spasi ekstra terpotong bersih.

---

### 🔬 Test Case 2: Multi-Slot Boss Kembar di Map Sama (Engine 2)
* **Kasus Uji (Map Clements Mine):**
  * T0 (14:00:00): Spawn Log 1 `Death Knight Yami di Clements Mine`
  * T1 (14:05:00): Spawn Log 2 `Death Knight Yami di Clements Mine`
  * T2 (14:30:00): Kill Log 1 `Death Knight Yami dikalahkan oleh Player1`
  * T3 (14:35:00): Kill Log 2 `Death Knight Yami dikalahkan oleh Player2`
* **Expected Output:**
  * Di T1: Terbentuk 2 slot terpisah: `Death Knight Yami #1` (SPAWN) dan `Death Knight Yami #2` (SPAWN).
  * Di T2: `Death Knight Yami #1` masuk status COUNTDOWN/RUNNING (countdown 30m), `Death Knight Yami #2` tetap status SPAWN.
  * Di T3: `Death Knight Yami #2` masuk status COUNTDOWN/RUNNING (countdown 30m).
* **Kriteria Lulus:** Kedua kartu beroperasi mandiri tanpa saling menimpa state atau killer.

---

### 🔬 Test Case 3: Resolusi Lokasi untuk Nama Boss Sama (Engine 3)
* **Kasus Uji:**
  * T0 (15:00:00): `Knight of All-Evil muncul di [Nerais]`
  * T1 (15:05:00): `Knight of All-Evil muncul di [Dungeon Silon-Aleph]`
  * T2 (15:10:00): `Knight of All-Evil dikalahkan oleh Player` (Tanpa nama map di pesan)
* **Expected Output:**
  * Kill di T2 dicocokkan ke instans `Nerais` (instans hidup terlama / FIFO).
  * Kartu `Knight of All-Evil @ Nerais` beralih ke COUNTDOWN.
  * Kartu `Knight of All-Evil @ Dungeon Silon-Aleph` tetap SPAWN.
* **Kriteria Lulus:** Lokasi terselesaikan dengan benar meski chat kill tidak memuat nama lokasi.

---

### 🔬 Test Case 4: Filter Ambang Batas $\ge 10$m & Game Snapping (Engine 4)
* **Kasus Uji (Eliminasi Pasangan Palsu 6-7 Menit):**
  * T0 (14:00:00): Titan Skull #1 mati.
  * T1 (14:24:00): Titan Skull #2 mati (6 menit sebelum T2).
  * T2 (14:30:00): Titan Skull #1 spawn.
* **Expected Output:**
  * Selisih $14:30 - 14:24 = 6\text{ Menit}$ **DITOLAK** karena $< 10\text{ Menit}$.
  * Sistem mengambil kill T0 (14:00:00): $14:30 - 14:00 = \mathbf{30 \text{ Menit}}$.
  * Nilai yang disimpan ke database / config adalah **30 Menit Bulat**.
* **Kriteria Lulus:** Durasi respawn terhitung 30 menit murni (bukan 6 atau 7 menit).

---

### 🔬 Test Case 5: Perhitungan Countdown Waktu Nyata (Engine 5)
* **Kasus Uji:**
  * Timestamp pesan mati dari Discord API (`msg.createdTimestamp`) = 10 menit yang lalu dari sekarang.
  * Durasi respawn boss = 30 menit (1800 detik).
* **Expected Output:**
  * $\text{targetEndTime} = \text{createdTimestamp} + (30 \times 60 \times 1000)$.
  * Sisa detik saat ini ($\text{Date.now()}$) = **tepat 1200 detik (20 Menit)**.
* **Kriteria Lulus:** Sisa waktu presisi detik per detik tanpa dipengaruhi perbedaan zona waktu (WIB/WITA/WIT/GMT).

---

### 🔬 Test Case 6: Skalabilitas Multi-Seal (Dukungan Multi-Server)
* **Kasus Uji:**
  * Server A (Seal Server Arus) & Server B (Seal Server Duran) berjalan bersamaan.
  * Di Server Arus, DK Yami mati jam 14:00 (respawn 30m).
  * Di Server Duran, DK Yami mati jam 14:15 (respawn 45m).
* **Expected Output:**
  * Sistem mempartisi state berdasarkan `sealId`:
    * Server 1 (Arus): `death_knight_yami__clements_mine_slot_1` $\to$ 30m
    * Server 2 (Duran): `death_knight_yami__clements_mine_slot_1` $\to$ 45m
  * Interval dan status masing-masing Seal server terisolasi 100% tanpa tabrakan data.
* **Kriteria Lulus:** State dan config per Seal server independen dan terisolasi.

---

## 💻 3. Cara Menjalankan Script Test Otomatis

Jalankan perintah berikut di terminal:
```bash
node plan_architecture/test_suite_runner.js
```

Jika seluruh 6 test suite lulus, terminal akan menampilkan output:
```
======================================================
🎉 ALL 6 TEST SUITES PASSED (100% ACCURACY VERIFIED)!
======================================================
```

---

## 🤖 4. Template Prompt untuk AI Model Sebelah

Salin teks berikut untuk meminta review independen dari model AI lain:

```text
Halo! Tolong lakukan code review dan audit logika independen terhadap sistem pelacak boss game Seal Online ini.

Silakan pelajari dokumen:
1. `plan_architecture/SYSTEM_BRAIN_LOGIC.md` (Spesifikasi 6 Mesin Logika Utama)
2. `plan_architecture/PLAN_TESTING_VERIFIKASI.md` (Kasus Uji dan Kriteria Kelulusan)
3. `daemon/bot-daemon.js` dan views/tracker.blade.php (Implementasi Kode)

Mohon verifikasi apakah:
- Penanganan Multi-Slot (boss kembar di map yang sama) sudah bebas bug.
- Filter ambang batas >= 10 menit berhasil mengeliminasi selisih 6-7 menit.
- Perhitungan countdown berbasis UTC epoch sudah kebal perbedaan zona waktu.
- Arsitektur multi-seal dapat diskalakan secara dinamis.
```
