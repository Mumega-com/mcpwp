# mcpwp.net Pricing Page Render Fix (#504)

> STATUS: recipe validated on the rig — **apply to production only when Hadi gates it.** Zero production writes were made by the night-run (one accidental empty draft, page 995, is flagged for cleanup — see bottom).

## The bug (root cause, reproduced on the rig)

The pricing page (production page **502**) renders wrong because three post-meta keys combine so Elementor hijacks `the_content()` and renders empty/stale data instead of the intended content:

| Meta key | Value | Effect |
|---|---|---|
| `_wp_page_template` | `elementor_canvas` | Canvas template — strips header/footer, hands the whole `<body>` to Elementor |
| `_elementor_edit_mode` | `builder` | Elementor replaces WordPress's `the_content()` with its own renderer |
| `_elementor_data` | `[]` (empty) or stale | Elementor renders *its* data (nothing, or the old design); the new Gutenberg `post_content` is never output |

One sentence: **`_elementor_edit_mode=builder` makes Elementor hijack `the_content()` unconditionally; with empty/stale `_elementor_data` the new content never surfaces.**

## The fix (Path A — give the canvas real Elementor data)

Chosen over "switch template back to theme default so Gutenberg renders" because a commercial pricing page needs designed tier cards, and production already uses canvas — adding real Elementor data is the minimally invasive fix.

A clean 3-tier reference page was built + verified on the rig (`http://127.0.0.1:8086/mcpwp-pricing-ref/`, page 20): HTTP 200, 19/19 render checks pass, no Gutenberg bleed. Export its `_elementor_data` as the production payload.

### Production recipe (gated)

```
# 1. Push real Elementor data to page 502 (use the rig page 20 _elementor_data as the source)
POST /wp-json/mcpwp/v1/elementor/502
{ "elementor_data": "<JSON from rig page 20>" }

# 2. Regenerate CSS
POST /wp-json/mcpwp/v1/elementor/regenerate-css
{ "id": 502, "force": true }

# 3. Verify
curl -sL "https://mcpwp.net/?page_id=502" | grep -o "Free\|Pro\|Simple, transparent"
```

The Gutenberg `post_content` on 502 can be left as-is or cleared — it won't render once real Elementor data is present with `_elementor_edit_mode=builder`.

## Pending dependencies
- **Freemius checkout URLs (#512):** tier CTA buttons use placeholder anchors (`#freemius-pro`, `#freemius-agency`) until Freemius checkout is configured (needs Hadi). Replace before launch.
- **Managed-energy credit price (#449):** the credits tier shows no number until measured.

## Cleanup owed
A rig subagent's MCP client was accidentally pointed at production mcpwp.net and created an **empty draft page 995** ("MCPWP Pricing"). Harmless but should be removed: `wp post delete 995 --force --allow-root` (or wp-admin trash). Mitigation going forward: rig-only agents are constrained to the rig endpoint, never the live-site MCP connector.
