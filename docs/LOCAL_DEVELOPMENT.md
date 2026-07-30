# Local Development Guide

Panduan ini ditujukan bagi *developer* yang ingin menjalankan, memodifikasi, dan melakukan *debug* pada proyek **O-Present-SMA UII** di lingkungan lokal. Proyek ini dirancang agar terisolasi (menggunakan Docker) untuk mencegah konflik dependensi di komputer *host* Anda.

---

## 1. Persiapan Awal (Prerequisites)

Pastikan sistem Anda sudah terinstal perlengkapan berikut:
- **Docker & Docker Compose**: Mesin utama untuk menjalankan database dan PHP server.
- **Bun (v1.0+)**: Digunakan sebagai *package manager* dan *task runner* utama (pengganti npm/yarn).
- **Composer**: Untuk mengelola dependensi PHP (dapat dijalankan via *host* jika ada PHP lokal, atau lewat Docker).
- **Git**: Untuk version control.

---

## 2. Alur Instalasi Lokal

1. **Clone Repository**
   ```bash
   git clone https://github.com/Rosyiii/O-Present-SMA-UII.git
   cd O-Present-SMA-UII
   ```

2. **Install Dependensi**
   Jalankan ini di terminal *host* Anda (laptop/PC):
   ```bash
   bun install
   composer install
   ```

3. **Konfigurasi Environment**
   Salin file env bawaan:
   ```bash
   cp env .env
   ```
   Pastikan pengaturan `.env` diarahkan ke localhost untuk development:
   ```ini
   CI_ENVIRONMENT = development
   app.baseURL = 'http://localhost:8100/'
   
   # Konfigurasi Database Lokal (Sesuai docker-compose.dev.yml)
   database.default.hostname = localhost
   database.default.database = opresent
   database.default.username = postgres
   database.default.password = postgres
   database.default.DBDriver = Postgre
   database.default.port = 5432
   ```

---

## 3. Menjalankan Development Server (Bun-Centric)

Kami menggunakan **Bun** sebagai orkestrator *command*. Semua interaksi dengan Docker atau script dibungkus dalam `package.json`.

### A. Setup Pertama Kali (Wajib)
Jalankan perintah ini satu kali saat pertama kali mengonfigurasi proyek:
```bash
bun run dev:setup
```
*Apa yang terjadi di balik layar?*
- Membangun *image* Docker development.
- Menyalakan container `opresent-app-dev` (Aplikasi) dan `opresent-db-dev` (Database).
- Menjalankan migrasi database (`php spark migrate`).
- Mengisi data awal (Seeder) seperti role, admin, dan pegawai *dummy*.

### B. Rutinitas Koding Harian
Setiap kali Anda mulai bekerja, cukup jalankan:
```bash
bun run dev
```
Perintah ini akan menjalankan dua proses secara paralel:
1. **Backend**: `php spark serve` jalan di port `8100`.
2. **Frontend**: `vite` HMR jalan di port `5173`.

Akses aplikasi di browser Anda melalui: **http://localhost:8100/**

---

## 4. Alur Kerja (Workflow) & Caveats

### Mengedit Frontend (TS / CSS)
- Karena Vite HMR berjalan, setiap perubahan pada file di folder `frontend/src/**/*.ts` atau `.css` akan langsung ter- *inject* ke browser tanpa perlu *reload*.
- Standar penulisan dijaga oleh **Biome**. Biasakan menjalankan `bun run format` sebelum *commit*.

### Mengedit Backend (PHP Controller/Model)
- Perubahan pada file PHP akan langsung terbaca. Namun, kualitas kode dijaga oleh **PHPStan** dan **PHP CS Fixer**.

### Mengedit View (Twig `.twig`) — SANGAT PENTING!
CodeIgniter 4 memiliki mekanisme *caching* untuk Twig agar render lebih cepat. Di lokal, ini sering membuat perubahan HTML/Twig **tidak langsung muncul** di browser.
Setiap kali selesai mengedit file `.twig`, buka tab terminal baru dan jalankan:
```bash
bun run clear
```
Perintah ini akan menghapus folder `writable/cache/twig/*`.

---

## 5. Troubleshooting (Masalah Umum)

### 1. Kamera atau Lokasi (GPS) Tidak Berfungsi
Browser modern memblokir API Kamera dan Geolocation jika tidak menggunakan **HTTPS**. Karena di lokal kita menggunakan `http://localhost`, fitur ini mungkin gagal berjalan (terutama saat diakses lewat HP dalam jaringan WiFi yang sama).
**Solusi**: Gunakan *tunneling* dengan [ngrok](https://ngrok.com/).
```bash
ngrok http 8100
```
Ubah sementara `app.baseURL` di `.env` menjadi URL ngrok Anda (contoh: `https://abcd.ngrok-free.app/`), lalu buka URL tersebut di HP Anda.

### 2. Port 5432 Bentrok (Database)
Jika di laptop Anda sudah terinstal PostgreSQL yang memakan port 5432, Docker akan gagal menyala.
**Solusi**: Buka file `docker/docker-compose.dev.yml`, cari bagian `ports:` pada service db, ubah menjadi `- "5433:5432"`. Sesuaikan `database.default.port` di `.env` menjadi `5433`.

### 3. Error Build Vite (Out of Memory)
Jika `bun run build` gagal karena masalah memori, pastikan node_modules sudah diinstal ulang (`rm -rf node_modules && bun install`).

---

## 6. Daftar Perintah CLI (Cheatsheet)

| Perintah | Deskripsi |
|----------|-----------|
| `bun run dev` | Menjalankan backend & frontend paralel (HMR aktif). |
| `bun run clear` | Menghapus cache (Wajib setelah edit `.twig`). |
| `bun run lint:fix` | Memperbaiki error *linter* frontend otomatis (Biome). |
| `bun run cs:fix` | Memperbaiki *code-style* PHP otomatis (PSR-12). |
| `composer stan` | Menjalankan PHPStan (Static Analysis). |
| `bun run migrate` | Menjalankan CodeIgniter migration. |
| `bun run seed` | Menjalankan CodeIgniter seeder. |
| `bun run dev:down`| Mematikan semua container Docker. |
