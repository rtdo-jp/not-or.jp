# not-or.jp

Source repository for https://not-or.jp/.

## Scope
- This repository mainly tracks the WordPress theme and related project files.
- Production deploy is managed manually on Sakura hosting.

## Main Paths
- `wp/wp-content/themes/nor/`: Active WordPress theme source.
- `static/`: Reference-only static archive (not for deploy).
- `scripts/`: Utility scripts for local checks.

## Deployment Notes
- No automated CI/CD pipeline is configured in this repository.
- Changes are validated locally/staging, then reflected manually to production.

## Operational Rule
- Treat `wp/wp-content/themes/nor/` as the single source of truth for site templates.

