# Batumi.zone Project Status & Handoff Document

**Last Updated:** 2026-01-22
**Session:** CSS consolidation, logo update, server documentation

---

## Project Overview

**Batumi.zone** is a local services marketplace for Batumi, Georgia built on WordPress.

| Environment | URL | Server Path |
|-------------|-----|-------------|
| Production | https://batumi.zone | `/var/www/batumi-prod/` |
| Development | https://dev.batumi.zone | `/var/www/batumi/` |
| Analytics | https://analytics.batumi.zone | `/opt/plausible/` (Docker) |

**Server:** `38.242.143.10` (SSH as root, passwordless auth configured)

---

## Current State (2026-01-22)

### What's Working
- Dark-mode only theme (no light mode toggle)
- Multilingual support (Georgian, Russian, English) via Polylang
- Service listings with infinite scroll
- Filter panel (category, area, price, sort)
- Favorites system (localStorage based)
- User authentication and service creation
- Report modal for flagging listings
- Ad container system (sidebar + inline)
- Responsive design (mobile-first)

### Recent Changes This Session

| Change | Files Modified | Status |
|--------|---------------|--------|
| CSS Consolidation | `theme/style.css` (10,401 lines) | Deployed |
| Phone emoji → SVG icon | `content-service-card.php`, `single-service_listing.php`, `fancy-frontend.js` | Deployed |
| New logo (transparent PNG) | `theme/assets/images/logo.png`, `favicon.png` | Deployed |
| Logo sizing 4x/2x | `theme/style.css` (180px desktop, 64px mobile) | Deployed |
| Server deployment guide | `SERVER-DEPLOYMENT-GUIDE.md` | Committed |

### Git Status
- **Branch:** `main`
- **Last Commit:** `592ebc8` - Add server deployment guide for batumi.work
- **Remote:** https://github.com/tulashvilimindia/batumi.zone.git
- **Dev/Prod Sync:** Both environments deployed and identical

---

## Architecture

### Directory Structure
```
batumi-zone/
├── theme/                    # WordPress theme (active: "flavor")
│   ├── style.css            # Unified CSS (10,401 lines) - v1.0.6
│   ├── functions.php        # Theme functions, enqueues, ACF
│   ├── header.php           # Glassmorphism header with filters
│   ├── footer.php           # Footer with ad containers
│   ├── single-service_listing.php  # Service detail page
│   ├── archive-service_listing.php # Services grid with infinite scroll
│   ├── template-parts/
│   │   └── content-service-card.php # Service card component
│   ├── js/
│   │   ├── fancy-frontend.js # Main JS (infinite scroll, filters, favorites)
│   │   ├── report-modal.js   # Report functionality
│   │   └── service-form-inline.js # Service creation form
│   └── assets/images/
│       ├── logo.png         # Main logo (400x267, transparent)
│       └── favicon.png      # Favicon (64x43, transparent)
├── plugin/                   # batumizone-core plugin
├── scripts/                  # Deployment scripts
├── SERVER-DEPLOYMENT-GUIDE.md # For deploying batumi.work
├── BUG-FIXES-TRACKER.md     # Bug tracking document
├── PROJECT-STATUS.md        # This file
└── README.md                # Basic project info
```

### Key Theme Files

| File | Purpose | Notes |
|------|---------|-------|
| `style.css` | All CSS styles | Consolidated from 9 files, version 1.0.6 |
| `functions.php` | Theme setup, ACF fields, REST API | ~900 lines |
| `header.php` | Header with search, filters, language switcher | Uses Polylang |
| `fancy-frontend.js` | Infinite scroll, favorites, filters, ads | XSS-protected |

### Custom Post Types
- `service_listing` - Main listing type

### Taxonomies
- `service_category` - Service categories (directions)
- `coverage_area` - Geographic areas
- `service_tag` - Tags for services

