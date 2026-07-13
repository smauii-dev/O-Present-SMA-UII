# Docker Deployment — O-Present SMA UII

## Overview

Aplikasi di-deploy sebagai Docker container yang join ke `nginx-net` network. Tidak ada DB container — database external di NeonDB.

## Architecture

```
┌─────────────────────────────────────┐
│  nginx-net (Docker network)         │
│                                     │
│  ┌──────────────┐                   │
│  │ nginx-proxy  │ ← ports 80/443   │
│  └──────┬───────┘                   │
│         │                           │
│  ┌──────▼───────────┐               │
│  │ smauii-opresent  │ ← port 80    │
│  │ -app             │               │
│  └──────────────────┘               │
│                                     │
└─────────────────────────────────────┘
         │
         │ SSL (port 5432)
         ▼
┌─────────────────────────────────────┐
│  NeonDB (AWS us-east-1)            │
│  ep-snowy-dawn-atm5m66c            │
│  Database: neondb                   │
└─────────────────────────────────────┘
```

## Naming Convention

Following Awankinton by Koneksi Cloud pattern:

| Resource | Name |
|----------|------|
| Container | `smauii-opresent-app` |
| Image | `smauii-opresent:latest` |
| Compose project | `opresent` (auto from dir name) |
| Volumes | `opresent_uploads`, `opresent_photos_masuk`, etc. |
| Nginx config | `/home/dev/web/infrastructure/nginx/conf.d/smauii/presensi.conf` |

## Dockerfile

```dockerfile
FROM php:8.2-apache

# Install PostgreSQL extension & dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_pgsql pgsql zip \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP settings
RUN sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' /etc/php/8.2/apache2/php.ini \
    && sed -i 's/post_max_size = .*/post_max_size = 25M/' /etc/php/8.2/apache2/php.ini \
    && sed -i 's/max_execution_time = .*/max_execution_time = 120/' /etc/php/8.2/apache2/php.ini \
    && sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.2/apache2/php.ini

# Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy application code
COPY . /var/www/html/

# Install composer dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && rm -rf /var/www/html/.git

# Permissions for CI4 writable directories
RUN chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable

EXPOSE 80
```

## docker-compose.yml

```yaml
services:
  opresent-app:
    build:
      context: .
      dockerfile: Dockerfile
    image: smauii-opresent:latest
    container_name: smauii-opresent-app
    environment:
      TZ: Asia/Jakarta
    env_file:
      - .env
    networks:
      - nginx-net
    restart: always
    volumes:
      - opresent_uploads:/var/www/html/writable/uploads
      - opresent_photos_masuk:/var/www/html/public/assets/img/foto_presensi/masuk
      - opresent_photos_keluar:/var/www/html/public/assets/img/foto_presensi/keluar
      - opresent_files:/var/www/html/public/assets/file/surat_keterangan_ketidakhadiran

volumes:
  opresent_uploads:
    external: true
  opresent_photos_masuk:
    external: true
  opresent_photos_keluar:
    external: true
  opresent_files:
    external: true

networks:
  nginx-net:
    external: true
```

## Environment Variables (`.env`)

```env
CI_ENVIRONMENT=production

app.baseURL = 'https://presensi.smauiiyk.sch.id/'

database.default.hostname = ep-snowy-dawn-atm5m66c.c-9.us-east-1.aws.neon.tech
database.default.database = neondb
database.default.username = neondb_owner
database.default.password = npg_f7FUXv2ocBnY
database.default.DBDriver = Postgre
database.default.port = 5432
```

## Docker Volumes

```bash
docker volume create opresent_uploads
docker volume create opresent_photos_masuk
docker volume create opresent_photos_keluar
docker volume create opresent_files
```

## Commands

```bash
# Build & start
docker compose up -d --build

# View logs
docker logs -f smauii-opresent-app

# Run migrations
docker exec smauii-opresent-app php spark migrate

# Run seeds
docker exec smauii-opresent-app php spark db:seed All

# Restart
docker compose restart

# Stop
docker compose down

# Rebuild
docker compose up -d --build --force-recreate
```

## File Permissions

| Path | Owner | Permissions |
|------|-------|------------|
| `/var/www/html/writable/` | www-data:www-data | 775 |
| `/var/www/html/writable/cache/` | www-data:www-data | 775 |
| `/var/www/html/writable/logs/` | www-data:www-data | 775 |
| `/var/www/html/writable/session/` | www-data:www-data | 775 |
| `/var/www/html/public/assets/img/` | www-data:www-data | 775 |
| `/var/www/html/public/assets/file/` | www-data:www-data | 775 |
