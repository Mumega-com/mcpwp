# Packaging

## Packages

The repository builds multiple distributions from one source tree.

| Package | Build Script | ZIP | Root Folder | Purpose |
|---|---|---|---|---|
| WP.org Free | `scripts/build-wporg.sh` | `scripts/mcpwp-VERSION.zip` | `mcpwp/` | WordPress.org submission |
| Freemius Pro | `scripts/build-freemius.sh` | `scripts/mcpwp-freemius-VERSION.zip` | `mcpwp/` | Freemius paid/trial distribution |
| Self-hosted Legacy | `scripts/build-selfhosted.sh` | `scripts/mcpwp-selfhosted-VERSION.zip` | `mcpwp/` | Legacy updater-based distribution |

## Build Commands

```bash
bash scripts/build-wporg.sh
bash scripts/build-freemius.sh
```

## WP.org Build Rules

- Injects `MCPWP_WPORG_BUILD`.
- Uses package root `mcpwp/`.
- Uses text domain `mcpwp`.
- Excludes `freemius/`.
- Excludes `includes/freemius-init.php`.
- Excludes `includes/pro/`.
- Excludes `includes/class-mcpwp-updater.php`.
- Excludes build scripts, tests, docs, development metadata, and ZIP files through `.distignore`.
- Must pass Plugin Check with no errors before upload.

## Freemius Build Rules

- Injects `MCPWP_FREEMIUS_BUILD`.
- Includes `freemius/`.
- Includes `includes/freemius-init.php`.
- Excludes `includes/class-mcpwp-updater.php`; Freemius should own paid package updates.
- Excludes build scripts, tests, docs, development metadata, and ZIP files through `.distignore`.
- `mcpwp.php` loads Freemius only when `MCPWP_FREEMIUS_BUILD` is defined.

## Release Checklist

1. Confirm version in `mcpwp.php`.
2. Confirm `Stable tag` in `readme.txt`.
3. Run PHP syntax lint.
4. Run `bash scripts/build-wporg.sh`.
5. Run `bash scripts/build-freemius.sh`.
6. Install and activate the WP.org ZIP in a clean WordPress test site.
7. Run Plugin Check against the installed `mcpwp` plugin.
8. Confirm Plugin Check has `0 ERROR`.
9. Confirm the WP.org ZIP does not contain `freemius/`, `includes/freemius-init.php`, or `includes/class-mcpwp-updater.php`.
10. Confirm the Freemius ZIP contains `freemius/start.php` and `includes/freemius-init.php`, and does not contain `includes/class-mcpwp-updater.php`.
11. Merge the release PR.
12. Tag the release, for example `v2.8.31`.
13. Publish the GitHub release and attach intended ZIPs if needed.

## Current Verified Baseline

PR #257 verified the following baseline:

- WP.org ZIP: `scripts/mcpwp-2.8.31.zip`.
- Freemius ZIP: `scripts/mcpwp-freemius-2.8.31.zip`.
- WordPress test matrix: WordPress 6.9 / PHP 8.2.
- Local WordPress approval/apply/rollback smoke test: passed on version `2.8.8`; section patch smoke passed on version `2.8.9`; internal link suggestion smoke passed on version `2.8.10`; internal link application smoke passed on version `2.8.11`; internal link validation smoke passed on version `2.8.12`; weighted content graph smoke passed on version `2.8.13`; SEO readiness smoke passed on version `2.8.14`; structured data smoke passed on version `2.8.15`; combined E2E and media SEO smoke passed on version `2.8.16`; site SEO audit smoke passed on version `2.8.17`; content quality smoke passed on version `2.8.18`; stored SEO issue smoke passed on version `2.8.19`; control room smoke passed on version `2.8.20`; state visual smoke passed on version `2.8.21`; Control Room action smoke passed on version `2.8.22`; event store and REST event smoke passed on version `2.8.23`; site-state snapshot smoke passed on version `2.8.24`; Control Room event inbox smoke passed on version `2.8.25`; agent playbook contract smoke passed on version `2.8.26`; content coherence report smoke passed on version `2.8.27`; SEO autofix plan smoke passed on version `2.8.28`; search performance import smoke passed on version `2.8.29`; WooCommerce SEO report smoke passed on version `2.8.30`; Freemius update refresh smoke passed on version `2.8.31`.
- WP.org free ZIP contents: 110 files, no Freemius SDK, no Pro modules, no legacy updater.
- Freemius ZIP contents: 369 files, Freemius SDK included, Freemius bootstrap included, Pro modules included, no legacy updater.
- Self-hosted ZIP contents: 145 files, Pro modules included, legacy updater included, no Freemius SDK.
- Plugin Check 1.9.0 result for WP.org ZIP: `0 ERROR`, `402 WARNING` on packaged `mcpwp` ZIP for `2.8.30`.
- WP.org ZIP SHA256: `7e843cf1a4ce3c8c0a5d9111b10ae0877f471ac539bbd6b7b3c24b7d1e177c51`.
- Freemius ZIP SHA256: `eef0affb9ece6d4c119893101add5b7a46467fceba93657c2dc355672ba1caaa`.
- Self-hosted ZIP SHA256: `906b8124288c46b0c4b7148077ec5edcc21bd65d6adee4c8df2f6e9fab4f45c5`.
- GitHub draft release upload: https://github.com/Mumega-com/mcp-for-wp/releases/tag/untagged-00ded21aac68db3fac9d
- Local static publish: `/var/www/mcp-updates/version.json` and `/var/www/mcp-updates/mcpwp-latest.zip` are `2.8.31`.
- Public manifest blocker: `https://mumega.com/mcp-updates/version.json` still resolves to `2.8.2`, so the active public origin or server-level override must be updated before GTM.
