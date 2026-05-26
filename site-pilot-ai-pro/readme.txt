=== MCPWP Pro ===
Contributors: digidinc
Tags: ai, api, rest-api, elementor, seo, forms, woocommerce, multilang
Requires at least: 5.0
Tested up to: 6.9.1
Requires PHP: 7.4
Stable tag: 1.0.25
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pro add-on for MCPWP. Unlocks the agent-safety and SEO-intelligence layers, plus WooCommerce, Elementor Pro, forms, and multilingual.

== Description ==

MCPWP Pro extends the free MCPWP core with the paid Operator and Agency tiers. On top of free core execution (content, media, menus, taxonomy, Elementor and Gutenberg editing), Pro unlocks the two layers agents need to operate a site safely and intelligently:

= Agent-Safety Layer =
* Approval and rollback workflows for agent mutations
* Event store with outbound webhooks
* Site-state snapshots for deterministic agent starts
* Content-coherence reports
* Control Room for supervising approvals, rollbacks, and agent activity

= SEO-Intelligence Layer =
* SEO audits and stored issue tracking
* Approval-safe SEO autofix planning
* Search Console and Bing search-performance import
* SEO trend reporting
* WooCommerce SEO intelligence

The Operator tier is single-site; the Agency tier extends these capabilities across multiple sites. Billing is handled through Freemius.

Pro also adds these integrations:

= Elementor Pro Features =
* Template management (create, list, apply)
* Landing page builder
* Page cloning with Elementor data
* Widget listing
* Global colors and fonts access

= SEO Integration =
* Yoast SEO support
* RankMath support
* AIOSEO support
* SEOPress support
* Unified API for all SEO plugins
* Bulk SEO updates
* SEO analysis

= Forms Integration =
* Contact Form 7 support
* WPForms support
* Gravity Forms support
* Ninja Forms support
* Form entries retrieval
* Unified forms API

= WooCommerce Integration =
* Product management (CRUD)
* Order management and status updates
* Customer data access
* Sales analytics and reports
* Category and tag management

= Multilanguage Support =
* WPML integration
* Polylang support
* TranslatePress support
* Language switching
* Translation status

= Site Management =
* Theme management
* Widget management
* User management
* Site-wide settings

= Works With Any Agent Runtime =

Like the free core, Pro speaks standard MCP. It works with Claude, GPT, Codex, Gemini, and any MCP client, and as a supplier to agent platforms that speak MCP such as AWS Bedrock AgentCore, Google Antigravity, Claude Managed Agents, OpenClaw, Hermes, and n8n.

== Installation ==

1. Ensure MCPWP (free) is installed and activated
2. Upload the plugin files to `/wp-content/plugins/site-pilot-ai-pro`
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Pro features will be automatically available via the REST API

== Changelog ==

= 1.0.24 =
* Version sync with base plugin
* Compatibility patch for Freemius checkout activation flow

= 1.0.23 =
* Hardened Pro activation/bootstrap to avoid fatal errors on missing/incomplete package files
* Added explicit admin error notices and safe load guards

= 1.0.22 =
* Version sync with base plugin hotfix release
* Freemius/activation stability update compatibility

= 1.0.21 =
* Version sync with base plugin
* Fixed WooCommerce REST controller inheritance for permission callbacks

= 1.0.14 =
* Version sync with base plugin
* Security improvements inherited from base plugin

= 1.0.13 =
* Switched to Freemius updates (via base plugin)
* Removed custom updater

= 1.0.12 =
* Added WooCommerce module (products, orders, customers, analytics)
* Added Multilanguage module (WPML, Polylang, TranslatePress)
* Added Theme management
* Added Widget management
* Added User management
* Added Site Manager module

= 1.0.0 =
* Initial release
* Elementor Pro features (templates, landing pages, cloning)
* SEO integration (Yoast, RankMath, AIOSEO, SEOPress)
* Forms integration (CF7, WPForms, Gravity Forms, Ninja Forms)
