=== MCPWP ===
Contributors: mumega
Donate link: https://mcpwp.net/
Tags: ai, claude, mcp, elementor, api
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.8.45
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress to Claude and other MCP clients for safe AI-assisted posts, pages, media, drafts, and Elementor basics.

== Description ==

MCPWP connects your WordPress site to AI assistants like Claude using the Model Context Protocol (MCP). It gives approved AI clients a scoped, auditable way to read site context and help manage core WordPress content through natural language.

The WordPress.org package is the free core: API keys, scoped access, activity logging, posts, pages, media, drafts, menus, site context, and basic Elementor operations when Elementor is installed.

= Key Features =

* **Secure MCP Access** - Connect MCP-capable clients with hashed API keys and scoped permissions
* **Content Management** - Create, edit, and delete posts and pages
* **Media Handling** - Upload files or import media from URLs
* **Draft Management** - List and clean up drafts
* **Menus and Site Context** - Read site structure and basic navigation data
* **Basic Elementor Support** - Read and update Elementor page data when Elementor is installed
* **Activity Log** - Audit API and MCP activity from wp-admin
* **Tool Controls** - Disable tool categories that should not be exposed to agents
* **MCP Compatible** - Works with Claude Code, Claude Desktop, and other MCP-compatible clients

= How It Works =

1. Install and activate the plugin
2. Copy your API key from MCPWP in the admin menu
3. Configure your MCP server with the API key
4. Review enabled tool categories and scopes
5. Start creating drafts and managing WordPress content with natural language

= Example Commands =

* "Create a blog post about summer recipes"
* "Create a draft landing page for our spring campaign"
* "List recent drafts and delete the unused ones"
* "Upload this image and set it as the featured image for post 123"
* "Read the site context before updating the About page"

== Installation ==

= From WordPress Admin =

1. Go to Plugins → Add New
2. Search for "MCPWP"
3. Click Install Now, then Activate
4. Go to MCPWP in the admin menu to get your API key

= Manual Installation =

1. Download the plugin ZIP file
2. Go to Plugins → Add New → Upload Plugin
3. Select the ZIP file and click Install Now
4. Activate the plugin
5. Go to MCPWP in the admin menu to get your API key

= MCP Server Setup =

Add to your `~/.claude.json`:

`{
  "mcpServers": {
    "mumega-mcp": {
      "command": "node",
      "args": ["/path/to/mcp-server/dist/index.js"],
      "env": {
        "WP_URL": "https://yoursite.com",
        "WP_API_KEY": "spai_your_api_key_here"
      }
    }
  }
}`

== Frequently Asked Questions ==

= What is MCP? =

Model Context Protocol (MCP) is an open protocol that enables AI assistants like Claude to interact with external tools and services. MCPWP exposes your WordPress site as an MCP-compatible tool.

= Is this secure? =

Yes. All requests require a unique API key. Keys are hashed using WordPress password hashing (not stored in plain text). A dedicated service account with limited capabilities handles API requests. Activity logging tracks all API usage for auditing.

= Does it work with any AI? =

MCPWP works with any AI assistant that supports the MCP protocol. Currently, this includes Claude Code and Claude Desktop. More integrations are planned.

= Do I need coding skills? =

No. Once configured, you control WordPress through natural language. The AI handles all the technical details.

= What about Elementor? =

Basic Elementor page read/write support is included when Elementor is installed. Advanced Elementor Pro and theme-builder workflows are not part of the WordPress.org free package.

= Who is this for? =

MCPWP is best for site owners, developers, agencies, and content teams that want AI assistants to help operate WordPress while keeping API keys, scopes, and activity logs under site-owner control.

= Does it support Elementor 4? =

Yes. MCPWP includes fallback handling for Elementor page data saves.

= Can I use this on multiple sites? =

Each site needs its own plugin installation and API key.

== Privacy ==

MCPWP can send anonymous usage data from your WordPress server to PostHog when the "Share anonymous tool-usage data" setting is enabled in WP Admin > MCPWP > Settings.

