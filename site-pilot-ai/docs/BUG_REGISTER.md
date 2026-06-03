# Bug Register

Bugs found during live agent sessions. Each entry: symptom, root cause, status, workaround.

---

## BUG-001 — Admin role keys missing admin scope
**Status:** Fixed (v2.8.35)
**Symptom:** API keys created for admin-role users only had `['read','write']` scopes. Admin-only tools rejected with 403.
**Root cause:** Key creation logic did not auto-assign scopes based on role — relied on checkbox submission. Admin checkbox not submitted = scopes not stored.
**Fix:** Role is now authoritative. Admin role always receives `['read','write','admin']` regardless of checkbox state.

---

## BUG-002 — `wp_set_custom_css` no-op on sites with child themes
**Status:** Partially mitigated (v2.8.36) — better alternatives returned; underlying WP Customizer behavior unchanged
**Symptom:** CSS stored via `wp_set_custom_css` never appears in rendered page output. Verified with TESTMARKER string — present in DB, absent from HTML.
**Root cause:** Child theme enqueues a static CSS file that overrides WP Customizer output. WP's `wp_add_inline_style` for customizer CSS targets the parent theme handle; child theme's static file loads after and wins specificity order.
**Workaround:** Inject `<style>` block into an Elementor HTML widget inside the site's header template (post 79, widget `logohtml`). Renders on every page via Elementor's template system, bypasses WP Customizer entirely.
**Repro:** Call `wp_set_custom_css` with unique marker string. Fetch rendered homepage HTML. Grep for marker — absent.

---

## BUG-003 — `wp_bulk_find_replace` corrupts Elementor JSON
**Status:** Fixed (v2.8.36)
**Symptom:** After calling `wp_bulk_find_replace` to replace a URL substring (e.g. `/get-started` → `https://app.example.com/get-started`), Elementor data on affected pages is wiped or corrupted. Pages render blank.
**Root cause:** `elementor_data` is stored as a JSON string. A substring replace on a URL segment inside JSON can: (a) produce invalid JSON if the replaced string changes length in a way that breaks serialization, or (b) silently corrupt nested structure. WordPress then fails to parse the meta and returns empty data.
**Workaround:** Never use `wp_bulk_find_replace` for URL replacement in Elementor data. Instead: `wp_get_elementor` → Python/JS string replace on the raw JSON → `wp_set_elementor`. Validate JSON round-trips before writing.
**Severity:** High — data loss, silent, hard to detect until page is checked visually.

---

## BUG-004 — Session MCP key is read-only; no hot-swap mechanism
**Status:** Open
**Symptom:** The API key configured in the MCP session connection is read-only (author/editor role). Write operations fail. No way to switch to an admin key mid-session without restarting the MCP server connection.
**Root cause:** MCP key is set in transport config at session start; MCPWP authenticates per-request but Claude Code's MCP client sends the same headers for the session lifetime.
**Workaround:** Spawn a `bash` subprocess with `curl` passing the admin key as `X-API-Key` header for write operations that require elevated scope.
**Fix path:** Add a `wp_switch_context` tool that accepts a new key and caches it for the session duration, OR document that admin key must be configured before session start.

---

## BUG-005 — `wp_add_menu_item` parameter name mismatch
**Status:** Fixed (client-side discovery)
**Symptom:** `wp_add_menu_item` with `object_type` param silently ignored; item created with wrong type.
**Root cause:** Correct param name is `type`, not `object_type`. Schema/docs inconsistency.
**Fix:** Use `type` parameter. Verify against tool schema via `wp_get_widget_schema` equivalent for menu tools.

---

## BUG-006 — `wp_setup_menu` accumulates duplicate items on repeat calls
**Status:** Fixed (v2.8.36) — overwrite=true now clears existing items before repopulating
**Symptom:** Calling `wp_setup_menu` twice with the same item list results in 22 items (2× the intended 11). No deduplication or clear-before-add behavior.
**Root cause:** `wp_setup_menu` appends items without first clearing existing ones. There is no idempotency check.
**Workaround:** Before calling `wp_setup_menu`, list existing items with `wp_list_menu_items` and delete each with `wp_delete_menu_item`.
**Fix path:** Add `replace: true` option to `wp_setup_menu` that clears existing items first.

---

## BUG-007 — `wp_update_page` slug param has no effect
**Status:** Partially fixed (v2.8.36) — now returns `slug_warning` when WP silently rewrites the slug; underlying WP collision behavior is core, not fixable at plugin layer
**Symptom:** Passing `slug: "schools"` to `wp_update_page` leaves page slug as `schools-3`. No error returned.
**Root cause:** WordPress internal uniqueness resolution silently appends suffix when a slug collision exists (even from auto-drafts or trashed posts). The API call succeeds but WP rewrites the slug.
**Workaround:** Fix via WP Admin > Pages > Quick Edit (manually force the slug). Or delete conflicting auto-drafts/trashed posts first.
**Affected pages:** `schools-3`, `community-3`, `team-3` on crophelp.ai as of 2026-06-01.

---

## BUG-008 — SVG uploads blocked at base64 endpoint
**Status:** Improved (v2.8.36) — error message now explains XSS risk and suggests alternatives
**Symptom:** `wp_upload_media_b64` rejects SVG files. No error distinguishes SVG-block from other failures.
**Root cause:** v2.8.34 blocked SVG at the base64 upload endpoint to prevent stored XSS via malicious SVG `<script>` tags. Correct security decision.
**Gap:** Error message does not explain SVG is disallowed or suggest alternative. Tool description does not list SVG as unsupported.
**Fix path:** Return explicit `{"error": "SVG uploads are not supported (XSS risk). Use PNG/WebP instead."}`.

---

## Security Fixes Shipped in v2.8.35

These were found during session audit, not classified as open bugs:

| ID | Issue | Fix |
|----|-------|-----|
| SEC-01 | `wp_create_api_key` defaulted `scopes` to `['read','write','admin']` when omitted | Changed default to `['read']` |
| SEC-02 | Caller-supplied `mime_type` trusted before file detection | Detect-first, verify-match |
| SEC-03 | `wp_upload_media_b64` accepted SVG; SVGs can contain `<script>` | Blocked SVG at endpoint |
