# WP.org Submission Audit

Pre-submission checklist for WordPress.org plugin directory. Run before submitting.

## License ✅

- Plugin header: `License: GPL v2 or later` — correct
- `LICENSE` file in plugin root: GPL-2.0 — present
- Freemius SDK: GPL-3.0 — compatible, allowed on WP.org
- All PHP files: no proprietary licenses found
- No obfuscated or minified PHP

## Plugin Header ✅

```
Plugin Name:       MCPWP
Plugin URI:        https://mcpwp.net/
Description:       Connect WordPress to AI assistants via MCP...
Version:           2.8.50
Requires at least: 5.0
Requires PHP:      7.4
Author:            Mumega
Author URI:        https://mumega.com/
Text Domain:       mcpwp
License:           GPL v2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
```

All required header fields present.

## Text Domain ✅

Text domain: `mcpwp` (declared in header and used in `load_plugin_textdomain()`).
Domain path: `/languages`.

**Action required before submission:** Confirm all `__()`, `_e()`, `esc_html__()` calls use `mcpwp` as text domain. Run:

```bash
grep -r "__(\|_e(\|esc_html__(\|esc_attr__(" mcpwp/includes/ | grep -v "mcpwp" | grep -v "vendor/"
```

Any hits with a different domain need fixing.

## External Services — Required Disclosure

WP.org requires readme.txt `== Third Party Services ==` section disclosing every external service the plugin calls. Current services:

| Service | When called | User opt-in? | URL |
|---------|------------|--------------|-----|
| PostHog | MCP tool analytics (opt-in free / opt-out paid) | Yes | https://posthog.com |
| OpenAI API | Image generation, vision (when key configured) | Yes | https://openai.com |
| Google Gemini API | Vision, text (when key configured) | Yes | https://ai.google.dev |
| ElevenLabs | Text-to-speech (when key configured) | Yes | https://elevenlabs.io |
| Pexels | Stock photo search (when key configured) | Yes | https://www.pexels.com |
| Figma | Design file access (when key configured) | Yes | https://www.figma.com |
| Google Indexing API | URL indexing (when service account configured) | Yes | https://developers.google.com |
| Self-update check | Version check against mumega.com manifest | No (on update check) | https://mumega.com |

**Action required:** Add `== Third Party Services ==` to `readme.txt` before submission. Template:

```
== Third Party Services ==

This plugin optionally connects to third-party services. All connections require
explicit configuration by the site administrator. No data is sent without setup.

= PostHog (Analytics) =
When the analytics toggle is enabled in Settings, anonymous MCP tool usage data
(tool name, success/fail, duration) is sent to PostHog. No site content or PII.
Service: https://posthog.com — Privacy: https://posthog.com/privacy

= OpenAI, Google Gemini, ElevenLabs, Pexels, Figma, Google Indexing API =
These integrations are optional. Data is only sent when you configure an API key
in WP Admin → MCPWP → Integrations and use the corresponding tool.

= Plugin Updates =
Version checks are performed against https://mumega.com/mcp-updates/version.json
when checking for plugin updates via the WordPress admin.
```

## Pro / Free Split

Pro-only code lives in `includes/pro/`. The free WP.org zip should exclude this directory entirely. The `Mcpwp_License` class degrades gracefully when Freemius is not active.

Pro features (excluded from WP.org free zip):
- `includes/pro/api/class-mcpwp-rest-elementor-pro.php` — Elementor Pro template conditions
- `includes/pro/core/` — any pro-only core classes

Verify via: `grep -r "mcpwp_get_fs_instance\|is_paying" mcpwp/includes/ --include="*.php" | grep -v "class-mcpwp-license\|class-mcpwp-loader"`

## Files to Exclude from WP.org Zip

```
mcpwp/tests/
mcpwp/docs/
mcpwp/scripts/
mcpwp/composer.json
mcpwp/composer.lock
mcpwp/.github/
mcpwp/CLAUDE.md
mcpwp/CHANGELOG.md
mcpwp/MCP_IMPLEMENTATION.md
mcpwp/includes/pro/          ← pro features
```

Keep:
- `mcpwp/freemius/` — Freemius SDK (GPL, allowed, required for upgrade flow)
- `mcpwp/readme.txt` — WP.org readme
- `mcpwp/LICENSE`

## WP.org readme.txt Requirements

- [x] `=== Plugin Name ===` header
- [x] `Stable tag:` matches current version
- [x] `License:` and `License URI:`
- [x] `== Description ==`
- [x] `== Installation ==`
- [x] `== Changelog ==`
- [ ] `== Third Party Services ==` — **ADD THIS** (see above)
- [ ] `== Screenshots ==` — add after screenshots are ready (T41)

## Known WP.org Review Gotchas

1. **No `eval()`** — grep confirms none present
2. **No remote code execution** — no `preg_replace` with `/e` modifier
3. **Sanitize all inputs** — uses `Mcpwp_Sanitization` trait throughout
4. **Nonces on all forms** — admin forms use `wp_nonce_field`
5. **Prefix everything** — all functions/classes use `mcpwp_` or `Mcpwp_` prefix ✅
6. **No hardcoded credentials** — all keys stored in `wp_options` ✅
7. **Stable tag must match version** — currently in sync ✅

## Action Items Before Submission

| # | Action | Owner |
|---|--------|-------|
| 1 | Add `== Third Party Services ==` to readme.txt | agent |
| 2 | Verify text domain consistency (grep check above) | agent |
| 3 | Run build-wporg.sh and inspect output zip | agent |
| 4 | Create WP.org account | Hadi |
| 5 | Upload zip + screenshots | Hadi |
