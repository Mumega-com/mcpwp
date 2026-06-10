# ADR 0001 — MCPWP Extension Architecture: Gateway + Modular Monolith

**Status:** Accepted — G1–G5 implemented (v3.x) · **Date:** 2026-06-09 (updated 2026-06-10) · **Supersedes:** `docs/MICROKERNEL-REFACTOR-PLAN.md`

> **Progress:** The Modular Monolith (G1–G4) and the gateway contract (G5) are **done and merged**.
> G6 (untrusted-addon path) is next when prioritized; **G7 (marketplace) is deferred to v7** — see §Migration.

---

## Context

Two forces, often conflated:

1. **Now-pain — five oversized files** carry ~⅓ of the plugin and breed fatals:
   `api/class-mcpwp-rest-site.php` (4531), `mcp/class-mcpwp-mcp-free-tools.php` (3992),
   `admin/class-mcpwp-admin.php` (3491), `mcp/class-mcpwp-mcp-pro-tools.php` (3332),
   `core/class-mcpwp-elementor-basic.php` (3299).

2. **Future-ambition — an open ecosystem.** Site owners write their own tools; third parties
   build, sell, and others install addons (`docs/STRATEGY.md`: the marketplace is the revenue
   engine, M4/v3.x). This is an *untrusted-code-at-scale* problem, not just a file-layout problem.

The prior plan answered both with one tool: an in-process PHP **microkernel** (manifest + DI
container + module registry + topo-loader). On review that is the wrong instrument — for a reason
that only surfaces once you separate the two forces above by **trust boundary**.

## The decisive observation

**An in-process PHP kernel cannot isolate untrusted code.** A third-party `module.php` loaded into
MCPWP's process is arbitrary PHP: one fatal, infinite loop, or `unlink()` and the owner's site is
down. The headline reason you'd want "let others run code" — *safety* — is exactly what an
in-process kernel **cannot deliver**. It is a framework, not a sandbox.

So a classic microkernel is simultaneously:
- **Overkill** for first-party organization — WordPress hooks already are a plugin/extension host.
- **Insufficient** for untrusted third-party — no isolation; it can't even gate a *remote* addon.

The boundary, not the framework, is what matters. Different trust → different boundary.

## Decision

Adopt a **two-layer architecture**, each layer matched to its trust boundary.

### Layer 1 — First-party internals: **Modular Monolith** (WordPress-native)

Split the five big files into focused classes that **self-register through WordPress's own seams**
(`rest_api_init`, `admin_menu`, and the existing `mcpwp_register_tools` filter). No custom kernel,
no DI container, no manifest system. WordPress *is* the module host; we stop fighting it. WooCommerce
runs thousands of files and thousands of third-party extensions on exactly this model.

### Layer 2 — Extension / marketplace: **MCPWP as an MCP Gateway**

MCPWP's real job is **authenticate → gate → route → observe**. A "tool" is not in-process code; it
is a *pointer to a handler* at some trust distance. The dispatcher
(`api/class-mcpwp-rest-mcp.php` + `Mcpwp_Custom_Tool_Registry`) already does this — the architecture
is latent in the codebase and just needs to be named and hardened.

```
        MCP client (Claude / Codex / ChatGPT / owner's agent)
                       │  JSON-RPC, X-API-Key
            ┌──────────▼───────────────────────────────────┐
            │                 MCPWP GATEWAY                 │
            │  authenticate · gate(tier/scope/rate/cap) ·   │
            │  route · observe(PostHog) · curate/rank       │
            └───────┬─────────────┬──────────────┬──────────┘
       routes to →  │             │              │
        ┌───────────▼──┐  ┌───────▼────────┐  ┌──▼───────────────────┐
        │ first-party  │  │ third-party WP │  │ remote / owner code  │
        │ PHP handler  │  │ plugin (hook)  │  │ external endpoint    │
        │ (trusted)    │  │ (vetted)       │  │ (untrusted, ISOLATED)│
        └──────────────┘  └────────────────┘  └──────────────────────┘
```

### The three trust tiers (the core of the decision)

| Who writes it | Boundary | Mechanism | Isolation |
|---|---|---|---|
| **You (first-party)** | none — code hygiene | split files, register via WP hooks | n/a (trusted) |
| **Vetted marketplace addon** | WordPress plugin | a normal WP plugin using `mcpwp_register_tools` + REST contract; WP gives activation/update/dep-checks/security | WP's plugin model |
| **Untrusted owner / arbitrary addon** | **process / protocol** | tool's `rest_path`/endpoint points at an external service (their Worker/site/MCP server); MCPWP routes + gates | **real — out of process** |