**What is collected:** Names of MCP tools called, whether each call succeeded or failed, execution duration, plugin version, WordPress version, and PHP major/minor version.

**What is NOT collected:** Post content, page content, user data, API keys, site URL, or any personally identifiable information.

**Identifier:** Each site is assigned a random site UUID (e.g. mcpwp-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx). This UUID is stored in wp_options and cannot be traced back to a domain name or individual.

**Where data is sent:** PostHog, Inc. (posthog.com). Your server's outbound IP address is transmitted as part of the HTTP request to PostHog.

**Default state:** Disabled for free tier (requires opt-in). Enabled by default for paid subscribers (can be disabled at any time).

**How to opt out:** Uncheck "Share anonymous tool-usage data" under WP Admin > MCPWP > Settings.

== Screenshots ==

1. Control Room — supervised approvals, SEO findings, rollback-ready changes, and recommended next actions
2. Setup — MCP endpoint, client configuration, and role-scoped API key management
3. Tools — category controls for agent capability discovery and execution enforcement
4. Integrations and Chat — connected services plus safety-first agent workflow

== Changelog ==

= 2.8.45 =
* New: Server-side MCP tool analytics — when enabled, sends anonymous tool call data (tool name, success/failure, duration) to PostHog. Opt-in for free tier, opt-out for paid. No site content or PII is ever collected.
* New: Site support UUID displayed in WP Admin > Settings with a Copy button. Share this ID when contacting MCPWP support.
* New: PostHog configurable via WP Admin > Integrations — token and host are no longer hardcoded.
* Docs: Added == Privacy == section to readme.txt per WP.org plugin guidelines.

