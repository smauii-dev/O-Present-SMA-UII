# Testing Guide

Proyek O-Present mengedepankan kualitas dan keandalan kode. Dokumen ini menjelaskan bagaimana suite pengujian kami disusun, dikonfigurasi, dan bagaimana Anda dapat berkontribusi di dalamnya.

---

## 1. Jenis-jenis Pengujian

Proyek ini membagi pengujian ke dalam dua lapis utama:

1. **Static Analysis & Type Checking**: 
   Bukan pengujian konvensional, tapi menangkap bug sebelum kode dijalankan. Menggunakan **PHPStan (Level 9)** untuk backend dan **TypeScript (Strict)** untuk frontend.
2. **Integration / End-to-End (E2E) Testing**:
   Memastikan interaksi antar komponen (Database, Routing, Controller, Template, dan Bundle Vite) bekerja dengan baik secara nyata.

---

## 2. End-to-End (E2E) Testing

Karena proyek ini menggunakan perpaduan unik antara Twig, HTMX, dan Vite, kami sangat bergantung pada skrip E2E kustom untuk memverifikasi fungsionalitas keseluruhan.

### File Utama: `tests/EndToEndTest.php`
Ini adalah skrip uji integrasi utama yang mensimulasikan interaksi HTTP terhadap aplikasi yang sedang menyala.

**Apa yang diuji dalam file ini?**
1. **Server Status (Health Check)**: Mengecek apakah *built-in server* menyala.
2. **Login Flow**: Mengirim *request* halaman login, memeriksa apakah form login (email & password) ada di halaman.
3. **Template & Vite Manifest**: Memastikan file Twig berhasil memanggil fungsi `vite()` dan *bundle* JavaScript utama (`main-*.js`) ter- *inject* dengan benar.
4. **Zod Registration**: Memastikan skema Zod diregistrasikan di global object `window.schemas`.
5. **CSRF Protection**: Memeriksa keberadaan meta tag CSRF dan cookie.
6. **Authentication & Authorization**: Memeriksa mekanisme autentikasi dan memvalidasi HTTP response code (Misal: admin bisa akses `/admin/pegawai`, student di-redirect).
7. **API Endpoints**: Memastikan route API (seperti `/api/presensi/today`) terdaftar dan mengembalikan JSON/status code yang relevan.

---

## 3. Cara Menjalankan Pengujian

### 3.1. Prasyarat Pengujian
Sebelum menjalankan E2E Test, pastikan Anda berada di lingkungan pengembangan lokal (Development Environment) dan database sudah tersambung.

1. **Jalankan Aplikasi Backend**:
   Uji integrasi membutuhkan web server aktif. Di satu terminal, jalankan:
   ```bash
   bun run serve
   ```
   (Ini akan menjalankan `php -S localhost:8100 -t public/`)

2. **Build Vite Manifest**:
   Pengujian akan gagal jika file manifest Vite belum terbentuk. Build frontend terlebih dahulu:
   ```bash
   bun run build
   ```

### 3.2. Eksekusi E2E Test
Buka terminal baru, dan jalankan perintah berikut:
```bash
php tests/run-tests.php
```

**Output yang Diharapkan:**
Jika pengujian berhasil, Anda akan melihat output terminal yang bertuliskan:
```text
Test 1: Server Status
✓ Server is running

Test 2: Login Page
✓ Login page accessible
✓ Login page contains form
...
===========================================
ALL TESTS PASSED SUCCESSFULLY!
===========================================
```
Jika gagal, skrip akan menampilkan pesan *assertion failed* beserta baris error, dan skrip akan langsung berhenti (exit code 1).

---

## 4. Menulis Pengujian Baru (Guidelines)

Jika Anda menambahkan fitur baru, terutama rute atau proteksi keamanan, Anda **wajib** menyesuaikan file `tests/EndToEndTest.php`.

**Tips Menulis Asersi Kustom**:
Gunakan fungsi `assertContains($needle, $haystack, $message)` atau `assertEquals($expected, $actual, $message)` yang sudah tersedia di dalam skrip `EndToEndTest.php`.

Contoh menambahkan uji rute baru:
```php
// Test X: Fitur Baru Laporan
echo "Test X: Fitur Baru Laporan\n";
echo "-------------------------------------------\n";
// Lakukan fetch ke rute
$result = fetchUrl($baseUrl . '/rekap');

// Pastikan halaman di-render (HTML valid)
assertEquals(200, $result['httpCode'], 'Route rekap harus bisa diakses');
assertContains('<table', $result['content'], 'Harus terdapat tabel data rekap');
echo "✓ Fitur Laporan bekerja\n\n";
```

---

## 5. Continuous Integration (CI)

Saat Anda membuat *Pull Request* atau *push* ke repositori, **GitHub Actions** akan secara otomatis menjalankan semua pipeline pengujian ini. 

*Pull Request* **tidak akan bisa di-merge** jika:
- PHPStan mendeteksi masalah (Level 9).
- TypeScript mendeteksi masalah *type-checking*.
- E2E Tests gagal (terjadi 404, 500, atau struktur HTML berubah tak terduga).
