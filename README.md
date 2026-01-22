# Batumi.zone

Local services marketplace for Batumi, Georgia.

**Live:** https://batumi.zone | **Dev:** https://dev.batumi.zone

## Structure

```
├── theme/                    # WordPress theme ("flavor")
│   ├── style.css            # Unified CSS (v1.0.6)
│   ├── functions.php        # Theme functions
│   ├── js/                  # JavaScript files
│   └── assets/images/       # Logo, favicon
├── plugin/                   # batumizone-core plugin
├── scripts/                  # Deployment scripts
├── PROJECT-STATUS.md        # Full project status & handoff
├── BUG-FIXES-TRACKER.md     # Bug tracking
└── SERVER-DEPLOYMENT-GUIDE.md # Server deployment guide
```

## Documentation

| Document | Purpose |
|----------|---------|
| `PROJECT-STATUS.md` | Complete project status, architecture, and handoff notes |
| `BUG-FIXES-TRACKER.md` | Bug tracking with phases and change log |
| `SERVER-DEPLOYMENT-GUIDE.md` | Server setup for deploying batumi.work |

## Quick Deploy

**To Dev:**
```bash
scp -r theme/* root@38.242.143.10:/var/www/batumi/wp-content/themes/flavor/
```

**To Prod:**
```bash
scp -r theme/* root@38.242.143.10:/var/www/batumi-prod/wp-content/themes/flavor/
```

## Server

- **IP:** 38.242.143.10
- **SSH:** `ssh root@38.242.143.10` (passwordless)
- **Dev Path:** `/var/www/batumi/wp-content/themes/flavor/`
- **Prod Path:** `/var/www/batumi-prod/wp-content/themes/flavor/`

## Tech Stack

- WordPress with custom theme
- Polylang (multilingual: GE/RU/EN)
- ACF Pro (custom fields)
- Dark mode only (no light theme)
- Mobile-first responsive design

## Current Version

- **Theme:** flavor v1.0.6
- **CSS:** Unified single file (10,401 lines)
- **Last Deploy:** 2026-01-22
