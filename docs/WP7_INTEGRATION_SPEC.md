# MCPWP × WordPress 7.0 — Integration Spec & Audit

**Ground truth:** WP 7.0 live in container `wp-rig-wp-demo` (`$wp_version = '7.0'`, `$wp_db_version = 61833`). All API surfaces below were read from `/var/www/html/wp-includes/` source and/or the live REST index at `https://demo.mcpwp.net/wp-json/`. Web facts cross-checked against the [WP 7.0 Field Guide](https://make.wordpress.org/core/2026/05/14/wordpress-7-0-field-guide/).

**Headline strategic finding:** WP 7.0 ships an **Abilities API** (`/wp-abilities/v1`, live on the demo) and an **AI Client**, and the Field Guide describes a first-party **MCP adapter** that turns registered abilities into MCP tools. **That adapter is NOT in core `wp-includes` and NOT registered as a namespace on the demo** — `mcpwp/v1` is still the only `/mcp` endpoint on the box. So WP core is now standing up the *substrate* MCPWP already productizes (240 governed tools, approvals, audit, Elementor depth). The right posture is **adopt the standard, keep the moat**: register MCPWP's surface as core Abilities so first-party WP/agent tooling can discover it, while MCPWP keeps owning governance, Elementor, SEO, blueprints, and design coherence.

---

## Part A — Verified WP 7.0 additions (the audit)

Legend for mcpwp value: **High** = build now, **Med** = worth a backlog issue, **Low** = note only / don't backlog.

| # | WP 7.0 addition | Source-verified surface | mcpwp angle | Value |
|---|---|---|---|---|
| A1 | **Abilities API** (genuinely new in 7.0) | `wp-includes/abilities-api/` — `wp_register_ability()`, `wp_get_ability()`, `WP_Abilities_Registry`, `WP_Ability::execute()/validate_input()/check_permissions()`. Live REST: `/wp-abilities/v1/abilities`, `/abilities/{name}/run`, `/categories`. Hook: `wp_abilities_api_init`. | Register mcpwp's tools as core Abilities → discoverable by WP-native agents, the WP AI Client, and any future core MCP adapter, without losing mcpwp's governance layer. **The single highest-leverage integration.** | **High** |
| A2 | **WP AI Client** (new in 7.0) | `wp-includes/ai-client.php` — `wp_supports_ai()`, `wp_ai_client_prompt()` → `WP_AI_Client_Prompt_Builder` (fluent: `using_system_instruction()`, `using_temperature()`, `generate_text_result()`, `generate_image_result()`, `as_json_response()`). Provider lib in `php-ai-client/src/`. | mcpwp already wraps OpenAI/Gemini/ElevenLabs directly. The core client is a provider-agnostic fallback: when no mcpwp provider key is set but the host configured a core Connector, route alt-text / excerpt / image gen through `wp_ai_client_prompt()`. Reduces "no key configured" dead-ends. | **Med** |
| A3 | **Connectors API** (new in 7.0) | `wp-includes/connectors.php` — `wp_is_connector_registered()`, `wp_get_connector()`, `wp_get_connectors()`; `WP_Connector_Registry`; hook `wp_connectors_init`. Holds AI provider creds at the host level. | Read-only: surface host-level connectors in `wp_onboard` / `/integrations/status` so the agent knows a Gemini/OpenAI key already exists at the WP layer (pairs with A2). Don't write — creds are the host's concern. | **Low/Med** |
| A4 | **Global Styles read/write** (functions pre-date 7.0; 7.0 extends theme.json + makes classic themes receive full styles) | `wp_get_global_styles()`, `wp_get_global_settings()`, `wp_get_global_stylesheet()` (`@since 7.0.0`: classic themes now get full styles, `base-layout-styles` deprecated). Write path: `wp_global_styles` CPT (post_content = theme.json JSON), `WP_Theme_JSON_Resolver::get_user_data_from_wp_global_styles($theme, true)`, `WP_Theme_JSON_Data::update_with()`. REST: `/wp/v2/global-styles/{id}` (GET/PUT), `/themes/{stylesheet}/variations` (GET). | **New mcpwp design-token surface for block themes.** Read/write design tokens (palette, typography, spacing) at the *theme* level — the block-theme analogue of Elementor kit-css. This is Job A. | **High** |
| A5 | **Interactivity API `watch()` + `data-wp-watch`** (new in 7.0) | `wp-includes/interactivity-api/` — `WP_Interactivity_API::state()/config()/process_directives()/get_context()`; `wp_interactivity_state()`, `wp_interactivity_config()`, `wp_interactivity_process_directives()`. New: JS `watch()` export, `data-wp-watch` directive; `state.navigation.*` deprecated. | Thin for mcpwp. mcpwp doesn't author custom blocks. Realistic angle: validate/lint interactive-block markup (presence + correctness of `data-wp-*` directives) when reading/saving Gutenberg blocks. Honestly low ROI — see Job B. | **Low** |
| A6 | **theme.json dimension + typography additions** (new in 7.0) | `settings.dimensions.dimensionSizes[]` presets → `--wp--preset--dimension-size--{slug}`; `settings.dimensions.width/height` toggles; `typography.textIndent`; pseudo-class states (`:hover/:focus/:active`) on blocks in theme.json. | Folds into A4 — the global-styles tools should understand and round-trip these new keys so AI-set tokens don't get stripped. Schema awareness, not a separate tool. | **Med** (rides A4) |
| A7 | **Font Library global (classic + block themes)** (API since 6.5; **7.0 makes it work on classic themes too**) | Live REST: `/wp/v2/font-collections`, `/wp/v2/font-families`, `/font-families/{id}/font-faces`. PHP: `WP_Font_Library`, `wp_register_font_collection()`. | **New for mcpwp.** Agent can list/install Google-font families and assign them as the brand typeface — works on classic *and* block themes now. Pairs with design coherence (set the brand font, then reference it in global styles / kit). | **Med/High** |
| A8 | **Icons registry + REST** (new in 7.0) | Live REST: `/wp/v2/icons`, `/wp/v2/icons/{namespace}/{name}`. PHP: `WP_REST_Icons_Controller`, `WP_Icons_Registry`. Core "Icons" block. | Read-only enrichment: expose available icons so the agent picks valid icon slugs when building Icon blocks / icon widgets. Low effort, low-med value. | **Low/Med** |
| A9 | **PHP-only block registration** (new in 7.0) | `register_block_type()` + `supports: { autoRegister: true }`; render_callback + typed attributes, no JS build. | Lets mcpwp (or agent-authored addons) ship dynamic blocks server-side with zero build step. Future-facing: the "agent authors a block" path. Not a today-build. | **Low** (strategic) |
| A10 | **Real-time collaboration persistence layer** (partial in 7.0; editor UI **cut before RC3**) | `wp-includes/collaboration/` — `WP_Sync_Post_Meta_Storage`, `WP_HTTP_Polling_Sync_Server`, `interface-wp-sync-storage.php`. UI shipped only in Gutenberg plugin. | No fit. mcpwp is API-first, not a co-editing UI. Note and ignore. | **Low (skip)** |
| A11 | **View Transitions in wp-admin** (new in 7.0) | `wp_enqueue_view_transitions_admin_css()`, `wp_get_view_transitions_admin_css()`. CSS-only admin nav animation. | Cosmetic, admin-only. No mcpwp angle. Skip. | **Low (skip)** |
| A12 | **Speculative loading** | `WP_Speculation_Rules` — **shipped 6.8, not 7.0.** Filters `wp_speculation_rules_configuration`, action `wp_load_speculation_rules`. | A perf signal mcpwp's `/signals` could report (is speculative loading on?), but it's a 6.8 feature, out of scope for a "WP7" sprint. | **Low (skip for WP7)** |
| A13 | **Block Bindings: custom-block support** (new in 7.0) | `block_bindings_supported_attributes` filter; Pattern Overrides now work on custom blocks. Registry `WP_Block_Bindings_Registry` is 6.5. | mcpwp doesn't manage bindings/pattern-overrides today. Note for a future "dynamic content" surface. | **Low** |
| A14 | **Attachment REST: filename/filesize** (new in 7.0) | `WP_REST_Attachments_Controller::get_attachment_filename()/get_attachment_filesize()` → new `filename`/`filesize` response fields. | Free metadata mcpwp's `/media` listing can pass through. Trivial. | **Low** |
| A15 | **Script Module i18n** (new in 7.0) | `wp_set_script_module_translations()`, `load_script_module_textdomain()`. | Irrelevant to mcpwp (no script modules shipped). Skip. | **Low (skip)** |

**Net:** Build now = **A1 (Abilities), A4 (Global Styles), A7 (Font Library)**. Backlog/ride-along = A2/A3 (AI Client + Connectors), A6 (theme.json schema awareness, rides A4), A8 (Icons). Explicitly **don't backlog**: A5 Interactivity (thin), A10 collab, A11 view transitions, A12 speculation (wrong version), A14/A15 (trivial/irrelevant).

---

## Part B — API surface from source

### B1. Global Styles (Job A)

**Read (no auth issues — server-side functions):**
```php
wp_get_global_styles( array $path = [], array $context = [] ): mixed
// $context: ['block_name'=>..., 'origin'=>'all'|'base', 'transforms'=>['resolve-variables']]
wp_get_global_settings( array $path = [], array $context = [] ): mixed   // palette, fontSizes, spacing presets
wp_get_global_stylesheet( array $types = [] ): string                    // compiled CSS
WP_Theme_JSON_Resolver::get_merged_data( 'custom'|'theme'|'base' ): WP_Theme_JSON
WP_Theme_JSON_Resolver::get_theme_data(): WP_Theme_JSON                  // theme defaults
WP_Theme_JSON_Resolver::get_user_data(): WP_Theme_JSON                   // user overrides only
```

**Write (user-level overrides, the safe layer):** user global styles live in a `wp_global_styles` CPT whose `post_content` is the theme.json document. The supported write path:
```php
// 1. Get (or create) the user global-styles post for the active theme:
$id = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();   // creates if missing
// 2. Read current user theme.json:
$post   = get_post( $id );
$json   = json_decode( $post->post_content, true );               // { version, styles, settings, ... }
// 3. Merge new tokens via the sanitizing data object (respects schema, strips invalid keys):
$data   = new WP_Theme_JSON_Data( $json, 'custom' );
$data   = $data->update_with( $new_partial );                     // deep-merges
$merged = $data->get_data();                                      // sanitized array
// 4. Persist + bust cache:
wp_update_post([ 'ID'=>$id, 'post_content'=> wp_json_encode( $merged ) ]);
WP_Theme_JSON_Resolver::clean_cached_data();
```
REST equivalent (auth'd, editor cap): `GET/PUT /wp/v2/global-styles/{id}` with body `{ "styles": {...}, "settings": {...} }`; `GET /wp/v2/global-styles/themes/{stylesheet}/variations` for theme-provided presets.

**theme.json v3 keys mcpwp must round-trip (7.0):** `settings.color.palette[]`, `settings.typography.fontSizes[]`/`fontFamilies[]`, `settings.spacing.spacingSizes[]`, **new:** `settings.dimensions.dimensionSizes[]`, `settings.dimensions.width|height`, `styles.typography.textIndent`, pseudo-class blocks (`styles.blocks.{name}.:hover`).

**Relation to existing mcpwp surfaces:**
- `wp_get_kit_css` / `wp_set_kit_css` → **Elementor** global kit (post meta `_elementor_page_settings.custom_css`). Page-builder scoped.
- `/blocks/design-system` (`wp_get_block_design_system`) → reads `WP_Block_Type_Registry` + patterns + `wp_get_theme()`; descriptive, read-only, no token write.
- **Gap → new tools:** neither reads/writes the *theme* global-styles token layer. That's what A4 adds. Decision rule for the agent: **Elementor site → kit-css; block theme → global-styles.** `wp_onboard`/`site-info` already exposes `elementor_layout_mode`; add a `design_system` discriminator (`elementor` | `block-theme`).

### B2. Interactivity API (Job B)

```php
WP_Interactivity_API::state( ?string $ns, ?array $state ): array
WP_Interactivity_API::config( string $ns, array $config = [] ): array
WP_Interactivity_API::process_directives( string $html ): string
WP_Interactivity_API::get_context( ?string $ns ): array
// Procedural wrappers: wp_interactivity_state(), wp_interactivity_config(),
//                      wp_interactivity_process_directives(), wp_interactivity_get_context()
```
Directives are HTML attributes: `data-wp-interactive`, `data-wp-context`, `data-wp-bind`, `data-wp-on`, **`data-wp-watch` (new 7.0)**. New JS `watch()` export; `state.navigation.hasStarted/hasFinished` deprecated.

**Honest fit assessment:** This API is for **plugin/theme authors writing interactive blocks in PHP+JS**. mcpwp is a content/design operator over REST — it does not register blocks or ship a `view.js`. There is **no first-class mcpwp tool** here that isn't a stretch. The only non-fabricated angle is a **read-only linter**: when mcpwp reads Gutenberg block markup, detect `data-wp-*` directives and flag obvious mistakes (e.g. `data-wp-on` without a matching action, deprecated `state.navigation`). That's a validation enhancement to `wp_validate_blocks`, not a new headline feature. **Recommendation: do not over-build. Ship only the lint check if/when block-authoring becomes a product surface.**

### B3. Abilities API (the strategic one — A1)

```php
wp_register_ability( string $name, array $args );  // on wp_abilities_api_init hook
//   $args: label, description, category, input_schema (JSON Schema),
//          output_schema, execute_callback, permission_callback, meta:['show_in_rest'=>true]
wp_get_ability( $name ): ?WP_Ability;  $ability->execute( $input );  // validates + perms + runs
WP_Abilities_Registry::get_instance();  // register/get/get_all/unregister
```
Live REST (auth'd): `GET /wp-abilities/v1/abilities`, `GET /abilities/{name}`, `POST /abilities/{name}/run`, `GET /categories`. Field Guide: a first-party MCP adapter exposes abilities as MCP tools + `mcp_exposed_abilities` filter — **but that adapter is not present in this WP 7.0 build's core or as a namespace.**

---

## Part C — Integration design (the worthwhile ones)

### C1 — Global Styles tools (A4 + A6) — effort **M**

**Files:**
- New: `mcpwp/includes/core/class-mcpwp-global-styles.php` (`Mcpwp_Global_Styles`, static read/write helpers).
- New: `mcpwp/includes/api/class-mcpwp-rest-global-styles.php` (`Mcpwp_REST_Global_Styles extends Mcpwp_REST_API`) — routes `/global-styles` (GET/PUT), `/global-styles/tokens` (GET), `/global-styles/revisions` (GET).
- Edit: `mcpwp/includes/mcp/traits/trait-mcpwp-*-tools-*.php` (or a new `trait-mcpwp-tools-global-styles.php`) — define tools.
- Edit: free/pro `get_tool_map()` — wire tool→route.
- Edit: `class-mcpwp-rest-site.php` onboard/site-info — add `design_system` discriminator.
- Register the REST controller in `class-mcpwp-loader.php` alongside the other REST controllers.

**PHP sketch (core helper):**
```php
class Mcpwp_Global_Styles {
    public static function supported(): bool { return function_exists( 'wp_get_global_styles' ); }

    public static function read_tokens(): array {
        return [
            'palette'      => wp_get_global_settings( [ 'color', 'palette' ] ),
            'font_sizes'   => wp_get_global_settings( [ 'typography', 'fontSizes' ] ),
            'font_families'=> wp_get_global_settings( [ 'typography', 'fontFamilies' ] ),
            'spacing'      => wp_get_global_settings( [ 'spacing', 'spacingSizes' ] ),
            'dimensions'   => wp_get_global_settings( [ 'dimensions', 'dimensionSizes' ] ), // 7.0
            'styles'       => wp_get_global_styles(),
            'stylesheet'   => wp_get_global_stylesheet( [ 'variables', 'presets' ] ),
        ];
    }

    /** Merge a partial theme.json (settings/styles) into USER global styles. Approval-gated. */
    public static function write( array $partial ): array|WP_Error {
        if ( ! self::supported() ) return new WP_Error( 'mcpwp_no_block_theme', 'Global Styles API unavailable', [ 'status' => 400 ] );
        $id   = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
        $post = get_post( $id );
        $json = json_decode( $post->post_content, true ) ?: [ 'version' => WP_Theme_JSON::LATEST_SCHEMA ];
        $data = ( new WP_Theme_JSON_Data( $json, 'custom' ) )->update_with( $partial );
        $merged = $data->get_data();
        $res = wp_update_post( [ 'ID' => $id, 'post_content' => wp_json_encode( $merged ) ], true );
        if ( is_wp_error( $res ) ) return $res;
        WP_Theme_JSON_Resolver::clean_cached_data();
        return [ 'updated' => true, 'post_id' => $id ];
    }
}
```

**New MCP tools** (follow `define_tool` + tool_map pattern):
| Tool | Method/route | Notes |
|---|---|---|
| `wp_get_global_styles` | GET `/global-styles` | full user+theme merged theme.json (read) |
| `wp_get_design_tokens` | GET `/global-styles/tokens` | palette/typography/spacing/dimensions, flattened for AI |
| `wp_set_design_tokens` | PUT `/global-styles` | write partial theme.json; **routes through approvals** like other destructive design ops |
| `wp_get_global_styles_revisions` | GET `/global-styles/revisions` | `/wp/v2/global-styles/{id}/revisions` passthrough for rollback |

**Risks:** (1) Only meaningful on block themes — gate with `Mcpwp_Global_Styles::supported()` and the `design_system` discriminator; on Elementor sites the tool should return a clear "use kit-css instead" hint. (2) Writing raw theme.json can break a site's look — **must go through the existing approval lifecycle** (`/approvals`) and be rollback-able (store prior `post_content`). (3) `WP_Theme_JSON_Data::update_with()` sanitizes/strips unknown keys — good (prevents garbage) but means the agent should read-back to confirm. (4) Version skew: guard every call with `function_exists()`/`class_exists()` so the plugin stays compatible with WP < 7.0.

### C2 — Abilities API bridge (A1) — effort **M/L**

**Goal:** register MCPWP's governed tools as core WP Abilities so the WP AI Client, wp-admin AI features, and any first-party MCP adapter can discover and run them — **through mcpwp's permission + approval layer**, not around it.

**Files:**
- New: `mcpwp/includes/core/class-mcpwp-abilities-bridge.php` (`Mcpwp_Abilities_Bridge`).
- Edit: `class-mcpwp-loader.php` — `add_action('wp_abilities_api_init', [...,'register'])` (guarded by `function_exists('wp_register_ability')`).

**PHP sketch:**
```php
class Mcpwp_Abilities_Bridge {
    public static function register(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) return;          // WP < 7.0
        $registry = ( new Mcpwp_MCP_Free_Tools() )->get_tools();           // + pro if active
        foreach ( $registry as $tool ) {
            wp_register_ability( 'mcpwp/' . ltrim( $tool['name'], 'wp_' ), [
                'label'            => $tool['name'],
                'description'      => $tool['description'],
                'category'         => $tool['annotations']['category'] ?? 'site',
                'input_schema'     => $tool['inputSchema'],
                'output_schema'    => [ 'type' => 'object' ],
                'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
                'execute_callback' => function( $input ) use ( $tool ) {
                    // Reuse the SAME dispatch the /mcp endpoint uses → keeps governance,
                    // capability gating, category toggles, approvals, and audit log intact.
                    return Mcpwp_REST_MCP::dispatch_tool( $tool['name'], $input );
                },
                'meta'             => [ 'show_in_rest' => true ],
            ] );
        }
    }
}
```
(Requires extracting the body of `handle_tools_call`'s dispatch into a reusable static `Mcpwp_REST_MCP::dispatch_tool($name,$args)` — small refactor, also de-dupes the MCP path.)

**Why high value:** as WP-native and host-platform agents start consuming the Abilities registry, a site with mcpwp installed automatically advertises 240 governed capabilities to them — distribution + lock-in, and it future-proofs against core's own MCP adapter by making mcpwp the *richest* ability provider on the box. **Risks:** (1) capability mapping — abilities have one `permission_callback`; mcpwp's per-category role scoping must be honored inside `execute_callback`, not just the coarse cap. (2) Don't double-expose write tools without the approval gate. (3) Opt-in setting (some hosts won't want the surface broadcast) — add a Settings toggle, default off for free / on for pro, mirroring analytics.

### C3 — Font Library tools (A7) — effort **S/M**

**Files:** new `class-mcpwp-rest-fonts.php` (thin passthrough to `/wp/v2/font-collections` + `/font-families`), tool defs, tool_map.

**New tools:** `wp_list_font_collections` (GET `/wp/v2/font-collections`), `wp_list_installed_fonts` (GET `/wp/v2/font-families`), `wp_install_font_family` (POST `/wp/v2/font-families`, **approval-gated**), `wp_set_brand_font` (install + set as `fontFamily` token via C1's `write()`).

**Risks:** font installation downloads/writes files → approval-gate + cap check (`edit_theme_options`). Works on classic themes now (7.0) — note in tool description. Keep as **pro** (brand-typography is a paid-tier value).

---

## Part D — Ready-to-file GitHub issues

> Repo: the MCPWP tenant repo (board #2). One issue per worthwhile item. A2/A3/A8 folded as stretch tasks where cheap; A5/A10/A11/A12/A14/A15 intentionally omitted (see audit).

---

### Issue 1 — Global Styles / design-token tools for block themes (WP 7.0)

**Labels:** `enhancement`, `wp7`, `design`, `pro`
**Title:** Add Global Styles (theme.json) read/write MCP tools for block themes

**Description**
WP 7.0 makes `wp_get_global_styles()` / `wp_get_global_settings()` / `wp_get_global_stylesheet()` first-class and serves full styles to classic themes too. mcpwp currently manages design tokens only for Elementor (kit-css) and only *describes* block design (`/blocks/design-system`). On block-theme sites the agent has no way to read or set the brand palette/typography/spacing at the theme level. Add a Global Styles surface that mirrors kit-css for block themes, writing to the safe **user** global-styles layer (`wp_global_styles` CPT) via `WP_Theme_JSON_Data::update_with()`, round-tripping the new 7.0 theme.json keys (`dimensions.dimensionSizes`, `typography.textIndent`, pseudo-class block states).

**Acceptance criteria**
- [ ] `wp_get_global_styles`, `wp_get_design_tokens`, `wp_set_design_tokens`, `wp_get_global_styles_revisions` tools exist and appear in `wp_onboard` for block-theme sites.
- [ ] On Elementor/classic non-block sites the write tool returns a clear hint to use `wp_set_kit_css` instead (no error spam); guarded by `function_exists('wp_get_global_styles')`.
- [ ] `wp_set_design_tokens` routes through the existing approval lifecycle and is rollback-able (prior `post_content` snapshotted).
- [ ] `wp_onboard`/`site-info` expose a `design_system` discriminator (`elementor` | `block-theme` | `classic`).
- [ ] New 7.0 theme.json keys survive a read→write→read round-trip (regression test).
- [ ] Plugin still loads/activates cleanly on WP 6.x (no fatal from missing functions).

**Tasks**
- [ ] `class-mcpwp-global-styles.php` (read_tokens/write helpers, capability guard).
- [ ] `class-mcpwp-rest-global-styles.php` + register in loader.
- [ ] Tool defs + tool_map entries (pro tier).
- [ ] Approval integration + rollback snapshot.
- [ ] Onboard/site-info discriminator.
- [ ] phpunit: round-trip + WP6 no-fatal guard.
- [ ] Version bump (mcpwp.php / readme.txt / version.json), CHANGELOG.

---

### Issue 2 — Register MCPWP tools as core WP Abilities (WP 7.0 Abilities API)

**Labels:** `enhancement`, `wp7`, `platform`, `strategic`
**Title:** Bridge MCPWP tools into the WordPress 7.0 Abilities registry

**Description**
WP 7.0 ships the Abilities API (`wp_register_ability`, `/wp-abilities/v1`) and an AI Client; the Field Guide describes a first-party MCP adapter that turns abilities into MCP tools (not yet in this core build). Register MCPWP's ~240 governed tools as core Abilities so WP-native AI features, the AI Client, and any future core MCP adapter discover them — while execution still flows through MCPWP's capability gating, category toggles, approvals, and audit log. This makes a site-with-mcpwp the richest ability provider on the box and future-proofs against core's own adapter.

**Acceptance criteria**
- [ ] On `wp_abilities_api_init`, MCPWP registers its tools as `mcpwp/*` abilities with correct `input_schema` from each tool's `inputSchema`.
- [ ] Ability execution reuses the exact `/mcp` dispatch path (governance preserved) — no second, ungoverned code path.
- [ ] Per-category role scoping is enforced inside `execute_callback`, not just the coarse `permission_callback`.
- [ ] Write/destructive abilities still honor the approval gate.
- [ ] Settings toggle "Expose tools as WordPress Abilities" — default off (free) / on (pro); guarded by `function_exists('wp_register_ability')`.
- [ ] No fatal on WP 6.x.
- [ ] Adversarial review of the bridge (sensitive surface: auth + external discovery) runs in parallel with correctness review before GREEN.

**Tasks**
- [ ] Refactor `handle_tools_call` dispatch into reusable `Mcpwp_REST_MCP::dispatch_tool($name,$args)`.
- [ ] `class-mcpwp-abilities-bridge.php` registering abilities from the tool registry.
- [ ] Capability/category enforcement inside execute_callback.
- [ ] Settings toggle + loader hook (guarded).
- [ ] phpunit: ability run == /mcp run for a sample tool; perms denied for out-of-scope category.
- [ ] Security review sign-off.
- [ ] Version bump + CHANGELOG.

---

### Issue 3 — Font Library / brand-typography tools (WP 7.0)

**Labels:** `enhancement`, `wp7`, `design`, `pro`
**Title:** Add Font Library MCP tools (list/install/set brand font), classic + block themes

**Description**
WP 7.0 makes the Font Library work on classic themes as well as block themes, exposed via `/wp/v2/font-collections` and `/wp/v2/font-families`. Add tools so the agent can browse available font collections, list installed families, install a Google-font family, and set it as the brand typeface — completing the design-coherence loop alongside Issue 1.

**Acceptance criteria**
- [ ] `wp_list_font_collections`, `wp_list_installed_fonts`, `wp_install_font_family`, `wp_set_brand_font` tools exist (pro tier).
- [ ] `wp_install_font_family` and `wp_set_brand_font` are approval-gated and cap-checked (`edit_theme_options`).
- [ ] `wp_set_brand_font` installs the family then writes it as the `fontFamily` token (reuses Issue 1's global-styles write on block themes; documents the classic-theme path).
- [ ] Guarded by route availability; clean no-op message on WP < 7.0.
- [ ] Tool descriptions note classic-theme support is 7.0+.

**Tasks**
- [ ] `class-mcpwp-rest-fonts.php` passthrough controller + loader registration.
- [ ] Tool defs + tool_map (pro).
- [ ] Approval + capability gating.
- [ ] Integration with Issue 1 token-write for `wp_set_brand_font`.
- [ ] phpunit: list works unauth-guarded; install gated.
- [ ] Version bump + CHANGELOG.

---

### Issue 4 (stretch) — Surface core AI Connectors + AI Client fallback

**Labels:** `enhancement`, `wp7`, `integrations`
**Title:** Read host-level AI Connectors and fall back to WP AI Client

**Description**
WP 7.0 adds the Connectors API (host-level AI provider creds) and the WP AI Client (`wp_ai_client_prompt()`). When MCPWP has no provider key configured but the host has a core Connector, surface that in `/integrations/status` and route alt-text/excerpt/image generation through `wp_ai_client_prompt()` as a fallback — fewer "no key configured" dead-ends.

**Acceptance criteria**
- [ ] `/integrations/status` and `wp_onboard` report host connectors via `wp_get_connectors()` (read-only; never write host creds).
- [ ] Alt-text / excerpt generation falls back to `wp_ai_client_prompt()` when `wp_supports_ai()` is true and no mcpwp provider key exists.
- [ ] All calls guarded with `function_exists()`; no behavior change on WP 6.x or when mcpwp keys are present.

**Tasks**
- [ ] Connector read-through in integrations status + onboard.
- [ ] AI Client fallback in alt-text/excerpt handlers behind a capability check.
- [ ] phpunit: fallback only triggers when no mcpwp key + `wp_supports_ai()`.
- [ ] Version bump + CHANGELOG.

---

## Honest "don't backlog" list (so it doesn't become noise)
- **Interactivity API (A5):** no genuine operator-tool fit; at most a read-only block linter inside `wp_validate_blocks` — ship only if block-authoring becomes a product. Do **not** create a headline "Interactivity tools" issue.
- **Real-time collaboration (A10):** UI cut from 7.0; mcpwp isn't a co-editing surface.
- **View Transitions (A11), Script Module i18n (A15), attachment filename/filesize (A14):** trivial/cosmetic/irrelevant.
- **Speculative loading (A12):** a 6.8 feature, not WP7 — out of scope for this sprint (could be a `/signals` line item separately).
- **PHP-only blocks (A9) & custom-block bindings (A13):** real but strategic/future; depend on mcpwp adding a block-authoring surface first.
