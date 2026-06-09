# MCPWP / mupot — Strategy (canonical single source)

> **This is the one place the whole picture lives.** Thesis, the money model, the funnel, the roadmap.
> `BACKLOG.md` is the task tracker (issues + status). `docs/PRICING_TIERS.md` is the tier numbers detail.
> The [mupot repo](https://github.com/Mumega-com/mupot) holds the org-substrate code + its own roadmap.
> When those disagree with this doc, this doc is the intent; fix the others.
>
> **Last updated:** 2026-06-09.

---

## 1. Thesis in one paragraph

**mupot is the product. MCPWP is the gateway.** mupot is a sovereign, governed agent organization —
departments → squads → agents + humans — that a customer forks and deploys to their *own* Cloudflare, that
runs a real business under human-gated governance. MCPWP, the WordPress MCP plugin, is the most concrete,
biggest-market on-ramp into that org: "AI runs your WordPress" is tangible, and WordPress is 43% of the web.
A customer adopts MCPWP, points the AI agent they already pay for at their site, sees it work — and then
wants more surfaces, a squad instead of one agent, always-on instead of on-demand. That want *is* mupot.
We give the engine away and sell the **vertical snapshots** (a business-in-a-box) and the **agency resale
layer** on top. GoHighLevel's go-to-market, on a sovereign substrate the customer owns.

**Land-and-expand is agents and surfaces, not sites.**

---

## 2. Product vs wedge

| | mupot (the product) | MCPWP (the wedge) |
|---|---|---|
| What it is | Sovereign agent-org substrate on the customer's Cloudflare | WordPress MCP plugin — one surface the org operates |
| Role | Where the customer expands and where the money is | The first agent, the concrete entry, the biggest market |
| Monetized as | Snapshots + agency resale + hosted bus + DFY/support | Priced for **adoption** (generous free + cheap Pro) |
| Why it wins | Fork-it-own-it, runtime-agnostic, governed, no per-seat tax | "AI runs your WordPress" — tangible, huge TAM, fast value |

Do not optimize MCPWP's price in isolation. Its job is customer acquisition for the org. The LTV is the
upgrade *into a pot*, not the plugin license.

---

## 3. The product chain (one picture)

```
  BYO agent runtime           flock              the pot (org)            the work
  (any of 5, see §5)      →   check-in    →   department → squad   →   squad-pack (config)
  Hermes · openclaw ·         (live in        + humans as members        ↓
  Codex · Claude · ChatGPT     Fleet)          capability RBAC        metered, capped, GATED loop
                                                                          ↓
                                              surfaces it operates ←  MCPWP (WordPress) + GHL + social + repos
                                                                          ↓
                                              a full vertical SNAPSHOT = squad-pack + harness pack +
                                              loop + MCPWP blueprint + brand-crystal
                                                                          ↓
                                              AGENCY clones the snapshot per client, rebills  →  scale
```

Every arrow already has a contract in code: mupot `src/org/squad-packs.ts` (squad-packs), the
loop-manifest contract (metered/gated loops), the flock-harness-pack contract (any runtime joins), the
MCP `ResourceRef` seam (any MCP server — e.g. MCPWP — plugs into a loop with zero adapter code), and
MCPWP `wp_deploy_site_blueprint` (the WordPress half of a snapshot). The pieces exist; the work is
connecting and productizing them.

---

## 4. The money model (CFO view)

This is — and must stay — a **software / IP-margin business**, not a compute reseller.

| | |
|---|---|
| **Engine (mupot)** | Free. Fork it, own it. Runs on the **tenant's Cloudflare + tenant's model tokens.** Our marginal COGS per tenant ≈ **$0**. |
| **A snapshot / pack** | **Config** — a digital good, ~0 reproduction cost, with a **hard-capped governed runtime** (the meter blocks before any model call would breach budget, so nothing runs away). |
| **Where revenue sits** | (1) **snapshot / pack sales** — the configured vertical · (2) **agency white-label resale** — clone per client, rebill (highest LTV, lowest marginal cost) · (3) **hosted bus / flock plane** · (4) **DFY provisioning + support + the official brand**. |
| **MCPWP licensing** | Per-site Freemius license + official updates + support, priced for adoption. Precedent: **AI Engine** ($59/yr/site, BYOK, monetizes software not AI). |
| **Discipline** | BYOK by default → token-cost risk sits with the tenant and is hard-capped. Sell config + resale, never compute. |

### Why we don't paywall capability — and where the gate actually is
GPL code is forkable, the free primitives are expressive enough that a capable agent reproduces the Pro
convenience tools, and WordPress's own REST API routes around tool-gating. So **capability cannot be the
paywall.** The durable, un-routable gates are all off-code:

| Gate | Hard to route around? | Why |
|------|----------------------|-----|
| **What we host** — managed agent, agency proxy, snapshot delivery, hosted bus | **Hardest** | You cannot fork a server you do not own. |
| **License clause on the managed layer** (resale-barring, BSL/Sustainable-Use style; GPL core stays free) | Very hard (legal) | Stops an agency reselling *our managed service* as theirs. |
| **Official brand + update channel + trust** | Hard | GPL frees the code, not the name; forks get no updates and carry malware risk. |
| **Per-site license + support (Freemius)** | Medium | The entire WP market runs on this (Elementor, Yoast, WP Rocket). |
| **Agency white-label / sub-billing (GHL model)** | Medium | The reseller, not us, owns per-client consumption economics. |
| Feature-gating inside shipped GPL PHP | **Trivial** — abandon as a revenue gate | Confirmed by every comparable; use tool toggles for safety UX only. |

Full evidence and the tier numbers: `docs/PRICING_TIERS.md`.

**The CFO punchline:** marginal cost of one more tenant ≈ $0 (their CF, their tokens); spend is hard-capped
(no runaway support cost); revenue is selling config (IP-margin ~90%+) + agency resale (recurring,
high-LTV). The token risk is structurally pushed to the tenant and capped.

---

## 5. The fleet is the funnel

Every "bring-your-own-agent" runtime that speaks MCP is a door into a pot. mupot's flock is
**runtime-agnostic by design** — the moat versus single-vendor agent platforms and versus GHL (locked to
its runtime). The customer brings the brain they already pay for.

| Runtime | Always-on? | Auth | Connects | Pack priority |
|---------|-----------|------|----------|---------------|
| **Hermes Agent** (Nous) v0.16 | ✅ daemon + subagents + cron | bearer / OAuth2.1 / mTLS | **now** | **1st** — unattended autonomous loop |
| **openclaw** (systemd) | ✅ daemon | bearer + OAuth | **now** (streamable-http) | **1st** — cleanest bus client |
| **Codex** (GPT-5.4-Codex) | partial (Automations) | bearer | **now** | 2nd — supervise CLI for always-on |
| **Claude Code / Cowork / Managed** | desktop bound · Managed = cloud | OAuth Vaults | Managed needs **OAuth** | 2nd / post-auth |
| **ChatGPT Business** (Workspace Agents) | cloud | **OAuth 2.1 + DCR, no bearer** | needs **OAuth + DCR** | post-auth (biggest market) |

The fleet splits on auth: **bearer runtimes connect today**; **OAuth-only runtimes** (ChatGPT Business,
Claude Managed) are opened by the keystone build below. The always-on daemons (Hermes, openclaw) are where
the autonomous promise lives — they run *loops* unattended; desktop runtimes run human-in-loop *flights*.

---

## 6. The keystone: OAuth 2.1 + DCR (pulled forward to v2.9)

Not a wall someone else holds — **a build we own, and the single highest-leverage one.** Bearer runtimes
already connect. OAuth 2.1 + **Dynamic Client Registration** is the one thing that converts the two biggest,
otherwise-unreachable markets into reachable funnel: **ChatGPT Business** (connectors mandate OAuth 2.1 +
DCR; bearer refused) and **Claude Managed Agents** (OAuth Vaults). It opens both doors at once, so it ships
**with** Distribution at v2.9, not in a later v3.0. The same auth server admits OAuth runtimes into the
mupot bus flock, not just MCPWP-on-WP.

**Transport note:** confirm the bus serves **streamable-http** (MCP deprecated SSE 2026-04) — required by
Codex and openclaw.

---

## 7. Strategic objectives (the must-be-trues)

1. **Capability is free; the gate is off-code** — host, brand, license-trust, agency model.
2. **Stay software/IP-margin** — BYOK by default; never a token reseller.
3. **Governance + brand-crystal are the differentiator** — on-brand not slop; approval → rollback → audit;
   hard-capped metered loops. Hold these or it is a feature, not a company.
4. **WooCommerce beachhead, agencies as channel** — vertical ROI first; agencies = highest LTV.
5. **Runtime-agnostic fleet = the funnel** — every BYO runtime is a door in.
6. **Validate before widening** — prove the loop + one paying agency before building more engine.

---

## 8. Roadmap (the realigned milestone sequence)

| # | Version | Milestone | What it unlocks |
|---|---------|-----------|-----------------|
| **P0** | current builds | **MVP proof loop** | One Woo store → MCPWP + one pot → squad → Telegram approval. Validates the whole thesis. *Doable now.* |
| **M1** | v2.8.x | **Launch-Ready** | Free plugin + Pro funnel; zero-COGS recurring license. Admin UI ✅. Gated on Hadi: pricing (T87), privacy, install test, WAF. |
| **M2** | v2.9 | **Auth Keystone** (pulled ▲) | OAuth 2.1 + DCR → ChatGPT Business + Claude Managed fleet. Bearer fleet connects in parallel, now. |
| **M3** | v2.9 | **Distribution** | WP.org · MCP registries · ChatGPT GPT/App · Claude Connector. Rides on M2 auth. |
| **M4** | v3.2 | **Hosted Marketing Agent** | agent.mcpwp.net + mupot = the P0 loop productized. First hosted gate; metered, capped, BYOK/pass-through. |
| **M5** | v4.0 | **Content Engine** | Keyword research (live) · Telegram/social distribution · Remotion video. |
| **M6** | v5.0 | **Platform Foundations** | `spai_`→`mcpwp_` rebrand + microkernel. |
| **M7** | v6.0 | **Snapshot System** | Vertical snapshots (squad-pack + harness pack + loop + MCPWP blueprint + brand-crystal): Woo, LearnPress. |
| **M8** | v6.x | **Agency Reseller — THE FULL ONE** | White-label SaaS-mode · client sub-billing · snapshot marketplace · sovereign deploy. Highest LTV. |

**The spine:** P0 proves one loop → M4 productizes it as a hosted agent → M7 generalizes it into reusable
snapshots → M8 resells them at scale. Revenue maturity tracks it: M1 = recurring license (now, zero-COGS) →
M4 = metered managed-compute (first hosted gate) → M8 = agency white-label (highest LTV).

### Sequencing — now / next / later
- **NOW (no version bump):** run the **P0 MVP loop** end-to-end + measure it; land **one paying agency**;
  wire the **Hermes + openclaw** bearer packs; close M1 launch gates.
- **NEXT (v2.9):** build **OAuth 2.1 + DCR** + Distribution together; confirm streamable-http on the bus.
- **LATER (v3.2 → v6.x):** hosted agent → content engine → platform hardening → snapshots → agency SaaS-mode.

**Binding constraint now is not engine width — it is P0 validation + M1 pricing sign-off.** Everything past
M3 waits on one question: *will one agency pay?*

---

## 9. Build-state discipline (sell only what's built)

| Capability | State | Earliest |
|-----------|-------|----------|
| On-brand content, SEO, content-graph, images, approvals/rollback, brand-crystal, Woo SEO, blueprints, keyword research | ✅ built | now |
| Metered/capped/gated loop runtime, squad-packs, harness-pack contract, MCP ResourceRef seam | ✅ built (mupot) | now |
| OAuth 2.1 + DCR, streamable-http on the bus | 🔶 to build | v2.9 |
| Hosted agent (agent.mcpwp.net), Telegram/social, Remotion video | 🔶 specced | v3.2–v4.0 |
| Vertical snapshot product + agency SaaS-mode + sub-billing | 🔶 parts exist, not productized | v6.0–v6.x |

The pricing page and the marketplace launch on Phase-1 (built) promises only. Later-phase items are listed
as "included as they ship," never as live today.
