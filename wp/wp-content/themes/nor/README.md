# nor (WordPress theme)

Theme source for https://not-or.jp/.

## What This Directory Contains
- PHP templates (`front-page.php`, taxonomy templates, single/archive templates, etc.)
- `functions.php` and shared rendering helpers
- Theme assets under `assets/`

## Project Assumptions
- WordPress core/plugins are managed outside this directory.
- This theme directory is the canonical template implementation.
- Deploy to production is manual (Sakura hosting workflow).

## Editing Guidelines
- Prefer shared helpers/template parts over duplicated markup.
- Keep fallback behavior decisions explicit and consistent across templates.
- Validate major template changes on key URLs (home, taxonomy child pages, single works).
