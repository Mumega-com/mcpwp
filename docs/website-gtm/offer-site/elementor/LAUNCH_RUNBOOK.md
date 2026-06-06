# MCPWP Elementor Launch Runbook

This folder contains the staged Elementor funnel for `mcpwp.net`.

## Current Staged Pages

| Route | Draft source | Live target | URL |
| --- | ---: | ---: | --- |
| Homepage | `541` | `95` | `https://mcpwp.net/` |
| Pricing | `556` | `502` | `https://mcpwp.net/pricing/` |
| Download | `560` | `33` | `https://mcpwp.net/download/` |
| First connection | `552` | `34` | `https://mcpwp.net/get-started/` |

## Safety Rules

- Main marketing pages are Elementor-first.
- Gutenberg is reserved for blog/editorial content or Gutenberg-specific demos.
- Avoid fixed tool or blueprint counts in launch copy because live capabilities can change.
- Avoid hard-coded prices, free/no-paywall promises, or trial claims unless current packaging verifies them.
- Use `MCPWP` and `mcpwp` naming. Do not reintroduce legacy internal names in public-facing config examples.

## Dry Run

Run this before promotion:

```bash
node "Offer website for https_github.comMumega-commcpwp/elementor/promote-staged-funnel.mjs"
```

Expected result:

- `readyToPromote: true`
- Every route has `ready: true`
- Stale claim checks are all `false`

## Promote

Only run this after explicit approval to overwrite the live pages:

```bash
node "Offer website for https_github.comMumega-commcpwp/elementor/promote-staged-funnel.mjs" --deploy --confirm-promote
```

The script writes backup files before replacing target Elementor payloads:

```text
Offer website for https_github.comMumega-commcpwp/elementor/backups/
```

## Rollback

Dry-run a restore first:

```bash
node "Offer website for https_github.comMumega-commcpwp/elementor/restore-funnel-backup.mjs" --backup="/absolute/path/to/backup.json"
```

Apply the restore only after confirming the dry-run target:

```bash
node "Offer website for https_github.comMumega-commcpwp/elementor/restore-funnel-backup.mjs" --backup="/absolute/path/to/backup.json" --deploy --confirm-restore
```

## Post-Promotion QA

Check these public URLs in the browser after promotion:

- `https://mcpwp.net/`
- `https://mcpwp.net/pricing/`
- `https://mcpwp.net/download/`
- `https://mcpwp.net/get-started/`

Minimum acceptance:

- Desktop and mobile pages render without horizontal overflow.
- Primary CTA path is homepage -> pricing/download -> get started.
- Copy explains that WordPress becomes an MCP server for safe AI-assisted site operations.
- The first connection page includes the endpoint and a scoped API-key setup path.
- No stale fixed-count, old-name, old-update-URL, placeholder, or hard-price claims appear.
