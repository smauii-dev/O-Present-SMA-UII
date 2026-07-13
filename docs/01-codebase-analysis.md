# Codebase Analysis — O-Present SMA UII

## Current State

Codebase ini adalah aplikasi presensi berbasis GPS dengan selfie, dibangun dengan CodeIgniter 4 + MySQL (MariaDB 10.11.18). Belum ada Docker/deployment config — sebelumnya di-deploy secara tradisional dengan Apache/XAMPP.

## Directory Structure

```
O-Present-SMA-UII/
├── app/
│   ├── Config/           # 38 config files (App, Database, Email, Routes, etc.)
│   ├── Controllers/      # 9 controllers
│   ├── Database/
│   │   ├── Migrations/   # 2 migration files (15 tables total)
│   │   └── Seeds/        # 8 seed files
│   ├── Models/           # 9 model files
│   ├── Libraries/        # Custom validation
│   └── Views/            # Admin, auth, presensi, etc.
├── public/
│   ├── .htaccess         # Apache mod_rewrite
│   ├── index.php         # Front controller (path issue!)
│   └── assets/           # CSS, JS, images, uploads
├── writable/             # CI4 cache, logs, session, uploads
├── sma35ui1_presensi_sma_uii.sql  # Full MySQL dump
├── composer.json
├── Dockerfile            # (to be created)
└── docker-compose.yml    # (to be created)
```

## Database Schema (15 Tables)

### Core Tables (Migration 1)

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `jabatan` | Job positions | id, jabatan, slug, timestamps, deleted_at |
| `lokasi_presensi` | Attendance locations | id, nama_lokasi, lat/long, radius, jam_masuk/pulang |
| `pegawai` | Employees | id, nip (unique), id_jabatan (FK), id_lokasi_presensi (FK) |
| `presensi` | Attendance records | id, id_pegawai (FK), tanggal_masuk/keluar, foto |
| `ketidakhadiran` | Leave requests | id, id_pegawai (FK), tipe, tanggal, status |
| `email_tokens` | Email verification | id, email (unique), token |

### Auth Tables (Migration 2 — Myth/Auth)

| Table | Purpose |
|-------|---------|
| `users` | User accounts (linked to pegawai via id_pegawai FK) |
| `auth_logins` | Login attempts |
| `auth_tokens` | Remember-me tokens |
| `auth_reset_attempts` | Password reset attempts |
| `auth_activation_attempts` | Account activation attempts |
| `auth_groups` | Roles: head, admin, pegawai |
| `auth_permissions` | Permissions: kelola_data, isi_presensi, kelola_pengajuan_cuti |
| `auth_groups_permissions` | Role→Permission mapping |
| `auth_groups_users` | User→Role mapping |
| `auth_users_permissions` | User→Permission mapping |

## Foreign Key Relationships

```
pegawai.id_jabatan        → jabatan.id (CASCADE)
pegawai.id_lokasi_presensi → lokasi_presensi.id (CASCADE)
presensi.id_pegawai        → pegawai.id (CASCADE)
ketidakhadiran.id_pegawai  → pegawai.id (CASCADE)
users.id_pegawai           → pegawai.id (CASCADE)
auth_tokens.user_id        → users.id (CASCADE)
auth_groups_*              → auth_groups.id / users.id (CASCADE)
auth_users_permissions     → users.id / auth_permissions.id (CASCADE)
```

## Features

- GPS-based attendance with selfie photo capture
- Location radius validation (500m default)
- Leave/absence management (cuti/izin/sakit)
- Excel export of attendance reports
- Profile management
- Email-based password reset and account activation
- Role-based access control (head, admin, pegawai)

## Security Concerns (Current)

1. **Database credentials hardcoded** in `app/Config/Database.php`
2. **SMTP password hardcoded** in `app/Config/Email.php`
3. **No `.env` file** — all config hardcoded in PHP files
4. **`public/index.php` has wrong path** — line 34 references `../../o-present/app/Config/Paths.php`
5. **Vendor modifications** to Myth/Auth files (AuthController, RoleFilter, Auth.php)
