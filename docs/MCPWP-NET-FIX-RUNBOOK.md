# mcpwp.net §2b fix runbook — staged, awaiting write authorization

> Every change from `DOGFOOD-MCPWP-NET.md` §2b, resolved to exact API calls against live IDs
> (verified 2026-06-10). Execute top-to-bottom via MCP tools or REST. All reversible.
> Blocked 2026-06-10 by the session permission layer: production writes to mcpwp.net need
> Hadi's explicit authorization (or an allow rule for `mcp__mcpwp__*` write tools).

## A. Header menu repair (menu_id 14)

| # | Action | Call |
|---|--------|------|
| 1 | Retitle homepage item | `wp_update_menu_item(menu_id:14, item_id:96, title:"Home")` |
| 2 | Repoint Demo on-site | `wp_update_menu_item(menu_id:14, item_id:513, url:"https://mcpwp.net/demo/")` |
| 3 | Repoint Integrations on-site | `wp_update_menu_item(menu_id:14, item_id:251, url:"https://mcpwp.net/features/#integrations")` |
| 4 | Add Pricing to nav | `wp_add_menu_item(menu_id:14, title:"Pricing", type:"post_type", object:"page", object_id:502, position:5)` |
| 5 | Remove Brand Canon from public nav (page survives) | `wp_delete_menu_item(menu_id:14, item_id:248)` |

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
