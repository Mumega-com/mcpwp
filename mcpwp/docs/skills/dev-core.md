---
name: dev-core
description: Domain/service layer of MCPWP — the classes REST controllers and MCP tools delegate into. Load when editing mcpwp/includes/core/.
---

# MCPWP Dev — Core / Service Layer

> Load this when working in `mcpwp/includes/core/`. Post-rebrand naming: `Mcpwp_*` / `mcpwp_*` / `MCPWP_*`.

## What this area is

The core layer is the domain logic behind the REST API and MCP tool surfaces. REST controllers in `mcpwp/includes/api/` and MCP tool handlers in `mcpwp/includes/mcp/` instantiate these classes and delegate reads/writes to them. Core classes own the data contracts (WP option keys, post meta keys, DB tables) and are WordPress-native: they use `get_option`, `WP_Query`, `wp_insert_post`, and `WP_Error` return values throughout.

## File map

### Elementor engine
| File | Responsibility | ~Lines |
|------|---------------|--------|
| `class-mcpwp-elementor-basic.php` | GET/SET full Elementor data, section add/edit/remove/replace, patch, find-replace, bulk fetch; widget validation with Levenshtein typo hints | 3299 |
| `class-mcpwp-elementor-widgets.php` | Static widget schema registry (`get_all()`, `get()`, `get_valid_keys()`, `find_closest()`); covers free + pro widgets | 1694 |

### Analytics
| File | Responsibility | ~Lines |
|------|---------------|--------|
| `class-mcpwp-analytics.php` | PostHog HTTP Capture API gateway; `is_enabled()`, `get_site_uuid()`, `capture()`, `on_tool_called()` | 144 |

### Integrations / Providers
| File | Responsibility | ~Lines |
|------|---------------|--------|
| `class-mcpwp-integration-manager.php` | Singleton; stores/retrieves encrypted provider keys in `mcpwp_integrations` option; `set_provider_key()`, `get_provider_key()`, `get_provider_config()`, `get_posthog_config()`, `get_preferred_provider()`, `test_provider()` | 591 |
| `class-mcpwp-provider-openai.php` | OpenAI API wrapper (image gen, vision, text) | 268 |
| `class-mcpwp-provider-gemini.php` | Google Gemini wrapper | 255 |
| `class-mcpwp-provider-elevenlabs.php` | ElevenLabs TTS | ~120 |
| `class-mcpwp-provider-pexels.php` | Pexels stock photos | ~100 |
| `class-mcpwp-figma.php` | Figma API; `get_file()`, `get_node()`, `test_connection()` | 627 |
| `class-mcpwp-screenshot.php` | Page screenshots (Cloudflare worker or mshots fallback) | 499 |
| `class-mcpwp-encryption.php` | Sodium secretbox wrapper; `encrypt()` / `decrypt()`; key derived from `AUTH_SALT` | 133 |

### Content (pages / posts / media)
| File | Responsibility | ~Lines |
|------|---------------|--------|
| `class-mcpwp-core.php` | Site info, capabilities detection, `detect_plugins()`, `get_analytics()` from `wp_mcpwp_activity_log` | 315 |
| `class-mcpwp-pages.php` | Pages CRUD + clone; uses `Mcpwp_Sanitization` trait | 367 |
| `class-mcpwp-posts.php` | Posts CRUD; blocked post-type list; `allowed_post_types = ['post','wp_block']` | 465 |
| `class-mcpwp-media.php` | Upload from file/URL/base64, list, delete; lazy-requires WP admin includes | 572 |
| `class-mcpwp-drafts.php` | Draft list + bulk delete | ~150 |
| `class-mcpwp-design-references.php` | Design reference library (stored in `mcpwp_design_references` option) | 446 |
| `class-mcpwp-site-blueprints.php` | Page archetypes / parts library | 458 |
| `class-mcpwp-site-memory.php` | Long-term site memory store | 231 |

### SEO / Content intelligence
| File | Responsibility | ~Lines |
|------|---------------|--------|
| `class-mcpwp-seo-audit-store.php` | Stores audit runs (`mcpwp_seo_audit_runs`) and normalized issues (`mcpwp_seo_issues`); max 30 runs, 1000 issues | 362 |
| `class-mcpwp-seo-autofix.php` | Approval-safe SEO fix planner | 263 |
| `class-mcpwp-content-coherence.php` | Content coherence scoring | 266 |
| `class-mcpwp-search-performance.php` | Search Console import storage | 347 |
| `class-mcpwp-woocommerce-seo.php` | WooCommerce product SEO report | 322 |
| `class-mcpwp-keyword-research.php` | Keyword research utilities | 263 |
| `class-mcpwp-site-state.php` | Coherent site-state snapshot | 396 |
| `class-mcpwp-signals.php` | Site signal aggregation | 415 |

