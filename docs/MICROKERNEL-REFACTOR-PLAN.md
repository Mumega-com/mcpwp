# Microkernel Refactor Plan — MCPWP v5

> **Status: PLAN (capture-only). Do NOT execute now.** Target: **v5.0 / M6**.
> Written 2026-06-09, after the v3.0.0 rebrand gave us a clean, consistently-named base.
> The rebrand was the prerequisite: you don't redesign architecture while also renaming 117 classes.

---

## 0. Why

Two forces converge on the same redesign:

1. **The big files don't fit in context and don't fit in a head.** Five files carry a third of the
   plugin's logic:

   | File | Lines | What it crams together |
   |------|-------|------------------------|
   | `api/class-mcpwp-rest-site.php` | 4531 | onboard, site-info, settings, introspect, options, plugins, fetch, batch, analytics, screenshot, update — a dozen unrelated surfaces |
   | `mcp/class-mcpwp-mcp-free-tools.php` | 3992 | ~160 tool definitions, every category in one array builder |
   | `admin/class-mcpwp-admin.php` | 3491 | every admin page's render method in one class |
   | `mcp/class-mcpwp-mcp-pro-tools.php` | 3332 | ~113 pro tool definitions |
   | `core/class-mcpwp-elementor-basic.php` | 3299 | read, write, validate, normalize, CSS — the whole Elementor engine |

   Edits to these are slow and error-prone. The conditional-class-at-file-load fatal that took
   `mcpwp.net` down site-wide (a class defined only if Elementor was loaded, registered later
   assuming it existed) is exactly the class of bug a big tangled file breeds.

2. **The economy needs a clean addon seam.** The product thesis (`docs/STRATEGY.md`) is a
   marketplace: addons and snapshots register into the plugin as items. That only stays clean if
   there is a **kernel** addons register *into* — not a monolith they have to monkey-patch. We
   already have the proto-seam (`mcpwp_register_tools` filter + `Mcpwp_*_Tool_Registry`). The
   microkernel generalizes that one good pattern to the whole plugin.

The same architecture sos/inkwell already use: **a minimal core that knows how to host modules,
and everything else is a module that registers itself.**

---

## 1. Target architecture

```
                ┌──────────────────────────────────────┐
                │              KERNEL                    │
                │  (small, stable, ~no domain logic)     │
                │                                        │
                │  • Module registry   (discover/load)   │
                │  • Service container (resolve deps)    │
                │  • Hook bus          (events)          │
                │  • Tool registry     (already exists)  │
                │  • Route registrar   (REST wiring)     │
                │  • Capability gate   (free/pro/scope)  │
                └───────────────┬────────────────────────┘
                                │ each module declares a manifest
        ┌───────────────┬───────┴───────┬───────────────┬─────────────┐
        ▼               ▼               ▼               ▼             ▼
  ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐
  │ elementor│   │   seo    │   │ content  │   │   woo    │   │  …addon  │
  │  module  │   │  module  │   │  module  │   │ (pro)    │   │ (3rd-pty)│
  │          │   │          │   │          │   │          │   │          │
  │ services │   │ services │   │ services │   │ services │   │ services │
  │ + tools  │   │ + tools  │   │ + tools  │   │ + tools  │   │ + tools  │
  │ + routes │   │ + routes │   │ + routes │   │ + routes │   │ + routes │
  │ + admin  │   │ + admin  │   │ + admin  │   │ + admin  │   │ + admin  │
  └──────────┘   └──────────┘   └──────────┘   └──────────┘   └──────────┘
```

**Invariant:** the kernel never imports a module. Modules depend on the kernel; the kernel depends
on nothing domain-specific. A first-party module and a third-party addon use the *same*
registration contract — that's what makes the marketplace clean.

### The module contract

A module is a directory with a manifest. Sketch (PHP, illustrative — not final API):