### ACF Fields (per service_listing)
- `title_ge`, `title_ru`, `title_en` - Multilingual titles
- `desc_ge`, `desc_ru`, `desc_en` - Multilingual descriptions
- `price_model`, `price_value`, `currency` - Pricing
- `location_lat`, `location_lng`, `neighborhood` - Location
- `phone`, `whatsapp`, `email` - Contact info
- `_gallery_image_ids` - Gallery images (comma-separated IDs)

---

## Deployment

### Quick Deploy Commands

**To Development:**
```bash
scp -r theme/* root@38.242.143.10:/var/www/batumi/wp-content/themes/flavor/
```

**To Production:**
```bash
scp -r theme/* root@38.242.143.10:/var/www/batumi-prod/wp-content/themes/flavor/
```

**Using Deploy Scripts:**
```bash
ssh root@38.242.143.10 "/var/www/deploy/deploy-dev.sh"
ssh root@38.242.143.10 "/var/www/deploy/deploy-prod.sh"
```

### Cache Busting
After CSS changes, bump version in `functions.php`:
```php
wp_enqueue_style('batumi-theme-style', get_stylesheet_uri(), array(), '1.0.6');
```

---

## Known Issues & Pending Work

### Phase 1 Completed (Security)
- [x] Wrong API endpoint fixed
- [x] XSS in service cards fixed
- [x] API error handling added
- [x] CSRF nonce in report form

### Phase 2 Pending (Security Hardening)
- [ ] XSS in report modal
- [ ] XSS in jQuery .html() calls
- [ ] Server-side file type validation
- [ ] response.ok check in favorites

### Phase 3 Pending (UX)
- [ ] Error display for favorites
- [ ] localStorage quota handling
- [ ] Form validation improvements
- [ ] Phone number validation

### Phase 4 Pending (Polish)
- [ ] SQL query optimization
- [ ] API rate limiting
- [ ] Filter validation
- [ ] Accessibility labels
- [ ] Memory leak in IntersectionObserver

See `BUG-FIXES-TRACKER.md` for full details.

---

## Server Information

| Resource | Value |
|----------|-------|
| IP | 38.242.143.10 |
| CPU | 4 cores (AMD EPYC) |
| RAM | 7.8 GB (~6.2 GB free) |
| Disk | 145 GB (~139 GB free) |
| Docker | v29.1.5 |
| Docker Compose | v5.0.1 |

### Ports in Use
- 22: SSH
- 53: DNS
- 80/443: Nginx (all sites)
- 3306: MariaDB
- 8000/8443: Plausible Analytics

### Reserved Port Range
- **8100-8999** for batumi.work deployment

---

## Important Notes for Next Agent

1. **Dark Mode Only** - Site has no light mode. Theme switcher was removed.

2. **CSS is Unified** - All styles in single `style.css`. Don't create separate CSS files.

3. **Phone Icon** - Uses SVG, not emoji. Check `.phone-icon` class for styling.

4. **Logo** - PNG with transparent background. Sized via CSS (180px desktop, 64px mobile).

5. **Deployment** - Always deploy to dev first, verify, then deploy to prod.

6. **Server Caution** - Production server hosts multiple sites. Never modify existing nginx configs.

7. **Polylang** - Handles translations. Language codes: `ka` (Georgian), `ru` (Russian), `en` (English).

8. **Backups** - Located at `/var/www/backups/` on server.

---

## Useful Commands

```bash
# Check server status
ssh root@38.242.143.10 "systemctl status nginx && docker ps"

# View nginx error logs
ssh root@38.242.143.10 "tail -50 /var/log/nginx/error.log"

# Check theme version on server
ssh root@38.242.143.10 "head -10 /var/www/batumi/wp-content/themes/flavor/style.css"

# Diff dev vs prod
ssh root@38.242.143.10 "diff -rq /var/www/batumi/wp-content/themes/flavor/ /var/www/batumi-prod/wp-content/themes/flavor/"
```

---

*Document created for session handoff - 2026-01-22*
