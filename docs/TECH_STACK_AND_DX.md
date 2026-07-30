# Tech Stack & Developer Experience (DX) — O-Present SMA UII

**Project:** O-Present SMA UII  
**Framework:** CodeIgniter 4.7.4  
**Last Updated:** 26 Juli 2026  
**Audience:** Ahmad Hanif (Full-stack Developer)

---

## 1. Backend Stack

### Core Framework
| Component | Version | Purpose |
|-----------|---------|---------|
| **CodeIgniter 4** | 4.7.4 | MVC framework, routing, DI container, CLI spark commands |
| **PHP** | 8.2.32 | Runtime (Docker: `php:8.2-apache`) |
| **Composer** | 2.x | Dependency management |

### Authentication & Authorization
| Package | Version | Purpose |
|---------|---------|---------|
| **Myth/Auth** | ^1.3 | Authentication, authorization, user management, login throttling, remember-me, password reset, groups/permissions |

### Database
| Component | Version | Purpose |
|-----------|---------|---------|
| **PostgreSQL** | 16 (NeonDB serverless) | Production database |
| **PostgreSQL** | 16 (local Docker) | Development database |
| **pdo_pgsql / pgsql** | PHP ext | PostgreSQL driver |
| **CodeIgniter Database** | Built-in | Query builder, migrations, seeds |

### Data Layer Patterns
| Pattern | Implementation |
|---------|----------------|
| **Active Record / Query Builder** | `$db->table('users')->get()` |
| **Models** | `App\Models\*Model` extends `CodeIgniter\Model` |
| **Entities** | `App\Entities\*` (optional, lightly used) |
| **Migrations** | `app/Database/Migrations/*.php` (timestamped) |
| **Seeds** | `app/Database/Seeds/*.php` |

### Session & Cache
| Driver | Config | Purpose |
|--------|--------|---------|
| **FileHandler** | `app.sessionDriver = FileHandler` | **Production (current fix)** — local filesystem, sub-ms latency |
| **DatabaseHandler** | `ci_sessions` table | **Previous (caused 3s TTFB)** — remote NeonDB round-trip |
| **FileHandler** | `writable/cache/` | Cache (default) |

### Email
| Service | Config |
|---------|--------|
| **SMTP (Gmail)** | `smtp.gmail.com:465 SSL` |
| **CodeIgniter Email** | Built-in library |

### File Storage / S3
| Component | Purpose |
|-----------|---------|
| **RustFS (MinIO-compatible)** | S3-compatible object storage for photos, uploads |
| **AWS SDK PHP** | `aws/aws-sdk-php` via `App\Services\S3Service` |
| **Buckets** | `opresent` (production), `opresent-dev` (dev) |

---

## 2. Frontend Stack

### Build Tool & Runtime
| Tool | Version | Purpose |
|------|---------|---------|
| **Vite** | 6.3.5 | Dev server (HMR), production build |
| **Bun** | 1.3.x | Package manager + runtime (replaces npm/node) |

### CSS Framework
| Tool | Version | Purpose |
|------|---------|---------|
| **Tailwind CSS** | 4.1.7 | Utility-first CSS (JIT compiler via Vite plugin) |
| **@tailwindcss/vite** | 4.1.7 | Vite integration for Tailwind v4 |

### JavaScript / TypeScript
| Tool | Version | Purpose |
|------|---------|---------|
| **TypeScript** | 5.7.0 | Type safety |
| **ESLint** | 10.8.0 | Linting |
| **@typescript-eslint** | 8.65.0 | TS-aware linting |
| **Biome** | 2.5.5 | Fast formatter + linter (replaces Prettier + partial ESLint) |

### Template Engine
| Engine | Version | Purpose |
|--------|---------|---------|
| **Twig** | 3.x (via `nongbit/twig`) | Server-side rendering, template inheritance, macros |

### Frontend Architecture
```
resources/
├── css/
│   └── app.css              # Tailwind imports (@import "tailwindcss")
├── js/
│   ├── app.ts               # Entry point
│   ├── components/          # Vanilla JS components (alpine-like)
│   ├── utils/               # Helpers (api, date, dom)
│   └── pages/               # Page-specific logic
└── views/                   # Twig templates (synced to app/Views via build?)
```

**Asset Pipeline:**
1. Vite compiles `resources/` → `public/build/`
2. Manifest JSON (`public/build/.vite/manifest.json`) maps source → hashed output
3. Twig `vite()` helper reads manifest for correct asset URLs

---

## 3. Infrastructure & Deployment