### Events / Webhooks / Approvals
| File | Responsibility | ~Lines |
|------|---------------|--------|
| `class-mcpwp-event-store.php` | `emit()`, `list_events()`, `get_schema()`; stores up to 200 events in `mcpwp_recent_events` option; fires `mcpwp_event_emitted` + type-specific hook + webhooks | 238 |
| `class-mcpwp-approvals.php` | Approval request CRUD with diff, apply, rollback; stored in `mcpwp_approval_requests` (max 200); `create_post_content_request()` + similar factory methods | 449 |
| `class-mcpwp-action-log.php` | Activity log read/write to `wp_mcpwp_activity_log` DB table | 521 |
| `class-mcpwp-feedback.php` | User feedback store | 491 |

### Workflows / Guides / Playbooks
| File | Responsibility | ~Lines |
|------|---------------|--------|
| `class-mcpwp-workflows.php` | Static workflow templates for AI agents; `get_all()` filters by active capabilities | 1245 |
| `class-mcpwp-guides.php` | Guide index; `get_all()`, `get_by_topic()` | 1060 |
| `class-mcpwp-agent-playbooks.php` | Playbook contracts | ~200 |

### Branding
| File | Responsibility | ~Lines |
|------|---------------|--------|
| `class-mcpwp-white-label.php` | Agency white-label settings in `mcpwp_white_label` option; all methods static | 327 |

## Key classes & entry points

**`Mcpwp_Core`** — instantiated per-request by REST controllers. `get_capabilities()` returns detected plugin flags; cached 1 hr in `mcpwp_capabilities_cache` transient; `clear_capabilities_cache()` (static) is hooked to `activated_plugin`/`deactivated_plugin`. `get_site_info()` combines WP globals + `get_capabilities()` + `Mcpwp_License`. `get_analytics($days)` reads `wp_mcpwp_activity_log` directly.

**`Mcpwp_Elementor_Basic`** — instantiated per-request. `get_elementor_data($page_id, $data)` reads `_elementor_data` post meta and returns decoded JSON; `set_elementor_data($page_id, $json_or_array)` writes it back; `validate_post($post_id)` guards allowed types (`page`, `post`, `elementor_library`, `elementor_snippet`). The largest file at 3299 lines — patch/section operations are all in here.

**`Mcpwp_Integration_Manager`** — singleton via `get_instance()`. All provider secrets flow through `set_provider_key()` → `Mcpwp_Encryption::encrypt()` → `mcpwp_integrations` option. Reads decrypt on-demand. `get_preferred_provider($capability)` walks `CAPABILITY_PROVIDERS` and returns the first configured slug. `get_posthog_config()` is the only named shortcut (used by `Mcpwp_Analytics`).

**`Mcpwp_Analytics`** — all methods static. Called exclusively via the `mcpwp_tool_called` action: `add_action('mcpwp_tool_called', ['Mcpwp_Analytics', 'on_tool_called'], 10, 4)`. `capture()` is fire-and-forget (`blocking: false`); it never throws. UUID stored in `mcpwp_site_uuid` option (prefix `mcpwp-`).

**`Mcpwp_Event_Store`** — all methods static. `emit($type, $payload, $meta)` normalizes, stores (ring-buffer of 200 in `mcpwp_recent_events`), fires `mcpwp_event_emitted`, fires a type-specific hook (e.g. `mcpwp_approval_created`), and calls `Mcpwp_Webhooks::get_instance()->trigger()`. Event IDs are `evt_<uuid-no-dashes>`.

**`Mcpwp_Approvals`** — all methods static. Factory methods (`create_post_content_request()`, similar for Elementor/meta changes) build a diff + rollback snapshot and store to `mcpwp_approval_requests` option (max 200). Emits events via `Mcpwp_Event_Store::emit()` at each state transition.

**`Mcpwp_Encryption`** — singleton via `get_instance()`. Requires libsodium (PHP 7.2+ built-in). Key derived by hashing `AUTH_SALT` with `sodium_crypto_generichash`. Check `is_available()` before using; `encrypt()`/`decrypt()` return `false` on failure. Multi-field provider configs are stored as JSON then encrypted as a single blob.

## Conventions in this area

- **Static vs instance:** Event/approval/analytics helpers are pure static classes. Content and Elementor handlers are instantiated per-request. `Mcpwp_Integration_Manager` and `Mcpwp_Encryption` are singletons (`get_instance()`).
- **Return shapes:** Methods return `array` on success or `WP_Error` on failure. Always check `is_wp_error()` in callers. Error arrays include a `hint` field with agent-readable guidance.
- **Options:** All `get_option` / `update_option` calls use the `mcpwp_*` key prefix. No autoload (`false`) on event/approval stores to avoid bloating page-load.
- **Sanitization:** Content classes use the `Mcpwp_Sanitization` trait. `Mcpwp_Event_Store::sanitize_array()` recursively sanitizes event payloads. Always `sanitize_key()` for slugs, `sanitize_text_field()` for strings.
- **Traits:** `Mcpwp_Api_Auth`, `Mcpwp_Sanitization`, `Mcpwp_Logging` are loaded from `mcpwp/includes/traits/`.

