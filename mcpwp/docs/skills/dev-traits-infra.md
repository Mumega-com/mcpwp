---
name: dev-traits-infra
description: Cross-cutting infrastructure for MCPWP — API auth, sanitization, activity logging, rate limiting, bootstrap load order, hook wiring, and the self-updater. Load when touching any of these layers.
---

# MCPWP Dev — Traits & Infrastructure

> Load when touching auth, sanitization, logging, rate limiting, the bootstrap, hook wiring, or the self-updater. All names follow `Mcpwp_*` / `mcpwp_*` / `MCPWP_*` — zero `spai_` / `Spai_` aliases exist.

## What this area is

Shared PHP traits mixed into every REST controller, plus the infrastructure classes that underpin every request: API-key validation, input sanitization, activity logging, rate limiting, hook collection, and the self-hosted update pipeline. Changes here affect every endpoint.

## File map

| File | Class / Trait | Responsibility |
|------|---------------|----------------|
| `includes/traits/trait-mcpwp-api-auth.php` | `Mcpwp_Api_Auth` | API-key extraction, hash comparison, scope/role enforcement, OAuth token path, category-gating |
| `includes/traits/trait-mcpwp-sanitization.php` | `Mcpwp_Sanitization` | `sanitize_post_data`, `sanitize_page_data`, `sanitize_query_args`, `validate_url`, `sanitize_json`, `strip_wrapper_html_tags` |
| `includes/traits/trait-mcpwp-logging.php` | `Mcpwp_Logging` | `log_activity` → `{prefix}mcpwp_activity_log` table; `clean_old_logs`; redaction of sensitive keys |
| `mcpwp.php` | — | Bootstrap: constants, `mcpwp_load_plugin()`, activation/deactivation hooks |
| `includes/class-mcpwp-loader.php` | `Mcpwp_Loader` | Hook-collector pattern; `add_action`/`add_filter` defer registration until `run()`; registers all REST routes on `rest_api_init`; injects rate-limit headers via `rest_post_dispatch` |
| `includes/class-mcpwp-rate-limiter.php` | `Mcpwp_Rate_Limiter` | Singleton; per-minute / per-hour / burst windows stored as transients; reads `mcpwp_rate_limit_settings`; `check_limit()` called from `Mcpwp_Api_Auth::check_rate_limit()` |
| `includes/class-mcpwp-updater.php` | `Mcpwp_Updater` | Self-hosted update check; hooks `pre_set_site_transient_update_plugins` / `plugins_api`; excluded from WP.org builds |
| `includes/class-mcpwp-security.php` | `Mcpwp_Security` | Static SSRF guard: `validate_external_url()` blocks localhost/private-range/non-http(s) |
| `includes/class-mcpwp-alerts.php` | `Mcpwp_Alerts` | Cron-driven; fires `api.alert.5xx_spike` / `api.alert.auth_spike` webhooks when error-rate thresholds exceeded |
| `includes/class-mcpwp-ai-presence.php` | `Mcpwp_AI_Presence` | Serves `/llms.txt`, injects `<link>` tag in `<head>`, appends hint to `robots.txt` |
| `includes/class-mcpwp-error-hints.php` | `Mcpwp_Error_Hints` | Enriches `WP_Error` objects with human-readable `hint` fields |

## Bootstrap & load order

`mcpwp.php` runs on `plugins_loaded` via `mcpwp_load_plugin()`.

**Constants defined at file top (before any require):**

| Constant | Value |
|----------|-------|
| `MCPWP_VERSION` | `'3.0.0'` |
| `MCPWP_PLUGIN_DIR` | `plugin_dir_path(__FILE__)` |
| `MCPWP_PLUGIN_URL` | `plugin_dir_url(__FILE__)` |
| `MCPWP_PLUGIN_BASENAME` | `plugin_basename(__FILE__)` |
| `MCPWP_MIN_WP_VERSION` | `'5.0'` |
| `MCPWP_MIN_PHP_VERSION` | `'7.4'` |
| `MCPWP_POSTHOG_DEFAULT_HOST` | `'https://us.i.posthog.com'` |

**Require sequence (abbreviated):**
1. Traits (`api-auth`, `sanitization`, `logging`)
2. Infrastructure (`loader`, `i18n`, `activator`, `deactivator`, `rate-limiter`, `error-hints`, `security`, `webhooks`, `alerts`, `license`)
3. Freemius init — only when `MCPWP_FREEMIUS_BUILD` is defined and file exists
4. Self-updater — only when `class-mcpwp-updater.php` exists (absent from WP.org builds)
5. `Mcpwp_AI_Presence`
6. Core classes, MCP registries, optional Pro bootstrap (requires `Mcpwp_License::get_instance()->is_pro()` and absence of `MCPWP_WPORG_BUILD`)
7. All REST API controllers
8. Admin classes
9. DB migration check: if `mcpwp_db_version` < `MCPWP_VERSION`, runs `Mcpwp_Activator::activate()` and updates option
10. `$loader = new Mcpwp_Loader(); $loader->run();`
11. Inline: wires `mcpwp_tool_called` → `Mcpwp_Analytics::on_tool_called`, `Mcpwp_White_Label::init()`, `Mcpwp_Signals::schedule()`, action-log daily prune cron, `new Mcpwp_Updater()`