The third tier is the one the ambition requires, and it is **not** an in-process kernel. It is the
gateway routing to an endpoint the owner controls. Backlog **T71b** ("Admin defines tool → MCPWP
proxies to external URL, no PHP needed") and the `rest_path` field on every `mcpwp_register_tools`
entry are this design, already begun.

## The contract lives at the protocol/data level, not in PHP

A PHP manifest can only describe in-process PHP — it cannot gate or version a remote addon. So the
addon contract is a **language-agnostic data contract** validated and enforced by the gateway:

```jsonc
{
  "name": "string",            // MCP tool name (exists)
  "description": "string",     // (exists)
  "inputSchema": { },          // JSON Schema (exists)
  "endpoint": "string",        // local route OR remote URL  (generalize rest_path)
  "tier": "free|pro",          // entitlement gate           (add)
  "category": "string",        // grouping + toggle          (exists, formalize)
  "capabilities": ["..."],     // what it may touch          (add)
  "version": "semver"          // negotiation                (add)
}
```

The gateway **validates this schema on registration** and **gates every call** (tier · scope · rate ·
capability) at the single dispatch choke point — where `mcpwp_tool_called` already fires. Curation
and performance-ranking (the marketplace levers) attach here too, fed by PostHog-everywhere.

## What we DO build

- **Split the five big files** (modular monolith, WP-native registration).
- **Harden the gateway:** formalize + validate the registration schema; enforce tier/scope/rate/
  capability gating in the dispatcher; first-class support for **remote/proxy tools** (T71b) and the
  **visual tool builder** (T71c) — the safe path for owners who aren't PHP devs.
- **Marketplace registry** later: curation, ranking, the cut (M4) — a layer over the gateway.

## What we do NOT build

- `Mcpwp_Kernel`, the DI container, the PHP module manifest, the topological registry. They duplicate
  WordPress + the existing tool registry and give no isolation. **The v5.0-a kernel skeleton
  (PR #486) is shelved** — it was inert/additive, so nothing to unwind.

## Why this is future-proof for the full ambition

- **Unlimited third-party addons** — same as Woo/Elementor scale, on WP's proven plugin model.
- **Untrusted owner code is safe by construction** — it runs out of process behind the protocol
  boundary; the gateway gates capability/scope/rate. A bad addon cannot fatal the site.
- **Language-agnostic** — a remote addon in any stack conforms to the data contract; a PHP kernel
  could never host those.
- **The marketplace is a registry + curation layer over the gateway**, not a runtime framework.

## Supporting research (inkwell / SOS, 2026-06-09)

Both Mumega kernels were studied. Neither argues *for* an in-process PHP kernel here:
- **SOS** runs addons as **out-of-process services** that self-register tools and are gated by
  **capability tokens** — i.e. SOS already validates the gateway/protocol-boundary model for
  untrusted code.
- **inkwell** keeps its contract at the **manifest/`McpToolDef` data level** (`{name, description,
  inputSchema, handler}`) with a config allowlist — a data contract, not a DI framework.
- Cross-language reuse is impossible (TS/Python vs PHP); the *contract shape* is the only portable
  thing — and it belongs at the protocol level, which is exactly this ADR.

(inkwell ↔ MCPWP **sync** — inkwell as brand/CRM brain feeding briefs that MCPWP publishes to
WordPress, brand vector via `wp_set_site_context` ↔ inkwell memory — remains a separate
MCP-to-MCP integration track, unaffected by this decision.)

## Migration sequence (strangler-fig; every step behavior-identical, branch+PR, v3 never breaks)

| Step | Target | Status | Work | Risk |
|---|---|---|---|---|
| **G1** | v3.x | ✅ done (PR #488) | Split `rest-site` (4531) → 8 per-surface controllers; WP-native `register_rest_route`. | low |
| **G2** | v3.x | ✅ done (PR #489) | Split free/pro tool mega-files → per-category trait groups (3992→1046, 3332→824). | med |
| **G3** | v3.x | ✅ done (PR #490) | Split `admin` (3491→612) → per-page trait groups (`admin_menu`). | low |
| **G4** | v3.x | ✅ done (PR #491) | Split `elementor-basic` (3299→39) → reader/writer/validator/css traits. | med |
| **G5** | v3.x | ✅ done (PR #492) | **Gateway contract:** schema validation + tier/capability gate; adversarial review closed P0 `rest_path` SSRF + P1 name-shadowing. | med (sensitive → adversarial review) |
| **G6** | v4–v5 | next | **Remote/proxy tools** (T71b) + **visual tool builder** (T71c) — the untrusted-addon path goes live. Gated behind validating the P0 marketing-proof loop first. | med |
| **G7** | **v7** | **deferred** | **Marketplace registry** — curation, ranking, the cut. Productization of the open ecosystem. Deferred to **v7**: it is the revenue engine but must NOT be built before the P0 proof loop (`docs/STRATEGY.md`) validates the thesis — "marketplace before a working snapshot = death." | product, not refactor |

**G1–G5 are complete** (the Modular Monolith + the gateway contract — all merged, behavior-identical,
each its own CI-green PR; main stayed shippable throughout). G6 is the next build when the
untrusted-addon path is prioritized. **G7 (marketplace) is explicitly deferred to v7** — pulling it
forward before the marketing-proof loop is validated would build the revenue engine on an unproven
thesis. Deferred lower-severity G5 follow-ups: derive custom-tool write/admin scope from
`method`+`destructive` (auth trait); `rawurlencode` path-param substitution (dispatcher).

## Consequences

- **Positive:** idiomatic WP, real isolation for untrusted code, language-agnostic addons, smaller
  files, the marketplace contract is a thin data spec, no parallel framework to maintain.
- **Negative / trade:** the explicit "single typed kernel contract you fully control" is given up in
  favor of WP's looser hook model for in-process extensions — acceptable, because the *untrusted*
  extensions (the ones that need strong guarantees) live behind the protocol boundary where the
  guarantees actually hold.
- **Guardrails:** behavior-identical refactors, characterization tests before each carve, adversarial
  review on G5 (external-facing gate), no change to shipped v3.0.0 until each PR is green.
