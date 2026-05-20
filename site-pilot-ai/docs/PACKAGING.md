# Packaging

## Packages

The repository builds multiple distributions from one source tree.

| Package | Build Script | ZIP | Root Folder | Purpose |
|---|---|---|---|---|
| WP.org Free | `scripts/build-wporg.sh` | `scripts/mumega-mcp-VERSION.zip` | `mumega-mcp/` | WordPress.org submission |
| Freemius Pro | `scripts/build-freemius.sh` | `scripts/site-pilot-ai-freemius-VERSION.zip` | `site-pilot-ai/` | Freemius paid/trial distribution |
| Self-hosted Legacy | `scripts/build-selfhosted.sh` | `scripts/mumega-mcp-VERSION.zip` | `site-pilot-ai/` | Legacy updater-based distribution |

## Build Commands

```bash
bash scripts/build-wporg.sh
bash scripts/build-freemius.sh
```

## WP.org Build Rules

- Injects `SPAI_WPORG_BUILD`.
- Uses package root `mumega-mcp/`.
- Uses text domain `mumega-mcp`.
- Excludes `freemius/`.
- Excludes `includes/freemius-init.php`.
- Excludes `includes/pro/`.
- Excludes `includes/class-spai-updater.php`.
- Excludes build scripts, tests, docs, development metadata, and ZIP files through `.distignore`.
- Must pass Plugin Check with no errors before upload.

## Freemius Build Rules

- Injects `SPAI_FREEMIUS_BUILD`.
- Includes `freemius/`.
- Includes `includes/freemius-init.php`.
- Excludes `includes/class-spai-updater.php`; Freemius should own paid package updates.
- Excludes build scripts, tests, docs, development metadata, and ZIP files through `.distignore`.
- `site-pilot-ai.php` loads Freemius only when `SPAI_FREEMIUS_BUILD` is defined.

## Release Checklist

1. Confirm version in `site-pilot-ai.php`.
2. Confirm `Stable tag` in `readme.txt`.
3. Run PHP syntax lint.
4. Run `bash scripts/build-wporg.sh`.
5. Run `bash scripts/build-freemius.sh`.
6. Install and activate the WP.org ZIP in a clean WordPress test site.
7. Run Plugin Check against the installed `mumega-mcp` plugin.
8. Confirm Plugin Check has `0 ERROR`.
9. Confirm the WP.org ZIP does not contain `freemius/`, `includes/freemius-init.php`, or `includes/class-spai-updater.php`.
10. Confirm the Freemius ZIP contains `freemius/start.php` and `includes/freemius-init.php`, and does not contain `includes/class-spai-updater.php`.
11. Merge the release PR.
12. Tag the release, for example `v2.8.26`.
13. Publish the GitHub release and attach intended ZIPs if needed.

## Current Verified Baseline

PR #257 verified the following baseline:

- WP.org ZIP: `scripts/mumega-mcp-2.8.26.zip`.
- Freemius ZIP: pending rebuild on `freemius/pro-packaging`.
- WordPress test matrix: WordPress 6.9 / PHP 8.2.
- Local WordPress approval/apply/rollback smoke test: passed on version `2.8.8`; section patch smoke passed on version `2.8.9`; internal link suggestion smoke passed on version `2.8.10`; internal link application smoke passed on version `2.8.11`; internal link validation smoke passed on version `2.8.12`; weighted content graph smoke passed on version `2.8.13`; SEO readiness smoke passed on version `2.8.14`; structured data smoke passed on version `2.8.15`; combined E2E and media SEO smoke passed on version `2.8.16`; site SEO audit smoke passed on version `2.8.17`; content quality smoke passed on version `2.8.18`; stored SEO issue smoke passed on version `2.8.19`; control room smoke passed on version `2.8.20`; state visual smoke passed on version `2.8.21`; Control Room action smoke passed on version `2.8.22`; event store and REST event smoke passed on version `2.8.23`; site-state snapshot smoke passed on version `2.8.24`; Control Room event inbox smoke passed on version `2.8.25`; agent playbook contract smoke passed on version `2.8.26`.
- WP.org free ZIP contents: 106 files, no Freemius SDK, no Pro modules, no legacy updater.
- Plugin Check 1.9.0 result for WP.org ZIP: `0 ERROR` on packaged `mumega-mcp` ZIP for `2.8.26`.
- WP.org ZIP SHA256: `e9338bb8cbc505e826182f53422a456ca406d5c1eda191b1f7c6164b108a0496`.
- Freemius ZIP SHA256: pending rebuild on `freemius/pro-packaging`.
- GitHub draft release upload: https://github.com/Mumega-com/mcp-for-wp/releases/tag/untagged-6e8bf6009d0eb8c5ddac