## Hook registration (the loader)

`Mcpwp_Loader` uses a collector pattern:

```php
$this->add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1);
$this->add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1);
// Both push into $this->actions / $this->filters arrays.

$loader->run(); // iterates both arrays and calls add_action/add_filter
```

`Mcpwp_Loader::register_rest_routes()` fires on `rest_api_init`; it instantiates every REST controller and calls `->register_routes()`, then fires `do_action('mcpwp_register_rest_routes')` for Pro/third-party additions.

Rate-limit headers are injected via `rest_post_dispatch` → `add_mcpwp_rate_limit_headers()`; 4xx/5xx responses are also logged there (covers routes where `permission_callback` fails before controller code runs).

## API auth (trait-mcpwp-api-auth)

Mixed into every REST controller (`Mcpwp_REST_API` base class uses it).

**Key storage:** `mcpwp_api_keys` WP option — array of records, each with `id`, `hash` (wp_hash_password), `scopes`, `role`, `rate_limits`, `revoked_at`, `last_used_at`. Legacy single-key stored in `mcpwp_api_key`; auto-migrated to hashed scoped store on first use.

**Validation flow (`verify_api_key`):**
1. `apply_filters('mcpwp_bypass_api_key_check', false)` — returns `true` only when combined with `is_admin()` + `activate_plugins` capability + `DOING_AJAX`. Safe for the Chat tab.
2. Batch sub-requests (`X-SPAI-Batch-Sub-Request` header) skip rate limiting, run `verify_api_key_auth_only()`.
3. Key extracted from `X-API-Key` header or `Authorization: Bearer`.
4. JWT-shaped tokens routed to `authenticate_oauth_access_token()`.
5. `find_scoped_api_key()` iterates `mcpwp_api_keys`, hashes and compares with `wp_check_password`.
6. Fallback to `mcpwp_api_key` legacy option; auto-migrates plaintext key to `wp_hash_password`.
7. Rate limit checked via `Mcpwp_Rate_Limiter::get_instance()->check_limit($identifier, $method, $key_record)`.
8. Scope check: `get_required_scope_for_request()` returns `read|write|admin`; MCP requests delegate to `get_required_scope_for_mcp_request()` which inspects the tool name. `key_has_scope()` validates.
9. Category check: `check_disabled_category_for_route()` mirrors the MCP-layer tool-category toggle — prevents bypassing via raw REST.
10. On success: stores matched record in `$this->current_api_key_record`; calls `touch_api_key_last_used()`; calls `set_api_user_context()`.

**All API keys must have the `mcpwp_` prefix.** There is no `spai_` alias.

## Sanitization & logging traits

**`Mcpwp_Sanitization`** — mixed into controllers. Key methods:
- `sanitize_post_data(array)` / `sanitize_page_data(array)` — WP sanitize functions per field
- `sanitize_query_args(array)` — caps `per_page` at 100; validates `status`, `orderby`, `order` against allowlists; maps `ids` CSV to `post__in`; maps `fields` CSV to `_mcpwp_fields`
- `strip_wrapper_html_tags(string)` — strips `<html>/<head>/<body>` from injected snippets; returns `{content, changed}`
- `validate_url(string)` — `esc_url_raw` + http/https scheme check
- `sanitize_json(string)` — decode + re-encode round-trip validation

**`Mcpwp_Logging`** — mixed into `Mcpwp_Loader` and REST controllers.
- `log_activity($action, $request, $response, $status_code)` — writes to `{prefix}mcpwp_activity_log` only when `mcpwp_settings['enable_logging']` is truthy
- Sensitive keys redacted recursively: defaults `api_key, x-api-key, authorization, password, secret, token`; configurable via `mcpwp_settings['log_redaction_keys']`
- Response data stored only when `mcpwp_settings['log_store_response_data']` is set and response JSON ≤ 1000 bytes
- Retention: `mcpwp_settings['log_retention_days']` (default 30); daily cron `mcpwp_cleanup_logs` calls `cleanup_old_logs()`

## Rate limiting

`Mcpwp_Rate_Limiter` — singleton, reads `mcpwp_rate_limit_settings` WP option.

