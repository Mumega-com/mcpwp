## What
[One sentence description of this change]

## Why
Closes #[issue number] — [or explain motivation]

## Checklist
- [ ] Tested on localhost:8080 (`docker compose up -d` in `wp-test/`)
- [ ] `wp_onboard` returns expected tools after change
- [ ] Free/pro split correct (see `docs/FREE_PRO_SPLIT.md`)
- [ ] If new tools added: descriptions ≤120 chars, snake_case, `wp_` prefix
- [ ] If new REST endpoints: `CLAUDE.md` endpoint table updated
- [ ] Version bump NOT included (maintainer handles version bumps)
- [ ] No `var_dump`, `error_log`, `console.log` debug statements left in
- [ ] PHP syntax clean: `find site-pilot-ai -name "*.php" -exec php -l {} \;`
