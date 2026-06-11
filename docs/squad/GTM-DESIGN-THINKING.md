# GTM Hardening — Design Thinking Pass (2026-06-11)

> 5-phase d.school run on: "MCPWP is hardening to go to market with 10 customer installs live."
> Method: design-thinking skill. Grounding: live fleet data, 52 published posts, dogfood audits,
> tenant organism YAMLs, today's market research.

## 1. Empathize — four users, grounded

### Persona A — The agency operator (Hadi-shaped; the Agency-tier buyer)
- **Does:** admin on 10 client WP sites; pays GHL for funnels/CRM; runs agents from a phone on the street.
- **Says:** "I can't install a plugin on mobile." "Stop storing the plugin on mumega."
- **Pains:** per-site toil (keys, integrations, updates ×10); fear of an update breaking a client site; tool sprawl (WP + GHL + agents + analytics not one loop).
- **Gains sought:** fleet-wide operations from one gate; an audit trail clients can be shown; billing leverage ("AI ops" as a service line).

### Persona B — The customer site owner (dentist / course seller / consultant — per tenant YAMLs)
- **Does:** runs a business, not a website. Never heard of MCP.
- **Thinks:** "Will this break my site? Who changed my page?"
- **Pains:** invisible changes; jargon; anything that needs their time.
- **Gains sought:** more leads, site stays up, someone accountable. **They buy outcomes + safety, never tools.**

### Persona C — The AI agent as user (Cowork / ChatGPT Business / Hermes session)
- **Does:** connects, calls `wp_onboard`, builds pages, runs SEO loops.
- **Pains:** wrong widget keys silently break renders; 200-tool lists blow context (Gemini 100-tool limit); stale docs (post 720 ships `spai_` endpoint).
- **Gains sought:** deterministic schemas, fast onboarding brief, scoped keys, error hints. **The agent is a first-class persona — it converts or churns in the first session** (download → first tool call).

### Persona D — The WP developer / agency evaluating (the WP.org browser)
- **Pains:** plugin trust (43k abandoned plugins), "is this another wrapper?", official mcp-adapter exists — "why pay?"
- **Gains sought:** governance the official adapter lacks, Elementor depth, one-click rollback, proof (live demo site).

## 2. Define

### POV statements
1. **Agency operators** need to operate N client sites through one gated surface, because their unit economics break when every site means separate keys, updates, and risk.
2. **Site owners** need every AI change to be approvable, attributable, and reversible, because their trust is the product being sold — not the AI.
3. **AI agents** need the first session to succeed in minutes, because the conversion event is the first successful tool call, not the download.

### How Might We (priority order)
1. HMW make the **first connection** a < 10-minute guaranteed success on every runtime (Cowork, ChatGPT Business, Antigravity, Codex, Hermes, OpenClaw)?
2. HMW let one operator run a **10-site fleet** as one surface (keys, updates, approvals, audit) without per-site toil?
3. HMW make **safety visible enough to sell** — approval queue + rollback + audit as the demo, not the fine print?
4. HMW cross the v3 slug change on customer sites with **zero customer action**?
5. HMW turn our own fleet's PostHog exhaust into the **performance-ranked snapshot** moat?

## 3. Ideate (clustered; judged against the two-plane law)

| Cluster | Ideas | Verdict |
|---------|-------|---------|
| First-session success | Per-runtime connect guides as plugin-served `wp_get_guide` topics; onboarding wizard emits a copy-paste config per client; `wp_introspect` advertises layout mode + key format; kill stale `spai_` snippets (#502) | **Do** — cheapest conversion lever |
| Fleet-as-one-surface | Agency proxy (exists in spai-proxy-worker) + fleet page (STRATEGY §4 representation layer); per-tenant scoped keys from one dashboard; bridge release auto-migration (#505) | **Do** — Agency tier justification |
| Sellable safety | Control-Room demo video on pricing page; approval-queue screenshot in WP.org listing; "EU AI Act ready" audit CSV in marketing copy | **Do** — differentiation vs official adapter |
| Zero-action crossing | 2.8.57 bridge w/ dual-prefix key auth (#505) | **Do** — already filed |
| Moat-building | PostHog cross-fleet dashboards; snapshot performance ranking; A/B at edge (Inkwell) | **Later** — post-revenue |
| Wild | MCPWP as default WP.com-competitor "agentic hosting" bundle w/ VPS+mupot | **Parked** — revisit after GA |

## 4. Prototype

The prototype of an operating system is a runbook. → `docs/GTM-RUNBOOK.md` (phases A–F,
issue-linked, owner-tagged). Document-prototypes to build next: per-runtime connect one-pagers,
Control-Room demo script (docs/demo-script.md exists — refresh), fleet-page spec.

## 5. Test (what "working" means)

| Hypothesis | Metric | Instrument |
|------------|--------|------------|
| First-session success drives conversion | median download→first `mcp_tool_called` < 10 min | PostHog funnel per site UUID |
| Safety sells | pricing-page → download CTR after demo embed | PostHog web + UTM |
| Fleet surface justifies Agency tier | ≥3 of 10 tenants operated through proxy in 30 days | proxy logs |
| Bridge release is safe | 0 deactivated plugins across 12-site crossing | fleet version report |

Iterate: re-run this file's §5 table monthly; failed hypothesis → new HMW → next pass.
