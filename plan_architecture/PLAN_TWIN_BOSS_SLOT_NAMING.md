# 📋 SPESIFIKASI & LOGIKA: MULTI-SLOT TWIN BOSS NAMING (#1 & #2)
**Project:** Seal Online Boss Timer & Auto-Screening Tracker  
**File:** `plan_architecture/PLAN_TWIN_BOSS_SLOT_NAMING.md`  
**Fitur:** Penanganan Otomatis Boss Kembar di Lokasi Map yang Sama  
**Status:** Terimplementasi & Teruji 100% Akurat  

---

## 🎯 1. Latar Belakang & Masalah
Di game Seal Online, terdapat beberapa lokasi map yang memiliki **2 monster boss identik yang hidup berdampingan di spot berbeda pada map yang sama** (contoh: *2 Death Knight Yami* dan *2 Titan Skull* di map *Clements Mine*).

### Tantangan Tanpa Sistem Multi-Slot:
1. **Data Tertimpa (*Overwrite Bug*):** Saat spawn boss ke-2 muncul, sistem yang tidak memiliki slot akan menimpa data boss ke-1.
2. **Ambiguitas Kematian:** Saat salah satu mati, sistem tidak tahu instans mana yang mati dan mana yang masih hidup.
3. **Kerusakan Perhitungan Interval:** Muncul selisih waktu salah (misal 6–7 menit) karena waktu mati boss ke-2 dipasangkan dengan spawn boss ke-1.

---

## 🏗️ 2. Arsitektur Struktur Data Slot

Sistem menggunakan struktur `Map` bernama `bossSlotsMap` dengan kunci unik berbasis kombinasi nama boss dan nama lokasi:

```
bossSlotsMap (Map)
│
└── Key: "death_knight_yami__clements_mine"
    │
    ├── [0] Slot #1:
    │   ├── slotNumber: 1
    │   ├── displayName: "Death Knight Yami #1"
    │   ├── baseName: "Death Knight Yami"
    │   ├── location: "Clements Mine"
    │   ├── status: "running" | "spawned"
    │   ├── lastSpawnTime: 1724313600000 (14:00:00)
    │   └── lastKillTime: 1724315400000 (14:30:00)
    │
    └── [1] Slot #2:
        ├── slotNumber: 2
        ├── displayName: "Death Knight Yami #2"
        ├── baseName: "Death Knight Yami"
        ├── location: "Clements Mine"
        ├── status: "spawned"
        ├── lastSpawnTime: 1724313900000 (14:05:00)
        └── lastKillTime: null
```

---

## 🔄 3. Alur Siklus Hidup & Transisi State (Lifecycle Flow)

```
[1. SPAWN 1 MASUK] ──► Slot #1 Dibuat: "Death Knight Yami" (Status: SPAWN)
                            │
                            ▼
[2. SPAWN 2 MASUK] ──► Slot #1 sedang aktif hidup!
                       ➔ Buat Slot #2: "Death Knight Yami #2" (Status: SPAWN)
                       ➔ Ubah Nama Slot #1 secara retroaktif: "Death Knight Yami #1"
                            │
                            ▼
[3. KILL 1 MASUK]  ──► FIFO Matching mencocokkan ke Slot dengan spawn tertua (Slot #1)
                       ➔ Slot #1: Masuk status RUNNING (Countdown 30m dimulai)
                       ➔ Slot #2: TETAP status SPAWN (karena belum dibunuh)
                            │
                            ▼
[4. KILL 2 MASUK]  ──► FIFO Matching mencocokkan ke Slot tersisa (Slot #2)
                       ➔ Slot #2: Masuk status RUNNING (Countdown 30m dimulai)
                            │
                            ▼
[5. RESPAWN]       ──► Masing-masing slot kembali ke status SPAWN secara mandiri
                       ketika countdown masing-masing mencapai 00:00:00
```

---

## ⚙️ 4. Logika Algoritma Kode Program

### A. Alur Spawn (`handleSpawnLogic`):
1. Cari apakah ada slot yang sedang kosong atau berstatus bukan `spawned`.
2. Jika semua slot yang ada sedang `spawned` (artinya ada boss kembar baru muncul):
   * Alokasikan `slotNumber = totalSlot + 1`.
   * Beri nama tampilan `displayName = "${bossName} #${slotNumber}"`.
   * **Penamaan Retroaktif:** Jika slot ke-2 baru dibuat dan slot ke-1 belum berlabel `#1`, perbarui nama slot ke-1 menjadi `${bossName} #1` agar serasi.
3. Daftarkan kartu aktif ke `activeBossMap` dengan ID unik: `${baseKey}_slot_${slotNumber}`.

### B. Alur Kill (`handleKillLogic`):
1. Cari slot dari boss tersebut yang sedang berstatus `spawned` dengan waktu kemunculan paling awal (*Oldest Spawn FIFO*).
2. Ubah status slot tersebut menjadi `running` dan catat jam kematiannya (`lastKillTime`).
3. Slot kembarannya yang belum mati **tetap aman dalam status `spawned`** tanpa terganggu.

---

## 🖥️ 5. Representasi Antarmuka Web (UI Wireframe)

Di layar dashboard web, sistem akan menampilkan 2 kartu terpisah secara rapi:

```
┌─────────────────────────────────────────┐  ┌─────────────────────────────────────────┐
│ 👾 Death Knight Yami #1                 │  │ 👾 Death Knight Yami #2                 │
│ 📍 Clements Mine                        │  │ 📍 Clements Mine                        │
│ [BERJALAN] 🟢                           │  │ [SPAWN / READY] 🔴                      │
├─────────────────────────────────────────┤  ├─────────────────────────────────────────┤
│ ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬░░░░░░░░░░░░░░           │  │ ─────────────────────────────────────── │
│                00:15:20                 │  │                00:00:00                 │
│          Interval: 00:30:00 ✏️          │  │          Interval: 00:30:00 ✏️          │
├─────────────────────────────────────────┤  ├─────────────────────────────────────────┤
│ [⏸ Berhenti]  [🔄 Reset]  [🗑 Hapus]    │  │ [▶ Start]     [🔄 Reset]  [🗑 Hapus]    │
└─────────────────────────────────────────┘  └─────────────────────────────────────────┘
```

---

## 🧪 6. Pengujian & Bukti Akurasi

Pengujian otomatis untuk logika Multi-Slot ini terdapat di `plan_architecture/test_suite_runner.js` pada **Test Suite 2**:

```bash
node plan_architecture/test_suite_runner.js
```

### Output Validasi:
```
🧪 [SUITE 2] Testing Multi-Slot Twin Boss Matching...
✅ [SUITE 2] PASSED: Twin Boss Multi-Slot isolation 100% accurate!
```

---

## 💡 7. Kesimpulan
Dengan implementasi **Dynamic Multi-Slot Naming (#1 & #2)**:
* Pemain game dapat membedakan dengan jelas boss mana yang sedang hidup dan boss mana yang sedang dalam hitungan mundur di map yang sama.
* Eliminasi total terhadap bug tumpang tindih data dan bug perhitungan waktu respawn.