### Containerization
| Component | Image | Purpose |
|-----------|-------|---------|
| **App (Production)** | `php:8.2-apache` + custom Dockerfile | Apache + PHP-FPM (mod_php) |
| **App (Dev)** | Same image | `php spark serve --port=8080` for HMR proxy |
| **Database (Prod)** | NeonDB (managed) | Serverless PostgreSQL |
| **Database (Dev)** | `postgres:16` | Local container |
| **S3 (Prod)** | `rustfs/rustfs:latest` | `smauii-rustfs` container |
| **S3 (Dev)** | `rustfs/rustfs:latest` | `smauii-rustfs-dev` container |
| **Reverse Proxy** | `nginx:alpine` | `nginx-proxy` (shared, host ports 80/443) |

### Networking
| Network | Members | Purpose |
|---------|---------|---------|
| `nginx-net` (external) | nginx-proxy, smauii-opresent-app, smauii-rustfs, moodle, slims, etc. | Shared reverse proxy |
| `smauii-dev-net` (internal) | smauii-opresent-app-dev, smauii-opresent-db-dev, smauii-rustfs-dev | Dev isolation |

### Naming Convention
```
Prefix: smauii-opresent-*
├── smauii-opresent-app        (production app)
├── smauii-opresent-db         (production DB - not used, NeonDB instead)
├── smauii-rustfs              (production S3)
├── smauii-opresent-app-dev    (dev app)
├── smauii-opresent-db-dev     (dev DB)
├── smauii-rustfs-dev          (dev S3)
```

### SSL / Certificates
- **Certbot / Let's Encrypt** managed by nginx-proxy
- Certificate: `presensi.smauiiyk.sch.id` (valid)
- **No wildcard** for `*.smauiiyk.sch.id` yet
- Dev env: HTTP only (no SSL cert for `dev-presensi`)

---

## 4. Configuration Management

### Environment Files
| File | Purpose |
|------|---------|
| `.env.example` | Template (committed) |
| `.env.production` | Production values (mounted as `/var/www/html/.env:ro`) |
| `.env.dev` | Development values (mounted in dev container) |

### Key Configuration Patterns
```php
// All config uses env() helper for environment-based values
public string $driver = env('app.sessionDriver', FileHandler::class);
public string $savePath = env('app.sessionSavePath', WRITEPATH . 'session');
```

### Security
- **No hardcoded credentials** in committed code (since f1610f9)
- **`.env.production`** never committed (in `.gitignore`)
- **Database credentials** via NeonDB connection string
- **Email SMTP** via env vars
- **S3 credentials** via env vars

---

## 5. Developer Experience (DX) Tooling

### Code Quality
| Tool | Command | Purpose |
|------|---------|---------|
| **ESLint** | `bun run lint` | JS/TS linting |
| **Biome** | `bun run check` | Format + lint (fast, Rust-based) |
| **TypeScript** | `bun run typecheck` | Type checking |
| **PHP CS Fixer** | `vendor/bin/php-cs-fixer fix` | PHP code style |

### Development Commands
```bash
# Install deps
bun install

# Dev server (Vite HMR + spark serve)
bun run dev          # Runs: concurrently "bun run dev:vite" "docker exec ... spark serve"

# Build for production
bun run build        # vite build

# Type check
bun run typecheck    # tsc --noEmit

# Format code
bun run format       # biome format --write .

# PHP code style
vendor/bin/php-cs-fixer fix
```

### Database Commands
```bash
# Migration
php spark migrate           # Run pending
php spark migrate:status    # Check status
php spark migrate:rollback  # Rollback last batch

# Seeding
php spark db:seed ClassName
php spark db:seed           # Runs DatabaseSeeder (if exists)

# Dev DB setup
bun run dev:setup            # migrate + seed (dev container)
```

### Docker Commands
```bash
# Production
docker compose -f docker/docker-compose.yml up -d --build
docker compose -f docker/docker-compose.yml down

# Development
docker compose -f docker/docker-compose.dev.yml up -d --build
docker compose -f docker/docker-compose.dev.yml down -v

# Logs
docker logs -f smauii-opresent-app
docker logs -f nginx-proxy
```

### Debugging
```bash
# App container shell
docker exec -it smauii-opresent-app bash

# Run spark commands in container
docker exec smauii-opresent-app php spark migrate

# Check logs
docker exec smauii-opresent-app tail -f writable/logs/log-$(date +%Y-%m-%d).log

# Nginx config test
docker exec nginx-proxy nginx -t
```

---

## 6. Architecture Decisions & History

### Session Driver Evolution
| Commit | Date | Driver | Rationale |
|--------|------|--------|-----------|
| **Before f1610f9** | — | `FileHandler` | Fast, local, but sessions lost on container restart |
| **f1610f9** | Jul 13, 2026 | `DatabaseHandler` | "Sessions persist in PostgreSQL (ci_sessions table)" — by `smauii-dev` |
| **Current (HEAD)** | Jul 26, 2026 | **`FileHandler`** | **FIXED** — Remote NeonDB latency (us-east-1) caused 3s TTFB |