## Gotchas

- **Capabilities transient:** `mcpwp_capabilities_cache` has a 1-hour TTL. The cache is invalidated on plugin activate/deactivate, but NOT when settings change. If you see stale capability flags in tests or after manual changes, call `Mcpwp_Core::clear_capabilities_cache()` or delete the transient directly.
- **Capabilities cache + license invalidation:** `get_capabilities()` also checks `capabilities_cache_matches_license()` — if the stored array has a `plan` or `pro_active` key that disagrees with current license state, the cache is busted. Adding new license-gated flags to the capabilities array must be consistent with this check.
- **Elementor file size:** `class-mcpwp-elementor-basic.php` is 3299 lines. All section/patch/find-replace logic lives in one file. Edits here carry high blast radius — run `php -l` and the Elementor test suite before merging.
- **`Mcpwp_Webhooks` lives outside core:** It is at `mcpwp/includes/class-mcpwp-webhooks.php`, not under `core/`. `Mcpwp_Event_Store::emit()` calls it via `class_exists('Mcpwp_Webhooks')` guard — if the class is absent (e.g. testing stubs), webhook delivery silently no-ops.
- **White-label conditional class load:** `class-mcpwp-white-label.php` uses typed method signatures (`: array`, `: void`) requiring PHP 7.1+. The file is always included; there is no conditional guard beyond ABSPATH. Do not add at-file-load side effects.
- **Analytics is fire-and-forget:** `Mcpwp_Analytics::capture()` uses `blocking: false` on `wp_remote_post`. You cannot test the PostHog payload in a synchronous unit test — use action spies on `mcpwp_tool_called` instead.
- **Encryption key tied to AUTH_SALT:** Migrating a site (new wp-config.php, cloned DB) will make all stored provider keys unreadable. `decrypt()` returns `false`; callers must handle gracefully and prompt re-entry.
- **Event store is an option, not a DB table:** `mcpwp_recent_events` is stored in `wp_options`. At 200 events this can grow large. Avoid adding large payloads — keep event `payload` lean.

## How to add / change things

**Add a new provider integration:**
1. Add a slug + metadata entry to `Mcpwp_Integration_Manager::PROVIDERS`.
2. If it needs multiple fields, add a `fields` sub-array and implement `test_<provider>_provider()` in the same class.
3. If it is capability-selectable (e.g. image generation), add it to `CAPABILITY_PROVIDERS`.
4. Create a `class-mcpwp-provider-<slug>.php` in `core/` with a `test_connection()` method returning `['success' => bool, 'message' => string]`.
5. Wire the new case into `get_provider_instance()`.

**Add an Elementor widget schema:**
1. Add to `Mcpwp_Elementor_Widgets::get_free_widgets()` or `get_pro_widgets()` in `class-mcpwp-elementor-widgets.php`.
2. Schema shape: `['description' => '', 'category' => '', 'settings' => ['key' => ['type' => '', 'default' => '', 'description' => '']], 'example' => [...], 'common_mistakes' => [...]]`.

**Add a new event type:**
1. Add the type string to `Mcpwp_Event_Store::get_schema()`.
2. Add it to `Mcpwp_Webhooks::$events` (`mcpwp/includes/class-mcpwp-webhooks.php`).
3. Call `Mcpwp_Event_Store::emit('your.event', $payload, $meta)` at the relevant mutation point.

**Add a new site capability flag:**
1. Add to the `$capabilities` array in `Mcpwp_Core::get_capabilities()`.
2. If the flag is license-gated and appears in the cached array, update `capabilities_cache_matches_license()` to invalidate on license change.

## Testing

Tests live in `mcpwp/tests/`. The bootstrap uses lightweight WordPress stubs (no full WP install needed).

```bash
composer install --working-dir mcpwp
./mcpwp/vendor/bin/phpunit -c mcpwp/tests/phpunit.xml
```

Key test files for this area:
- `EventStoreTest.php` — covers `Mcpwp_Event_Store` emit + list
- `SeoAutofixTest.php` — covers `Mcpwp_SEO_Autofix`
- `SiteStateTest.php` — covers `Mcpwp_Site_State`
- `SearchPerformanceTest.php` — covers `Mcpwp_Search_Performance`
- `ContentCoherenceTest.php` — covers `Mcpwp_Content_Coherence`
- `test-elementor-basic.php` — functional smoke test (requires live WP)

Lint a single file: `php -l mcpwp/includes/core/class-mcpwp-elementor-basic.php`

## Related skills
- [[dev-api]] REST controllers that call into core
- [[dev-mcp-tools]] MCP tools that expose core
- [[dev-architecture]] top-level map
