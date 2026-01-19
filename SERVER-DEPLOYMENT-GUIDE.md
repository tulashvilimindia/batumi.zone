# Server Deployment Guide for batumi.work

## CRITICAL WARNING

**THIS IS A SHARED PRODUCTION SERVER hosting batumi.zone and related services.**

**Existing projects that MUST NOT be disrupted:**
- batumi.zone (WordPress - PRODUCTION)
- dev.batumi.zone (WordPress - DEV/STAGING)
- analytics.batumi.zone (Plausible Analytics)
- admin.batumi.zone, api.batumi.zone (Admin/API services)

**ALWAYS:**
1. Use unique ports (recommended: 8100-8999)
2. Create backups before modifying nginx
3. Test nginx config with `nginx -t` before reload
4. Never modify existing configs in `/etc/nginx/sites-available/`

---

## Server Access

```bash
ssh root@38.242.143.10
```
*(Passwordless SSH configured)*

---

## Server Resources

| Resource | Total | Available |
|----------|-------|-----------|
| CPU | 4 cores (AMD EPYC) | - |
| RAM | 7.8 GB | ~6.2 GB |
| Disk | 145 GB | ~139 GB |
| Docker | v29.1.5 | Installed & running |
| Docker Compose | v5.0.1 | Installed |

---

## PORTS ALREADY IN USE - DO NOT USE

| Port | Service | Description |
|------|---------|-------------|
| 22 | SSH | System access |
| 53 | DNS | systemd-resolved |
| 80 | Nginx | HTTP (all sites) |
| 443 | Nginx | HTTPS (all sites) |
| 3306 | MariaDB | WordPress database |
| 8000 | Plausible | Analytics web UI |
| 8443 | Plausible | Analytics HTTPS |

**For batumi.work, use ports in range: 8100-8999** (supports 900 ports for multi-container deployments)

---

## Existing Nginx Virtual Hosts - DO NOT MODIFY

```
/etc/nginx/sites-available/
├── batumi.zone              # PRODUCTION - DO NOT TOUCH
├── dev.batumi.zone          # DEV - DO NOT TOUCH
├── admin.batumi.zone        # Admin panel
├── admin.dev.batumi.zone    # Dev admin
├── api.batumi.zone          # REST API
├── api.dev.batumi.zone      # Dev API
├── analytics.batumi.zone    # Plausible proxy
└── default                  # Default site
```

---

## Directory Structure

```
/var/www/
├── batumi/              # batumi.zone DEV (WordPress)
├── batumi-prod/         # batumi.zone PROD (WordPress)
├── backups/             # Centralized backups
├── deploy/              # Deployment scripts
└── html/                # Default nginx root

/opt/
├── plausible/           # Plausible Analytics (Docker)
└── [batumi-work]/       # <- YOUR NEW PROJECT HERE
```

---

## Deploying batumi.work

### Step 1: Create Project Directory

```bash
mkdir -p /opt/batumi-work
cd /opt/batumi-work
```

### Step 2: Add Your Docker Compose File

Create `/opt/batumi-work/docker-compose.yml` with your application.

**Example port binding (use 127.0.0.1 for security):**
```yaml
services:
  app:
    ports:
      - "127.0.0.1:8100:80"  # Internal only, nginx will proxy
```

### Step 3: Create Nginx Virtual Host

Create NEW file: `/etc/nginx/sites-available/batumi.work`

```nginx
# HTTP to HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name batumi.work www.batumi.work;
    return 301 https://batumi.work$request_uri;
}

# HTTPS - www redirect to non-www
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.batumi.work;

    ssl_certificate /etc/letsencrypt/live/batumi.work/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/batumi.work/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

    return 301 https://batumi.work$request_uri;
}

# Main HTTPS server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name batumi.work;

    ssl_certificate /etc/letsencrypt/live/batumi.work/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/batumi.work/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    location / {
        proxy_pass http://127.0.0.1:8100;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header X-Forwarded-Host $host;

        # WebSocket support (if needed)
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    access_log /var/log/nginx/batumi.work.access.log;
    error_log /var/log/nginx/batumi.work.error.log;
}
```

### Step 4: Enable Site (DO NOT reload nginx yet!)

```bash
ln -s /etc/nginx/sites-available/batumi.work /etc/nginx/sites-enabled/
```

### Step 5: Get SSL Certificate

**Option A: Let's Encrypt (if DNS points directly to server)**
```bash
certbot certonly --nginx -d batumi.work -d www.batumi.work
```

**Option B: Cloudflare Origin Certificate (if using Cloudflare proxy)**
1. Generate Origin Certificate in Cloudflare dashboard
2. Save to `/etc/ssl/certs/batumi.work.pem` and `/etc/ssl/private/batumi.work.key`
3. Update nginx config to use these paths

### Step 6: Test & Reload Nginx

```bash
# ALWAYS test first!
nginx -t

# Only if test passes:
systemctl reload nginx
```

### Step 7: Start Docker Application

```bash
cd /opt/batumi-work
docker compose up -d

# Verify it's running
docker compose ps
docker compose logs -f
```

### Step 8: Configure DNS

Add DNS records for batumi.work:
- `A` record → `38.242.143.10`
- `A` record for `www` → `38.242.143.10`

---

## Useful Commands

### Docker
```bash
cd /opt/batumi-work
docker compose up -d          # Start
docker compose down           # Stop
docker compose restart        # Restart
docker compose logs -f        # View logs
docker compose pull           # Update images
docker ps                     # List running containers
```

### Nginx
```bash
nginx -t                      # Test config (ALWAYS DO THIS)
systemctl reload nginx        # Graceful reload
systemctl restart nginx       # Full restart
tail -f /var/log/nginx/batumi.work.error.log   # View errors
```

### System
```bash
ss -tlnp | grep LISTEN        # Check used ports
df -h                         # Check disk space
free -h                       # Check memory
htop                          # Process monitor
```

---

## Troubleshooting

### Container won't start
```bash
docker compose logs
docker compose down && docker compose up -d
```

### 502 Bad Gateway
- Check if container is running: `docker compose ps`
- Check container logs: `docker compose logs`
- Verify port binding: `ss -tlnp | grep 8100`

### SSL Certificate Issues
```bash
certbot certificates                    # List certificates
certbot renew --dry-run                # Test renewal
```

### Nginx won't reload
```bash
nginx -t                               # See the error
journalctl -u nginx -n 50              # View nginx logs
```

---

## Backup Before Major Changes

```bash
# Backup nginx configs
cp -r /etc/nginx/sites-available /etc/nginx/sites-available.bak.$(date +%Y%m%d)

# Backup docker compose
cp /opt/batumi-work/docker-compose.yml /opt/batumi-work/docker-compose.yml.bak
```

---

## Emergency Recovery

If batumi.zone stops working after your changes:

```bash
# 1. Check nginx
nginx -t
systemctl status nginx

# 2. Disable your new site temporarily
rm /etc/nginx/sites-enabled/batumi.work
nginx -t && systemctl reload nginx

# 3. Check existing sites are working
curl -I https://batumi.zone
```

---

## Summary Checklist for batumi.work

- [ ] Create `/opt/batumi-work/` directory
- [ ] Add `docker-compose.yml` (use port 8100)
- [ ] Create `/etc/nginx/sites-available/batumi.work`
- [ ] Enable site with symlink
- [ ] Get SSL certificate
- [ ] Run `nginx -t` (MUST PASS)
- [ ] Reload nginx
- [ ] Start Docker containers
- [ ] Configure DNS
- [ ] Test https://batumi.work

---

*Server IP: 38.242.143.10*
*Last updated: January 18, 2026*