Default windows: 60 req/min, 1000 req/hr, burst 30. GET requests get 2× burst/minute allowance.

State stored in three WP transients per identifier (keyed by API-key ID or IP hash):
- `mcpwp_rl_min_{id}` — rolling minute window
- `mcpwp_rl_hr_{id}` — rolling hour window
- `mcpwp_rl_burst_{id}` — burst window

Per-key overrides: `rate_limits` field on the key record passed as third arg to `check_limit()`.

Headers emitted on every `/mcpwp/v1/*` response: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`, `Retry-After` (on 429).

REST endpoints: `GET /mcpwp/v1/rate-limit` (status), `POST /rate-limit/reset` (requires `admin` scope).

## Self-update

`Mcpwp_Updater` — instantiated in `mcpwp_load_plugin()` only when the file exists. Not present in WP.org builds.

**Resolution order:**
1. `mcpwp_update_info` WP option — site-level override; ignored if its version is older than installed
2. `mcpwp_version_url` WP option (default `https://mumega.com/mcp-updates/version.json`) — fetched via `wp_remote_get`
3. `select_manifest()` picks the candidate with the newer semver

**Install:** hooks `pre_set_site_transient_update_plugins`; when a newer version is found, injects a record that WP's `Plugin_Upgrader` uses to download `mcpwp-latest.zip` from the manifest's `download_url`. Transient cache key `mcpwp_update_check`, TTL 12 hours.

**Stale override risk:** a non-empty `mcpwp_update_info` with an old version is silently dropped, but a well-formed override pointing to a stale ZIP will win over the remote manifest if its version number is higher. Clear the option when done.

## Conventions & security

- **Key storage:** `wp_hash_password` for API keys in `mcpwp_api_keys`; never plaintext. Legacy plaintext keys in `mcpwp_api_key` are auto-rehashed on first use.
- **Provider secrets:** stored via `Mcpwp_Encryption` (AES via `openssl_encrypt`); never raw in `mcpwp_integrations` option.
- **SSRF:** all external URL fetches (media import, screenshot, fetch endpoint) must pass through `Mcpwp_Security::validate_external_url()`.
- **Output escaping:** `esc_html`/`esc_url`/`wp_json_encode` at all echo/response boundaries; no raw `json_encode` in REST responses.
- **Nonces:** not used on REST routes (API-key auth is the boundary); nonces required on all admin-facing AJAX handlers (`wp_ajax_mcpwp_*`).
- **Capabilities transient:** `Mcpwp_Core` caches site capabilities; cleared on `activated_plugin` / `deactivated_plugin` hooks.

## Gotchas

- **No `spai_` key alias.** MCP client configs must use keys with the `mcpwp_` prefix. Old keys issued under the previous plugin name are invalid and will return `invalid_api_key`.
- **Stale `mcpwp_update_info` blocks releases.** Leave the option empty unless intentionally overriding. A stale override whose version exceeds the remote manifest will prevent legitimate updates.
- **Updater absent from WP.org builds.** `class-mcpwp-updater.php` is excluded. Checking `class_exists('Mcpwp_Updater')` before use is correct (the bootstrap already does this).
- **`mcpwp_bypass_api_key_check` filter scope.** The guard also requires `is_admin() && current_user_can('activate_plugins') && DOING_AJAX` — returning `true` from the filter alone is not sufficient to bypass auth.
- **Batch sub-requests skip rate limiting** (identified by `X-SPAI-Batch-Sub-Request` header) — the outer request counts once.
- **Category-toggle enforcement is dual-layer:** `Mcpwp_MCP_Free_Tools` checks it for the MCP protocol path; `check_disabled_category_for_route()` in `Mcpwp_Api_Auth` checks it for direct REST calls. Both must stay consistent with `get_category_for_route()`.
- **`mcpwp_settings['enable_logging']` gates all activity logging.** If the option is falsy, `log_activity()` is a no-op — no error, no table write.

## Testing

Test files live in `mcpwp/tests/`. Run with PHPUnit via `composer test` or directly:

```bash
# Lint a file
php -l mcpwp/includes/traits/trait-mcpwp-api-auth.php

# PHPUnit (requires WordPress test suite)
./vendor/bin/phpunit --config mcpwp/tests/phpunit.xml
```

Key test files for this area: `ApiAuthTest.php`, `RateLimiterTest.php`, `SanitizationWrapperTagsTest.php`.

## Related skills

- [[dev-api]] — REST controllers that use `Mcpwp_Api_Auth`, `Mcpwp_Sanitization`, `Mcpwp_Logging`
- [[dev-pro]] — licensing constants (`MCPWP_FREEMIUS_BUILD`, `MCPWP_WPORG_BUILD`), Pro bootstrap
- [[dev-architecture]] — top-level map of all plugin layers
