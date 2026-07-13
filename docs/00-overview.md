# O-Present SMA UII — Deployment Overview

> **Project:** O-Present (Sistem Presensi SMA UII Yogyakarta)
> **Host:** Awankinton by Koneksi Cloud (konxc-services)
> **Domain:** `presensi.smauiiyk.sch.id`
> **Database:** NeonDB (PostgreSQL)
> **Last Updated:** 2026-07-13

---

## Architecture

```
Internet (HTTPS)
  │
  ▼
nginx-proxy (Docker, port 80/443)
  │ SSL termination — Let's Encrypt
  │
  └── presensi.smauiiyk.sch.id → smauii-opresent-app:80
                                  │
                                  └── NeonDB (external PostgreSQL on AWS)
                                      ep-snowy-dawn-atm5m66c.c-9.us-east-1.aws.neon.tech
```

## Tech Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Language | PHP | ^8.0 |
| Framework | CodeIgniter 4 | ^4.0 |
| Auth | Myth/Auth | ^1.2 |
| Spreadsheet | PhpOffice/PhpSpreadsheet | ^1.29 |
| Database | PostgreSQL (NeonDB) | 15+ |
| Web Server | Apache (in Docker) | 2.4 |
| CSS | Tabler.io (Bootstrap 5) | - |
| JS | jQuery 3.7.0 | - |
| Container | Docker + Docker Compose | - |
| Reverse Proxy | nginx (Docker) | stable-alpine |
| SSL | Let's Encrypt (Certbot) | - |
| Runtime | Debian 12 (bookworm) | - |

## Key Files

| File | Purpose |
|------|---------|
| `app/Config/Database.php` | Database configuration (env-based) |
| `app/Config/Email.php` | SMTP email configuration |
| `app/Models/*.php` | Business logic models |
| `app/Database/Migrations/` | Database schema definitions |
| `app/Database/Seeds/` | Initial data seeding |
| `.env` | Environment variables (secrets) |
| `Dockerfile` | Container image build |
| `docker-compose.yml` | Service orchestration |
| `docs/*.md` | This documentation |

## Related Infrastructure

| Resource | Path/URL |
|----------|----------|
| Host | `konxc-services` (202.162.40.161) |
| Nginx config | `/home/dev/web/infrastructure/nginx/conf.d/smauii/` |
| Instance config | `/home/dev/web/instances/smauii/instance.yaml` |
| Docker network | `nginx-net` (shared with all public services) |
| SSL certs | `/etc/letsencrypt/live/presensi.smauiiyk.sch.id/` |

## Team

| Role | Identity | GitHub |
|------|----------|--------|
| Operator | sandikodev | github.com/sandikodev |
| IT/Developer | smauii-dev | github.com/smauii-dev |
| Head IT | rosyiii (Ahmad Hanif) | github.com/rosyiii |

## Document Index

| Doc | Description |
|-----|-------------|
| [01-codebase-analysis](./01-codebase-analysis.md) | Current codebase state & MySQL usage |
| [02-mysql-to-postgresql](./02-mysql-to-postgresql.md) | Migration guide: MySQL → PostgreSQL |
| [03-docker-deployment](./03-docker-deployment.md) | Docker & Docker Compose setup |
| [04-nginx-ssl](./04-nginx-ssl.md) | Nginx reverse proxy & SSL config |
| [05-deployment-steps](./05-deployment-steps.md) | Step-by-step execution checklist |
