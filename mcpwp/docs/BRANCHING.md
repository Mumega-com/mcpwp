# Branching Model

MCPWP uses two long-lived release branches plus short-lived feature branches.

## Long-Lived Branches

### `free/wporg`

WordPress.org-compatible distribution source.

- Builds `scripts/mcpwp-VERSION.zip`.
- Package root is `mcpwp/`.
- Text domain is `mcpwp`.
- Excludes Freemius SDK.
- Excludes `includes/pro/`.
- Excludes the legacy self-updater.
- Must pass WordPress.org Plugin Check with no errors before upload.

### `freemius/pro`

Commercial Freemius distribution source.

- Builds `scripts/mcpwp-freemius-VERSION.zip` unless the Freemius product slug changes.
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

Public product name: `MCPWP`.

Stable identifiers:

- WP.org slug: `mcpwp`
- Text domain: `mcpwp`
- WP.org-compatible package root: `mcpwp/`
- Existing REST namespace: `mcpwp/v1`
- Existing PHP prefixes/classes: `mcpwp_*`, `Mcpwp_*`

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
