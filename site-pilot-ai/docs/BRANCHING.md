# Branching Model

Mumega MCP uses two long-lived release branches plus short-lived feature branches.

## Long-Lived Branches

### `free/wporg`

WordPress.org-compatible distribution source.

- Builds `scripts/mumega-mcp-VERSION.zip`.
- Package root is `mumega-mcp/`.
- Text domain is `mumega-mcp`.
- Excludes Freemius SDK.
- Excludes `includes/pro/`.
- Excludes the legacy self-updater.
- Must pass WordPress.org Plugin Check with no errors before upload.

### `freemius/pro`

Commercial Freemius distribution source.

- Builds `scripts/site-pilot-ai-freemius-VERSION.zip` unless the Freemius product slug changes.
- Includes free features plus Pro modules.
- Includes Freemius SDK/bootstrap.
- Excludes the legacy self-updater.
- Gates paid capabilities through Freemius license state.

## Working Branches

Use short-lived branches for implementation:

```text
feature/example
  -> main
     -> free/wporg
     -> freemius/pro
```

Free-only fixes may target `free/wporg` first, then merge or cherry-pick back to `main`.

Pro-only work should target `freemius/pro`. Shared abstractions should be merged back to `main` when they are useful outside the paid build.

## Naming Rules

Public product name: `Mumega MCP`.

Stable identifiers:

- WP.org slug: `mumega-mcp`
- Text domain: `mumega-mcp`
- WP.org-compatible package root: `mumega-mcp/`
- Existing REST namespace: `site-pilot-ai/v1`
- Existing PHP prefixes/classes: `spai_*`, `Spai_*`

Do not rename REST namespaces, PHP prefixes, option names, or existing package identifiers without a migration plan.

## Pro Messaging In Free

Do not add aggressive Pro upsells before WordPress.org approval.

Allowed after approval:

- A small "Upgrade" or "Pro" page under the plugin menu.
- A single link from relevant disabled/pro-only capabilities.
- Clear free/pro comparison in documentation.
- Dismissible, capability-relevant notices only when useful.

Avoid:

- Dashboard-wide banners.
- Undismissible notices.
- Locked paid code inside the WordPress.org ZIP.
- Sending users off-site without clear disclosure.
