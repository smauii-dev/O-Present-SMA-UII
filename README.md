# O-Present-SMA UII

[![made-with-codeigniter4](https://img.shields.io/badge/Made%20with-CodeIgniter4-DD4814.svg)](https://www.codeigniter.com/) [![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-brightgreen.svg)](https://phpstan.org/) [![TypeScript Strict](https://img.shields.io/badge/TypeScript-Strict-3178C6.svg)](https://www.typescriptlang.org/) [![Biome](https://img.shields.io/badge/Biome-Lint%20%26%20Format-60A5FA.svg)](https://biomejs.dev/) [![Open Source? Yes!](https://badgen.net/badge/Open%20Source%3F/Yes%21/blue?icon=github)](https://github.com/josephines1/o-present)

Aplikasi presensi online berbasis web untuk **SMA UII Yogyakarta** — mencatat kehadiran via foto selfie + GPS, dengan manajemen data pegawai, lokasi presensi, laporan harian/bulanan, dan pengajuan ketidakhadiran.

---

## Asal-usul & Sejarah

Proyek ini awalnya merupakan fork dari [`josephines1/o-present`](https://github.com/josephines1/o-present) oleh **Ahmad Hanif** ([@Rosyiii](https://github.com/Rosyiii), teknisi IT SMA UII Yogyakarta), yang diimplementasikan sebagai sistem presensi sementara (alternatif dari [aksesekolah](https://github.com/SMA-UII-Yogyakarta/aksesekolah)) yang tengah dikerjakan oleh tim **PT Koneksi Jaringan Indonesia**.

Karena kualitas kode yang perlu perbaikan besar, **sandikodev** ([@sandikodev](https://github.com/sandikodev)) selaku tim lead proyek aksesekolah kemudian ikut turun tangan — melakukan perombakan menyeluruh mulai dari struktur aplikasi, perancangan tech stack, refactoring naming convention (Indonesia → English), implementasi Clean Architecture (DTO, Repository, Service, Validator, ViewModel), integrasi frontend modern (TypeScript, Zod, Vite), migrasi ke Twig, hingga refining UI/UX.

Filosofi desain: **server-rendered MVC + progressive enhancement** — bukan SPA, bukan API-only.

---

## Tech Stack & Architecture (Detail Lengkap)

Proyek ini menggunakan pendekatan **Clean Architecture** pada backend dan **Progressive Enhancement** pada frontend.

### Backend (Clean Architecture Layering)

*   **Layering**: Controller → Service → Repository → Model
*   **Framework**: [CodeIgniter 4.5+](https://codeigniter.com/) (PHP 8.3)
*   **Template Engine**: [Twig 3](https://twig.symfony.com/) (dikombinasikan dengan ViewModels)
*   **Auth**: [Myth/Auth 1.2+](https://github.com/lonnieezell/myth-auth) (SHA-384 + bcrypt, Role-based access: Admin, Head, Pegawai)
*   **Database**: PostgreSQL 15+ (NeonDB cloud) — migrasi dari MySQL, migration-first workflow via CI4 Migration
*   **Storage**: S3-compatible (AWS S3 / MinIO / Cloudflare R2) via AWS SDK for PHP v3 — upload presensi, foto profil, dokumen ketidakhadiran; presigned URL untuk akses aman
*   **Spreadsheet**: [PhpOffice/PhpSpreadsheet 1.29+](https://phpspreadsheet.readthedocs.io/) — export Excel laporan presensi, pegawai, ketidakhadiran, lokasi
*   **Geo/Location**: Custom helper `geo_helper.php` — Haversine distance (meter), coordinate normalization (7 desimal ≈ 1.1cm), Google Maps URL parser (place pin, @lat,lng, query params, DMS), canonical SMA UII locations (3 titik resmi), deduplication service
*   **Image Processing**: GD/Imagick via intervention/image (opsional) — validasi MIME, resize, konversi base64 → binary
*   **Email**: CodeIgniter Email + SMTP (Gmail/Mailgun) — reset password, aktivasi akun, notifikasi ketidakhadiran
*   **Session**: CI4 Session (database-backed via `ci_sessions` table) + CSRF double-submit cookie + meta tag
*   **Caching**: File-based (default), Redis-ready via `Config\Cache`
*   **Logging**: Monolog via CI4 Logger (daily rotating, level-based)
*   **Validation**: CI4 Validation + custom rules (`PegawaiRules`, `myCustomValidation`) + Zod schema shared dengan frontend
*   **Helper Libraries**: `geo_helper`, `s3_helper`, `date_helper` (Indonesia locale)
*   **Commands**: CLI Spark commands — `InitS3`, `CreateAdminUser`, `deduplicate:lokasi`, `seed:canonical`

#### Backend Directory Structure
```
app/
├── Config/              # Semua konfigurasi (Database, Auth, Email, S3, Routes, Filters, Validation, View, CSP, dll)
├── Controllers/
│   ├── Api/             # Pure JSON API (session-based auth, CSRF via header)
│   │   ├── Admin/       # Admin-only endpoints (Jabatan, Lokasi, Role, Ketidakhadiran)
│   │   ├── Auth.php     # Login, logout, me, forgot/reset password
│   │   ├── Dashboard.php
│   │   ├── Pegawai.php  # CRUD + import/export Excel + template
│   │   ├── Presensi.php # Clock-in/out, rekap, export harian/bulanan/rekap
│   │   ├── Profile.php  # Update profile, upload foto, change password
│   │   └── Ketidakhadiran.php
│   └── Web/             # Server-rendered Twig + HTMX endpoints
│       ├── Auth.php
│       ├── Dashboard.php (Overview)
│       ├── Pegawai.php
│       ├── Presensi.php
│       ├── Rekap.php
│       ├── Ketidakhadiran.php
│       ├── Lokasi.php
│       ├── Jabatan.php
│       ├── Profile.php
│       ├── Media.php    # Proxy S3 untuk private assets
│       └── Overview.php
├── Database/
│   ├── Migrations/      # Schema versioning (create tables, add columns, fix nullable)
│   └── Seeds/           # Auth groups, permissions, admin user, jabatan, lokasi, pegawai
├── Filters/
│   ├── ApiSessionAuth.php  # JWT-like session guard untuk API
│   ├── ApiCors.php
│   └── RoleFilter.php
├── Helpers/
│   ├── geo_helper.php       # Haversine, normalize_coordinate, parse_google_maps_url, canonical_sma_uii_locations
│   └── s3_helper.php
├── Language/
│   └── en/Validation.php
├── Libraries/
│   └── Validation/myCustomValidation.php
├── Models/              # ActiveRecord-style (extends CodeIgniter\Model)
│   ├── PegawaiModel.php
│   ├── PresensiModel.php
│   ├── KetidakhadiranModel.php
│   ├── LokasiPresensiModel.php
│   ├── JabatanModel.php
│   ├── UsersModel.php
│   ├── RoleModel.php
│   ├── UsersRoleModel.php
│   └── EmailTokenModel.php
├── Services/            # Business logic terpusat (Single Source of Truth)
│   ├── PresensiService.php     # Clock-in/out, validasi lokasi, hitung durasi/terlambat, rekap, enrich foto URL
│   ├── PhotoService.php        # Upload/foto presensi & profil ke S3, decode base64, delete, generate URL
│   ├── S3Service.php           # Low-level S3 client wrapper (putObject, deleteObject, presigned URL, bucket policy)
│   └── LokasiService.php       # CRUD lokasi + validasi unik nama/koordinat/slug + canonical upsert + deduplication
├── Validation/
│   └── PegawaiRules.php        # Shared validation rules (create, update, profileUpdate, phone, gender)
├── Views/
│   ├── layouts/         # Twig base layouts (app.twig, auth.twig, base.twig, splash.twig)
│   └── routes/          # Twig templates per fitur (presensi, rekap, absensi, pegawai, jabatan, lokasi, overview, auth, profile)
└── ThirdParty/          # Placeholder untuk library non-composer
```

### Frontend (Progressive Enhancement)

*   **Build Tool**: [Bun](https://bun.sh/) + [Vite 6](https://vitejs.dev/) — native ESM, HMR, TypeScript, CSS bundling, asset hashing
*   **Language**: TypeScript 5.5+ (Strict mode, `noEmit: true` untuk type-check only, Vite handle transpile)
*   **Runtime**: Alpine.js 3.14+ (global `Alpine` + component registration via `Alpine.data()`)
*   **Partial Updates**: HTMX 2.0+ (hx-get, hx-post, hx-swap, hx-trigger, hx-indicator, hx-target, HX-Trigger response headers)
*   **Validation**: [Zod 3.23+](https://zod.dev/) — schema-first, shared types via `z.infer<>`, client-side validate + server re-validate
*   **CSS Framework**: [Tailwind CSS v4](https://tailwindcss.com/) (native CSS cascade, `@import "tailwindcss"`, `@theme` config, JIT compiler via Vite plugin)
*   **Icons**: [Tabler Icons](https://tabler.io/icons) (inline SVG, zero-runtime)
*   **Date/Time**: Native `Intl.DateTimeFormat` (locale `id-ID`) + helper `formatDate`, `formatTime` di `window`
*   **PWA**: Service Worker (`sw.js`) — `CacheFirst` untuk static assets, `NetworkFirst` untuk API/HTML, offline fallback page
*   **CSRF**: Dual-header strategy — `<meta name="csrf-token">` + cookie `csrf_cookie_name` → HTMX `htmx:configRequest` inject `X-CSRF-TOKEN`
*   **Toast/Notification**: Custom `window.showToast(message, type)` via `CustomEvent('toast')` + Alpine component
*   **Modal**: Alpine `Modal` component (register via `registerModalComponent`) — akses via `x-data="modal"`, trigger via `x-on:click="$dispatch('open-modal', { id: 'modal-id' })"`
*   **Camera/GPS**: WebRTC `getUserMedia` (facingMode: user) + `navigator.geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 12000 })` — wrap di Alpine component `clockIn`
*   **Forms**: HTMX form submit (`hx-post`, `hx-swap="none"`) + Alpine validation mixin (`Alpine.data('validation', schema)`) → Zod validate on submit, hx-on::after-request handle response

#### Frontend Directory Structure
```
resources/
├── ts/
│   ├── main.ts                 # Entry point: Alpine, HTMX, Zod schemas, global utils, toast, formatDate/Time
│   ├── lib/
│   │   ├── api.ts              # Wrapper fetch (get/post/put/del) + CSRF + Zod validate + error handling
│   │   ├── alpine-validation.ts# Alpine.data('validation', schema) mixin
│   │   └── validate.ts         # Zod validateWith helper
│   ├── schemas/                # Zod schemas (login, pegawai, jabatan, lokasi, ketidakhadiran, clockIn, profile, rekapFilter, dll)
│   ├── components/
│   │   └── Modal.ts            # Alpine modal component registration
│   └── types/
│       └── globals.d.ts        # Window extensions (api, validate, schemas, showToast, formatDate, formatTime, AlpineValidationMixin)
├── css/
│   ├── main.css                # @import "tailwindcss"; @theme { ... } + custom utilities
│   └── components/             # (optional) component-specific CSS
└── public/ (served as-is)
    ├── images/
    ├── favicon.svg
    └── manifest.json
```

**Build Output**: `public/build/` (hashed filenames, `.vite/manifest.json` untuk Twig `asset()` helper)

### Quality Assurance & Testing

| Tool | Config | Command |
|------|--------|---------|
| **PHPStan** | Level 9 (`phpstan.neon`, `phpstan-baseline.neon`) | `composer stan` |
| **PHP CS Fixer** | PSR-12 + strict types (`.php-cs-fixer.php`) | `composer cs-fix` / `composer cs-check` |
| **Rector** | PHP 8.3 modernisasi (rector.php) | `composer rector` |
| **Biome v2** | Lint + Format (biome.json, `biome.json`) | `bun run lint` / `bun run lint:fix` / `bun run format` |
| **TypeScript** | Strict (`tsconfig.json`) | `bun run typecheck` |
| **PHPUnit** | CI4 TestSupport (Feature/Unit) | `composer test` |
| **Playwright** | E2E (Chromium/Firefox/WebKit) | `bun run test:e2e` |
| **Pre-commit** | Husky (`.husky/pre-commit`) → Biome + TSC + CS Fixer | Auto on commit |

**CI/CD**: GitHub Actions (`.github/workflows/ci.yml`) — matrix PHP 8.2/8.3, Node/Bun, PostgreSQL service; run stan, cs-check, rector --dry-run, typecheck, lint, test, test:e2e.

### Infrastructure & Deployment

| Component | Detail |
|-----------|--------|
| **Container Runtime** | Docker 26+ / Docker Compose v2 |
| **Base Image** | `php:8.3-apache-bookworm` (custom Dockerfile: install php extensions pdo_pgsql, gd, intl, zip, bcmath, redis, imagick; enable apache mod_rewrite, headers, expires) |
| **Dev Compose** | `docker-compose.dev.yml` — services: `app` (PHP+Apache), `db` (PostgreSQL 15), `redis` (Alpine), `vite` (HMR server port 5173) |
| **Prod Compose** | `docker-compose.yml` — services: `app`, `db` (external NeonDB), `nginx` (reverse proxy, SSL termination) |
| **Web Server** | Apache (dev) / Nginx 1.26+ (prod) — gzip, brotli, CSP, HSTS, rate-limit |
| **Reverse Proxy** | Nginx (host-level) → `nginx-net` network → container `smauii-opresent-app:80` |
| **SSL** | Let's Encrypt (Certbot) — auto-renew via systemd timer, certs di `/etc/letsencrypt/live/presensi.smauiiyk.sch.id/` |
| **Domain** | `presensi.smauiiyk.sch.id` (A record → host `konxc-services` / 202.162.40.161) |
| **Database (Prod)** | NeonDB (PostgreSQL 15, AWS us-east-1) — connection pooling via PgBouncer, branch `main` |
| **Database (Dev)** | PostgreSQL 15 container (volume `pgdata_dev`) |
| **Secrets** | `.env` (gitignored) → `APP_KEY`, `DB_*`, `S3_*`, `EMAIL_*`, `CSP_*`; CI secrets via GitHub Actions `secrets` |
| **CI Runner** | GitHub Actions (ubuntu-latest) + self-hosted runner untuk deploy ke `konxc-services` via SSH |
| **Deploy Script** | `deploy.sh` — `docker compose pull`, `docker compose up -d --remove-orphans`, `php spark migrate`, `php spark cache:clear`, health check |
| **Monitoring** | Laravel Telescope (not installed) — current: `log_message()` + `tail -f writable/logs/` + nginx access/error log |

### Key Packages (composer.json / package.json)

**PHP (composer.json)**
```json
{
  "require": {
    "php": "^8.2",
    "codeigniter4/framework": "^4.5",
    "codeigniter4/translations": "^4.5",
    "myth/auth": "^1.2",
    "aws/aws-sdk-php": "^3.280",
    "phpoffice/phpspreadsheet": "^1.29",
    "twig/twig": "^3.8",
    "laminas/laminas-diactoros": "^2.9",
    "vlucas/phpdotenv": "^5.6",
    "ext-pdo_pgsql": "*",
    "ext-gd": "*",
    "ext-intl": "*",
    "ext-bcmath": "*",
    "ext-zip": "*"
  },
  "require-dev": {
    "phpstan/phpstan": "^1.11",
    "friendsofphp/php-cs-fixer": "^3.50",
    "rector/rector": "^1.2",
    "phpunit/phpunit": "^10.5",
    "codeigniter4/shield": "^1.0"
  }
}
```

**Frontend (package.json)**
```json
{
  "dependencies": {
    "alpinejs": "^3.14",
    "htmx.org": "^2.0",
    "zod": "^3.23",
    "tailwindcss": "^4.0",
    "@tailwindcss/vite": "^4.0"
  },
  "devDependencies": {
    "typescript": "^5.5",
    "vite": "^6.0",
    "@biomejs/biome": "^1.8",
    "@playwright/test": "^1.45",
    "bun-types": "^1.1"
  }
}
```

### Environment Variables (`.env` / `.env.example`)

| Variable | Required | Description |
|----------|----------|-------------|
| `CI_ENVIRONMENT` | Ya | `development` \| `testing` \| `production` |
| `app.baseURL` | Ya | Base URL aplikasi (contoh: `https://presensi.smauiiyk.sch.id/`) |
| `app.appKey` | Ya | 32-char random string (encryption, CSRF, session) |
| `database.default.hostname` | Ya | DB host (localhost / NeonDB endpoint) |
| `database.default.database` | Ya | Nama database |
| `database.default.username` | Ya | DB user |
| `database.default.password` | Ya | DB password |
| `database.default.DBDriver` | Ya | `Postgre` |
| `database.default.port` | Ya | `5432` |
| `S3.endpoint` | Ya | S3 endpoint (contoh: `https://s3.amazonaws.com` atau MinIO) |
| `S3.region` | Ya | Region (contoh: `us-east-1`) |
| `S3.bucket` | Ya | Nama bucket |
| `S3.accessKey` | Ya | Access Key ID |
| `S3.secretKey` | Ya | Secret Access Key |
| `S3.usePathStyle` | Ya | `true` untuk MinIO, `false` untuk AWS S3 |
| `S3.publicEndpoint` | Tidak | Public CDN endpoint (CloudFront, Cloudflare R2 public URL) |
| `email.SMTPHost` | Ya | SMTP host (smtp.gmail.com, smtp.mailgun.org) |
| `email.SMTPPort` | Ya | 587 / 465 |
| `email.SMTPUser` | Ya | SMTP username |
| `email.SMTPPass` | Ya | SMTP password |
| `email.fromEmail` | Ya | Sender email |
| `email.fromName` | Ya | Sender name |
| `CSP.enabled` | Tidak | `true` / `false` — Content Security Policy |
| `FeatureFlags.*` | Tidak | Feature toggle (misal: `FeatureFlags.pwa = true`) |

---

## Fitur Utama (Features)

Aplikasi ini tidak hanya mencatat presensi, tetapi juga mengelola seluruh siklus kehadiran pegawai secara end-to-end.

- **Presensi Validasi Ganda (GPS & Selfie)**
  Mencatat kehadiran (Clock-In/Clock-Out) secara presisi menggunakan geofencing (validasi jarak radius lokasi) dan verifikasi foto selfie via WebRTC/Kamera, yang diunggah dan disimpan ke S3/Lokal.
- **Pengajuan & Manajemen Ketidakhadiran**
  Pegawai dapat mengajukan Cuti, Izin, atau Sakit lengkap dengan unggahan dokumen pendukung (mendukung drag & drop multipart/form-data dan validasi Zod). Sakit disetujui otomatis, sementara Cuti/Izin membutuhkan approval manual dari Head/Atasan.
- **Laporan & Rekapitulasi (Export to Excel)**
  Menyediakan rekapitulasi kehadiran (Hadir, Alpha, Izin, Cuti) secara harian dan bulanan yang dapat difilter secara real-time dan diekspor ke format Excel.
- **Master Data Management**
  Operasional CRUD (Create, Read, Update, Delete) yang responsif tanpa full-page reload menggunakan HTMX dan modal Alpine.js untuk data Pegawai, Jabatan, dan Lokasi Presensi (berbasis peta interaktif Leaflet).
- **Multi-role Auth & Access Control**
  Pemisahan hak akses yang ketat antara `Admin` (pengelola master data), `Head` (pimpinan penyetuju laporan), dan `Pegawai/Student` (pengguna akhir), dikawal oleh middleware filter CI4.
- **Progressive Web App (PWA)**
  Dapat diinstal di perangkat (Installable), dilengkapi fallback halaman offline, dan Service Worker untuk caching aset statis demi kecepatan akses.

---

## Getting Started

Proyek memisahkan **development** (isolated, lokal menggunakan Docker) dari **production** agar kerja harian aman dan tidak merusak environment asli. Proyek ini sangat **bun-centric**: cukup satu entry point (`bun run`) untuk mengontrol frontend dan backend.

### 1. Requirements
- [PHP 8.1+](https://www.php.net/) (8.3 sangat direkomendasikan)
- [Composer](https://getcomposer.org/) & [Bun](https://bun.sh/)
- Docker & Docker Compose (Disarankan untuk setup environment development lokal)

### 2. Clone & Setup Environment
```bash
git clone https://github.com/Rosyiii/O-Present-SMA-UII.git
cd O-Present-SMA-UII

# Install dependencies (Backend & Frontend)
composer install
bun install

# Setup env lokal
cp env .env
```

### 3. Menjalankan Development Server (Isolated via Docker)
Semua perintah PHP/Composer dapat dijalankan di dalam container `opresent-app-dev` secara otomatis melalui script Bun.

```bash
# A. Pertama kali setup: Build images, start container, migrate & seed DB lokal
bun run dev:setup

# B. Rutinitas koding harian: Menjalankan Vite HMR (port 5173) & CI4 spark serve (port 8100) paralel
bun run dev
```
Akses aplikasi di browser Anda:
- Frontend dev (HMR Asset server): `http://localhost:5173/`
- Backend dev (Akses Utama Aplikasi): `http://localhost:8100/`

> **PENTING:** Jika Anda mengedit file template **Twig (.twig)** atau **PHP view**, Anda **WAJIB** menjalankan `bun run clear` di terminal terpisah untuk menghapus cache Twig (`writable/cache/twig/*`) agar perubahan layout Anda langsung terlihat.

**Perintah Pembantu Lainnya:**
```bash
bun run dev:down  # Menghentikan layanan container dev
bun run dev:logs  # Memantau log dari container dev
bun run clear     # Clear cache (Twig, Route, dll)
bun run build     # Compile/build frontend JS/CSS untuk production (ke folder public/build/)
```

---

## Akun Default (Database Seeder)

Setelah migrasi dan seed berhasil (secara otomatis lewat `bun run dev:setup`), Anda bisa login menggunakan kredensial default berikut:

| Role | Email | Password | Keterangan |
|------|-------|----------|------------|
| Head | jaya@present.com | password123 | Akses laporan harian/bulanan & approval |
| Admin | tamani@present.com | password123 | Akses penuh CRUD master data |
| Pegawai | choland@present.com | password123 | Akses antarmuka presensi standar |

*(Catatan: Seeder menggunakan `password123` untuk semua entitas akun yang digenerate).*

---

## Code Standards & Enforcement

Kualitas kode proyek ini dijaga ketat baik di backend maupun frontend melalui otomatisasi:

### Backend (PHP)
- **PHPStan (Level 9)**: `composer stan`
- **PHP CS Fixer**: `composer cs-fix` (auto-fix format) atau `composer cs-check` (dry-run)
- **Rector**: `composer rector` (modernisasi kode otomatis)

### Frontend (TypeScript / JavaScript)
- **Biome (v2.x)**: Tool all-in-one super cepat pengganti ESLint + Prettier. 
  Gunakan `bun run lint` (cek), `bun run lint:fix` (auto-fix aturan), dan `bun run format` (merapikan kode).
- **TypeScript**: `bun run typecheck` (`tsc --strict`). Type global untuk library frontend (seperti `window.Alpine` atau `window.htmx`) dikelola secara statis di `src/types/globals.d.ts`.

### Pre-commit Hook & CI/CD
Proyek mengonfigurasi `.git/hooks/pre-commit` untuk mencegah commit ke branch apabila ada pelanggaran code-standard (menjalankan Biome, TSC, dan CS Fixer secara lokal). Sementara itu, workflow `.github/workflows/ci.yml` (GitHub Actions) mengeksekusi pipeline Quality Assurance secara menyeluruh sebelum proses integrasi deployment.

---

## Tips & Troubleshooting

1. **"Only secure origins are allowed" (Isu Kamera/GPS di localhost)** — Fitur geolocation/kamera mensyaratkan protokol HTTPS. Jika menggunakan mobile device untuk testing lokal, gunakan `ngrok http 8100` untuk membuat tunneling HTTPS.
2. **"Origin Does Not Have Permission to use Geolocation Service" (khusus Safari/iOS)** — Tambahkan header berikut pada konfigurasi NGINX production Anda:
   ```nginx
   add_header Content-Security-Policy "upgrade-insecure-requests";
   ```
3. **Konflik Port Database** — Jika daemon PostgreSQL di lokal PC Anda sudah menggunakan port `5432`, `docker-compose.dev.yml` mungkin gagal memetakan port. Ubahlah mapping port host di `docker-compose.dev.yml` (contoh: `"5433:5432"`).

---

## Dokumentasi Tambahan

- **[Local Development Guide](docs/LOCAL_DEVELOPMENT.md)** — Panduan setup environment dev yang lebih komprehensif. **WAJIB dibaca sebelum mulai berkontribusi.**
- [Testing Guide](docs/TESTING_GUIDE.md) — Panduan penulisan dan menjalankan pengujian otomatis (Unit, Integration, E2E).
- [Architecture & Naming Plan](docs/ARCHITECTURE_AND_NAMING_PLAN.md) — Ketentuan aturan penamaan (Naming Conventions) dan pola arsitektur kode.
- [Tech Stack Knowledge Base](docs/TECH_STACK_KNOWLEDGE_BASE.md) — Catatan arsitektural yang menjelaskan alasan mengapa teknologi spesifik ini dipilih.

---

## Contributing

Kontribusi bersifat terbuka dan sangat dihargai. Silakan buat *issue* baru jika menemukan *bug* atau kebutuhan perbaikan, atau langsung kirim *pull request* dengan mematuhi format *conventional commits* (contoh: `feat:`, `fix:`, `refactor:`, `docs:`).

---

## Credits

> Diinisiasi dan dibuat awalnya melalui [josephines1/o-present](https://github.com/josephines1/o-present), kemudian di-fork dan diimplementasikan secara spesifik untuk SMA UII oleh **Ahmad Hanif / Rosyiii** ([@Rosyiii](https://github.com/Rosyiii)).
> 
> Dirancang ulang dan disempurnakan secara komprehensif (Backend, Frontend, Infrastruktur) oleh **sandikodev** ([@sandikodev](https://github.com/sandikodev)) — **PT Koneksi Jaringan Indonesia**.
> 
> **UI Kit & Assets**: [Tailwind CSS v4](https://tailwindcss.com/) + [Tabler Icons](https://tabler.io/icons) (inline SVG)