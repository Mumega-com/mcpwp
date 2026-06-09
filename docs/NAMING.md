# MCPWP Naming Reference

Canonical brand and code name: **MCPWP** (mcpwp.net).

As of **v3.0.0** (2026-06-09) the plugin was hard-renamed. There is **one** name everywhere —
no `spai_` / `site-pilot-ai` / `mumcp` / `mumega-mcp` aliases, no backward-compat dual naming.
Those prefixes are dead. If you find one in live plugin code, it is a bug — fix it.

## Canonical values (the only correct forms)

| Surface | Value |
|---------|-------|
| Plugin folder + main file | `mcpwp/` · `mcpwp.php` |
| Plugin Name header | `MCPWP` |
| PHP classes | `Mcpwp_*` |
| Functions / hooks / option keys | `mcpwp_*` |
| PHP constants | `MCPWP_*` (e.g. `MCPWP_VERSION`) |
| `@package` docblock | `MCPWP` |
| REST namespace | `mcpwp/v1` |
| API key prefix | `mcpwp_` |
| Text domain | `mcpwp` |
| HTTP headers | `X-MCPWP-*` |
| npm package | `@mcpwp.net/mcpwp` |
| MCP registry id | `io.github.mumega848/mcpwp` |
| CLI binary | `mcpwp` |
| version.json `name` | `MCPWP` |
| README H1 | `MCPWP` |
| Freemius slug | `MCPWP` |

## No migration

User is the only operator and reinstalls fresh on the two sites (mcpwp.net, crophelp.ai).
No stored-option migration, no client-config compat shim, no deprecation window. The rebrand
was clean precisely because there was no external contract to preserve — only our own
Cloudflare worker and our own MCP client configs consume the surface, and both we control.

## Known stale references (documentation only — fix on sight)

These are docs/records, not live code. Some are *frozen historical records that should keep the
old names* — do not "fix" those.

| File | Status |
|------|--------|
| `docs/REBRAND-PLAN.md` | **keep** — documents the rename; old names are the subject matter |
| `docs/superpowers/plans/*` | **keep** — frozen plan records |
| `docs/website-gtm/**/backups/*.json` | **keep** — frozen Elementor snapshots |
| `docs/V3_PLAN.md`, `docs/COMPATIBILITY.md`, `docs/KNOWN_ISSUES.md` | update or archive (pre-rebrand) |
| `docs/blog-launch-post.md` | update to MCPWP + paid copy |
| `mcp-server/src/{index,config,setup}.ts` | live code — config paths `~/.mumega-mcp` / `~/.wp-ai-operator` → `~/.mcpwp` (separate npm artifact; own change) |
| `SECURITY.md`, `CONTRIBUTING.md` | contributor-facing — update when convenient |
| `.github/PULL_REQUEST_TEMPLATE.md` | dev checklist — update when convenient |

## Deferred cross-repo / infra renames (tracked, not yet done)

| Item | Why deferred |
|------|--------------|
| `spai-proxy-worker/` → `mcpwp-proxy-worker` | changes deployed Cloudflare worker name (DNS/routing) — needs explicit go |
| repo/dir `wp-ai-operator` → `mcpwp` | filesystem rename; breaks active worktrees mid-session |