```php
// modules/elementor/module.php
return new Mcpwp_Module( array(
    'id'         => 'elementor',
    'requires'   => array(),                 // other module ids
    'tier'       => 'free',                   // free | pro
    'depends_on' => array( 'elementor/elementor.php' ), // WP plugins, optional
    'services'   => array(                    // class => factory (lazy)
        'elementor.reader'    => fn( $c ) => new Mcpwp_Elementor_Reader( $c->get('wpdb') ),
        'elementor.writer'    => fn( $c ) => new Mcpwp_Elementor_Writer( ... ),
        'elementor.validator' => fn( $c ) => new Mcpwp_Elementor_Validator(),
    ),
    'tools'      => array( Mcpwp_Elementor_Tools::class ),   // define_tool() providers
    'routes'     => array( Mcpwp_Rest_Elementor::class ),    // REST controllers
    'admin'      => array(),                                  // admin page providers
    'boot'       => function ( Mcpwp_Kernel $k ) { /* hooks */ },
) );
```

The kernel:
1. discovers `modules/*/module.php`,
2. topologically sorts by `requires`,
3. skips modules whose `tier`/`depends_on` aren't satisfied (graceful — no fatals),
4. registers each module's services (lazy), tools, routes, admin,
5. calls `boot()`.

This subsumes the existing `mcpwp_register_tools` filter: third-party addons drop a `module.php`
(or keep using the filter as a thin compatibility shim over the kernel).

---

## 2. Per-big-file decomposition

### 2.1 `api/class-mcpwp-rest-site.php` (4531) → split by surface
It's a junk drawer. One controller per real surface, each ~150–400 lines:
- `Mcpwp_Rest_Onboard` (`/onboard`, `/introspect`)
- `Mcpwp_Rest_Site_Info` (`/site-info`, `/plugins`, `/options`)
- `Mcpwp_Rest_Settings` (`/settings`)
- `Mcpwp_Rest_Fetch` (`/fetch`, `/screenshot`)
- `Mcpwp_Rest_Batch` (`/batch`) — already partly separate, fold here
- `Mcpwp_Rest_Update` (`/update`, `/rate-limit`)
- `Mcpwp_Rest_Analytics` (`/analytics`)
These become the `site` + `discovery` modules' route lists. **No behavior change** — pure move +
re-register. Highest ROI, lowest risk; do this one first as the pattern-setter.

### 2.2 `mcp/class-mcpwp-mcp-free-tools.php` (3992) + `-pro-tools.php` (3332) → split by category
Today one giant `get_tools()` builds every category. Split into per-category tool providers that
each return their slice, gathered by the kernel's tool registry:
- `Mcpwp_Tools_Content`, `Mcpwp_Tools_Elementor`, `Mcpwp_Tools_Media`, `Mcpwp_Tools_Seo`,
  `Mcpwp_Tools_Menus`, `Mcpwp_Tools_Approvals`, … (free)
- pro equivalents under their pro modules.
Each tool provider lives **next to the module it belongs to** (Elementor tools in the elementor
module), so the tier/category gate and the code are co-located. Kills the free/pro file split as
the organizing axis — tier becomes a per-tool/per-module attribute, not a file boundary.

### 2.3 `admin/class-mcpwp-admin.php` (3491) → one renderer per page
Each admin page (Setup, Control Room, Chat, Library, Integrations, Tools, Settings, Activity Log)
becomes its own `Mcpwp_Admin_Page_*` class implementing a small `render()`/`slug()`/`title()`
interface, registered by its owning module. The kernel's admin registrar builds the menu from
registered pages. Settings page already has its own class — follow that precedent.

### 2.4 `core/class-mcpwp-elementor-basic.php` (3299) → reader / writer / validator / css
Split the engine by verb (the dev-core skill already names these seams):
- `Mcpwp_Elementor_Reader` — get + summary + preview
- `Mcpwp_Elementor_Writer` — set + section/widget edits + patch + find-replace
- `Mcpwp_Elementor_Validator` — schema validation + auto-id + warnings (the find-replace
  structural-key protection lives here)
- `Mcpwp_Elementor_Css` — kit CSS + regenerate
Wire them as the elementor module's services. The widget schema registry
(`class-mcpwp-elementor-widgets.php`, 1694) stays as a fifth service.

