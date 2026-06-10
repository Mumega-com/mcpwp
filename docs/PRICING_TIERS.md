# Pricing — taxing the economy (decision spec)

> **Status:** **DECIDED 2026-06-10** (Hadi sign-off, T87 / #417) — numbers validated against
> market evidence in `docs/PRICING_RESEARCH_2026-06.md`. One cell deliberately blank:
> the managed-energy credit price awaits the #449 $/task measurement.
> **Frame:** see `docs/STRATEGY.md` — **we design an economy and tax its flows.** We do not price a tool.
> This supersedes the per-site-license-spine and compute-spine drafts.
> **Last updated:** 2026-06-10.

---

## 1. The principle

The earlier drafts priced *software*. Wrong object. We operate an economy; we **tax its flows**. Marginal
COGS per tenant ≈ $0 (their Cloudflare, their tokens), so this is a software/IP-margin business: we monetize
**marketplace transactions + recipes + rent + an adoption toll** — never compute.

Five revenue streams, ranked by how much they scale and how defensible they are:

| # | Stream | What it taxes | Why it's defensible | Scales? |
|---|--------|---------------|---------------------|---------|
| 1 | **Marketplace cut** | every addon + snapshot sale | App-Store model; we don't build the items | ✅✅ unbounded |
| 2 | **Performance-ranked snapshot premium** | proven, data-certified loadouts | only our observability can rank by real performance | ✅✅ unique |
| 3 | **Infra rent** | hosted bus, distribution pipes, observability | you can't self-run what we host | ✅ recurring |
| 4 | **On-ramp toll** | per-site MCPWP license + official build + support | the WP market already pays this (AI Engine $59/yr) | ✅ recurring |
| 5 | **Metered energy** (pass-through) | compute, at-cost / BYOK | not a profit center — alignment + caps | — |

**The big money is #1 and #2 — the marketplace.** #3/#4 are the recurring floor. #5 never profits.

---

## 2. The marketplace (the revenue engine)

Items and recipes others build; we curate, rank, and tax.

- **Addons / skills** = items. Telegram, WhatsApp, service bindings, tools. Builders set a price (one-time
  or subscription); **we take a cut** (App-Store standard: **15–30%** — recommend **20%**, 15% for small
  builders to seed liquidity).
- **Snapshots** = recipes — curated mixes of addons + skills + config = a deployable vertical. Tradeable
  meta-items. Builders sell loadouts; same cut.
- **Performance-ranked snapshots** = the unique SKU. Because we see every project's PostHog, we can **certify
  a loadout by real outcomes** ("converts at X% across N stores"). Certified recipes command a premium and a
  **larger cut** (recommend **30%**) — nobody else can produce this signal.

Cold-start: **we are the first builder.** Our own addons + our own dogfood-store snapshot seed the shelves
before third parties arrive.

---

## 3. Infra rent + the on-ramp toll (the recurring floor)

| SKU | Price (decided 2026-06-10) | What it is |
|-----|---------------|-----------|
| **MCPWP Free** | $0 | WP.org build, full tool surface, brand-crystal, approvals/rollback. BYOK. The funnel. |
| **MCPWP Pro** | **$59/yr/1 · $99/yr/3 (headline) · $199/yr/25** + lifetime $199/$349/$699 | Official build + updates + support + Pro tools. BYOK. The $59 entry is the real adoption toll (entry cluster is $49–59: AI Engine, Elementor, WP Rocket; Novamira €49/3); $99 buys 3 sites, mirroring Elementor Advanced. Lifetime ≈3.5× annual — proven WP segment. |
| **Pot hosting / bus rent** | **$59/mo incl. 10 sites + $4/site/mo**, white-label bundled | Hosted flock plane (mcp.mumega.com), observability, distribution pipes. Sits in the empty $5–15/site band above commodity management tools ($2–5/site). |
| **Managed energy** (add-on) | **credits = measured $/task × 4 — price blank until #449** | For owners who won't BYOK: we run the loop. Monthly credits (no rollover) + top-ups (roll over), hard stop at 0, ~10k-token/task ceiling via `cap_micro_usd`, BYOK tier = platform fee with zero model markup. Never flat-unlimited. |

The toll is deliberately cheap. Its job is **adoption into the economy**, not revenue. The economy taxes the
flows above it.

---

## 4. Margin discipline (so the economy doesn't bleed)

1. **BYOK / pass-through model cost** at no markup — never profit on tokens.
2. **The mupot meter** — hard pre-call `cap_micro_usd`; loops block before overspending.
3. **Hard caps + top-ups** on any managed-energy SKU; never flat-unlimited (one power user wipes six).
4. **Self-host escape valve** — heavy users run on their own Cloudflare; they never sit on our margin.
5. **Coherence gate = anti-inflation** — junk burned before it floods the marketplace or trips Google. Quality
   protects the currency.

---

## 5. Decisions (signed 2026-06-10 — evidence: `docs/PRICING_RESEARCH_2026-06.md`)

1. **Marketplace cut** — **0% founding-builder window** (until June 2027 or first $25K per
   builder, whichever first), pre-announced in the listing terms from day one, stepping up to
   **15% small-builder / 20% standard / 30% certified**. Rationale: every successful challenger
   marketplace bought liquidity at 0% (GHL through 2026, Atlassian/Shopify 0%/first-$1M);
   builders' fallback is Freemius at ~10.5% all-in. The 30% certified tier activates only once
   the certification badge demonstrably lifts conversions.
2. **Snapshot pricing model** — **builder's choice** (one-time or subscription) for standard
   snapshots; **certified snapshots are subscription-only** — certification is re-validated
   monthly from live performance data, which is what justifies both the recurring price and
   the larger cut.
3. **MCPWP Pro toll** — **$59/yr/1 site · $99/yr/3 sites (headline) · $199/yr/25 sites**, plus
   lifetime at $199/$349/$699 (~3.5× annual). The earlier $99/single-site draft was mid-market,
   not an adoption toll; the adoption story requires the $59 entry.
4. **Pot-hosting / bus-rent** — **$59/mo including 10 sites, then $4/site/mo**, white-label
   bundled (not an add-on).
5. **Managed-energy caps** — structure signed: monthly credits (no rollover) + purchasable
   top-ups (roll over), **hard stop at 0** (never auto-overage), ~10k-token per-task ceiling
   enforced via the `cap_micro_usd` meter, BYOK tier = platform fee with zero model markup.
   **Credit price = measured $/task × 4 — the one blank cell, filled by #449.**
6. **Checkout rails** — **Freemius for the toll** (MoR/VAT + licensing + updates worth ~10.5%
   all-in at launch volume; revisit vs EDD self-hosted around 500+ sales/yr); **Stripe Connect
   for the marketplace cut** (platform absorbs ~3.5% processing from its application fee).

The public pricing page and marketplace listing terms now write in one pass; only the energy
credit price waits on #449.

---

## 6. Why it's defensible

Not tool count (commoditizing: WP 6.9 Abilities API, Angie, AI Engine). The economy's value lives in the
**marketplace + the attractor + the network + the observability** — none of which forks with the code. An
agency can copy the engine; it cannot copy the market liquidity, the distribution gravity, the cross-site
learning, or the performance-ranking signal. The toll is cheap so the economy is valuable; coherence is the
currency that keeps the whole thing from inflating into slop.
