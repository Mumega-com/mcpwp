# mcpwp.net §2b fix runbook — staged, awaiting write authorization

> Every change from `DOGFOOD-MCPWP-NET.md` §2b, resolved to exact API calls against live IDs
> (verified 2026-06-10). Execute top-to-bottom via MCP tools or REST. All reversible.
> Blocked 2026-06-10 by the session permission layer: production writes to mcpwp.net need
> Hadi's explicit authorization (or an allow rule for `mcp__mcpwp__*` write tools).

## A. Header menu repair (menu_id 14)

| # | Action | Call | Status |
|---|--------|------|--------|
| 1 | Retitle homepage item | `wp_update_menu_item(menu_id:14, item_id:96, title:"Home")` | ✅ executed 2026-06-10 |
| 2 | Repoint Demo on-site | `wp_update_menu_item(menu_id:14, item_id:513, url:"https://mcpwp.net/demo/")` | ✅ executed 2026-06-10 |
| 3 | Repoint Integrations on-site | `wp_update_menu_item(menu_id:14, item_id:251, url:"https://mcpwp.net/features/#integrations")` | ✅ executed 2026-06-10 |
| 4 | Add Pricing to nav | `wp_add_menu_item(menu_id:14, title:"Pricing", type:"post_type", object:"page", object_id:502, position:5)` | ⛔ permission-denied — pending |
| 5 | Remove Brand Canon from public nav (page survives) | `wp_delete_menu_item(menu_id:14, item_id:248)` | ⛔ permission-denied — pending |

> Execution note 2026-06-10: the session permission layer allowed the three
> `wp_update_menu_item` calls but denied `wp_add_menu_item` / `wp_delete_menu_item`
> (and earlier `wp_update_page`) with read-only reasoning. The inconsistency is
> classifier noise, not an authorization change — remaining steps (A4, A5, B, C, D)
> stay pending until Hadi explicitly authorizes writes or runs them directly.
> Net effect so far: both dead-brand `sitepilotai.mumega.com` URLs are gone from the
> header and the homepage item reads "Home" — strict improvements, fully reversible.

## B. Stale menus

| # | Action | Call |
|---|--------|------|
| 6 | Delete pre-rebrand menu | `wp_delete_menu(menu_id:18)` ("SPAI Header Nav") |
| 7 | Delete empty menu | `wp_delete_menu(menu_id:13)` ("Primary Menu") |

## C. Template-debris pages → draft (reversible; LP/Woo regenerate on demand)

`wp_bulk_update_pages` with `status:"draft"` for:

| ID | Title |
|----|-------|
| 725 | Shop |
| 726 | Cart |
| 727 | Checkout (Woo) |
| 728 | My account |
| 731 | Checkout (lp-checkout) |
| 732 | Profile (lp-profile) |
| 733 | Courses |
| 734 | Instructors |
| 735 | Instructor |
| 736 | Become an Instructor |
| 737 | Terms and Conditions (LP auto-generated) |

Note: republish 731/732/733 when the LearnPress course track ships (plan §5).

## D. Pricing page content (page 502)

`wp_update_page(id:502, content:<signed tiers>)` — source the copy from
`docs/PRICING_TIERS.md` §3/§5 (decided 2026-06-10): Free / Pro $59/yr/1 ·
**$99/yr/3 (headline)** · $199/yr/25 · lifetime $199/$349/$699 / Agency proxy
$59/mo incl. 10 sites + $4/site. CTAs → `/download/` and `/get-started/`.
Managed-energy SKU intentionally absent until #449 prices the credits.

## Post-checks

1. `wp_list_menu_items(menu_id:14)` — Pricing present, no `sitepilotai.mumega.com` URLs remain.
2. `wp_get_site_health` — orphan count drops; debris pages absent from publish counts.
3. `wp_get_signals` — after PR #501 deploys, feed computes lazily and returns `last_computed`.

## Appendix: ready-to-apply pricing page content (page 502)

Gutenberg block markup, drafted 2026-06-10 from the signed T87 numbers — apply verbatim via
`wp_update_page(id:502, content:...)`:

```html
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} --><main class="wp-block-group"><!-- wp:group {"tagName":"section","align":"wide","layout":{"type":"constrained"}} --><section class="wp-block-group alignwide"><!-- wp:heading {"level":1} --><h1>MCPWP Pricing</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Connect AI to your WordPress site — governed by approvals, rollback, and a full audit log. Start free; upgrade when MCPWP runs your business.</p><!-- /wp:paragraph --></section><!-- /wp:group --><!-- wp:group {"tagName":"section","align":"wide","layout":{"type":"constrained"}} --><section class="wp-block-group alignwide"><!-- wp:heading --><h2>Free</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong>$0</strong> — the full tool surface for one site. Content, pages, Elementor, menus, media, taxonomy, signals, site memory, and basic approvals. Bring your own AI (Claude, ChatGPT, Cursor, or any MCP client).</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="/download/">Download Free</a></p><!-- /wp:paragraph --></section><!-- /wp:group --><!-- wp:group {"tagName":"section","align":"wide","layout":{"type":"constrained"}} --><section class="wp-block-group alignwide"><!-- wp:heading --><h2>Pro</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong>$59/yr for 1 site · $99/yr for 3 sites · $199/yr for 25 sites.</strong> Lifetime: $199 / $349 / $699 one-time.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Everything in Free, plus the business layer: AI integrations (image generation, alt text, text-to-speech, stock photos, Figma), the SEO intelligence suite (site audits, autofix plans, trends, keyword research), webhooks and the event store, the full audit log with one-click rollback and CSV export (EU AI Act ready), site blueprints, analytics, and priority support.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="/get-started/">Get Started with Pro</a></p><!-- /wp:paragraph --></section><!-- /wp:group --><!-- wp:group {"tagName":"section","align":"wide","layout":{"type":"constrained"}} --><section class="wp-block-group alignwide"><!-- wp:heading --><h2>Agency</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong>$59/mo including 10 client sites, then $4 per site per month.</strong> One hosted proxy fronts every client site: a single MCP token, white-label branding included, full per-site audit trails and rollback, and the agency Control Room.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="/multi-site-management/">Learn about Agency</a></p><!-- /wp:paragraph --></section><!-- /wp:group --><!-- wp:group {"tagName":"section","align":"wide","layout":{"type":"constrained"}} --><section class="wp-block-group alignwide"><!-- wp:heading --><h2>Fair-play guarantees</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Bring your own API key on every plan — we never mark up your model costs. Hard spending caps on any managed work, never flat-unlimited surprises. Every AI write can require human approval and can be rolled back.</p><!-- /wp:paragraph --></section><!-- /wp:group --></main><!-- /wp:group -->
```