### 2.5 Other 1k–2k files
`rest-seo-audit` (1825), `rest-content-graph` (1261), `rest-blocks` (1260),
`pro/core/class-mcpwp-page-builder.php` (2610), `pro/api/rest-elementor-pro` (1906),
`trait-mcpwp-api-auth` (1392) — decompose opportunistically when their module is migrated, not as
a separate pass. The auth trait is the one piece that may stay near the kernel (it's
cross-cutting), refactored into a `Mcpwp_Auth` kernel service rather than a trait mixed into every
controller.

---

## 3. Migration sequence (strangler-fig, incremental)

Never a big-bang rewrite. The kernel is born alongside the monolith and absorbs it module by
module, each step shippable and test-green.

1. **v5.0-a — Build the kernel skeleton.** Module registry + service container + route/tool/admin
   registrars, with the *current* classes registered as a single legacy "core" module. No file
   splits yet. Plugin behaves identically; CI green. This proves the kernel hosts the existing code.
2. **v5.0-b — Carve the first module: `site`/`discovery`** (§2.1). Move the rest-site surfaces into
   real controllers under the module. The lowest-risk, highest-clarity win; sets the template every
   later module copies.
3. **v5.0-c — `elementor` module** (§2.4 + its tools + routes). The most valuable decomposition
   (biggest engine, most-used surface).
4. **v5.0-d — `tools` reorg** (§2.2): per-category tool providers, co-located with modules. Retire
   the free/pro mega-files.
5. **v5.0-e — `admin` per-page** (§2.3).
6. **v5.0-f — pro modules** (woo, learnpress, elementor-pro, page-builder) as `tier: pro` modules
   with `depends_on` guards — formalizes the graceful-degradation the dev-pro skill documents.
7. **v5.0-g — Open the contract.** Document the module manifest as the public addon API; make
   `mcpwp_register_tools` a thin shim over it. **This is the marketplace seam going live.**

Each step: full PHPUnit + `php -l` + the 7 CI checks green before merge. Sensitive surfaces (auth
becoming a kernel service in any step) get an adversarial review per the agent-comms standard.

---

## 4. Non-goals / guardrails

- **No behavior change.** This is structure only. Every endpoint, tool name, option key, and
  response shape stays identical. Characterization tests pin behavior before each carve.
- **No new framework.** Plain PHP + WP. The "container" is a tiny array-of-factories, not a DI
  library. Kernel must stay small enough to read in one sitting — if the kernel grows domain logic,
  the refactor has failed.
- **Keep it under the 1MB/size and PHP 7.4 floors** the plugin already targets.
- **Don't bundle the rebrand's deferred work.** Cross-repo renames (proxy worker, etc.) are
  separate and already tracked.

## 5. Definition of done (v5)

- Kernel < ~600 lines, zero domain imports.
- No single plugin file > ~800 lines (soft target); the five big files gone.
- First-party features and a third-party addon register through the identical module contract.
- A new addon can ship as a dropped-in `module.php` with services + tools + routes + admin, no core
  edits — proving the marketplace seam.
- Full test suite + 7 CI checks green; no endpoint/tool/option behavior changed.

---

## 6. Effort & method

Large, mechanical-with-judgment, correctness-critical → subagent-driven, one module per task, with
the spec-then-quality review loop, characterization tests first. Estimated 6–8 focused build
sessions across v5.0-a…g. The rebrand already paid the naming cost; this is the structural cost,
de-risked by doing it on a clean, consistently-named base.

---

## 7. Mirror findings — inkwell (TS) + SOS (Python), 2026-06-09

Before building, we studied the two Mumega microkernels the plan referenced. Verdict from deep
reads of both codebases:

**No code is reusable. The contract is.** inkwell is TS/Astro/Cloudflare-Workers; SOS is Python;
MCPWP is PHP/WordPress. Not a line lifts. But both converge on the same *shape* worth mirroring.

### What to mirror (from inkwell's `PluginManifest` — the cleanest kin)
inkwell's kernel (`kernel/types.ts`, `plugin-loader.ts`, `adapter-registry.ts`) is a genuine
microkernel: kernel owns only contracts; each `plugins/{name}/manifest.ts` declares
`name, version, requiredRole, mountRoutes(app), mcpTools[], dashboardWidgets[], configDefaults,
migrations[]`; an `inkwell.config.ts` allowlist decides what loads; a hexagonal port/adapter
registry means features never touch infrastructure directly. Its `McpToolDef`
(`{name, description, inputSchema, handler}`) **is already our MCP tool shape**. The PHP module
manifest mirrors this:

