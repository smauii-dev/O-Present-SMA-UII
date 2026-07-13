# MySQL → PostgreSQL Migration Guide

## Overview

Migrasi dari MySQL (MariaDB 10.11.18) ke PostgreSQL (NeonDB). CodeIgniter 4 mendukung PostgreSQL secara native via driver `Postgre`.

## NeonDB Connection

```
Host:     ep-snowy-dawn-atm5m66c.c-9.us-east-1.aws.neon.tech
Port:     5432
Database: neondb
Username: neondb_owner
SSL:      require
```

## Changes Required

### 1. Database Configuration (`app/Config/Database.php`)

**Perubahan:**
- `DBDriver`: `MySQLi` → `Postgre`
- Semua credentials diambil dari environment variables
- Tambah DSN untuk SSL connection
- Port: `3306` → `5432`
- Hapus `DBCollat` (PostgreSQL tidak pakai collation MySQL)
- Hapus `strictOn` (MySQL-specific)

### 2. MySQL-Specific SQL Functions

#### `DATE_FORMAT()` → `TO_CHAR()`

**Before (MySQL):**
```php
->where('DATE_FORMAT(presensi.tanggal_masuk, "%Y-%m")', $bulan)
```

**After (PostgreSQL):**
```php
->where('TO_CHAR(presensi.tanggal_masuk, \'YYYY-MM\')', $bulan)
```

**Affected files:**
- `app/Models/PresensiModel.php` — 2 occurrences (lines 133, 135)
- `app/Models/KetidakhadiranModel.php` — 4 occurrences (lines 76, 77, 83, 84)

#### `YEAR()` → `EXTRACT(YEAR FROM ...)`

**Before (MySQL):**
```php
$builder->selectMin('YEAR(tanggal_masuk)', 'min_year');
```

**After (PostgreSQL):**
```php
$builder->selectMin('EXTRACT(YEAR FROM tanggal_masuk)', 'min_year');
```

**Affected files:**
- `app/Models/PresensiModel.php` — 1 occurrence (line 159)
- `app/Models/KetidakhadiranModel.php` — 1 occurrence (line 150)

### 3. `LIKE` Case Sensitivity

MySQL `LIKE` dengan collation `utf8_general_ci` case-insensitive. PostgreSQL `LIKE` case-sensitive.

**Before:**
```php
->like('nama', $keyword)
```

**After:**
```php
->ilike('nama', $keyword)
```

**Affected files:**
- `app/Models/PegawaiModel.php` — 3 occurrences
- `app/Models/JabatanModel.php` — 2 occurrences
- `app/Models/LokasiPresensiModel.php` — 2 occurrences
- `app/Models/KetidakhadiranModel.php` — 3 occurrences

### 4. Migration Files

CI4 Forge abstraction handle perbedaan MySQL/PostgreSQL:
- `unsigned => true` → PostgreSQL ignore (CI4 handle)
- `tinyint` → `smallint` (CI4 handle)
- `auto_increment` → sequences (CI4 handle)
- `int(11)` constraint → display width, ignore di PostgreSQL

**Tidak perlu edit migration files.**

### 5. Data Migration

**TIDAK pakai SQL dump** — `sma35ui1_presensi_sma_uii.sql` format MySQL, tidak bisa langsung import ke PostgreSQL.

**Solusi:** Jalankan CI4 migrations + seeds di NeonDB:
```bash
php spark migrate
php spark db:seed All
```

## Files to Edit

| File | Changes |
|------|---------|
| `app/Config/Database.php` | Driver, env-based config, DSN for SSL |
| `app/Config/Email.php` | Env-based SMTP credentials |
| `app/Models/PresensiModel.php` | DATE_FORMAT → TO_CHAR, YEAR → EXTRACT |
| `app/Models/KetidakhadiranModel.php` | DATE_FORMAT → TO_CHAR, YEAR → EXTRACT, like → ilike |
| `app/Models/PegawaiModel.php` | like → ilike |
| `app/Models/JabatanModel.php` | like → ilike |
| `app/Models/LokasiPresensiModel.php` | like → ilike |
| `public/index.php` | Fix path reference (line 34) |

## Compatibility Notes

| Feature | MySQL | PostgreSQL | Action |
|---------|-------|------------|--------|
| `insertID()` | LAST_INSERT_ID() | currval/RETURNING | CI4 handles |
| `getNumRows()` | metadata count | row count | Works, minor perf diff |
| Transactions | InnoDB | SAVEPOINT | CI4 handles |
| `GROUP BY` strictness | Relaxed | SQL standard strict | Verify queries |
| UTF-8 | utf8mb3/utf8mb4 | UTF-8 native | CI4 handles |
