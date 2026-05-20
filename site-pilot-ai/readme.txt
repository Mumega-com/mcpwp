=== Mumega MCP ===
Contributors: mumega
Donate link: https://sitepilotai.mumega.com/
Tags: ai, claude, mcp, elementor, api
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.8.22
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress to Claude and other MCP clients for safe AI-assisted posts, pages, media, drafts, and Elementor basics.

== Description ==

Mumega MCP connects your WordPress site to AI assistants like Claude using the Model Context Protocol (MCP). It gives approved AI clients a scoped, auditable way to read site context and help manage core WordPress content through natural language.

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
2. Copy your API key from Mumega MCP in the admin menu
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
2. Search for "Mumega MCP"
3. Click Install Now, then Activate
4. Go to Mumega MCP in the admin menu to get your API key

= Manual Installation =

1. Download the plugin ZIP file
2. Go to Plugins → Add New → Upload Plugin
3. Select the ZIP file and click Install Now
4. Activate the plugin
5. Go to Mumega MCP in the admin menu to get your API key

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

Model Context Protocol (MCP) is an open protocol that enables AI assistants like Claude to interact with external tools and services. Mumega MCP exposes your WordPress site as an MCP-compatible tool.

= Is this secure? =

Yes. All requests require a unique API key. Keys are hashed using WordPress password hashing (not stored in plain text). A dedicated service account with limited capabilities handles API requests. Activity logging tracks all API usage for auditing.

= Does it work with any AI? =

Mumega MCP works with any AI assistant that supports the MCP protocol. Currently, this includes Claude Code and Claude Desktop. More integrations are planned.

= Do I need coding skills? =

No. Once configured, you control WordPress through natural language. The AI handles all the technical details.

= What about Elementor? =

Basic Elementor page read/write support is included when Elementor is installed. Advanced Elementor Pro and theme-builder workflows are not part of the WordPress.org free package.

= Who is this for? =

Mumega MCP is best for site owners, developers, agencies, and content teams that want AI assistants to help operate WordPress while keeping API keys, scopes, and activity logs under site-owner control.

= Does it support Elementor 4? =

Yes. Mumega MCP includes fallback handling for Elementor page data saves.

= Can I use this on multiple sites? =

Each site needs its own plugin installation and API key.

== Screenshots ==

1. Setup tab — Activity log showing recent API requests
2. Connect AI tab — One-click configuration for Claude Desktop and Claude Code
3. Settings tab — Plugin configuration and integrations
4. Advanced tab — REST API reference with copy-paste curl examples

== Changelog ==

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
* Fix: Rename public display brand to Mumega MCP for WordPress.org packaging.
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
Public display brand is now Mumega MCP. Slug, text domain, and REST namespace remain stable.

== Disclaimer ==

This plugin is provided "as is" without warranty of any kind, express or implied. Use at your own risk. The authors are not liable for any damages, data loss, or issues arising from the use of this plugin. This plugin modifies WordPress content (posts, pages, Elementor data, media, settings) through its MCP and REST API endpoints — always maintain backups before making changes via AI assistants. By using this plugin you accept full responsibility for changes made through its API.

== Privacy Policy ==

Mumega MCP stores API keys, settings, and activity logs locally in WordPress. The plugin does not transmit site content to external services unless you configure an optional integration or ask an AI client connected to your site to send that data.

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
* Endpoint: https://sitepilotai.mumega.com/wp-json/site-pilot-ai/v1/feedback/relay
* Privacy Policy: https://sitepilotai.mumega.com/privacy

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

* Documentation: [Mumega MCP docs](https://sitepilotai.mumega.com/docs/)
* Support Forum: [wordpress.org/support/plugin/mumega-mcp](https://wordpress.org/support/plugin/mumega-mcp)
* GitHub: [github.com/Mumega-com/mcp-for-wp](https://github.com/Mumega-com/mcp-for-wp)
