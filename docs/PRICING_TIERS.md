# Pricing — taxing the economy (decision spec)

> **Status:** proposal for Hadi sign-off (the analysis half of T87 / #417).
> **Decision owner:** Hadi — this supplies the model + the numbers; public pages don't ship until confirmed.
> **Frame:** see `docs/STRATEGY.md` — **we design an economy and tax its flows.** We do not price a tool.
> This supersedes the per-site-license-spine and compute-spine drafts.
> **Last updated:** 2026-06-09.

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

| SKU | Price (sketch) | What it is |
|-----|---------------|-----------|
| **MCPWP Free** | $0 | WP.org build, full tool surface, brand-crystal, approvals/rollback. BYOK. The funnel. |
| **MCPWP Pro** | **$99/site/yr** ($199/3, $299/25) | Official build + updates + support + Pro convenience/plugin-ops tools. BYOK. Adoption-priced toll. (AI Engine $59 · Yoast $118 · Elementor 1/3/25 anchors.) |
| **Pot hosting / bus rent** | **$/mo per pot** | Hosted flock plane (mcp.mumega.com), observability, distribution pipes — for tenants who don't fully self-host. |
| **Managed energy** (add-on) | **capped $/mo or BYOK** | For owners who won't BYOK: we run the loop. Metered — platform actions separate from model cost; hard caps; never flat-unlimited. Most tenants self-host → ~$0 COGS to us. |

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

## 5. Open items for Hadi

1. **Marketplace cut %** — 20% standard / 15% small-builder / 30% certified-snapshot? (recommend those)
2. **Snapshot pricing model** — one-time vs subscription per vertical? This is the real product price.
3. **MCPWP Pro toll** — $99/yr + 1/3/25 site ladder? (recommend yes — adoption over revenue)
4. **Pot-hosting / bus-rent price** — $/mo per pot for the managed flock plane?
5. **Managed-energy caps** — task cap + per-task ceiling + action/model-cost split.
6. **Freemius vs own checkout** — toll via Freemius; marketplace needs its own transaction layer + payout.

Once 1–4 are confirmed, the public pricing + the marketplace listing terms write in one pass.

---

## 6. Why it's defensible

Not tool count (commoditizing: WP 6.9 Abilities API, Angie, AI Engine). The economy's value lives in the
**marketplace + the attractor + the network + the observability** — none of which forks with the code. An
agency can copy the engine; it cannot copy the market liquidity, the distribution gravity, the cross-site
learning, or the performance-ranking signal. The toll is cheap so the economy is valuable; coherence is the
currency that keeps the whole thing from inflating into slop.
