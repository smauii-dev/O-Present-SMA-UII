# Deployment Steps — O-Present SMA UII

> **Checklist ini harus diikuti secara berurutan.**
> **Dikerjakan oleh:** smauii-dev (it@smauiiyk.sch.id)

---

## Phase 1: Codebase Preparation

### Step 1.1 — Buat `.env` file

```bash
# Di direktori project
cat > .env << 'EOF'
CI_ENVIRONMENT=production

app.baseURL = 'https://presensi.smauiiyk.sch.id/'

database.default.hostname = ep-snowy-dawn-atm5m66c.c-9.us-east-1.aws.neon.tech
database.default.database = neondb
database.default.username = neondb_owner
database.default.password = npg_f7FUXv2ocBnY
database.default.DBDriver = Postgre
database.default.port = 5432
EOF
```

### Step 1.2 — Edit `app/Config/Database.php`

Ubah hardcoded credentials ke env-based, ganti driver ke PostgreSQL.

### Step 1.3 — Fix Models (MySQL → PostgreSQL)

| File | Fix |
|------|-----|
| `PresensiModel.php` | `DATE_FORMAT` → `TO_CHAR`, `YEAR` → `EXTRACT` |
| `KetidakhadiranModel.php` | `DATE_FORMAT` → `TO_CHAR`, `YEAR` → `EXTRACT`, `like` → `ilike` |
| `PegawaiModel.php` | `like` → `ilike` |
| `JabatanModel.php` | `like` → `ilike` |
| `LokasiPresensiModel.php` | `like` → `ilike` |

### Step 1.4 — Fix `public/index.php`

Line 34: Fix path reference yang salah.

### Step 1.5 — Edit `app/Config/Email.php`

SMTP credentials ke env-based.

### Step 1.6 — Buat `.env.example`

Template untuk developer lain.

---

## Phase 2: Docker Setup

### Step 2.1 — Buat `Dockerfile`

PHP 8.2 + Apache + pdo_pgsql extension.

### Step 2.2 — Buat `docker-compose.yml`

Service `opresent-app` join `nginx-net`, env_file `.env`.

### Step 2.3 — Create Docker Volumes

```bash
docker volume create opresent_uploads
docker volume create opresent_photos_masuk
docker volume create opresent_photos_keluar
docker volume create opresent_files
```

### Step 2.4 — Build & Test Locally

```bash
docker compose up -d --build
docker logs -f smauii-opresent-app
```

---

## Phase 3: Database Setup (NeonDB)

### Step 3.1 — Run Migrations

```bash
docker exec smauii-opresent-app php spark migrate
```

### Step 3.2 — Run Seeds

```bash
docker exec smauii-opresent-app php spark db:seed All
```

### Step 3.3 — Verify Tables

```bash
docker exec smauii-opresent-app php spark db:table jabatan
docker exec smauii-opresent-app php spark db:table users
```

---

## Phase 4: Nginx & SSL

### Step 4.1 — Generate SSL Certificate

```bash
certbot certonly --dns-cloudflare \
  -d presensi.smauiiyk.sch.id \
  --email admin@smauiiyk.sch.id \
  --agree-tos
```

### Step 4.2 — Buat Nginx Config

Buat `presensi.conf` di `/home/dev/web/infrastructure/nginx/conf.d/smauii/`.

### Step 4.3 — Reload Nginx

```bash
docker exec nginx-proxy nginx -t && docker exec nginx-proxy nginx -s reload
```

### Step 4.4 — Update `instance.yaml`

Tambah entry `opresent` service.

---

## Phase 5: Verification

### Step 5.1 — Test HTTP → HTTPS Redirect

```bash
curl -I http://presensi.smauiiyk.sch.id
# Should return 301 to HTTPS
```

### Step 5.2 — Test HTTPS Access

```bash
curl -I https://presensi.smauiiyk.sch.id
# Should return 200 with security headers
```

### Step 5.3 — Test Login Page

Buka `https://presensi.smauiiyk.sch.id` di browser.

### Step 5.4 — Test Database Connection

Login dengan credentials yang di-seed.

### Step 5.5 — Test Presensi Feature

Coba submit presensi dengan foto.

---

## Rollback Plan

Jika ada masalah:

```bash
# Stop O-Present
cd /home/dev/web/instances/smauii/services/O-Present-SMA-UII
docker compose down

# Disable nginx config
mv /home/dev/web/infrastructure/nginx/conf.d/smauii/presensi.conf \
   /home/dev/web/infrastructure/nginx/conf.d/smauii/presensi.conf.bak
docker exec nginx-proxy nginx -s reload

# Revert to MySQL (if needed)
# Edit Database.php back to MySQLi driver
# Re-point to original MySQL database
```

---

## Post-Deployment

- [ ] Update `instance.yaml` status to `running`
- [ ] Test all features (presensi, cuti, profile, export)
- [ ] Monitor logs for errors: `docker logs -f smauii-opresent-app`
- [ ] Verify SSL auto-renewal works
- [ ] Document any additional notes in this file
