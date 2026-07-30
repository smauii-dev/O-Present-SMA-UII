# Tech Stack Knowledge Base

Dokumen ini berisi rangkuman keputusan arsitektural dan teknologi (ADR - *Architecture Decision Records*) yang menjelaskan **mengapa** kami memilih teknologi spesifik dalam *stack* proyek O-Present-SMA UII, serta pertimbangan teknis di baliknya.

---

## 1. Backend Framework: CodeIgniter 4 (PHP 8.3)

*Mengapa tidak Laravel atau arsitektur Node.js/Go?*
- **Warisan Sejarah (Legacy)**: Proyek awal (versi 1) menggunakan PHP murni / CI lawas, sehingga CI4 merupakan jalur evolusi (upgrade) yang masuk akal tanpa harus merombak ulang 100% basis kodingan model lama pada saat *fork* terjadi.
- **Performa & Jejak Memori**: Dibandingkan Laravel yang cukup berat dan memakan banyak RAM, CI4 memiliki performa yang sangat ringan dan cepat. CI4 memiliki struktur konfigurasi minimal (tanpa sihir dependensi yang tersembunyi), yang cocok untuk aplikasi berskala spesifik seperti sistem presensi sekolah.
- **PHP 8.3**: Proyek menerapkan aturan tipe (Type Hinting) ketat, enum, dan readonly properties yang difasilitasi penuh oleh ekosistem PHP modern.

## 2. Template Engine: Twig (v3)

*Mengapa menggunakan Twig daripada Parser bawaan CI4 atau Blade?*
- **Sintaks Deklaratif yang Bersih**: Template CI4 standar memaksakan blok PHP murni (`<?php echo ... ?>`) yang seringkali berantakan. Twig (`{{ variable }}`) sangat bersih dan mencegah logika PHP tumpah ke ruang *View*.
- **Keamanan Bawaan**: Twig secara otomatis melakukan *escape* pada semua *output* variabel HTML, menjamin sistem aman dari serangan *Cross-Site Scripting* (XSS) tanpa perlu instruksi tambahan dari *developer*.
- **Inheritance & Block**: Fitur `{% extends %}` dan `{% block %}` memungkinkan pembuatan *layout* bersarang (*nested layouting*) yang sangat fleksibel.

## 3. Filosofi UI: Progressive Enhancement (HTMX + Alpine.js)

*Mengapa tidak menggunakan kerangka SPA (Single Page Application) modern seperti React, Vue, atau Next.js?*

1. **Kompleksitas yang Tidak Perlu**: Sistem presensi utamanya berkisar pada *form submit*, penampilan tabel, dan unggahan data. Membangun SPA akan memaksa kita mengelola dua *state* (klien dan server), *routing* ganda, dan kompleksitas API yang melelahkan.
2. **Kekuatan HTMX**: Kami mengembalikan status quo aplikasi ke *Server-Rendered HTML*. HTMX memungkinkan klien untuk meminta pembaruan spesifik di bagian DOM tertentu melalui AJAX (HTML *over* the wire), menghadirkan sensasi SPA tanpa kompleksitas JS bundler yang gemuk.
3. **Kekuatan Alpine.js**: Untuk antarmuka reaktif sederhana yang murni berada di klien (seperti membuka modal, navigasi kamera WebRTC web, mengelola status geolokasi), Alpine.js menawarkan sintaks direktif mirip Vue (`x-data`, `x-show`) secara deklaratif langsung di *markup* Twig. 

Kombinasi HTMX + Alpine + Twig menghasilkan ukuran *bundle* yang sangat kecil, aplikasi yang ringan dimuat (terutama penting bagi koneksi gawai sekolah yang kadang fluktuatif), dan *development speed* yang tinggi.

## 4. CSS Framework: Tailwind CSS v4

- **Utility-First**: Memungkinkan kustomisasi UI yang amat cepat langsung di dalam HTML (Twig) tanpa perlu melompat ke *stylesheet* `.css` yang terpisah.
- **Versi 4 (Vite-powered)**: Kami mengadopsi v4 JIT engine terbaru, yang sama sekali menghilangkan kebutuhan file `tailwind.config.js` raksasa, mengandalkan Vite dan CSS Variables secara murni (*lightning fast build*).

## 5. Front-End Tooling: Bun, Vite, Biome & TypeScript

- **Vite & Bun**: Proses instalasi (*Bun install* bisa 30x lebih cepat dari NPM) dan kompilasi (*Vite HMR*) sangat instan. Vite menyatukan seluruh aset Alpine, Tailwind, dan modul kustom ke dalam struktur manifes modern.
- **TypeScript & Zod**: Meskipun logika utama ada di sisi server, script klien untuk Kamera, Peta (Leaflet), dan Validasi Form membutuhkan akurasi tinggi. TypeScript menangkap *Runtime Errors* menjadi *Compile-time Errors*. Zod memastikan struktur objek data form di *client* sama persis dan konsisten sebelum dikirim ke server via *multipart/form-data* (HTMX).
- **Biome (v2.x)**: Pengganti holistik untuk **ESLint** dan **Prettier**. Biome ditulis dalam bahasa *Rust*, mengeksekusi proses *linting* dan *formatting* seluruh berkas TypeScript/JavaScript dalam hitungan milidetik (*blazing fast*).

## 6. Kualitas Statis Backend: PHPStan & PHP CS Fixer

- Mengandalkan pengecekan manual tidaklah cukup. **PHPStan (Level 9)** dipasang untuk memvalidasi *strict typing*, struktur *array*, dan potensi *null pointer exception*. 
- **PHP CS Fixer** memastikan standar koding PSR-12 diimplementasikan tanpa peduli *developer* mana (berbeda *IDE* atau *OS*) yang melakukan *commit*.

## Kesimpulan
Keseluruhan Tech Stack ini dipilih untuk mencapai keseimbangan ekstrem:
**Pengalaman pengguna serealistis SPA (Fast, Reactive)** dipadukan dengan **kesederhanaan dan keamanan dari Server-Rendered MVC klasik**, di bawah payung aturan tipe ketat (*strict types*) di kedua sisi (TS di Frontend, PHP 8 di Backend).
