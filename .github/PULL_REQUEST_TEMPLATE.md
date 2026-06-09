## What this does

## Why

## How to test

1. Start Docker: `cd wp-test && docker compose up -d`
2. Generate key: see CONTRIBUTING.md
3. Test: 

## Checklist

- [ ] Tested on Docker test site
- [ ] No new `console.log` or `error_log` in production code
- [ ] All functions/classes prefixed with `mcpwp_` / `Mcpwp_` / `MCPWP_`
- [ ] Text domain `mcpwp` on all translatable strings
