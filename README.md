# 👑 Seal Online Multi-Server Boss Tracker & Admin Dashboard (v2.0.0)

Aplikasi pelacak spawn boss real-time untuk game **Seal Online** dengan arsitektur **Multi-Server** berbasis **Laravel 12**, **MySQL**, dan **Node.js Real-time Daemon**.

---

## 🌟 Fitur Utama

1. **Multi-Server Seal Dinamis**:
   - Tambah dan kelola banyak server Seal secara bersamaan dari **Dashboard Admin**.
   - Input Discord User Token (`discord.js-selfbot-v13`) & Channel ID `#boss` secara dinamis per server.
   - Token tersimpan dengan enkripsi keamanan tingkat tinggi (`AES-256`).

2. **Kode Akses Unik & Proteksi Penuh (Pure Read-Only)**:
   - Setiap server Seal memiliki **Kode Akses Unik (Access Code)** acak yang panjang (misal: `seal_9a8b7c6d5e4f3a...`).
   - Penonton/pemain umum **hanya bisa melihat hitung mundur (Read-Only)** dan mendengarkan alarm bunyi tanpa bisa mengubah interval atau mereset data.

3. **Multi-Location & Multi-Slot Tracking**:
   - Boss bernama sama di map berbeda (contoh: *Knight of All-Evil* di Nerais vs Dungeon Silon-Aleph) dipisahkan secara otomatis.
   - Boss kembar pada map yang sama (contoh: 2 *Death Knight Yami* di Clements Mine) dikelola dengan slot independen (`#1`, `#2`).

4. **Penjamin Akurasi & Anti-Drift**:
   - *Startup Screening*: Otomatis membaca 100 riwayat chat Discord saat bot menyala.
   - *FIFO Kill Queue*: Mencegah mismatch selisih waktu antar boss kembar.
   - *Interval Snapping*: Menormalkan durasi game standar (30m, 120m, dll).
   - *Absolute Timestamp*: Countdown tidak akan melambat walau tab browser di-minimize.

5. **Audio Alarm & Browser Push Notification**:
   - Synthesizer Web Audio API bawaan (tanpa perlu file eksternal).
   - Notifikasi push desktop saat boss siap diburu (*READY / SPAWN*).

---

## 🛠️ Kebutuhan Sistem (Prerequisites)

- **PHP**: >= 8.2
- **Composer**: >= 2.0
- **Node.js**: >= 18.0
- **Database**: MySQL / MariaDB

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

### 1. Konfigurasi File `.env`
Buka file `.env` dan sesuaikan koneksi database MySQL Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seal_tracker
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

### 2. Jalankan Migrasi & Database Seeder
Jalankan perintah berikut di terminal:
```bash
php artisan migrate --seed
```
*Perintah ini akan membuat seluruh tabel database dan membuat akun Administrator default.*

> **Kredensial Default Admin:**
> - **URL Login:** `http://localhost:8000/admin/login`
> - **Email:** `admin@seal.local`
> - **Password:** `password123`

---

### 3. Menjalankan Aplikasi (Mode Development)

Buka 2 tab terminal:

**Terminal 1 — Jalankan Laravel Web Server:**
```bash
php artisan serve
```
*(Aplikasi web akan aktif di `http://127.0.0.1:8000`)*

**Terminal 2 — Jalankan Node.js Multi-Tenant Bot Daemon:**
```bash
npm start
```
*(Daemon WebSocket & Discord Listener akan aktif di port `3001`)*

---

## 🎮 Alur Penggunaan

1. **Masuk ke Dashboard Admin**: Buka `http://localhost:8000/admin/login` dan login dengan akun admin.
2. **Tambah Server Seal**:
   - Klik **"Tambah Server Baru"**.
   - Masukkan Nama Server (contoh: *Seal BOD Classic*), Discord User Token pembaca chat, dan Discord Channel ID `#boss`.
3. **Jalankan Bot**: Klik tombol **▶️ Start** pada tabel server untuk menghubungkan bot Discord.
4. **Salin Kode Akses**: Klik tombol 📋 **Salin Kode** di sebelah kode akses unik.
5. **Akses Penonton Publik**:
   - Buka portal depan di `http://localhost:8000/` dan masukkan kode akses tersebut.
   - Atau langsung buka link: `http://localhost:8000/tracker/{access_code}`.

---

## 🌐 Panduan Deployment di VPS (Production Hosting)

### 1. Jalankan dengan PM2 Process Manager
Gunakan file `ecosystem.config.js` yang telah disediakan:
```bash
# Install PM2 jika belum ada
npm install -g pm2

# Jalankan Laravel dan Node Daemon bersamaan
pm2 start ecosystem.config.js

# Simpan proses agar otomatis berjalan saat VPS reboot
pm2 save
pm2 startup
```

### 2. Konfigurasi Nginx Reverse Proxy (Contoh)
```nginx
server {
    listen 80;
    server_name sealtimer.yourdomain.com;
    root /var/www/count-boss-seal/public;

    index index.php index.html;

    # Laravel Web Routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    # WebSocket Proxy ke Node Daemon
    location /ws {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
    }
}
```