= 2.8.43 =
* Fix: wp_set_template_conditions now accepts both object form ({type, name, sub_name, sub_id}) and positional array form (["include", "singular", "product"]) — positional arrays were previously flattened to include>general due to wrong key lookup (#210).
* Fix: wp_get_elementor_globals now returns both system_typography and custom_typography under fonts, and system_colors + custom_colors under colors — previously only custom_typography was returned, hiding Elementor's 4 reserved defaults (#253).
* Fix: wp_upload_media_from_url tool definition now exposes filename, title, and alt parameters — the underlying PHP already supported them but they were missing from tools/list (#252).
* Fix: Elementor v4 atomic elements (e-heading, e-paragraph, e-button, etc.) now skip settings key validation — they use props/styles, not classic settings, so the validator was incorrectly flagging them (#211).
* Fix: Form widget static schema now includes button styling keys (button_color, button_background_color, button_border_color, button_hover_color, button_background_hover_color) — these are valid Elementor Pro form button controls that were missing from the validator allow-list, generating false-positive warnings (#250, #255).
* Fix: elementor_data_base64 JSON parse error now includes decoded_length in the response to help diagnose LLM-side truncation vs transport issues (#254).
* Docs: Added docs/FREE_PRO_SPLIT.md with full free/pro tool split, WP.org build constraints, and gating pattern (#258).

= 2.8.42 =
* Fix: Theme handler ($supported_themes) — removed corrupted duplicate 'flavor'/'flavflavor' entries and 'oceanwp' entry with wrong option_key. Only Astra, GeneratePress, and Kadence remain with accurate settings_type. Applied to both plugin copies.
* Fix: Integrations admin page — save/remove/test connection buttons now have JS handlers (were previously unimplemented). Uses correct spaiIntegrations nonce and AJAX action names.
* Fix: version.json download_url was pointing to a 404 URL; corrected to /spai-updates/mumega-site-pilot-ai-latest.zip.
* Polish: Removed inline styles from integrations display and chat display; extracted to CSS classes (spai-info-panel, spai-action-row, spai-field-row, is-hidden, etc.).

= 2.8.41 =
* Fix: Scoped API key scope enforcement — submitted scopes are now respected with a role-based ceiling. Non-admin roles are capped at read+write and cannot claim admin scope regardless of input (issue #333).
* Fix: SEO MCP tool contract drift — wp_set_seo now accepts normalized title/description/canonical_url fields alongside seo_title/seo_description aliases; wp_bulk_seo accepts items as alias for updates; Google Indexing tools now correctly registered in the Pro tool registry (issue #350).
* New: wp_create_elementor_custom_code, wp_get_elementor_custom_code, wp_update_elementor_custom_code — first-class MCP tools for Elementor Pro custom code snippets with injection location (head/body_start/body_end), conditions, raw code preservation, and dry_run support (issue #346).
* Tests: wp_list_pages pagination contract locked with RestPagesTest; extended PHPUnit bootstrap with WP_Query stub, wp_insert_post, and post_type_exists helpers (issue #353).

= 2.8.40 =
* Fix: wp_validate_seo_readiness transport deserialize error on large sites — reduced content graph query from 500 to 100 posts, eliminating memory/timeout failures that produced malformed JSON-RPC responses (issue #337).
* Fix: MCP tools/call response now handles json_encode failure gracefully instead of sending null in the text field, which caused client-side deserialize errors on non-UTF8 content.

= 2.8.39 =
* Fix: Freemius "free" plan slug no longer causes plan:free/pro_active:true contradiction when pro is active via Lemon Squeezy or developer constant (issue #319).
* Fix: Disabled tool categories now enforced at raw REST endpoint level — callers can no longer bypass category toggles by hitting /site-pilot-ai/v1/* routes directly (issue #328).

= 2.8.38 =
* New: wp_update_media — update alt text, title, caption, or description on an existing media attachment without re-uploading (issue #338).

= 2.8.37 =
* Fix: wp_seo_audit_site and wp_validate_seo_readiness PHP fatal — extract_internal_links_from_content() now defined in base REST class, accessible to all subclasses including the SEO audit controller (issue #336, #337).
* Fix: Disabled tool categories now enforced at execution time, not just discovery — calling a tool in a disabled category returns a clear error instead of executing silently (issue #328).
* Fix: Elementor custom-code tools (wp_list_elementor_custom_code, etc.) now require Elementor Pro and are hidden from tools/list when Elementor Pro is not installed, preventing ghost-route 404 errors (issue #335).

= 2.8.36 =
* Fix: wp_bulk_find_replace now operates on decoded element tree instead of raw JSON string — prevents JSON corruption when replacing URL substrings or text that appears inside serialized values.
* Fix: wp_setup_menu with overwrite=true now clears existing items before repopulating — prevents duplicate accumulation on repeat calls.
* Fix: wp_update_page now returns slug_warning when WordPress silently rewrites a requested slug due to collision with auto-drafts or trashed posts.
* Fix: SVG upload error message now explains the XSS risk and suggests PNG/WebP alternatives.
* Fix: wp_set_custom_css now returns structured alternatives array when CSS fails loopback verification, including Elementor Custom Code instructions.
* New: wp_get_kit_css and wp_set_kit_css — free-tier tools to read/write Elementor kit global CSS. More reliable than wp_set_custom_css on child-theme sites. Works without Elementor Pro.

= 2.8.35 =
* Fix: Admin role now automatically grants full scopes (read+write+admin) — no longer requires scope checkboxes to be manually set.
* UX: Scopes section hidden for preset roles; custom role shows scope controls.

= 2.8.34 =
* Security: Block SVG uploads via b64 endpoint — SVGs can carry stored XSS via script tags; use WP media library with sanitizer plugin instead.

= 2.8.33 =
* Security: Restore least-privilege default for API key scopes — omitting scopes now defaults to read-only, not full access.
* Security: MIME type validation for base64 uploads now verifies caller-supplied mime_type matches detected content type; mismatches are rejected.

= 2.8.32 =
* Fix: API key creation no longer falls back to read-only scope when scopes are omitted — defaults to full access (read, write, admin).
* Fix: wp_upload_media_b64 now accepts mime_type parameter; caller-supplied mime type takes priority over filename detection.
* Fix: REST schema for wp_create_api_key scopes param now documents default (all scopes) and enum values.

= 2.8.31 =
* Fix: Harden Freemius update checks so admin update pages reliably refresh the update cache.
* Fix: Avoid Freemius SDK host warnings during WP-CLI activation and update checks.

= 2.8.30 =
* New: Add read-only WooCommerce SEO intelligence report for product content, commerce evidence, and search performance.
* New: Add REST and MCP product SEO report with approval-safe next steps.

= 2.8.29 =
* New: Add provider-neutral Search Console, Bing, and manual search performance import storage.
* New: Add REST and MCP search trend report with top queries, top URLs, daily aggregates, and import history.

= 2.8.28 =
* New: Add approval-safe SEO autofix planner for stored audit issues.
* New: Add REST and MCP plan output that maps issues to safe tools, playbooks, approval gates, and manual review boundaries.

= 2.8.27 =
* New: Add content coherence score across context, graph, content, SEO, approvals, and events.
* New: Add REST and MCP report with prioritized recommendations mapped to deterministic playbooks.

= 2.8.26 =
* New: Add deterministic agent playbook contracts for Gutenberg, SEO, internal links, and rollback workflows.
* New: Add REST and MCP access to playbook gates, stop conditions, and rollback paths.

= 2.8.25 =
* New: Add Control Room event inbox with event type and risk filters.
* New: Add escalation rules for high-risk, failing SEO, and approval lifecycle events.

= 2.8.24 =
* New: Add compact site-state snapshot for deterministic agent starts across content, graph, SEO, approvals, events, and capabilities.
* New: Add MCP and REST access for agents to read recommended next actions before mutating WordPress content.

= 2.8.23 =
* New: Add AI-first event store and WordPress hooks for approval lifecycle and stored SEO audit events.
* New: Add event schema and recent event listing endpoints for agents and webhook subscribers.

= 2.8.22 =
* New: Add Control Room actions for approving, rejecting, applying, and rolling back supervised agent changes.
* New: Add a one-click stored SEO audit action and status, severity, and category filters for stored SEO issues.

= 2.8.21 =
* New: Add state-driven Control Room visuals for healthy, warning, critical, empty, and rollback-ready dashboard states.

= 2.8.20 =
* New: Add a WordPress admin Control Room for approvals, stored SEO issues, rollback-ready changes, recommendations, and recent agent activity.
* New: Document Search Console, Bing, Apify, keyword inventory, and WooCommerce SEO intelligence backlog direction.

= 2.8.19 =
* New: Add optional stored SEO audit runs and normalized issue records.
* New: Add stored SEO issue listing with status, severity, category, post, and run filters.

= 2.8.18 =
* New: Add read-only content quality and AI-search citation readiness audit for posts and pages.
* New: Check answer depth, summaries, FAQ/question coverage, entity-like names, freshness, trust signals, and reference hints.

= 2.8.17 =
* New: Add read-only SEO site audit summary for posts and pages.
* New: Aggregate readiness, structured data, and media SEO issues into prioritized URL-level recommendations.

= 2.8.16 =
* New: Add read-only media SEO audit for posts and pages.
* New: Check featured images, content images, alt text, filenames, dimensions, file size, lazy-loading hints, and duplicate image use.

= 2.8.15 =
* New: Add read-only structured data inventory and validation for posts and pages.
* New: Detect JSON-LD, microdata, schema.org hints, schema parse errors, missing schema shape, and page-appropriate schema recommendations.

= 2.8.14 =
* New: Add read-only SEO pre-publish readiness validation for WordPress content.
* New: Check title, slug, H1, heading order, meta description, image alt text, internal links, indexability, canonical, robots, sitemap, and schema hints.

= 2.8.13 =
* New: Add weighted content graph signals for SEO and internal linking workflows.
* New: Content graph nodes now include menu depth, freshness score, hub score, orphan severity, and PageRank-style rank score.
* New: Content graph now includes taxonomy edges for shared topic relationships.

= 2.8.12 =
* New: Add read-only internal link validation for SEO and agent publishing workflows.
* New: Detect self-links, duplicate internal targets, weak anchors, missing targets, unpublished targets, and non-canonical internal URLs.

= 2.8.11 =
* New: Add approval-first internal link application from content graph targets.
* New: Internal link application builds native Gutenberg link paragraphs and refuses invented or duplicate target links.

= 2.8.10 =
* New: Add internal link suggestions from the content graph for SEO-aware agent workflows.
* New: Suggestions use existing site URLs only and return approval-ready link diffs without mutating content.

= 2.8.9 =
* New: Add section-level Gutenberg patching by block path, anchor, or heading text.
* New: Section patches create approval requests by default so agents do not rewrite full pages directly.

= 2.8.8 =
* New: Add approval, diff, apply, and rollback workflow for agent mutations.
* New: Allow `wp_set_blocks` to create a pending approval request instead of saving immediately.
* New: Add MCP tools to list, inspect, approve, reject, apply, and roll back approval requests.

= 2.8.7 =
* New: Add Gutenberg block safety validation for agent-generated content.
* New: Add a read-only internal content graph for internal link and orphan-page workflows.
* Fix: Reject unsafe block saves by default unless restricted output is explicitly approved.
* Fix: Stabilize MCP endpoint buffering and endpoint test coverage.

= 2.8.6 =
* Fix: Rename public display brand to MCPWP for WordPress.org packaging.
* Docs: Keep technical identifiers documented as stable while updating user-facing naming.

= 2.8.5 =
* Fix: Align text domain and WordPress.org package slug with the assigned mumega-mcp slug.
* Fix: Build the WordPress.org ZIP as the free package.
* Fix: Exclude Freemius, the legacy updater, and Pro modules from the WordPress.org package.
* Fix: Disable Pro MCP exposure in WordPress.org builds.
* Docs: Update readme scope for the free WordPress.org package.

= 2.8.3 =
* Fix: Resolve Plugin Check errors for WordPress.org submission.

= 2.8.2 =
* Fix: API key creation form now defaults all scopes checked.

= 2.8.1 =
* Fix: Guard custom CSS, custom JavaScript, and Elementor custom-code endpoints for WordPress.org builds.
* Fix: Replace deprecated utf8_encode usage.
* Docs: Add external service disclosures required for WordPress.org review.

= 2.8.0 =
* New: Chat tab in wp-admin for authenticated site operations.
* Fix: Elementor HTML rendering cache flush after saves.

= 2.5.0 =
* New: Admin navigation for setup, integrations, tools, settings, and activity log.
* New: API key setup flow for MCP clients.

= 2.4.1 =
* Fix: Avoid fatal error when updater file is excluded from WordPress.org build.

= 2.4.0 =
* Rename plugin package for WordPress.org slug generation.
* Remove self-updater from WordPress.org build.
* Fix SQL escaping in activity log for WordPress.org scanner.

== Upgrade Notice ==

= 2.8.31 =
Improves Freemius update checks and removes WP-CLI host warnings during activation and update refreshes.

= 2.8.30 =
Adds a read-only WooCommerce SEO report for product content quality, commerce signals, and search evidence.

= 2.8.29 =
Adds read-only search performance imports and trend reporting for Search Console, Bing, and manual exports.

= 2.8.28 =
Adds a read-only SEO autofix plan so agents can prepare safe fixes without silently mutating content or metadata.

= 2.8.27 =
Adds a read-only content coherence score so agents and humans can prioritize focused site improvements.

= 2.8.26 =
Adds deterministic playbook contracts so agents can follow safe tool order, validation gates, approval gates, and rollback paths.

= 2.8.25 =
Adds an event inbox in the Control Room so humans can see and filter recent agent-relevant events.

= 2.8.24 =
Adds a coherent site-state snapshot so agents can read the whole WordPress system before choosing a playbook or proposing changes.

= 2.8.23 =
Adds normalized event hooks and recent event history so external agents and automations can react to supervised WordPress state changes.

= 2.8.22 =
Adds actionable Control Room workflows for approvals, rollback, stored SEO audits, and filtered SEO issue review.

= 2.8.21 =
Adds state-aware Control Room visuals so dashboard cards and issue rows adapt to current approval and SEO status.

= 2.8.20 =
Adds the first human Control Room screen for supervising agent work and SEO issue follow-up.

= 2.8.19 =
Adds stored SEO audit issues so agents can track open and resolved findings over time.

= 2.8.18 =
Adds content quality and citation-readiness checks for AI/search-oriented publishing.

= 2.8.17 =
Adds a site-level SEO audit summary so agents can prioritize URLs and fixes.

= 2.8.16 =
Adds media SEO checks so agents can catch image issues before publishing.

= 2.8.15 =
Adds structured data validation so agents can inspect schema safely before publishing.

= 2.8.14 =
Adds SEO pre-publish readiness checks for safer agent publishing workflows.

= 2.8.13 =
Adds weighted content graph signals for better internal linking and SEO workflows.

= 2.8.12 =
Adds internal link validation for safer SEO and publishing workflows.

= 2.8.11 =
Adds approval-first internal link application using existing content graph targets.

= 2.8.10 =
Adds graph-based internal link suggestions for safer SEO workflows.

= 2.8.9 =
Adds safer section-level Gutenberg patching with approval-first behavior.

= 2.8.8 =
Adds approval requests with apply and rollback support for safer AI-assisted WordPress editing.

= 2.8.7 =
Adds Gutenberg safety checks and a read-only content graph for safer AI-assisted WordPress editing.

= 2.8.6 =
Public display brand is now MCPWP. Slug, text domain, and REST namespace remain stable.

== Disclaimer ==

This plugin is provided "as is" without warranty of any kind, express or implied. Use at your own risk. The authors are not liable for any damages, data loss, or issues arising from the use of this plugin. This plugin modifies WordPress content (posts, pages, Elementor data, media, settings) through its MCP and REST API endpoints — always maintain backups before making changes via AI assistants. By using this plugin you accept full responsibility for changes made through its API.

== Privacy Policy ==

MCPWP stores API keys, settings, and activity logs locally in WordPress. The plugin does not transmit site content to external services unless you configure an optional integration or ask an AI client connected to your site to send that data.

== External Services ==

This plugin may connect to the following external services depending on your configuration:

= WordPress.com mShots =
Used as a fallback for generating website screenshot thumbnails when no Cloudflare Browser Rendering worker is configured.
* Data sent: The URL of the page to screenshot
* When: When the wp_screenshot_url MCP tool is called and no custom screenshot worker is configured
* Service: https://s0.wp.com/mshots/v1/
* Privacy Policy: https://automattic.com/privacy/

= Feedback Relay (optional) =
When a user submits feedback via the wp_submit_feedback tool, the feedback is relayed to a central endpoint so plugin developers receive bug reports. This can be disabled by defining `SPAI_DISABLE_FEEDBACK_RELAY` as true.
* Data sent: Feedback text, site URL, site name, plugin version
* When: Only when the wp_submit_feedback MCP tool is explicitly called
* Endpoint: https://mcpwp.net/wp-json/site-pilot-ai/v1/feedback/relay
* Privacy Policy: https://mcpwp.net/privacy

= GitHub API (optional) =
If configured in settings, feedback can be automatically posted as GitHub issues.
* Data sent: Feedback text, site name, plugin version
* When: Only when GitHub integration is configured and feedback is submitted
* Service: https://api.github.com/
* Privacy Policy: https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement

= OpenAI API (optional) =
Used for AI image generation (wp_generate_image, wp_generate_featured_image) and alt text generation when configured by the user.
* Data sent: Text prompts or image URLs for processing
* When: Only when the user has configured an OpenAI API key and explicitly calls AI generation tools
* Service: https://api.openai.com/
* Terms of Service: https://openai.com/policies/terms-of-use
* Privacy Policy: https://openai.com/policies/privacy-policy

= Google Gemini API (optional) =
Used as an alternative AI provider for image description and text generation when configured by the user.
* Data sent: Text prompts or image URLs for processing
* When: Only when the user has configured a Gemini API key and explicitly calls AI generation tools
* Service: https://generativelanguage.googleapis.com/
* Terms of Service: https://ai.google.dev/gemini-api/terms
* Privacy Policy: https://policies.google.com/privacy

= ElevenLabs API (optional) =
Used for text-to-speech generation when configured by the user.
* Data sent: Text content to convert to speech
* When: Only when the user has configured an ElevenLabs API key and calls the wp_text_to_speech tool
* Service: https://api.elevenlabs.io/
* Terms of Service: https://elevenlabs.io/terms-of-use
* Privacy Policy: https://elevenlabs.io/privacy-policy

= Pexels API (optional) =
Used for stock photo search and download when configured by the user.
* Data sent: Search queries for stock photos
* When: Only when the user has configured a Pexels API key and calls wp_search_stock_photos or wp_download_stock_photo
* Service: https://api.pexels.com/
* Terms of Service: https://www.pexels.com/api/documentation/#guidelines
* Privacy Policy: https://www.pexels.com/privacy-policy/

= Figma API (optional) =
Used to import design files, extract colors, typography, and component data from Figma when configured by the user. Supports both personal access tokens and OAuth 2.0.
* Data sent: Figma file keys and node IDs to retrieve design data; no content from your WordPress site is sent to Figma
* When: Only when the user has configured a Figma access token and calls wp_get_figma_file, wp_get_figma_node, or uses the Figma OAuth integration
* OAuth authorization: https://www.figma.com/oauth
* OAuth token exchange: https://api.figma.com/v1/oauth/token
* API service: https://api.figma.com/v1
* Terms of Service: https://www.figma.com/tos/
* Privacy Policy: https://www.figma.com/privacy/

= LottieFiles (optional) =
Used when the user sets an external Lottie animation URL on a Lottie widget. The browser (not the server) fetches the animation JSON file directly from the URL provided by the user.
* Data sent: HTTP request from the visitor's browser to the Lottie JSON URL
* When: Only when a Lottie widget is configured with an external source URL (e.g. https://assets.lottiefiles.com/...)
* Service: https://lottiefiles.com/
* Privacy Policy: https://lottiefiles.com/page/privacy-policy

= Google Indexing API (optional) =
Used to submit URLs to Google for indexing when configured by the user with a Google service account.
* Data sent: Page URLs from your WordPress site for indexing notification
* When: Only when the user has configured a Google service account JSON and calls wp_submit_to_google_index or wp_google_index_status
* Service: https://indexing.googleapis.com/v3/urlNotifications
* Required OAuth scope: https://www.googleapis.com/auth/indexing
* Terms of Service: https://developers.google.com/terms/
* Privacy Policy: https://policies.google.com/privacy

= Cloudflare Workers AI (optional) =
Powers the built-in Chat assistant when no OpenAI key is configured.
* Data sent: Chat messages, site context (name, URL, page titles)
* When: Only when the Chat tab is used and no OpenAI key is available
* Service: https://api.cloudflare.com/client/v4/accounts/{id}/ai/
* Terms of Service: https://www.cloudflare.com/terms/
* Privacy Policy: https://www.cloudflare.com/privacypolicy/

== Support ==

* Documentation: [MCPWP docs](https://mcpwp.net/docs/)
* Support Forum: [wordpress.org/support/plugin/mumega-mcp](https://wordpress.org/support/plugin/mumega-mcp)
* GitHub: [github.com/Mumega-com/mcp-for-wp](https://github.com/Mumega-com/mcp-for-wp)