**Root Cause of 3s TTFB:**
- Every request → `session()->start()` → `SELECT` + `UPDATE` on `ci_sessions`
- NeonDB in **us-east-1 (Virginia)** → ~100-150ms RTT per query from Indonesia
- 3-5 session queries/request → **300-750ms minimum**, plus TLS overhead = **3s TTFB**

**Fix Applied (Jul 26, 2026):**
```diff
# .env.production
- app.sessionDriver = CodeIgniter\Session\Handlers\DatabaseHandler
- app.sessionSavePath = ci_sessions
+ app.sessionDriver = CodeIgniter\Session\Handlers\FileHandler
+ app.sessionSavePath = /var/www/html/writable/session

# app/Config/Session.php
- public string $driver = DatabaseHandler::class;
- public string $savePath = 'ci_sessions';
+ public string $driver = FileHandler::class;
+ public string $savePath = '/var/www/html/writable/session';
```

**Result:** TTFB **3000ms → 32ms** (99% improvement)

### Nginx Optimizations Applied
| Setting | Before | After | Reason |
|---------|--------|-------|--------|
| `proxy_buffering` | `off` | `on` | Chunked SSL writes caused massive syscall overhead |
| `gzip` | `off` | `on` + types | 6.7KB HTML uncompressed → BREACH risk overstated |
| Regex locations | 7 blocks | 3 blocks | Reduced PCRE evaluation per request |
| `ssl_session_tickets` | `off` | `on` | Enable TLS session resumption |

---

## 7. Production URLs & Access

| Environment | URL | Access Method |
|-------------|-----|---------------|
| **Production** | `https://presensi.smauiiyk.sch.id` | Public (nginx-proxy → smauii-opresent-app:80) |
| **Development** | *No public URL* | `docker exec -it smauii-opresent-app-dev bash` or SSH tunnel |

### Dev Access Pattern
```bash
# Start dev stack
docker compose -f docker/docker-compose.dev.yml up -d --build

# Inside container (for spark serve + Vite HMR)
docker exec -it smauii-opresent-app-dev bash
> php spark serve --port=8080 --host=0.0.0.0  # Backend API
> bun run dev                                # Frontend HMR (port 5173)

# Or from host via SSH tunnel
ssh -L 8100:localhost:8080 user@server
# Access: http://localhost:8100
```

---

## 8. Monitoring & Observability

### Key Metrics to Watch
```prometheus
# TTFB histogram (target: <100ms p95)
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket{handler="presensi"}[5m]))

# Session driver health
ci4_session_driver{instance="presensi"}  # 1=File, 2=DB, 3=Redis

# Database connection pool
pg_stat_activity_count{datname="neondb"}

# Nginx upstream health
nginx_upstream_check_status{upstream="smauii-opresent-app"}
```

### Alert Rules
```yaml
- alert: HighTTFB
  expr: histogram_quantile(0.95, rate(http_request_duration_seconds_bucket{handler="presensi"}[5m])) > 1
  for: 2m
  labels:
    severity: critical
  annotations:
    summary: "TTFB > 1s for 95th percentile"

- alert: SessionDriverMismatch
  expr: ci4_session_driver{instance="presensi"} != 1
  for: 1m
  labels:
    severity: warning
  annotations:
    summary: "Session driver not FileHandler in production"
```

---

## 9. Troubleshooting Quick Reference

| Symptom | Check | Fix |
|---------|-------|-----|
| TTFB > 500ms | `grep sessionDriver .env` | Must be `FileHandler` |
| 500 Error | `docker logs smauii-opresent-app` | Check session dir writable |
| Session lost on restart | `ls -la writable/session/` | `chown -R www-data:www-data writable/session` |
| Assets 404 | `ls public/build/` | `bun run build` |
| DB migration stuck | `docker exec app php spark migrate:status` | Check NeonDB connectivity |
| S3 upload fails | `curl http://smauii-rustfs:9000/minio/health/live` | Check RustFS container healthy |

---

## 10. References

- [CodeIgniter 4 Sessions](https://codeigniter4.github.io/userguide/libraries/sessions.html)
- [Nginx Proxy Buffering](https://nginx.org/en/docs/http/ngx_http_proxy_module.html#proxy_buffering)
- [SSL Session Tickets](https://nginx.org/en/docs/http/ngx_http_ssl_module.html#ssl_session_tickets)
- [NeonDB Latency Best Practices](https://neon.tech/docs/manage/latency)
- [Myth/Auth Documentation](https://myth-auth.com/)
- [Tailwind CSS v4 with Vite](https://tailwindcss.com/docs/installation/framework-guides/vite)

---

**Document Location:** `docs/TECH_STACK_AND_DX.md`  
**Related:** `docs/PERFORMANCE_ANALYSIS.md` (deep-dive on 3s TTFB root cause)  
**Last Updated:** 26 Juli 2026  
**Maintained by:** Ahmad Hanif