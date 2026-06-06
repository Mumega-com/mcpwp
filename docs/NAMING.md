# MCPWP Naming Reference

Canonical brand: **MCPWP** (mcpwp.net). Internal code uses legacy `spai_` / `site-pilot-ai` prefixes for backward compatibility — intentional, do not change.

## Source of truth

| Surface | Canonical value | Status |
|---------|----------------|--------|
| Plugin Name header | `MCPWP` | ✓ correct |
| npm package | `@mcpwp.net/mcpwp` | ✓ correct |
| MCP registry id | `io.github.mumega848/mcpwp` | ✓ correct |
| CLI binary | `mcpwp` | ✓ correct |
| version.json name | `MCPWP` | ✓ correct |
| README H1 | `MCPWP` | ✓ correct |

## Locked for backward compatibility (do NOT change)

| Surface | Value | Why locked |
|---------|-------|-----------|
| REST namespace | `site-pilot-ai/v1` | Existing Claude Desktop / MCP client configs depend on this URL |
| WP option keys | `spai_*` | 189+ stored options in production databases |
| WP action/filter hooks | `spai_*` | Third-party integrations hook into these |
| HTTP headers | `X-SPAI-*` | Webhook subscribers validate these signatures |
| PHP constant | `SPAI_VERSION` | Referenced by update checks across installs |
| PHP package docblock | `MumegaMCP` | Internal, low priority |
| Text Domain | `mumega-mcp` | i18n — change only in a major version with translation migration plan |
| Pro plugin text domain | `site-pilot-ai-pro` | Same |

## Known stale references (fix on sight)

| File | Problem | Fix |
|------|---------|-----|
| `docs/COMPATIBILITY.md` | Says "mumcp" throughout | → MCPWP |
| `docs/KNOWN_ISSUES.md` | Says "mumcp" throughout | → MCPWP |
| `docs/blog-launch-post.md` | Says "mumcp", "mumcp-claude-plugin", "free forever" | → MCPWP + paid copy |
| `docs/V3_PLAN.md` | Says "mumcp", lists $0 free plan | → update or archive |
| `mcp-server/src/index.ts:64` | Config path shown as `~/.mumega-mcp/config.json` | → `~/.mcpwp/config.json` |
| `mcp-server/src/config.ts:31,70` | Config dir `~/.wp-ai-operator` | → `~/.mcpwp` with fallback |
| `site-pilot-ai/composer.json:2` | `mumega/mumega-mcp-dev` | → `mumega/mcpwp-dev` (low priority) |
| `.github/PULL_REQUEST_TEMPLATE.md` | References `spai_` and `site-pilot-ai` | Acceptable for dev checklist, update when convenient |

## Name history (do not resurface)

`WP AI Operator` → `DigID` → `Site Pilot AI` → `mumcp` / `Mumega MCP` → **MCPWP**

## Rule of thumb

- **Public-facing copy** (README, docs, UI strings, blog): always `MCPWP`
- **Code identifiers** (options, hooks, PHP classes, REST routes): keep `spai_` / `site-pilot-ai` / `Spai_` — these are internal and backward-compat locked
- **New code**: use `spai_` prefix for WP options/hooks, `MCPWP` for any user-visible strings
