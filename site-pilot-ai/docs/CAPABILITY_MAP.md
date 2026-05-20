# Capability Map

This document is the contract for free/pro behavior. The implementation should move toward a single PHP registry that mirrors this table and is used by REST routes, MCP tools, admin UI, packaging, and docs.

## Capability Table

| Capability | Tier | Current Surface | Future Router Action | Notes |
|---|---|---|---|---|
| Site info/context | Free | REST + MCP | `site.read` | Core setup and model orientation. |
| API keys/scopes | Free | Admin + REST + MCP | `auth.key.*` | Required for safe self-hosted use. |
| Posts | Free | REST + MCP | `post.list`, `post.get`, `post.create`, `post.update`, `post.delete` | Keep as core promise. |
| Pages | Free | REST + MCP | `page.list`, `page.get`, `page.create`, `page.update`, `page.delete` | Keep as core promise. |
| Media | Free | REST + MCP | `media.list`, `media.upload`, `media.import`, `media.update` | Large-file path should be improved separately. |
| Drafts | Free | REST + MCP | `draft.list`, `draft.delete` | Useful safety and cleanup workflow. |
| Menus | Free | REST + MCP | `menu.*` | Core site operations. |
| Taxonomy | Free | REST + MCP | `taxonomy.*` | Needed for posts/pages/products metadata. |
| Activity log | Free | Admin + REST | `log.list`, `log.get` | Keep local and configurable. |
| Approvals | Free | Admin + REST + MCP | `approval.*` | Required before publish/destructive/commerce/theme-builder actions. |
| Basic Elementor | Free | REST + MCP | `elementor.basic.*` | Basic read/write when Elementor is installed. |
| Event hooks | Free | WordPress hooks | `event.*` | Internal developer hooks for approvals, SEO, graph, and activity state changes. |
| Webhooks | Pro or advanced Free TBD | REST + MCP | `webhook.*` | Signed outbound subscriptions for external agents, chat channels, and automation tools. |
| AI providers | Pro | Integrations + MCP | `ai.image.*`, `ai.text.*`, `ai.vision.*` | External service dependency. |
| Screenshot worker | Pro | Integrations + MCP | `screenshot.render` | Free may keep WP.com mShots fallback if low-risk. |
| Figma | Pro | Integrations + MCP | `figma.file`, `figma.node` | Design intake and OAuth should be paid. |
| Design references | Pro | REST + MCP + Admin | `design_reference.*` | Product differentiator. |
| Page archetypes | Pro | REST + MCP + Admin | `archetype.page.*` | Production system feature. |
| Product archetypes | Pro | REST + MCP + Admin | `archetype.product.*` | Production system feature. |
| Reusable parts | Pro | REST + MCP + Admin | `part.*` | Production system feature. |
| Agent workflows | Pro | MCP router + REST | `workflow.*` | Multi-step outcomes such as build from design reference or SEO cleanup. |
| WooCommerce | Pro | REST + MCP | `commerce.*` | Paid because it touches revenue operations. |
| SEO integrations | Pro | REST + MCP | `seo.*` | Paid advanced workflow. |
| Forms integrations | Pro | REST + MCP | `form.*` | Paid advanced workflow. |
| Elementor Pro/theme builder | Pro | REST + MCP | `elementor.pro.*`, `theme_builder.*` | Paid advanced workflow. |
| Users/site manager | Pro | REST + MCP | `user.*`, `site_manager.*` | Higher-risk operations. |
| Multilang | Pro | REST + MCP | `language.*` | Advanced site workflow. |
| Google Indexing | Pro | REST + MCP | `indexing.*` | External service and SEO workflow. |
| Agency dashboard | Pro | Planned | `agency.*` | Future centralized operations. |

## Registry Shape

Future PHP registry entries should look like this:

```php
'page.update' => array(
	'tier'        => 'free',
	'scope'       => 'write',
	'resource'    => 'page',
	'action'      => 'update',
	'rest_route'  => '/pages/(?P<id>[\d]+)',
	'legacy_tool' => 'wp_update_page',
	'handler'     => array( Spai_Pages::class, 'update_page' ),
);
```

## Enforcement Rules

- Unknown action: return `unknown_action`.
- Known Pro action without Pro entitlement: return `pro_required`.
- Missing API key scope: return `insufficient_scope`.
- Invalid payload: return `invalid_payload` with schema hints.
- Handler failure: return normalized `WP_Error` data.
- Human approval required: return `approval_required` with the resource IDs and requested action.

## Documentation Rules

When a capability changes tier or behavior, update this file and the implementation in the same PR.

The agent-facing workflow model is documented in `docs/AGENT_WORKFLOWS.md`.
