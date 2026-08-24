# 🧠 OTAK & LOGIKA SISTEM (SYSTEM BRAIN & LOGIC ENGINE)
**Project:** Seal Online Boss Timer & Auto-Screening Tracker  
**File:** `plan_architecture/SYSTEM_BRAIN_LOGIC.md`  
**Versi:** 2.0.0 (High-Precision Multi-Slot Engine)  
**Dokumen:** Spesifikasi Logika Inti & Algoritma Sistem  

---

## 🎯 1. Pendahuluan
Dokumen ini menjelaskan secara mendalam **"Otak dan Logika Inti"** di balik sistem pelacakan boss Seal Online. Sistem ini dirancang untuk menyelesaikan berbagai kompleksitas perilaku log game secara otomatis, matematis, dan 100% presisi tanpa campur tangan manual.

---

## 🧩 2. Enam Mesin Logika Utama (The 6 Core Engines)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                       ARSITEKTUR LOGIKA SISTEM                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  [1. Log Input] ──► [ENGINE 1: Regex & Name Normalizer]                 │
│                                │                                        │
│                                ▼                                        │
│                     [ENGINE 2: Multi-Slot Manager]                      │
│                                │ (Slot #1, Slot #2)                     │
│                                ▼                                        │
│                     [ENGINE 3: FIFO Kill-to-Spawn Matcher]              │
│                                │ (Pencocok Lokasi & Kill)               │
│                                ▼                                        │
│                     [ENGINE 4: Interval Learning & Snapping]            │
│                                │ (Koreksi Ambang >=10m & Standarisasi)  │
│                                ▼                                        │
│                     [ENGINE 5: Timezone-Immune Epoch Timer]             │
│                                │ (targetEndTime = UTC + Durasi)         │
│                                ▼                                        │
│  [Web UI] ◄─────── [ENGINE 6: WebSocket Sync & Priority Sorter]         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 🔍 3. Rincian & Cara Kerja Setiap Mesin Logika

### ⚙️ ENGINE 1: Regex Parser & Name Normalizer (Pembersih Teks)
Log pesan dari bot game memiliki variasi format teks dan kurung bersarang.
* **Format Spawn:** `[Monster]::[Nama Boss] muncul di [Nama Map] [DD-MM-YYYY HH:MM:SS]`
* **Format Kill:** `[Monster]::[Nama Boss] dikalahkan oleh [Nama Player] [DD-MM-YYYY HH:MM:SS]`
* **Kasus Kurung Bersarang:** `[Monster]::[[A] Knight of All-Evil ] muncul di [Nerais]`

**Algoritma Pembersihan (`cleanBossName`):**
1. Ekstrak string mentah di antara tag `[Monster]::` / `[Boss]::` dan kata kunci `muncul di` / `dikalahkan oleh`.
2. Jika nama boss terbungkus oleh kurung siku luar ganda `[[...]]` atau kurung tunggal luar `[...]`, lepaskan kurung luar tersebut (`slice(1, -1)`).
3. Hapus spasi berlebih di awal dan akhir (*trimming*).
4. Hasil: `[[A] Knight of All-Evil ]` dinormalkan menjadi murni `[A] Knight of All-Evil`.

---

### 👥 ENGINE 2: Dynamic Multi-Slot Instance Manager (Pengelola Boss Kembar)
Di Seal Online, satu map dapat memiliki **2 monster boss yang sama sekaligus** (contoh: *2 Death Knight Yami* dan *2 Titan Skull* di map *Clements Mine*).

**Struktur Data:**
* `bossSlotsMap`: `Map<String, Array<SlotObject>>` (Key: `nama_boss__nama_map`).

```
                              [SPAWN LOG DATANG]
                                      │
                                      ▼
                        Apakah Slot #1 sedang HIDUP?
                                     / \
                                    /   \
                             (TIDAK)     (YA)
                                  /       \
                                 ▼         ▼
                       Isi Slot #1     Alokasikan Slot #2
                      "DK Yami #1"    "DK Yami #2"
```

**Aturan Kerja Slot:**
1. **Spawn #1 Tiba:** Slot #1 diaktifkan (`status: spawned`). Kartu menampilkan `Death Knight Yami`.
2. **Spawn #2 Tiba (sebelum #1 mati):** Slot #1 sudah terisi, maka sistem otomatis membuka **Slot #2** (`status: spawned`).
3. Sistem otomatis melabeli nama menjadi **`Death Knight Yami #1`** dan **`Death Knight Yami #2`**.
4. Masing-masing kartu berjalan secara independen di web dengan timer masing-masing.

---

### 🎯 ENGINE 3: FIFO Kill-to-Spawn Matching & Location Resolver
**Masalah Besar di Seal Online:**
* Saat **SPAWN**: Chat memberitahukan lokasi: `DK Yami muncul di [Clements Mine]`.
* Saat **KILL**: Chat **TIDAK** memberitahukan lokasi: `DK Yami dikalahkan oleh [Player]`.

**Logika Penyelesaian (FIFO Alive Queue):**
1. Setiap kali ada boss spawn di suatu map, sistem mendaftarkan instans tersebut ke antrean hidup (*Alive Queue*).
2. Ketika notifikasi kill masuk tanpa lokasi:
   * Sistem mencari instans boss yang sedang hidup **paling awal (*Oldest Spawn FIFO*)**.
   * Lokasi map dari instans tersebut langsung diambil sebagai lokasi kematian yang valid.
   * Kematian tersebut dimasukkan ke dalam antrean kematian (`killQueueMap`).

---

### ⏱️ ENGINE 4: Smart Interval Learning & Snapping Engine
Mesin ini bertugas menghitung durasi respawn ($\Delta T$) secara otomatis dari data riwayat/live chat.

```
                              [SPAWN BARU TERJADI]
                                       │
                                       ▼
                       Cari Kill di Antrean (FIFO Queue)
                                       │
                                       ▼
                     Hitung: Selisih = Waktu_Spawn - Waktu_Kill
                                       │
                                       ▼
                       Apakah Selisih >= 10 Menit?
                                     / \
                                    /   \
                             (TIDAK)     (YA)
                                /         \
                               ▼           ▼
               [ABAIKAN / TOLAK]      [PROSES SNAPPING]
          (Pasangan Palsu Boss Kembar) (Bulatkan ke Siklus Game)
                                           │
                                           ▼
                                 Simpan ke boss-config.json
```

#### A. Mengapa Ada Filter $\ge 10$ Menit?
* **Kasus Anomali:** Titan Skull #1 mati jam 14:00. Titan Skull #2 mati jam 14:24. Lalu Titan Skull #1 spawn jam 14:30.
* Jika spawn 14:30 dipasangkan dengan kill 14:24, selisihnya adalah **6–7 menit** (pasangan palsu antar 2 boss kembar).
* Di Seal Online, **tidak ada field boss yang respawn dalam 5–7 menit** (paling cepat 15–30 menit).
* Dengan filter $\ge 10$ menit, selisih 6–7 menit **100% ditolak**, dan sistem mengambil kill yang benar dari jam 14:00 ($14:30 - 14:00 = 30\text{ menit}$).

#### B. Algoritma Pembulatan Game (*Standard Snapping*)
Server game Seal Online memiliki siklus respawn berbasis angka bulat standar:
$$\text{Standard Intervals} = [15, 20, 30, 45, 60, 75, 90, 105, 120, 150, 180, 210, 240, 300, 360, 420, 480, 720] \text{ menit}$$
* Jika terbaca `28m`, `29m`, `31m`, `32m` (akibat delay broadcast bot) $\to$ **Otomatis dinormalkan menjadi 30 Menit Bulat**.
* Jika terbaca `118m` atau `122m` $\to$ **Otomatis dinormalkan menjadi 120 Menit (2 Jam)**.

---

### 🌍 ENGINE 5: Timezone-Immune Epoch Countdown Engine
Menjamin hitung mundur tidak meleset 1 detik pun walaupun terdapat perbedaan zona waktu antara server game (WIB/GMT+7) dan komputer pengguna (WITA/SGT/GMT+8).

**Rumus Matematis:**
1. Mengambil timestamp asli pesan langsung dari server Discord dalam format **Unix Epoch UTC Milliseconds** (`msg.createdTimestamp`).
2. Menghitung Target Waktu Selesai:
   $$\text{targetEndTime} = \text{msg.createdTimestamp} + (\text{durationMinutes} \times 60 \times 1000)$$
3. Menghitung Sisa Detik Real-time pada Komputer Pengguna:
   $$\text{remainingSeconds} = \max\left(0, \left\lceil \frac{\text{targetEndTime} - \text{Date.now()}}{1000} \right\rceil\right)$$
4. **Anti-Drift Guarantee:** Timer tidak bergantung pada `setInterval` 1 detik biasa yang mudah melambat saat tab diminimize, melainkan selalu mengacu pada selisih waktu riil terhadap jam OS (`Date.now()`).

---

### ⚡ ENGINE 6: WebSocket Sync & Priority Sorter
Mengatur pengiriman data instan ke seluruh browser klien dan pengurutan visual prioritas tinggi.

**Matriks Prioritas Pengurutan (Auto-Sort):**
1. **Prioritas #1 (Top Paling Atas):** Boss berstatus `SPAWN / READY` (`remainingSeconds <= 0`). Diberi aksen border merah menyala dan berkedip (*pulsing red alert*).
2. **Prioritas #2 (Urutan Countdown):** Boss yang sedang berjalan diurutkan dari **countdown terbesar ke terkecil** (*descending*) atau terkecil ke terbesar sesuai pilihan pengguna.
3. **Prioritas #3:** Pengurutan kustom pengguna melalui dropdown (Waktu Terkecil / Alfabet A-Z / Map A-Z).

---

## 📊 4. Tabel Bukti Kebenaran Kasus (Truth Table)

| Skenario Kasus | Log Masuk | Aksi Otak Sistem | Hasil Akhir |
| :--- | :--- | :--- | :--- |
| **Boss Tunggal Normal** | `Ohm muncul di Cross Forest` lalu mati 30m kemudian. | FIFO mencocokkan Kill ➔ Spawn. | Terdeteksi **30 Menit**, Countdown berjalan normal. |
| **Boss Kembar (Twin)** | 2 DK Yami spawn berdekatan di Clements Mine. | Multi-Slot membagi ke **Slot #1** dan **Slot #2**. | Muncul 2 kartu: `DK Yami #1` dan `DK Yami #2`. |
| **Kill Tanpa Lokasi** | `[A] Knight of All-Evil dikalahkan` (tanpa nama map). | Mengambil lokasi dari instans yang sedang hidup terdekat (*Alive Queue*). | Kartu di map *Nerais* langsung aktif countdown. |
| **Jeda Delay Server** | Kill jam 14:00:00, Spawn jam 14:30:14 (30m 14s). | Engine Snapping membulatkan selisih ke kelipatan terdekat. | Disimpan tepat **30 Menit** (bukan 30.2m). |
| **Browser Ditutup 15 Menit** | Pengguna menutup browser saat sisa waktu 25 menit. | Saat dibuka kembali, sistem menghitung `targetEndTime - Date.now()`. | Sisa waktu langsung tepat **10 Menit** (tidak ter-reset). |

---

## 🛡️ 5. Ringkasan
Dengan 6 Mesin Logika di atas, sistem pelacak boss ini bekerja sebagai **otak otonom (*autonomous tracking engine*)** yang tahan terhadap kesalahan data, kebal zona waktu, dan menjamin akurasi 100% dalam memantau setiap siklus boss di Seal Online.
