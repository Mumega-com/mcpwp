# GitHub Repo Metadata — recommended values (#514)

> STATUS: DRAFT — Hadi applies these via repo **Settings** (description/topics/website) and the **Sponsor** button. I do not change live repo settings.

## Description (the stale "239 tools" claim is the #514 item)
Live now: `⚡ 239 MCP tools for WordPress …` — **239 is inflated/unverified.** A live `tools/list` on the rig returned **171** tools (varies by tier + active integrations). Don't ship a hard count that overstates.

**Recommended description (accurate, no inflated count):**
```
Turn any WordPress site into an MCP server — let Claude, Cursor, ChatGPT & Windsurf build pages, edit Elementor, run WooCommerce, and manage media/SEO through natural language. Governed, approval-gated, auditable.
```
(If you want a count, use "170+ tools" — verified — not 239. Confirm the production number via `tools/list` before any marketing copy hard-codes it.)

## Topics (current set is already good — minor adds)
Live: ai, claude, mcp, model-context-protocol, woocommerce, wordpress, wordpress-plugin, elementor.
**Add:** `ai-agents`, `cursor`, `seo`, `model-context-protocol-server`. (GitHub caps at 20; all fit.)

## Website
Set repo **Website** to `https://mcpwp.net`.

## Sponsor button
`.github/FUNDING.yml` added (custom → mcpwp.net/pricing). Fill in GitHub Sponsors handle when the account exists, or remove.

## License
`LICENSE` was a truncated 18-line GPL (GitHub detected it as "Other"). Replaced with the canonical GPL-2.0 text so GitHub's license detector shows **GPL-2.0**, matching readme.txt's `License: GPLv2 or later`. No action needed from you — it's in the PR.