```php
// includes/modules/{id}/module.php
return array(
  'id'        => 'elementor',
  'version'   => '1.0.0',
  'requires'  => array(),            // module ids (topo-ordered)
  'tier'      => 'free',             // free|pro  (our concept; SOS/inkwell differ)
  'depends_on'=> array(),            // WP plugins, optional (graceful skip)
  'services'  => array(/* id => fn($c) lazy factory */),
  'tools'     => array(/* McpToolDef-shaped providers */),
  'routes'    => array(/* REST controller classes */),
  'admin'     => array(/* admin page providers */),
  'boot'      => function ( $kernel ) {},
);
```

### What to DROP (SOS machinery that doesn't fit a single-process WP plugin)
SOS's strengths are multi-process: a Redis-backed `ServiceRegistry` with TTL heartbeats, HTTP
service routing + health endpoints, content-addressed (CID) Ed25519-signed plugin artifacts, and
signed delegable capability tokens. **None translate** to one PHP process per request. Replace with:
in-memory per-request registry array; in-process callable dispatch (array lookup, no network hop);
trusted local module files (no CID/signing); and **reuse MCPWP's existing gate** — API key + role
scope + `mcpwp_disabled_tool_categories` + the `mcpwp_tool_called` choke point — as the call-time
capability check. SOS also gives us **no topological ordering and no free/pro tier model**; both are
net-new (simple `requires` + Kahn's algorithm; tier as a per-module attribute).

### Reuse what already exists
The proto-seam is real: `mcpwp_register_tools` filter + `Mcpwp_Custom_Tool_Registry` +
`mcpwp_tool_called`. The kernel generalizes that one good pattern; `mcpwp_register_tools` becomes a
thin shim over the kernel in v5.0-g.

### Sync angle (SEPARATE track, not the kernel)
inkwell (brand/CRM/strategy brain, 41 MCP tools at `inkwell.mumega.com/mcp`) and MCPWP (WP execution,
250+ tools) are **complementary, integrate over MCP-to-MCP, share no code**. Pipeline: inkwell
`onboard_client`/`content_strategy` → page briefs → MCPWP `wp_create_page`/`wp_set_elementor`
publishes them; brand vector synced via `wp_set_site_context` ↔ inkwell `remember`/`recall`. This is
its own spec (tracked in v5.0-g notes), **not** part of the kernel refactor.

---

## 8. Revised v5.0-a — additive & v3-safe

**Hard constraint (Hadi, 2026-06-09): the refactor must not break the shipped v3.0.0.** Therefore
v5.0-a is **additive, live boot path UNTOUCHED**:

- Land the kernel primitives as NEW files under `includes/kernel/`: `Mcpwp_Kernel`,
  `Mcpwp_Module` (interface) + `Mcpwp_Module_Manifest`, `Mcpwp_Module_Registry`, `Mcpwp_Container`
  (array-of-closures lazy DI), and a `Mcpwp_Legacy_Core_Module` placeholder.
- **Do NOT rewire `mcpwp.php` / `mcpwp_load_plugin`.** The plugin boots exactly as v3.0.0. The kernel
  is proven by full PHPUnit unit tests (discover → topo-order → tier/depends gate → register
  services/tools/routes/admin → boot lifecycle), not by taking over the live path yet.
- DoD for v5.0-a: kernel + contracts + tests land, `php -l` clean, full suite + 7 CI checks green,
  **zero change to any runtime behavior** (live path untouched by construction).

The first *live* carve happens in **v5.0-b**: the site/discovery surface is the first to route
through the kernel, at which point the kernel enters the boot path behind that one module while
everything else still loads the legacy way. Strangler-fig proceeds module by module from there. This
ordering trades a little of the original "host all legacy as one module in step a" purity for an
absolute guarantee that step a cannot break production.
