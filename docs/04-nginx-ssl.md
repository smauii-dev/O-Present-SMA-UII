# Nginx Reverse Proxy & SSL — O-Present SMA UII

## Overview

Traffic masuk melalui nginx-proxy container (shared dengan semua service di Awankinton), di-terminate SSL-nya, lalu di-forward ke container `smauii-opresent-app:80` via `nginx-net` Docker network.

## Domain

`presensi.smauiiyk.sch.id`

## Nginx Config

Lokasi: `/home/dev/web/infrastructure/nginx/conf.d/smauii/presensi.conf`

```nginx
# Rate limiting zones
limit_req_zone $binary_remote_addr zone=opresent_login:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=opresent_admin:10m rate=20r/m;
limit_req_zone $binary_remote_addr zone=opresent_general:10m rate=60r/m;

server {
    listen 80;
    server_name presensi.smauiiyk.sch.id;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
        try_files $uri =404;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl;
    server_name presensi.smauiiyk.sch.id;

    ssl_certificate /etc/letsencrypt/live/presensi.smauiiyk.sch.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/presensi.smauiiyk.sch.id/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
    proxy_hide_header X-Powered-By;

    # Block hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Block sensitive extensions
    location ~* \.(ini|conf|env|log|sh|sql|bak)$ {
        deny all;
    }

    # Block webshell patterns
    location ~* \.(alfa|haxor|shell|c99|r57|b374k|wso|indoxploit)\.php$ {
        deny all;
        return 403;
    }

    # Rate limiting login
    location ~* /login {
        limit_req zone=opresent_login burst=3 nodelay;
        limit_req_status 429;
        proxy_pass http://smauii-opresent-app:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # General
    location / {
        limit_req zone=opresent_general burst=100 nodelay;
        proxy_pass http://smauii-opresent-app:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
        proxy_connect_timeout 10s;
    }
}
```

## SSL Certificate

### Generate (Cloudflare DNS)

```bash
certbot certonly --dns-cloudflare \
  -d presensi.smauiiyk.sch.id \
  --email admin@smauiiyk.sch.id \
  --agree-tos
```

### Or Standalone (if DNS not on Cloudflare)

```bash
# Stop nginx-proxy temporarily
docker stop nginx-proxy

# Generate cert
certbot certonly --standalone \
  -d presensi.smauiiyk.sch.id \
  --email admin@smauiiyk.sch.id \
  --agree-tos

# Restart nginx-proxy
docker start nginx-proxy
```

### Auto-Renewal

Certbot auto-renewal sudah di-setup via systemd timer:
```
certbot-renew-docker.timer → runs daily
certbot-renew-docker.service → stops nginx-proxy, runs certbot, restarts nginx-proxy
```

## Reload Nginx

```bash
# Via awan CLI
awan nginx reload

# Or manual
docker exec nginx-proxy nginx -s reload

# Test config first
docker exec nginx-proxy nginx -t
```

## Instance YAML Update

Tambahkan ke `/home/dev/web/instances/smauii/instance.yaml`:

```yaml
- id: "opresent"
  name: "opresent"
  template: "custom"
  status: "running"
  domains:
    - "presensi.smauiiyk.sch.id"
  volumes:
    type: "docker"
    names:
      - "opresent_uploads"
      - "opresent_photos_masuk"
      - "opresent_photos_keluar"
      - "opresent_files"
```
