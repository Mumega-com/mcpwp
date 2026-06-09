# Legacy-Name Doc Sweep Plan

> **Goal:** purge dead `site-pilot-ai` / `spai_` / `mumcp` / `mumega-mcp` brand+API-surface names
> from **documentation** (not live code, not frozen records, not deployed-infra identifiers), so docs
> describe the shipped v3.0.0 MCPWP plugin truthfully.

**Mandate:** "we're only mcpwp everywhere, no site-pilot-ai, no mumcp." Hard-rename, no aliases.

## Canonical mapping (deterministic)

| Old | New |
|-----|-----|
| `MumegaMCP`, `SitePilotAI`, `Site Pilot AI` | `MCPWP` |
| `mumega-mcp` | `mcpwp` |
| `MUMCP_PRO` → `MCPWP_PRO`; `MUMCP` → `MCPWP`; `mumcp` (brand/CLI/plugin) | `mcpwp` |
| `site-pilot-ai/v1` (REST ns in doc examples) | `mcpwp/v1` |
| `site-pilot-ai` (other) | `mcpwp` |
| `SPAI_*` const | `MCPWP_*` |
| `Spai_*` class | `Mcpwp_*` |
| `spa_fs` | `mcpwp_fs` |
| `spai_*` func/hook/option/key-prefix | `mcpwp_*` |
| `spai-`, `Spai-` | `mcpwp-`, `Mcpwp-` |

## DO-NOT-TOUCH (keep verbatim — real identifiers / frozen records)

- **Filesystem paths** containing `/sitepilotai/` (e.g. `/mnt/.../projects/sitepilotai/wp-ai-operator`) — real on-disk dir.
- **Deployed-infra identifiers:** R2 bucket `mumcp-updates`; worker dirs/names `spai-proxy-worker`, `spai-updates-worker`, `mumcp-proxy`. (Renames are Hadi-gated item #4 — separate.)
- **Live REST path inside CODE** (`mcp-server/`, `spai-proxy-worker/`) — deployment-coupled; NOT in this sweep. (Docs examples DO flip to `mcpwp/v1`.)
- **Freemius slug history / changelog history** describing what shipped in a past version.

## Files IN scope (FIX)

Root: `AGENTS.md` `BACKLOG.md` `CONTRIBUTING.md` `README.md` `SECURITY.md` `posthog-setup-report.md`
docs/: `API.md` `BATCH_ENDPOINT.md` `BRAND_KIT.md` `chatgpt-gpt-instructions.md` `CI_CD_GUIDE.md`
`COMPATIBILITY.md` `demo-script.md` `DOGFOOD-MCPWP-NET.md` `ELEMENTOR_WIDGET_REFERENCE.md`
`FREE_PRO_SPLIT.md` `MCP_REGISTRY_REFACTOR.md` `openapi-chatgpt.yaml` `openapi.yaml`
`PHASE_2_OPERATOR_DASHBOARD.md` `RELEASE_CHECKLIST.md` `SITE_SYNC.md` `STRATEGY.md` `V3_PLAN.md`
`WP-ORG-AUDIT.md` `website-gtm/offer-site/SEO_COMPETITIVE_MAP.md`
integrations/: `chatgpt/SETUP.md` `clawhub/SKILL.md` `hermes/README.md` `hermes/SKILL.md`

## Files OUT of scope (excluded)

- `version.json` — frozen changelog HTML (past-version class/filter names).
- `docs/NAMING.md` — intentional (documents the dead names).
- `docs/REBRAND-PLAN.md`, `docs/superpowers/**`, `docs/website-gtm/**/backups/*.json`,
  `docs/website-gtm/**/*.mjs`, `*/site-flow-audit.json` — frozen records/snapshots.
- `mcpwp/docs/skills/dev-*.md` — intentional "no alias" warnings.
- `mcp-server/**`, `spai-proxy-worker/**` — live code, deployment-coupled (separate item).

## Method

Parallel subagents, one per file-cluster, each given the mapping + keep-list above. Each agent edits
its files in place, leaving DO-NOT-TOUCH tokens. Then: review `git diff` (confirm no path/infra token
changed, no frozen text touched), commit, PR, CI-green, merge. Docs only — no version bump.

## Done when

- Zero `site-pilot-ai` / `spai_` / `SPAI_` / `Spai_` / `mumcp` / `mumega-mcp` in the in-scope files
  EXCEPT the protected tokens above.
- `git diff` shows only brand/API-surface renames, no path/infra/frozen edits.
- CI green; PR merged.
