# MCPWP — Issue Drafts (NOT yet filed)

These are ready-to-file DRAFTS from the product/market analysis. They were checked
against all 110 open issues to avoid duplicating the SEO build-out (#296-316),
AI-first events (#312), control room (#309/#314), agent playbooks (#315),
content coherence (#316), WooCommerce SEO (#311), the licensing-gate issue (#212),
and the paid-GTM cleanup set (#317-325).

Each draft notes related issues, suggested labels, and is grounded in the current
code in `site-pilot-ai/` (the active plugin tree; note `site-pilot-ai-pro/` is a
divergent second copy).

---

## Draft 1 — Package the agent-safety + SEO-intelligence layer as a recurring paid tier

**Suggested labels:** `enhancement`, `high`

**Relates to:** #212 (generic licensing gate), #258/#259/#260/#261 (capability split + gating plumbing), #269 (approval gates), #307 (SEO autofix), #308 (AI search visibility), #309/#314 (Control Room), #312 (event layer), #313 (site state), #316 (content coherence), #311 (WooCommerce SEO), and the paid-GTM set #317-325.

### Problem
Our most defensible, billable value already exists in code but is not packaged or
priced as a distinct recurring tier. The agent-safety layer
(`includes/core/class-spai-approvals.php`, `class-spai-event-store.php`,
`class-spai-site-state.php`, `class-spai-content-coherence.php`) and the SEO-intelligence
layer (`class-spai-seo-audit-store.php`, `class-spai-seo-autofix.php`,
`class-spai-search-performance.php`, WooCommerce SEO) are mature, but the only
monetization framing today is the generic "Free 70 tools / Pro 239 tools" split
(#212) plus assorted Freemius cleanup (#317-325).

Meanwhile the raw MCP execution layer and the "239 tools" count are commoditizing
(free competitors: Meow Apps AI Engine, Elementor Angie, the official WP MCP Adapter),
so tool count is the wrong thing to charge for.

### Expected / Scope
Define and ship a value-based recurring tier built around outcomes, not tool count:

- **Positioning:** raw MCP execution + tool count = free funnel; agent-safety +
  SEO-intelligence = the paid, recurring product.
- **Tier definition:** decide which capabilities are the paid tier's billable core —
  approvals/rollback, event store + outbound webhooks, site-state snapshots,
  content coherence scoring, the SEO audit store / autofix / search-performance suite,
  and the Control Room. Map each to its existing class/REST/MCP surface.
- **Gating:** make these capabilities gate cleanly on plan/license state, reusing
  the capability registry (#259) and Pro gating plumbing (#260/#261) rather than the
  tool-count split. This epic owns the *packaging/pricing* decision; #260/#261 own the
  mechanical enforcement.
- **Pricing/messaging:** recurring price points and the upgrade story ("safe,
  governed AI changes + SEO intelligence for your whole site"), feeding the GTM copy
  issues (#318/#319/#322) so plan reporting and admin copy match the new tier.

This is a product/packaging epic; it should spawn or coordinate sub-issues, not
re-implement the features (which already exist).

### Acceptance Criteria
- A written tier definition lists every billable capability and its code surface.
- Each billable capability is gated by plan/license state, not by tool count.
- Free vs paid boundary is consistent with the capability registry (#259) and does
  not contradict #260/#261.
- Pricing and upgrade messaging are defined and handed to the GTM copy issues so
  admin UI, REST, MCP, and docs report the same plan/tier (#318/#319/#322).
- Raw execution + tool count are explicitly positioned as the free funnel in the
  packaging doc.

---

## Draft 2 — Admin "Tools" category toggles are not enforced on tool execution

**Suggested labels:** `bug`, `high`, `mcp`, `wordpress`

**Relates to:** #260 (gate Pro MCP tools/REST consistently), #261 (gate Pro admin UI), #259 (capability registry).

### Problem
The admin **Tools** tab lets an admin disable whole tool categories, and we advertise
role-scoped keys / category scoping. But disabling a category only hides its tools
from discovery — it does **not** block execution. An agent that already knows a tool
name in a "disabled" category can still run it.

Evidence (current code, `site-pilot-ai/`):
- The toggle saves to the `spai_disabled_tool_categories` option
  (`includes/admin/class-spai-tools-admin.php:209`).
- That option is read in exactly one place in the API layer — `get_all_tools()`
  (`includes/api/class-spai-rest-mcp.php:164`), which feeds `tools/list` and
  `get_introspection_data()` (discovery only).
- The execution path, `handle_tools_call()`
  (`includes/api/class-spai-rest-mcp.php:768`), checks per-API-key role categories
  (lines ~814-844) and required-plugin capability (lines ~846-868), but **never**
  consults `spai_disabled_tool_categories`.

Net effect: the admin Tools toggle is cosmetic for execution. A category an admin
believes is "off" is still callable via `tools/call` (and via the underlying REST
routes), which is a governance/security gap, not just a UI inconsistency.

### Expected / Scope
- Disabling a category in the Tools tab must block that category's tools from
  executing, not just from being listed.
- Enforce the same gate on every entry point: MCP `tools/call`, and the REST routes
  the tools dispatch to (so the gate can't be bypassed by calling the route directly).
- Return a clear, agent-friendly error (mirroring the existing role-category
  `-32003` error shape) when a tool in a disabled category is called.
- Keep discovery filtering in `get_all_tools()` consistent with execution gating so
  listed and runnable tools never diverge.

### Acceptance Criteria
- Calling a tool whose category is in `spai_disabled_tool_categories` via `tools/call`
  returns an error and does not execute.
- The same category is also blocked when its backing REST route is called directly.
- A tool in an enabled category continues to work unchanged.
- `tools/list` / introspection and execution agree on which tools are available.
- Regression test covers: disable category -> list omits it -> call is rejected ->
  re-enable -> call succeeds.

---

## Draft 3 — Tech debt: finish-or-demote thin Pro integrations and remove dead theme code

**Suggested labels:** `enhancement`, `medium`, `wordpress`

**Relates to:** #258 (free/pro capability split), #322 (tool-count claims), Draft 1 (packaging).

### Problem
Several Pro integrations are thin and inflate the tool count without adding
willingness-to-pay, and there is corrupted/dead code in the theme handler. Because
tool count is becoming our free-funnel commodity (see Draft 1), thin integrations
that masquerade as paid value are a liability for both trust and maintenance.

Evidence (current code, `site-pilot-ai/includes/pro/core/`):
- **ThimPress Events** (`class-spai-events.php`, ~258 lines): really a generic
  `tp_event` post-type wrapper (WP_Query + `wp_insert_post` + a flat meta map in
  `set_event_meta`). No ticketing/registration/attendee logic — thin for a "Pro
  integration."
- **Forms** (`class-spai-forms.php`, ~635 lines): read-only. Only
  `is_*_active`, `get_status`, `get_all_forms`, `get_forms`, `get_form`,
  `get_entries` — no create/update/delete. Agents cannot build or modify forms.
- **Multilang** (`class-spai-multilang.php`, ~775 lines): partial. Translation
  creation is WPML/Polylang only; TranslatePress is explicitly `not_supported`
  (line ~258). Much of the surface is detection/getters.
- **LearnPress** (`class-spai-learnpress.php`, ~1215 lines): has course/lesson
  create+update, so the "read-only" label in our analysis is partly inaccurate —
  this needs a capability audit to confirm what's actually write-capable vs
  read-only (e.g. enrollments/orders) before we describe or price it.
- **Theme handler dead/duplicate code** (`class-spai-themes.php`): the
  `$supported_themes` map (lines ~24-61) is copy-paste-corrupted — duplicate
  `'flavor'` array keys (PHP silently keeps only the last), placeholder
  "flavor"/"flavflavor" entries, an `oceanwp` entry whose `option_key` is the
  nonsensical `theme_mods_flavor`, and a `kadence` `option_key` (`theme_mods_kadence`)
  that doesn't match what `get_kadence_settings()` actually reads. `get_settings()`
  dispatches by slug to handlers like `get_oceanwp_settings()` that don't exist, so
  several "supported" themes silently fall through to generic. There are also TWO
  diverging copies of this class (`site-pilot-ai/includes/pro/core/` and
  `site-pilot-ai-pro/includes/core/`), which compounds the rot.

### Expected / Scope
For each thin integration, make an explicit **finish-or-demote** decision:
- **Finish:** complete the integration to genuine Pro depth (e.g. Forms
  create/update; Events ticketing if we keep it), or
- **Demote:** move it to the free funnel / mark experimental and stop counting it as
  paid value; update the tool-count and capability docs (#322, #258) accordingly.

Theme handler:
- Fix or remove the corrupted `$supported_themes` entries; ensure every "supported"
  slug has a matching `get_<slug>_settings` / `update_<slug>_settings` handler (or
  drop the claim).
- De-duplicate the two `class-spai-themes.php` copies (single source of truth).

LearnPress:
- Audit and document actual read vs write capability before it is described or priced.

### Acceptance Criteria
- Each thin integration (Events, Forms, Multilang, LearnPress) has a recorded
  finish-or-demote decision and its docs/tool-count reflect it (consistent with #322).
- `$supported_themes` contains no duplicate keys, no placeholder entries, and every
  listed theme has a working settings handler (verified) or is removed.
- Only one canonical `class-spai-themes.php` remains (or the two are intentionally
  kept in sync with a documented reason).
- Tool-count and capability claims (#258/#322) match what actually ships.

---

## Draft 4 — Document and market runtime neutrality ("works with any agent runtime")

**Suggested labels:** `documentation`, `medium`, `mcp`

**Relates to:** #312 (AI-first event hooks — implementation of the harness-neutral event layer), Draft 1 (packaging).

### Problem
A core strategic advantage is that MCPWP is **harness-neutral**: because it speaks MCP
and the event layer (#312) is runtime-agnostic, MCPWP can be a supplier to AWS
AgentCore, Google Antigravity, Claude Managed Agents, OpenClaw, Hermes, n8n, and
others — rather than being locked to one runtime. #312 builds the event mechanism and
lists "Docs describe this as harness-neutral integration" as one acceptance line, but
it does not own the external positioning. Today there is no GTM/docs content making
this neutrality explicit — `README.md` has zero mention of AgentCore / Antigravity /
Managed Agents / OpenClaw / Hermes or "works with any agent runtime."

### Scope
Create the external-facing positioning and docs for runtime neutrality (the marketing/
docs counterpart to #312's implementation):
- README + website section: "Works with any agent runtime," framing MCPWP as the
  governed WordPress supplier to any MCP-speaking harness.
- Name the concrete runtimes (AgentCore, Antigravity, Claude Managed Agents, OpenClaw,
  Hermes, n8n) with a short "how it connects" note for each.
- One short integration recipe / quickstart showing MCPWP connected to a generic MCP
  client and at least one named runtime.
- Tie the message to the paid story from Draft 1 (governed, approval-safe changes are
  what you get regardless of which runtime you bring).

This issue is docs/positioning only — it must not re-specify the event hooks/webhooks
already owned by #312.

### Acceptance Criteria
- README and website have a clear "works with any agent runtime" section naming the
  target runtimes.
- At least one runtime-agnostic connection quickstart exists and is verified end to end.
- Wording is consistent with the event-layer terminology in #312 (no contradictory
  claims) and with the paid-tier framing in Draft 1.
- No duplication of #312's hook/webhook implementation spec.

---

## Draft 5 — Reframe public docs around outcomes, away from "239 tools" tool-count claims

**Suggested labels:** `documentation`, `medium`

**Relates to:** #322 (reconcile tool-count claims across docs/UI/MCP — fixes accuracy), #208 (demo GIF), Draft 1 (packaging), Draft 4 (runtime neutrality).

### Problem
Our public narrative still leans on the "239 tools" count as the headline value. That
number is exactly the dimension that is commoditizing (Meow Apps AI Engine, Elementor
Angie, official WP MCP Adapter all offer free MCP execution). #322 already covers
making the tool count *accurate/consistent*, but nothing covers *demoting it as the
headline* and leading with the defensible value (agent safety + SEO intelligence).

### Scope
- Rewrite the headline value proposition in README and the website to lead with
  outcomes (approval-safe AI edits, rollback, SEO intelligence, content coherence,
  Control Room) instead of a raw tool count.
- Keep an accurate tool count as a supporting detail, not the headline (defer accuracy
  mechanics to #322).
- Align with the free-funnel vs paid-tier split from Draft 1.

This is positioning/messaging; it depends on #322 for the underlying number and on
Draft 1 for the tier definition.

### Acceptance Criteria
- README and website headline value props lead with agent-safety + SEO-intelligence
  outcomes, not tool count.
- Any remaining tool-count mentions are accurate and consistent with #322.
- Messaging matches the free/paid split defined in Draft 1.

---

_Drafts only — none of the above has been filed. Use the GitHub UI/CLI to create them
once approved._
