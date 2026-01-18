# Batumi.zone

Local services marketplace for Batumi, Georgia.

## Structure

```
├── theme/     - WordPress theme (batumi-theme)
├── plugin/    - Core plugin (batumizone-core)
├── scripts/   - Deployment scripts
```

## Branches

- `main` - Production code (batumi.zone)
- `dev` - Development code (dev.batumi.zone)

## Workflow

1. Make changes locally
2. Commit and push to `dev` branch
3. Run `scripts/deploy-dev.bat` to deploy to dev server
4. Test on dev.batumi.zone
5. When ready: merge `dev` → `main`
6. Run `scripts/deploy-prod.bat` to deploy to production

## Quick Deploy Commands

**Deploy to DEV:**
```bash
# From Windows
scripts\deploy-dev.bat

# Or via SSH directly
ssh root@38.242.143.10 "/var/www/deploy/deploy-dev.sh"
```

**Deploy to PRODUCTION:**
```bash
# From Windows
scripts\deploy-prod.bat

# Or via SSH directly
ssh root@38.242.143.10 "/var/www/deploy/deploy-prod.sh"
```

## Server Paths

- Dev: `/var/www/batumi/wp-content/`
- Prod: `/var/www/batumi-prod/wp-content/`
- Deploy repo: `/var/www/deploy/repo/`
