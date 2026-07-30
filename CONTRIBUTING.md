# Panduan Berkontribusi (Contributing Guide)

Terima kasih atas minat Anda untuk berkontribusi pada proyek **O-Present-SMA UII**. Panduan ini akan membantu Anda memahami proses kontribusi, mulai dari pelaporan bug hingga pengajuan *Pull Request* (PR).

---

## 1. Kode Etik

Dengan berpartisipasi dalam proyek ini, Anda setuju untuk menjaga lingkungan yang ramah, inklusif, dan profesional. Harap gunakan bahasa yang sopan dalam pelaporan *Issue* maupun diskusi pada *Pull Request*.

## 2. Melaporkan Masalah (Bug Reports)

Jika Anda menemukan bug, silakan buat *Issue* baru di repositori kami dengan format berikut:
- **Judul**: Singkat dan jelas (Contoh: `[BUG] Tombol presensi tidak merespons di iOS 15`).
- **Langkah Reproduksi**: Tuliskan urutan pasti untuk memunculkan masalah tersebut.
- **Ekspektasi vs Realita**: Apa yang seharusnya terjadi vs apa yang sebenarnya terjadi.
- **Environment**: OS (Windows/Mac/iOS/Android), Browser, dan versi PHP/Node yang dipakai.

## 3. Alur Kerja Kontribusi (Workflow)

Kami menggunakan pola **Git Flow** yang disederhanakan:
1. Pindah ke branch utama (`main` atau `develop` sesuai kebijakan repositori terbaru).
2. Lakukan `git pull` untuk memastikan kode Anda paling mutakhir.
3. Buat *branch* baru dari titik tersebut dengan format:
   - `feat/nama-fitur-baru` (Untuk penambahan fitur)
   - `fix/nama-bug` (Untuk perbaikan bug)
   - `refactor/nama-modul` (Untuk penataan ulang kode)
   - `docs/nama-dokumen` (Untuk perubahan README/dokumentasi)
4. Lakukan perubahan pada kode Anda.

## 4. Standar Penulisan Pesan Commit (Conventional Commits)

Kami sangat mewajibkan penggunaan **Conventional Commits** agar riwayat (*history*) git bersih dan dapat dibaca. Format:
`<type>[optional scope]: <description>`

**Tipe yang diizinkan:**
- `feat:` (fitur baru)
- `fix:` (perbaikan bug)
- `docs:` (perubahan hanya pada dokumentasi)
- `style:` (perubahan yang tidak mempengaruhi makna kode, misal: indentasi, spasi)
- `refactor:` (perubahan kode yang tidak memperbaiki bug atau menambah fitur)
- `perf:` (perubahan untuk meningkatkan performa)
- `test:` (menambah atau memperbaiki pengujian yang ada)
- `chore:` (perubahan pada proses build, tool tambahan, dsb)

**Contoh yang benar:**
`feat(presensi): tambahkan validasi foto selfie berbasis WebRTC`
`fix(ui): perbaiki tata letak modal jabatan di layar kecil`
`chore: perbarui dependencies bun`

## 5. Pemeriksaan Kualitas Lokal (Pre-commit Hooks)

Proyek ini dipasangi pagar kualitas ketat untuk meminimalisasi PR yang bermasalah.
Sebelum melakukan `git commit`, pastikan Anda telah menjalankan agregat standar kualitas kami. Anda bisa mengujinya secara manual sebelum *commit*:

**Untuk Frontend (TS/JS):**
```bash
bun run format
bun run lint:fix
bun run typecheck
```

**Untuk Backend (PHP):**
```bash
composer cs-fix
composer stan
```

*Catatan: Saat Anda mengetikkan `git commit`, **Husky/Git Hooks** secara otomatis akan menjalankan script pemeriksa (Biome, PHP-CS-Fixer, dll). Jika ada error atau tipe data tidak valid (di TypeScript/PHPStan), proses commit akan **gagal**. Silakan perbaiki errornya terlebih dahulu.*

## 6. Mengirimkan Pull Request (PR)

1. *Push* branch Anda ke repositori asal (`origin`).
2. Buat *Pull Request* baru dengan tujuan ke branch utama.
3. Beri deskripsi yang rinci tentang apa yang diubah dan *mengapa* perubahan tersebut dibutuhkan.
4. Lampirkan tangkapan layar (*screenshot*) jika PR Anda merubah elemen Visual/UI.
5. GitHub Actions (CI/CD) akan secara otomatis menjalankan serangkaian *Test* (PHPUnit, E2E Test, PHPStan, Biome Lint). **Pastikan semua lampu indikator berwarna hijau (Passed)**.
6. Tim *Reviewer* (Penyelia) akan mereview kode Anda, memberikan masukan, lalu melakukan merge jika semua sudah sesuai standar (Clean Architecture & Code Style).

---

*Mari kita bangun aplikasi yang bersih, solid, dan bermanfaat bersama-sama!*
