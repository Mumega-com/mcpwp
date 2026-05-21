# Compact MCP Router Plan

## Problem

The plugin currently exposes a very large MCP tool surface. That makes tool choice noisy for models, increases schema load, makes free/pro boundaries harder to audit, and overlaps with the direction of native WordPress MCP/Abilities support.

## Goal

Add a deterministic compact router while preserving legacy tools during migration.

The primary runtime user of the router is an external AI agent, not a human clicking through wp-admin. Humans still own setup, entitlement, approval, and rollback. Agents need a compact, stable contract that works from Claude Code, Codex CLI/Desktop, OpenClaw, Hermes, Claude Desktop, and other MCP clients.

The compact MCP surface should expose a small set of stable tools:

- `wp_site_info`
- `wp_capabilities`
- `wp_search`
- `wp_get`
- `wp_create`
- `wp_update`
- `wp_delete`
- `wp_media`
- `wp_run_workflow`
- `wp_get_schema`

The router maps compact calls to internal handlers through the capability registry.

See `docs/AGENT_WORKFLOWS.md` for the design-agent loop and workflow handles.

## Example

Instead of many direct tools:

```text
wp_update_page
wp_update_post
wc_update_product
wp_set_elementor
```

Use one deterministic call shape:

```json
{
  "resource": "page",
  "action": "update",
  "id": 123,
  "fields": {
    "title": "New title"
  }
}
```

## Router Responsibilities

1. Normalize resource/action names.
2. Resolve the action in the capability registry.
3. Check free/pro entitlement.
4. Check API key scope.
5. Validate the payload.
6. Dispatch to the handler.
7. Normalize success and error responses.
8. Log the routed action.

For multi-step work, the router should prefer `wp_run_workflow` over expanding the public MCP surface. Examples include `build_from_design_reference`, `create_landing_page_from_archetype`, `update_page_section_from_reference`, and `save_section_as_reusable_part`.

## Migration Strategy

1. Add `Spai_Capability_Registry`.
2. Add `Spai_MCP_Router`.
3. Add compact tools without removing legacy tools.
4. Add an admin setting for MCP mode:
   - `compact` default for new installs and WP.org.
   - `legacy` for backwards compatibility.
   - `expanded` for Pro/power users if needed.
5. Teach `tools/list` to return compact tools by default.
6. Keep legacy direct tools callable when explicitly enabled.
7. Add telemetry/logging that records compact vs legacy use.
8. After one or two releases, decide whether legacy should remain Pro-only, hidden, or deprecated.

## Suggested Classes

- `includes/mcp/class-spai-capability-registry.php`
- `includes/mcp/class-spai-mcp-router.php`
- `includes/mcp/class-spai-mcp-compact-tools.php`

## Current Architecture Map

- `site-pilot-ai.php` loads MCP registry classes, free/pro registries, built-in AI/Figma integrations, REST controllers, and Pro bootstrap.
- `includes/class-spai-loader.php` registers built-in MCP integrations through the `spai_integrations` filter.
- `includes/class-spai-loader.php` registers core REST controllers, the MCP controller, batch routes, Pro routes through `spai_register_rest_routes`, and third-party integration routes.
- `includes/api/class-spai-rest-mcp.php` is the central MCP aggregator. It merges free tools, former Pro tools, and integrations, then filters by active capability and disabled admin categories.
- `includes/mcp/class-spai-mcp-tool-registry.php` is the registry base class. It owns `define_tool()`, annotations, read-only/open-world/destructive/category helpers, and capability requirements.
- `includes/mcp/class-spai-mcp-free-tools.php` contains current core/free tool definitions, categories, required plugin capabilities, and tool-to-REST route mapping.
- `includes/mcp/class-spai-mcp-pro-tools.php` contains former Pro definitions for SEO, forms, Elementor templates/build/theme, WooCommerce, widgets, LMS, events, and multisite.
- `includes/mcp/class-spai-integration.php` is the extension interface for built-in and third-party integrations.
- `includes/traits/trait-spai-api-auth.php` performs request-level API key and scope checks. For MCP it infers required scope from the JSON-RPC method/tool name before controller dispatch.

## Current Exposure/Gating

Paid-plan gating is being restored across REST, MCP, and admin surfaces. `Spai_Pro_Bootstrap` should report the active Freemius plan/trial state instead of forcing paid features active for every install.

Current effective gates are:

- API auth and scopes: `read`, `write`, `admin`.
- MCP role/category filtering by API key.
- Active plugin/capability filtering.
- Admin-disabled category filtering through `spai_disabled_tool_categories`.
- Final REST permission checks during internal `rest_do_request()`.

## Insertion Points

- `includes/api/class-spai-rest-mcp.php`
	- currently owns `initialize`, `tools/list`, and `tools/call`.
	- should choose compact vs legacy tool list.
	- should route compact `tools/call` through `Spai_MCP_Router`.
- `includes/mcp/class-spai-mcp-tool-registry.php`
  - existing base for tool definitions and maps.
  - compact tool registry should reuse `define_tool()`.
- `includes/mcp/class-spai-mcp-free-tools.php`
  - legacy free direct tools.
  - should remain available during migration.
- `includes/mcp/class-spai-mcp-pro-tools.php`
  - legacy pro direct tools.
  - should become registry-backed over time.
- `includes/pro/class-spai-pro-bootstrap.php`
	- currently registers pro REST routes.
	- future gating should use capability registry before route registration or before endpoint execution.

Safest first insertion point: `Spai_REST_MCP::handle_tools_call()`, after tool existence, role/category, capability, and argument validation, but before route substitution/internal `WP_REST_Request` creation. At that point the call is authorized, mapped, and validated, and the router can deterministically choose, deny, or transform based on tool name, mapping, annotations, method, category, and arguments.

Second insertion point: extract the existing merge/filter helpers from `Spai_REST_MCP` into a resolver class that returns an immutable resolved tool record: definition, map, category, annotations, and required capability. This removes duplication between `tools/list`, `tools/call`, introspection, and schema validation.

Avoid placing router behavior inside individual registries. Registries should remain declarative catalogs. Also avoid only routing inside downstream REST targets because MCP-specific constraints, category gates, and annotations are resolved earlier in the MCP controller.

## Error Contract

The router should return deterministic errors:

```json
{
  "code": "pro_required",
  "message": "The figma.node action requires MCPWP Pro.",
  "data": {
    "resource": "figma",
    "action": "node",
    "required_tier": "pro"
  }
}
```

Other standard codes:

- `unknown_action`
- `invalid_payload`
- `insufficient_scope`
- `approval_required`
- `provider_not_configured`
- `integration_missing`
- `handler_failed`

## WordPress Native MCP Direction

The compact router should not replace official WordPress MCP/Abilities. It should complement it.

- Use native WordPress MCP/Abilities for generic WordPress operations when practical.
- Use MCPWP router actions for higher-level workflows, design memory, archetypes, commerce flows, and safety policy.
- Keep self-hosted fallback support for sites that do not have native MCP available.
