# Batumi.zone

Local services marketplace for Batumi, Georgia.

## Structure

```
├── theme/    - WordPress theme (batumi-theme)
├── plugin/   - Core plugin (batumizone-core)
```

## Branches

- `main` - Production code (batumi.zone)
- `dev` - Development code (dev.batumi.zone)

## Deployment

- Push to `dev` → deploys to dev.batumi.zone
- Merge to `main` → deploys to batumi.zone

## Server Paths

- Dev: `/var/www/batumi/wp-content/`
- Prod: `/var/www/batumi-prod/wp-content/`
