# MCPWP Dev Skills

Loadable reference docs for working **on** the MCPWP plugin source. One per code area, so an agent
or contributor about to touch an area loads that one file and is oriented fast. Excluded from the
shipped plugin zip (`/docs` is in `.distignore`).

**Start with [`dev-architecture.md`](dev-architecture.md)** — the top-level map — then open the
area you're touching:

| Skill | Load when working in |
|-------|----------------------|
| [dev-architecture](dev-architecture.md) | anywhere — read first |
| [dev-core](dev-core.md) | `mcpwp/includes/core/` — domain/service layer |
| [dev-api](dev-api.md) | `mcpwp/includes/api/` — REST controllers, `mcpwp/v1`, `/mcp` dispatch |
| [dev-mcp-tools](dev-mcp-tools.md) | `mcpwp/includes/mcp/` — tool registries, `define_tool`, gating |
| [dev-admin](dev-admin.md) | `mcpwp/includes/admin/` + `mcpwp/admin/` — admin UI + design system |
| [dev-pro](dev-pro.md) | `mcpwp/includes/pro/` + `freemius-init.php` — pro features + licensing |
| [dev-traits-infra](dev-traits-infra.md) | `traits/` + bootstrap/loader/rate-limiter/updater |

For the v5 structural redesign of the big files, see
[`MICROKERNEL-REFACTOR-PLAN.md`](../../../docs/MICROKERNEL-REFACTOR-PLAN.md) (at repo root `docs/`).

The **user-facing** agent skills (connect, setup, operate, design, elementor, tools, status) — how
agents *operate* a live site — live in the separate `mcpwp-claude-plugin` marketplace plugin, not
here.
