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

- [x] `=== Plugin Name ===` header (`=== MCPWP ===`)
- [x] `Contributors:` / `Tags:` (5) / `Requires at least:` / `Tested up to:` / `Requires PHP:`
- [x] `Stable tag:` matches current version (3.0.0)
- [x] `License:` and `License URI:` (GPLv2+)
- [x] `== Description ==`
- [x] `== Installation ==`
- [x] `== Changelog ==`
- [x] `== External Services ==` — full disclosure (mShots, Feedback Relay, GitHub, OpenAI; each with data-sent/when/service/privacy). Satisfies WP.org's third-party-disclosure requirement.
- [x] `== Privacy ==` — analytics opt-in for free, where-sent (PostHog), opt-out documented
- [x] `== Screenshots ==` — 4 entries + matching `assets/screenshot-{1..4}.png`

## Known WP.org Review Gotchas

1. **No `eval()`** — grep confirms none present
2. **No remote code execution** — no `preg_replace` with `/e` modifier
3. **Sanitize all inputs** — uses `Mcpwp_Sanitization` trait throughout
4. **Nonces on all forms** — admin forms use `wp_nonce_field`
5. **Prefix everything** — all functions/classes use `mcpwp_` or `Mcpwp_` prefix ✅
6. **No hardcoded credentials** — all keys stored in `wp_options` ✅
7. **Stable tag must match version** — currently in sync ✅

## v3.0.0 readiness — GREEN (code/package), audited 2026-06-10

Full audit on the `build-wporg.sh` free build (`mcpwp-3.0.0.zip`, 159 files):

| Check | Result |
|-------|--------|
| Version consistency (header / `MCPWP_VERSION` / Stable tag) | ✅ all 3.0.0 |
| readme.txt required headers + sections | ✅ complete (Privacy + External Services + Screenshots) |
| No Freemius SDK / Pro modules / self-updater in free build | ✅ stripped (sanity checks pass) |
| No `eval` / `create_function` / code-exec base64 | ✅ none |
| No remote CDN script/style enqueues | ✅ all local |
| Analytics phone-home | ✅ **opt-in for free tier**, disclosed, no hardcoded token |
| Standalone safety (no fatal without Pro) | ✅ pro refs `class_exists`-guarded; free main `php -l` clean |
| ABSPATH guards | ✅ on all class files (index.php are "Silence is golden" stubs) |
| Text domain `mcpwp` + `load_plugin_textdomain` | ✅ |
| Leftover debug (var_dump/print_r/console.log) | ✅ none |
| WP.org assets (icon 128/256, banner 1544×500/772×250, 4 screenshots) | ✅ present in `assets/` |

**Verdict: the v3.0.0 free package is WP.org-ready.** Remaining steps are human/process, not code:

| # | Action | Owner |
|---|--------|-------|
| 1 | Create WP.org account / submit plugin for initial review (T25) | Hadi |
| 2 | On approval: SVN commit `trunk/` (the free zip contents) + place icon/banner/screenshots in SVN sibling `assets/` (not in the plugin folder) | Hadi |
| 3 | Pricing page (T87) for the free→Pro upgrade link — not a package blocker, but the upgrade CTA points there | Hadi |
| 4 | (optional) Run the official WP **Plugin Check (PCP)** plugin locally for the final automated lint before submit | Hadi/agent |
