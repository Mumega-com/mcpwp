# MCPWP — Free vs Paid + Cloudflare Gating Model
**Date:** 2026-05-30
**Status:** Design decision — choose before v3 build starts

---

## What's Free vs Paid Today

### Free (WP.org tier — `class-spai-mcp-free-tools.php`)

| Category | Tools |
|----------|-------|
| Content | posts, pages, search, drafts, bulk ops |
| Taxonomies | categories, tags, custom terms |
| Menus | CRUD, locations, items |
| Media | upload (file/URL/base64), list, delete |
| Site | site_info, onboard, introspect, analytics, detect_plugins, options, custom CSS, rendered HTML |
| Content graph | get_content_graph, suggest_internal_links, apply_internal_link, validate |
| Basic SEO | validate_seo_readiness, validate_structured_data, audit_media_seo, audit_content_quality |
| Elementor (free) | get/set/preview, edit_section, edit_widget, widget_help, regenerate_css |
| Admin | api_keys, rate_limits, site_health, approval gates, activity log, webhooks |
| AI education | guides, workflows, elementor widget help |

### Pro (Freemius tier — `class-spai-mcp-pro-tools.php`)

| Category | Tools |
|----------|-------|
| SEO integrations | Yoast/RankMath/AIOSEO/SEOPress: get_seo, set_seo, analyze, bulk, scan, report |
| Forms | CF7/WPForms/Gravity: list_forms, get_form, get_entries |
| Elementor Pro | templates, archetypes, parts, landing pages, clone, globals, build_page, blueprints |
| Theme Builder | theme templates, conditions, assign |
| WooCommerce | products, orders, customers, analytics |
| LearnPress | courses, lessons, quizzes, curriculum |
| Events | ThimPress events |
| AI tools | stock photos, generate image, alt text, describe image, excerpt, text-to-speech |
| Translation | WPML/Polylang: get/create translations, set_language |
| Design references | Figma integration, design_reference CRUD |
| Multi-site | wp_switch_site, wp_list_sites (v3 Module M) |

### Agency (future tier)
- Everything Pro + agency dashboard + centralized key management

---

## The n8n Model (Full Open-Source + Remote Gating)

n8n puts **all code on GitHub** under a source-available license (AGPL/EE dual). Enterprise features are in the same codebase but gated by a license check against their server. No encrypted files, no SDK obfuscation.

**Applied to MCPWP:**
- All PHP code on GitHub (free + pro tools, no Freemius SDK)
- License key stored as WP option `mcpwp_license_key`
- On activation and periodically (cached 6 hours), plugin calls a Cloudflare Worker:
  ```
  POST https://license.mcpwp.net/validate
  { "license_key": "...", "site_url": "...", "version": "..." }
  → { "plan": "pro", "allowed_categories": ["seo","forms","woocommerce",...], "expires": "2027-05-30" }
  ```
- Plugin enables/disables tool categories based on the response
- CF Worker + D1 holds license records; checkout via Stripe/Lemon Squeezy

### Advantages over current Freemius model

| Dimension | Freemius | Cloudflare gating |
|-----------|----------|-------------------|
| WP.org compliance | Requires Freemius SDK (allowed but opinionated) | Clean: just a REST call, no SDK |
| Code transparency | SDK obfuscation in places | All code readable on GitHub |
| Community | Hard to fork/contribute Pro features | Anyone can submit PRs to pro tools |
| Checkout | Freemius handles | Need to build (Stripe/LemonSqueezy) |
| Trial management | Built-in | Need to build in CF Worker |
| Dashboard | freemius.com | Need to build or use Stripe dashboard |
| Revenue split | ~5% Freemius fee | 0% (just Stripe/LS fees) |
| Vendor lock-in | High | None |
| Build time | Done | 2–3 weeks for CF Worker + checkout |

### Disadvantages

- We'd lose Freemius trial, renewal emails, upgrade prompts — need to rebuild these
- Checkout flow needs to be built (though Lemon Squeezy makes this easy)
- CF Worker has usage costs (minimal at our scale)

---

## Cloudflare Worker Architecture (if we go this route)

```
Worker: license.mcpwp.net
  POST /validate       → check D1 for key, return plan + allowed_categories
  POST /activate       → new activation (check against site limit)
  POST /deactivate     → remove activation slot
  GET  /status/:key    → admin view
  POST /webhook/stripe → handle Stripe events (new sub, cancel, renewal)

D1 database:
  licenses(key, plan, seats, activations, expires_at, created_at)
  activations(key, site_url, activated_at)

KV cache:
  validation responses cached 6 hours per (key + site_url) pair
```

Plugin side:
```php
function mcpwp_get_license_plan(): array {
    $key = get_option('mcpwp_license_key', '');
    if (!$key) return ['plan' => 'free', 'categories' => []];
    
    $cached = get_transient('mcpwp_license_data');
    if ($cached) return $cached;
    
    $response = wp_remote_post('https://license.mcpwp.net/validate', [
        'body' => json_encode(['license_key' => $key, 'site_url' => home_url(), 'version' => SPAI_VERSION]),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 5,
    ]);
    
    if (is_wp_error($response)) return ['plan' => 'free', 'categories' => []];
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    set_transient('mcpwp_license_data', $data, 6 * HOUR_IN_SECONDS);
    return $data;
}
```

---

## Recommendation

**Short term (v2, now):** Keep Freemius. It's already wired, handles trial, and the WP.org submission is under the free slug anyway. Revenue infrastructure is working.

**Medium term (v3 launch):** Switch to CF Worker licensing. Reasons:
1. v3 is a ground-up rewrite (`mcpwp/v1` namespace, new slug) — clean break, no migration pain
2. Full open-source on GitHub is a stronger GTM story than "Freemius-powered"
3. The agency tier's custom pricing logic is easier in a CF Worker than Freemius
4. Avoids Freemius SDK in the codebase for WP.org compliance

**What stays on GitHub (always free):** everything in `class-spai-mcp-free-tools.php`  
**What requires license key (pro):** everything in `class-spai-mcp-pro-tools.php`  
**Agency tier gating:** CF Worker checks seat count against D1 `activations` table

---

## WP.org Gating (n8n pattern)

n8n's WP.org equivalent: they list the community edition on the marketplace for free. Enterprise features activate with a license key. The full source is public.

For MCPWP on WP.org:
- Submit the full plugin (free + pro code) — WP.org allows this
- Pro features show a "license required" notice when called without a key
- "Get Pro" links point to mcpwp.net/pricing (Stripe checkout)
- No Freemius SDK needed in the WP.org build

This removes the need for a separate `free/wporg` branch — one plugin, two activation states.

---

## Action Items

| # | Action | When | Owner |
|---|--------|------|-------|
| 1 | Keep Freemius for v2 | Now | - |
| 2 | Build CF Worker license validator | v3 sprint | Agents |
| 3 | Wire Stripe/LemonSqueezy checkout | v3 sprint | Agents |
| 4 | Remove Freemius SDK from v3 codebase | v3 sprint | Agents |
| 5 | Update WP.org listing to "full plugin, license activates Pro" | After v3 | Hadi |
