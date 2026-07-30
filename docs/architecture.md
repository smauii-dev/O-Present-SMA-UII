# Arsitektur & Konvensi O-Present

Dokumen ini menjelaskan secara lengkap arsitektur teknis O-present — mulai dari
struktur source code, pipeline aset Vite, integrasi CodeIgniter 4 + Twig,
hingga lingkungan Docker development dan production. Ditulis agar seluruh tim
memahami mengapa setiap keputusan diambil dan bagaimana semuanya saling
terhubung.

---

## Daftar Isi

1. [Ikhtisar Stack](#1-ikhtisar-stack)
2. [Struktur Direktori](#2-struktur-direktori)
3. [Pipeline Aset Vite](#3-pipeline-aset-vite)
4. [Integrasi Vite + Twig (PHP)](#4-integrasi-vite--twig-php)
5. [Apache Rewrite Rule](#5-apache-rewrite-rule)
6. [Error Pages (Twig)](#6-error-pages-twig)
7. [Environment Configuration](#7-environment-configuration)
8. [Docker Architecture](#8-docker-architecture)
9. [Development Workflow](#9-development-workflow)
10. [Production Workflow](#10-production-workflow)
11. [SDLC: Build, Test, Lint](#11-sdlc-build-test-lint)
12. [Daftar File Kunci](#12-daftar-file-kunci)
13. [Troubleshooting](#13-troubleshooting)
14. [Ringkasan Prinsip](#ringkasan-prinsip)

---

## 1. Ikhtisar Stack

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| Backend | CodeIgniter 4 | 4.7.x |
| Template Engine | Twig (nongbit/codeigniter-twig) | 3.x |
| Frontend Runtime | TypeScript + Vite | 6.3.x |
| CSS Framework | Tailwind CSS v4 | 4.1.x |
| JS Libraries | Alpine.js 3.x, HTMX 2.x, Zod 4.x | — |
| JS Runtime / Package Mgr | Bun | 1.3.x |
| Database | PostgreSQL 16 | — |
| Object Storage | RustFS (S3-compatible) | — |
| Container | Docker + Docker Compose | — |
| Web Server | Apache (mod_rewrite) | — |
| Reverse Proxy | nginx-proxy (external) | — |

---

## 2. Struktur Direktori

```
.
├── app/
│   ├── Config/
│   │   ├── App.php              # baseURL, timezone, charset
│   │   ├── Exceptions.php       # Custom TwigExceptionHandler
│   │   ├── Routes.php           # Rute web + API (English only)
│   │   └── Twig.php             # Konfigurasi Twig
│   ├── Controllers/
│   │   ├── BaseController.php   # Inisialisasi Twig + helper `vite()`, `vite_css()`
│   │   ├── Api/                 # REST API controllers
│   │   └── Web/                 # Server-rendered Twig controllers
│   ├── Debug/
│   │   └── TwigExceptionHandler.php  # Error handler → render Twig error pages
│   ├── Models/                  # Eloquent-style models (CI4)
│   ├── Services/                # Business logic layer
│   └── Views/
│       ├── errors/
│       │   ├── 404.twig         # Error 404 (extends base.twig)
│       │   ├── 500.twig         # Error 500 (extends base.twig)
│       │   ├── html/            # Fallback raw-PHP error views (CI4 default)
│       │   └── cli/             # CLI error views
│       ├── layouts/
│       │   ├── base.twig        # Layout utama — Vite assets + Alpine.js
│       │   ├── app.twig         # Layout authenticated (sidebar + mobile nav)
│       │   ├── auth.twig        # Layout login/register (split panel)
│       │   └── splash.twig      # Layout splash/landing (standalone)
│       └── routes/              # Halaman per rute
├── docker/
│   ├── Dockerfile               # Image PHP 8.2 + Apache + Composer + Bun
│   ├── docker-entrypoint.sh     # Auto-build: composer install, bun install, bun run build
│   ├── docker-compose.yml       # Production
│   └── docker-compose.dev.yml   # Development
├── public/
│   ├── .htaccess                # Apache rewrite: /assets/* → build/assets/*
│   ├── index.php                # CI4 entry point
│   ├── build/                   # ← VITE OUTPUT (gitignored)
│   │   └── .vite/
│   │       └── manifest.json    # Mapping: source → hashed filenames
│   └── assets/                  # Static assets (logo, uploads, dll)
├── resources/
│   ├── ts/
│   │   ├── main.ts              # Entry point JS → Alpine, HTMX, Zod, API client
│   │   ├── schemas/             # Zod schemas (validation)
│   │   ├── lib/                 # Utility: api.ts, validate.ts
│   │   └── components/          # Alpine components (Modal, dll)
│   └── css/
│       └── main.css             # Tailwind entry CSS
├── vendor/                      # PHP dependencies (gitignored)
├── node_modules/                # JS dependencies (gitignored)
├── writable/                    # CI4 runtime (cache, logs, session, twig cache)
├── vite.config.ts               # Vite build config
├── package.json                 # JS dependencies + scripts
├── tsconfig.json                # TypeScript config
├── biome.json                   # Biome linter/formatter config
├── .env                         # Default env (gitignored)
├── .env.dev                     # Development env
├── .env.production              # Production env (secrets, DB, S3)
└── .gitignore
```

---

## 3. Pipeline Aset Vite

### 3.1 Konfigurasi Build

File: `vite.config.ts`

```typescript
build: {
  outDir: "public/build",       // Output directory (gitignored)
  emptyOutDir: true,            // Bersihkan sebelum build
  manifest: true,               // Generate manifest.json
  rollupOptions: {
    input: resolve(__dirname, "resources/ts/main.ts"),
    output: {
      entryFileNames: "assets/[name]-[hash].js",
      chunkFileNames: "assets/[name]-[hash].js",
      assetFileNames: "assets/[name]-[hash].[ext]",
    },
  },
},
```

**Mengapa `public/build/`:**
- `public/` adalah document root Apache (`APACHE_DOCUMENT_ROOT=/var/www/html/public`)
- `build/` subdirectory khusus output Vite — terpisah dari static assets (`public/assets/`)
- Seluruh isi `public/build/` di-gitignore — TIDAK PERNAH di-commit

### 3.2 Manifest

Setelah `bun run build`, Vite menghasilkan:

```
public/build/
├── .vite/
│   └── manifest.json           # Mapping source → output
└── assets/
    ├── main-C2G8oTBe.js        # JS bundle (hashed)
    └── main-X04P5_LY.css       # CSS bundle (hashed)
```

Isi `manifest.json`:

```json
{
  "resources/ts/main.ts": {
    "file": "assets/main-C2G8oTBe.js",
    "name": "main",
    "src": "resources/ts/main.ts",
    "isEntry": true,
    "css": ["assets/main-X04P5_LY.css"]
  }
}
```

**Kunci:**
- `file` → path relatif dari `public/build/` (`assets/main-C2G8oTBe.js`)
- `css` → array file CSS yang diimpor entry ini
- **Tidak ada prefix `/build/`** — itu pekerjaan PHP helper

### 3.3 Alur Build

```
resources/ts/main.ts  ──→  Vite build  ──→  public/build/
                    (source)         (manifest + hashed files)
```

**Siapa yang menjalankan build?**

| Environment | Kapan | Bagaimana |
|-------------|-------|-----------|
| Development | Container startup | `docker-entrypoint.sh`: `[ ! -d public/build ] && bun run build` |
| Production | Container startup | Sama — entrypoint yang sama |
| Manual | Saat开发 | `bun run build` di terminal |

---

## 4. Integrasi Vite + Twig (PHP)

### 4.1 Prinsip

> Source code hanya berisi `vite('main.ts')` — tanpa hash, tanpa path build.
> PHP helper membaca manifest dan menghasilkan URL bersih `/assets/main-<hash>.js`.

### 4.2 Twig Helper Functions

File: `app/Controllers/BaseController.php` (baris 55–105)

```php
$this->twig->addFunctions([
    'vite' => function (string $entry) {
        $manifestPath = ROOTPATH . 'public/build/.vite/manifest.json';
        // ... baca manifest, return '/assets/main-<hash>.js'
    },
    'vite_css' => function (string $entry) {
        $manifestPath = ROOTPATH . 'public/build/.vite/manifest.json';
        // ... baca manifest, return '<link rel="stylesheet" href="/assets/main-<hash>.css">'
    },
]);
```

**Alur:**
1. Template Twig: `{{ vite('main.ts') }}`
2. Helper membaca `manifest.json`
3. Mencari key `"resources/ts/main.ts"` → dapat `"file": "assets/main-C2G8oTBe.js"`
4. Return `"/assets/main-C2G8oTBe.js"` (diprefiks `/`, TANPA `/build/`)

### 4.3 Penggunaan di Template

File: `app/Views/layouts/base.twig`

```twig
<head>
  <link rel="icon" href="/assets/logo.png" type="image/png">
  {{ vite_css('main.ts')|raw }}                    {# → <link href="/assets/main-<hash>.css"> #}
  {% block head %}{% endblock %}
  <script type="module" src="{{ vite('main.ts') }}"></script>  {# → /assets/main-<hash>.js #}
</head>
```

**Penting:**
- `|raw` diperlukan karena `vite_css()` mengembalikan HTML tag, bukan plain text
- JANGAN duplicate `vite_css()` di child layouts — `base.twig` sudah menyediakannya
- Child layouts hanya boleh menambahkan CSS/JS tambahan via `{% block head %}`

### 4.4 Manifest Fallback

Jika `manifest.json` tidak ada (belum build), helper mengembalikan path default:
- `vite()` → `'/assets/' . $entry` (misal: `/assets/main.ts` — tidak hashed)
- `vite_css()` → `''` (kosong — tidak ada CSS link)

Ini memungkinkan aplikasi tetap jalan meskipun aset belum di-build.

---

## 5. Apache Rewrite Rule

File: `public/.htaccess`

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Serve Vite build assets via clean /assets/ URLs.
    # Files live in public/build/assets/ but are served at /assets/*.
    RewriteCond %{DOCUMENT_ROOT}/build/assets/$1 -f
    RewriteRule ^assets/(.+)$ build/assets/$1 [L]

    # CI4 routing
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
```

### 5.1 Mengapa Rewrite?

```
Browser request:    GET /assets/main-C2G8oTBe.js
                          │
Apache .htaccess:   RewriteRule ^assets/(.+)$ build/assets/$1 [L]
                          │
File system:        public/build/assets/main-C2G8oTBe.js  ← yang sebenarnya
```

**URL bersih** (`/assets/...`) dipisah dari **lokasi file** (`public/build/assets/...`).

### 5.2 Mengapa Bukan Symlink?

- **Simpel:** rewrite rule 3 baris, tidak perlu symlink management
- **Atomic:** file baru langsung tersedia, tidak perlu update symlink
- **Portable:** bekerja di semua environment tanpa script tambahan

### 5.3 Pengecekan

Rewrite hanya aktif jika file benar ada di `build/assets/`:
```apache
RewriteCond %{DOCUMENT_ROOT}/build/assets/$1 -f
```

Jika file tidak ada, request jatuh ke CI4 routing → 404.

---

## 6. Error Pages (Twig)

### 6.1 Custom Exception Handler

File: `app/Debug/TwigExceptionHandler.php`

CI4 menggunakan `Config\Exceptions::handler()` untuk memilih exception handler.
Kita override dengan `TwigExceptionHandler` yang:

1. **HTML request** → render Twig error page (`errors/404.twig`, `errors/500.twig`)
2. **Non-HTML request** (API/AJAX) → return JSON
3. **Fallback** → delegasi ke CI4 raw-PHP error handler

### 6.2 Registrasi

File: `app/Config/Exceptions.php`

```php
public function handler(int $statusCode, Throwable $exception): ExceptionHandlerInterface
{
    return new TwigExceptionHandler($this);
}
```

### 6.3 Error Templates

```
app/Views/errors/
├── 404.twig          # extends base.twig — full layout + Vite assets
├── 500.twig          # extends base.twig — full layout + Vite assets
├── html/             # Fallback raw-PHP (CI4 default)
│   ├── error_exception.php   # Debug mode — detail exception
│   └── debug.js              # JS untuk debug tabs
└── cli/              # CLI error views
```

### 6.4 Twig Cache

File: `app/Config/Twig.php`

```php
public bool $cache = (ENVIRONMENT === 'production');
public string $cacheDir = WRITEPATH . 'twig';
```

- **Development:** cache dimatikan (auto-reload template)
- **Production:** cache diaktifkan (performa)
- **Setelah deploy:** hapus `writable/twig/*` jika template berubah

**PENTING — Dua direktori cache berbeda:**

| Direktori | Isi | Kapan dihapus |
|-----------|-----|---------------|
| `writable/cache/` | CI4 system cache (routing, config, hooks) | Saat ada perubahan config/routes |
| `writable/twig/` | Compiled Twig templates (PHP) | **Setiap kali template `.twig` berubah** |

Kesalahan umum: menghapus `writable/cache/` saat yang perlu dihapus adalah
`writable/twig/`. Kedua direktori ini terpisah dan memiliki isi yang berbeda.

---

## 7. Environment Configuration

### 7.1 File Environment

| File | `CI_ENVIRONMENT` | `app.baseURL` | Database |
|------|-------------------|---------------|----------|
| `.env.dev` | `development` | `http://localhost:8100/` | `smauii-opresent-db-dev:5432/opresent_dev` |
| `.env.production` | `production` | `https://presensi.smauiiyk.sch.id/` | NeonDB (AWS) |

**Semua file `.env*` di-gitignore** — tidak ada secrets yang di-commit.

### 7.2 Perbedaan Konfigurasi

| Aspek | Development | Production |
|-------|-------------|------------|
| `CI_ENVIRONMENT` | `development` | `production` |
| `app.baseURL` | `http://localhost:8100/` | `https://presensi.smauiiyk.sch.id/` |
| `app.forceGlobalSecureRequests` | `false` | `true` |
| `app.sessionDriver` | `FileHandler` | `FileHandler` |
| `S3_ENDPOINT_FORCE` | `http://smauii-rustfs-dev:9000` | `http://smauii-rustfs:9000` |
| Twig cache | Disabled | Enabled |
| PHP error display | Enabled | Disabled |
| Log threshold | 9 (verbose) | 1 (errors only) |

---

## 8. Docker Architecture

### 8.1 Development Stack

File: `docker/docker-compose.dev.yml`

```
┌─────────────────────────────────────────────────────────┐
│  smauii-dev-net (bridge)                                │
│                                                         │
│  ┌──────────────────┐   ┌──────────────────┐           │
│  │ PostgreSQL 16    │   │ RustFS (S3)      │           │
│  │ smauii-opresent- │   │ smauii-rustfs-   │           │
│  │ db-dev:5432      │   │ dev:9000         │           │
│  └────────┬─────────┘   └────────┬─────────┘           │
│           │                      │                      │
│           └──────────┬───────────┘                      │
│                      │                                  │
│           ┌──────────┴───────────┐                      │
│           │  PHP + Apache        │                      │
│           │  smauii-opresent-    │                      │
│           │  app-dev:80 → 8100   │                      │
│           │                      │                      │
│           │  Volumes:            │                      │
│           │  └─ ..:/var/www/html │  ← BIND MOUNT       │
│           │     (live edits!)    │                      │
│           └──────────────────────┘                      │
└─────────────────────────────────────────────────────────┘
```

**Karakteristik:**
- **Bind mount** (`..:/var/www/html`) — source code langsung di-mount dari host
- **Port mapping:** `8100:80` — akses dari host via `http://localhost:8100`
- **Database terisolasi** — `opresent_dev`, tidak menyentuh production DB
- **Volume terpisah** — uploads, files, session, db data

### 8.2 Production Stack

File: `docker/docker-compose.yml`

```
┌─────────────────────────────────────────────────────────┐
│  nginx-net (external)                                   │
│                                                         │
│  ┌──────────────────┐   ┌──────────────────┐           │
│  │ RustFS (S3)      │   │ nginx-proxy      │           │
│  │ smauii-rustfs:   │   │ (external)       │           │
│  │ 9000             │   │                  │           │
│  └────────┬─────────┘   └────────┬─────────┘           │
│           │                      │                      │
│           │    presensi.smauii-  │                      │
│           │    yk.sch.id → :80   │                      │
│           │                      │                      │
│           └──────────┬───────────┘                      │
│                      │                                  │
│           ┌──────────┴───────────┐                      │
│           │  PHP + Apache        │                      │
│           │  smauii-opresent-    │                      │
│           │  app:80 (internal)   │                      │
│           │                      │                      │
│           │  Image: smauii-      │                      │
│           │  opresent:latest     │                      │
│           │  (COPY, no mount!)   │                      │
│           └──────────────────────┘                      │
└─────────────────────────────────────────────────────────┘
```

**Karakteristik:**
- **TIDAK ada bind mount** — source di-COPY ke dalam image (immutable)
- **Tidak ada host port** — hanya `expose: 80`, diakses via nginx-proxy
- **Image dibuild** — `COPY . /var/www/html/` di Dockerfile
- **Build aset di startup** — entrypoint menjalankan `bun run build`
- **Named volumes** — uploads dan files persisten

### 8.3 Dockerfile

File: `docker/Dockerfile`

```dockerfile
FROM php:8.2-apache

# 1. Install sistem dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev libicu-dev libpng-dev libjpeg-dev \
    libfreetype6-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql zip intl gd pcntl \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. PHP settings
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' ...

# 3. Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri ... /etc/apache2/sites-available/*.conf

# 4. Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer

# 5. Install Bun
RUN curl -fsSL https://bun.sh/install | bash && \
    ln -s /root/.bun/bin/bun /usr/local/bin/bun

# 6. Copy source code
COPY . /var/www/html/

# 7. Copy + chmod entrypoint
COPY docker/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
```

**Penting:**
- `a2enmod rewrite` — diperlukan untuk `.htaccess` rewrite
- `AllowOverride All` — di-set via sed agar Apache membaca `.htaccess`
- `COPY . /var/www/html/` — hanya source code, `public/build/` belum ada

### 8.4 Entrypoint

File: `docker/docker-entrypoint.sh`

```bash
#!/bin/bash
set -e
cd /var/www/html

# 1. PHP dependencies
if [ ! -d "vendor" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# 2. JS dependencies
if [ ! -d "node_modules" ]; then
    bun install --frozen-lockfile 2>/dev/null || bun install
fi

# 3. Build frontend assets
if [ ! -d "public/build" ]; then
    bun run build    # → Vite build → public/build/
fi

# 4. Permissions
chown -R www-data:www-data writable
chmod -R 775 writable

# 5. Start Apache
exec apache2-foreground
```

**Logika:**
- Kondisional — hanya install/build jika direktori belum ada
- **Dev (bind mount):** `node_modules/` dan `public/build/` mungkin sudah ada dari host
- **Prod (COPY):** direktori tidak ada → selalu build saat startup
- `--frozen-lockfile` — gunakan `bun.lock` yang sudah ada, gagal jika tidak cocok
- Fallback: `|| bun install` — jika lockfile tidak ada/invalid

---

## 9. Development Workflow

### 9.1 Setup Awal

```bash
# Clone repository
git clone <repo-url>
cd O-Present-SMA-UII-ori

# Build + start containers
docker compose -f docker/docker-compose.dev.yml up -d --build

# Tunggu sebentar, lalu akses:
# http://localhost:8100
```

### 9.2 Daily Development

```bash
# Mulai container (jika belum jalan)
docker compose -f docker/docker-compose.dev.yml up -d

# Edit source code di host — langsung terlihat di container (bind mount)
# Tidak perlu rebuild container untuk perubahan PHP/Twig

# Jika mengubah package.json (add/remove dependency):
docker exec -it smauii-opresent-app-dev bun install

# Jika mengubah source TypeScript/CSS:
docker exec -it smauii-opresent-app-dev bun run build
```

### 9.3 Vite HMR (Optional)

```bash
# Uncomment port 8082 di docker-compose.dev.yml:
#   - "8082:8080"

# Jalankan di dalam container:
docker exec -it smauii-opresent-app-dev bun run dev

# Akses: http://localhost:5173 (atau via SSH tunnel)
```

**Cara kerja HMR:**
1. Vite dev server berjalan di `:5173` di dalam container
2. Browser fetch modules dari Vite server (bukan Apache)
3. Perubahan langsung terlihat tanpa reload
4. Apache di `:80` tetap melayani halaman utama

### 9.4 Database Migration

```bash
# Jalankan migration + seed
docker exec -it smauii-opresent-app-dev php spark migrate
docker exec -it smauii-opresent-app-dev php spark db:seed

# Atau gunakan script yang sudah ada:
docker exec -it smauii-opresent-app-dev bun run dev:setup
```

---

## 10. Production Workflow

### 10.1 Deploy

```bash
# Build image + start containers
docker compose -f docker/docker-compose.yml up -d --build

# Container akan:
# 1. Install composer deps (jika vendor/ belum ada)
# 2. Install bun deps (jika node_modules/ belum ada)
# 3. Build aset (jika public/build/ belum ada)
# 4. Set permissions writable/
# 5. Start Apache
```

### 10.2 Update Code

```bash
# Pull latest code (di host)
git pull origin main

# Rebuild image
docker compose -f docker/docker-compose.yml up -d --build

# Container baru akan build aset otomatis
```

### 10.3 Update Aset Tanpa Rebuild Image

Jika hanya source TypeScript/CSS yang berubah:

```bash
# Masuk ke container
docker exec -it smauii-opresent-app bash

# Build manual
cd /var/www/html
bun run build

# Hapus Twig cache
rm -rf writable/twig/*
```

### 10.4 Mengapa `docker cp` / `docker exec sed` TIDAK Cukup untuk Production

Production menggunakan `COPY . /var/www/html/` di Dockerfile — source code dibakar
(baked) ke dalam Docker image pada saat build. Perubahan yang dilakukan via
`docker cp`, `docker exec sed -i`, atau edit langsung ke container yang sedang
berjalan hanya hidup di container layer sementara (ephemeral layer).

**Kenapa tidak persisten:**

```
docker compose build --build
    │
    ▼
Dockerfile: COPY . /var/www/html/    ← menggunakan file dari HOST filesystem
    │
    ▼
Container baru dibuat dari image baru  ← perubahan via docker cp HILANG
```

**Workflow yang benar untuk production:**

1. Edit file di **host filesystem** (atau via dev container dengan bind mount → otomatis sync ke host)
2. **Rebuild image:** `docker compose -f docker/docker-compose.yml up -d --build`
3. **Clear Twig cache** di container baru: `docker exec -it smauii-opresent-app rm -rf writable/twig/*`

**Jangan pernah:** `docker cp` file ke production container lalu clear cache — itu
hanya temporary dan akan hilang saat container recreate.

### 10.5 Entrypoint Conditional Build & Impikasinya

Entrypoint hanya menjalankan `bun run build` jika `public/build/` belum ada:

```bash
if [ ! -d "public/build" ]; then
    bun run build
fi
```

Masalah: Dockerfile `COPY . /var/www/html/` juga menyalin `public/build/` dari host
jika direktori tersebut sudah ada di host. Akibatnya entrypoint melewati build
karena sudah ada — meskipun isinya stale (build lama).

**Solusi:** Selalu pastikan `public/build/` di host adalah hasil build terbaru
sebelum rebuild image:

```bash
# Di host atau dev container — pastikan build fresh
bun run build    # atau: docker exec smauii-opresent-app-dev bun run build

# Lalu rebuild production
docker compose -f docker/docker-compose.yml up -d --build
```

Alternatif: tambahkan `rm -rf public/build` di Dockerfile sebelum `COPY` agar
entrypoint selalu rebuild, atau gunakan `.dockerignore` untuk exclude
`public/build/` dari COPY (dan biarkan entrypoint yang build).

### 10.6 Monitoring

```bash
# Logs
docker compose -f docker/docker-compose.yml logs -f opresent-app

# Masuk ke container
docker exec -it smauii-opresent-app bash

# Cek status
ls public/build/              # Pastikan aset ter-build
cat public/build/.vite/manifest.json  # Cek manifest
curl -s localhost/login | grep assets  # Cek URL aset
```

---

## 11. SDLC: Build, Test, Lint

### 11.1 Frontend (TypeScript)

```bash
# Semua perintah dijalankan di dalam container
docker exec -it smauii-opresent-app-dev bash

# Install dependencies
bun install

# Type checking
bun run typecheck          # tsc --noEmit

# Linting
bun run lint               # eslint . && biome lint .

# Build
bun run build              # vite build → public/build/

# Testing
bun run test               # vitest run (76 tests)
bun run test:watch         # vitest (watch mode)
bun run test:coverage      # vitest run --coverage

# Formatting
bun run format             # biome format --write .
bun run check              # biome check --write .
```

### 11.2 Backend (PHP)

```bash
# Jalankan di dalam container
docker exec -it smauii-opresent-app-dev bash

# PHPUnit
vendor/bin/phpunit
```

### 11.3 Pre-commit Checks

```bash
# Full pipeline
bun run typecheck && bun run lint && bun run build && bun run test
```

---

## 12. Daftar File Kunci

### Aset Pipeline

| File | Fungsi | Siapa yang Memodifikasi |
|------|--------|------------------------|
| `vite.config.ts` | Konfigurasi build Vite | Developer |
| `resources/ts/main.ts` | Entry point JS | Developer |
| `resources/css/main.css` | Entry point CSS (Tailwind) | Developer |
| `public/build/.vite/manifest.json` | Mapping source → output | Vite (otomatis) |
| `public/build/assets/main-*.js` | Compiled JS bundle | Vite (otomatis) |
| `public/build/assets/main-*.css` | Compiled CSS bundle | Vite (otomatis) |

### PHP Integration

| File | Fungsi | Siapa yang Memodifikasi |
|------|--------|------------------------|
| `app/Controllers/BaseController.php:55-105` | `vite()` + `vite_css()` helpers | Developer |
| `app/Debug/TwigExceptionHandler.php:67-117` | Same helpers untuk error pages | Developer |
| `app/Views/layouts/base.twig:9,11` | Template yang menggunakan helpers | Developer |
| `public/.htaccess:4-7` | Apache rewrite `/assets/*` → `build/assets/*` | Developer |

### Docker

| File | Fungsi | Siapa yang Memodifikasi |
|------|--------|------------------------|
| `docker/Dockerfile` | Build image | Developer/DevOps |
| `docker/docker-entrypoint.sh` | Auto-build saat startup | Developer/DevOps |
| `docker/docker-compose.yml` | Production stack | Developer/DevOps |
| `docker/docker-compose.dev.yml` | Development stack | Developer/DevOps |

### Environment

| File | Fungsi | Di-commit? |
|------|--------|-----------|
| `.env.dev` | Development config | Ya (tanpa secrets) |
| `.env.production` | Production config | TIDAK (secrets) |
| `.env.example` | Template | Ya |
| `.gitignore` | Exclusion rules | Ya |

---

## 13. Troubleshooting

### 13.1 Aset tidak muncul (404 pada /assets/*)

```bash
# 1. Cek apakah file ada di filesystem
ls public/build/assets/main-*.js

# 2. Cek manifest
cat public/build/.vite/manifest.json

# 3. Cek .htaccess
cat public/.htaccess
# Pastikan ada RewriteRule untuk /assets/*

# 4. Cek Apache mod_rewrite
apache2ctl -M | grep rewrite
# Harus ada: rewrite_module

# 5. Build ulang
bun run build
```

### 13.2 CSS tidak dimuat (halaman tanpa styling)

```bash
# 1. Cek apakah vite_css() mengembalikan HTML
#    Lihat source HTML: harus ada <link href="/assets/main-<hash>.css">

# 2. Jika kosong, cek manifest
cat public/build/.vite/manifest.json
# Pastikan ada key "css": ["assets/main-<hash>.css"]

# 3. Hapus Twig cache (production)
rm -rf writable/twig/*

# 4. Cek apakah base.twig memiliki {{ vite_css('main.ts')|raw }}
```

### 13.3 Error page tidak styled (tanpa Tailwind)

```bash
# 1. Pastikan TwigExceptionHandler aktif
#    app/Config/Exceptions.php → handler() mengembalikan TwigExceptionHandler

# 2. Pastikan error templates ada
ls app/Views/errors/404.twig
ls app/Views/errors/500.twig

# 3. Hapus Twig cache
rm -rf writable/twig/*

# 4. Cek Apache logs
docker exec -it smauii-opresent-app cat /var/log/apache2/error.log
```

### 13.4 Dev container tidak start

```bash
# 1. Cek logs
docker compose -f docker/docker-compose.dev.yml logs opresent-app-dev

# 2. Cek apakah database ready
docker compose -f docker/docker-compose.dev.yml ps

# 3. Rebuild
docker compose -f docker/docker-compose.dev.yml up -d --build
```

### 13.5 Twig template tidak update di production (template lama masih ditampilkan)

**Gejala:** File template sudah benar di host dan di container, tapi rendered HTML
masih menampilkan versi lama.

**Penyebab:** Twig compiled cache. Di production, `$cache = (ENVIRONMENT === 'production')`
di `app/Config/Twig.php` mengaktifkan caching. Twig mengompilasi template `.twig`
menjadi file PHP di `writable/twig/` — dan terus menggunakan versi yang sudah
di-cache sampai dihapus.

**PENTING:** Twig cache ada di `writable/twig/`, **BUKAN** `writable/cache/`.

```bash
# Salah (tidak menghapus Twig cache):
rm -rf writable/cache/*

# Benar:
rm -rf writable/twig/*
```

**Debugging:**

```bash
# 1. Cek apakah template yang sudah di-cache masih ada
find /var/www/html/writable/twig -name "*.php" | head -5

# 2. Cek apakah cached version memiliki class baru
grep -l "hidden md:flex" /var/www/html/writable/twig/*/*.php
# Jika tidak ditemukan → cache masih versi lama!

# 3. Clear Twig cache
rm -rf /var/www/html/writable/twig/*

# 4. Verify
curl -s http://localhost/<route> | grep "class-baru"
```

**Prevention:** Selalu clear `writable/twig/*` saat deploy perubahan template ke
production, baik melalui entrypoint script, CI/CD, maupun manual.

### 13.6 Production build menggunakan aset lama (stale assets)

**Gejala:** CSS/JS berubah di source, tapi production masih menampilkan versi lama.

**Penyebab:** Dua kemungkinan:
1. `public/build/` di host sudah ada (build lama) → entrypoint skip `bun run build`
2. Hanya `docker cp` file ke container tanpa rebuild image

**Solusi:**

```bash
# 1. Pastikan host memiliki build terbaru
docker exec smauii-opresent-app-dev bun run build

# 2. Rebuild production image (COPY akan mengambil build terbaru dari host)
docker compose -f docker/docker-compose.yml up -d --build

# 3. Clear Twig cache di container baru
docker exec smauii-opresent-app rm -rf writable/twig/*

# 4. Verifikasi
docker exec smauii-opresent-app cat public/build/.vite/manifest.json
```

### 13.7 Production build gagal

```bash
# 1. Masuk ke container
docker exec -it smauii-opresent-app bash

# 2. Build manual
cd /var/www/html
bun install
bun run build

# 3. Cek error
# Jika "out of memory":
bun run build --minify=false  # Sementara disable minify
```

---

## Ringkasan Prinsip

1. **Source bersih** — `public/build/` di-gitignore, tidak pernah di-commit
2. **URL bersih** — `/assets/...` bukan `/build/assets/...`
3. **Build di container** — entrypoint auto-build saat startup
4. **Satu konvensi** — dev dan prod menggunakan URL yang sama (`/assets/...`)
5. **Manifest sebagai source of truth** — PHP helper selalu membaca `manifest.json`
6. **Twig untuk semua** — termasuk error pages (404, 500)
7. **Environment terisolasi** — dev dan prod memiliki DB, S3, dan config terpisah
8. **Immutable production** — COPY source ke image, tidak bind mount
9. **Template cache = `writable/twig/`** — bukan `writable/cache/`, clear saat deploy template
10. **Production = rebuild image** — `docker cp`/`exec sed` tidak persisten, harus rebuild
