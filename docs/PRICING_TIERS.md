# Pricing — MCPWP wedge + mupot product (decision spec)

> **Status:** proposal for Hadi sign-off (the analysis half of T87 / #417).
> **Decision owner:** Hadi — this doc supplies the numbers + the economics; the public pricing page does
> not ship until the numbers are confirmed.
> **Frame:** see `docs/STRATEGY.md` — **mupot is the product, MCPWP is the adoption wedge.** This doc prices
> both layers consistently. Supersedes the earlier compute-spine draft.
> **Last updated:** 2026-06-09.

---

## 1. The frame that sets every number

Two layers, priced for two different jobs:

- **MCPWP (the wedge)** — priced for **adoption**. Generous free + cheap per-site Pro. Its job is to land the
  first agent on a WordPress site, not to be the revenue. Precedent: **AI Engine** ($59/yr/site, BYOK,
  monetizes the *software* not the AI).
- **mupot (the product)** — priced for **value**. The org, the snapshots, the agency resale. This is where
  the money is, because this is what the customer expands into.

**The engine is given away** (mupot is fork-it-own-it, runs on the tenant's Cloudflare + tenant's tokens).
Marginal COGS per tenant ≈ $0. So this is a **software / IP-margin business**: we sell config + resale +
hosting + support, never compute. (Full gate analysis: `docs/STRATEGY.md` §4.)

---

## 2. Billing model — pick it before any price

| Model | What it is | Verdict |
|-------|-----------|---------|
| **Per-site license (BYOK)** | Flat $/site/yr; the user's own agent + tokens | ✅ **The MCPWP spine.** ~0 COGS, ~90% margin, recurring. AI Engine proves it. |
| **Snapshot / pack sale** | Sell the configured vertical (squad-pack + blueprint + brand-crystal) | ✅ **The mupot spine.** Digital good, ~0 reproduction cost, IP-margin. |
| **Agency white-label + rebill** | Flat platform fee; agency rebills its own clients | ✅ **Highest LTV.** Agency owns per-client consumption (GHL model). |
| **Managed compute (our infra)** | We run the agent, our tokens | 🟡 **Add-on only**, hard-metered. The only tier with real COGS. |
| **Flat-unlimited pooled compute** | Flat $/mo, unlimited tasks on our compute | ❌ **Never.** One power user wipes six. |

**Rule:** charge flat for orchestration + governance + config; pass model cost through at-cost or BYOK;
never profit on tokens. The mupot meter hard-caps every loop's spend (`cap_micro_usd`), so even the managed
tier cannot run away.

---

## 3. MCPWP tiers (the wedge — priced for adoption)

| Tier | Price | Sites | Gate (enforced off-code) | Compute |
|------|-------|-------|--------------------------|---------|
| **Free** | $0 | 1 | WP.org build, full tool surface, brand-crystal, approvals/rollback | BYOK (their agent) |
| **Pro** | **$59–99/site/yr** | 1 → 3 | Freemius license + official updates + support + Pro convenience/plugin-ops tools | BYOK |
| **Pro multi-site** | **$199–299/yr** | up to 25 | same, site-count ladder (Elementor/WP-Rocket pattern) | BYOK |

Anchors: AI Engine $59/yr · Yoast $118/yr/site · Elementor $59/1 → $199/25. We gate **sites + support +
updates + the official build**, *not* capability. Free is BYOK → ~$0 COGS, so a generous free tier is safe
and is the funnel into a pot.

---

## 4. mupot tiers (the product — priced for value)

| Tier | Price (sketch) | What it is | Gate |
|------|---------------|-----------|------|
| **Snapshot** | **$X one-time or $/mo per vertical** | A configured business-in-a-box (squad-pack + harness + loop + MCPWP blueprint + brand-crystal). Woo store, course creator. | The IP/config + brand + the official build. |
| **Managed Agent** (add-on) | **$99–299/mo, capped** | We host the agent loop for customers who won't BYOK. Metered: platform actions separate from model cost; BYOK or pass-through; hard task caps. | Runs on **our** infra — the hardest gate. |
| **Agency** | **$497–997/mo** | White-label, multi-client, sub-billing, snapshot resale. The GHL SaaS-mode analog, sovereign. | Resale-barring license on the *managed layer* (GPL core stays free) + white-label infra. |

Notes:
- **Snapshot price** is the open number — it is the vertical's value (a working marketing org), not a plugin
  price. Anchor to the marketing labor it replaces ($300–3000/mo), not to $2/site.
- **Managed Agent** is the *only* tier with real COGS; it exists for the no-BYOK segment and is priced to
  never lose money (caps + action/model split). Most tenants self-host on their own CF → ~$0 COGS to us.
- **Agency** is the durable high-LTV tier: recurring, multi-client, high switching cost, low marginal cost.

---

## 5. Margin protection (proven across comps)

For any tier where we touch compute:
1. **BYOK / pass-through model cost** at no markup (Relevance AI, AI Engine, GHL wallet).
2. **The mupot meter** — hard pre-call `cap_micro_usd`; the loop blocks before overspending.
3. **Weight by expense** (image 33× text; advanced model 10×) where credits are used.
4. **Hard caps + top-ups**, never flat-unlimited.
5. **Self-host escape valve** — heavy users run on their own CF (n8n pattern); they never sit on our margin.
6. **Agency rebilling** — the reseller owns consumption economics.

---

## 6. Open items for Hadi

1. **MCPWP Pro price** — $59 or $99 for 1 site? Recommend **$99/yr** (above AI-Engine to fund support, still
   adoption-friendly) with a $199/3-site and $299/25-site ladder.
2. **Snapshot price + shape** — one-time vs monthly? Per vertical? This is the real product price and the
   biggest open number.
3. **Managed Agent caps** — task cap + per-task ceiling; confirm action/model-cost split.
4. **Agency top number** — $497 / $697 / $997. GHL anchors $497; sovereign + WP-native justifies a premium.
   Recommend **$697**.
5. **Gate check** — prompt caching + tool-subsetting (T102) live before the managed tier leaves beta.
6. **Freemius mapping** — MCPWP Free (WP.org BYOK) / Pro / Pro-multisite → Freemius plans; mupot tiers →
   separate checkout.

Once 1–4 are confirmed, the mcpwp.net pricing page + the mupot pricing page write in one pass from these
tables.

---

## 7. Why the price is defensible

The premium is **not** tool count (commoditizing: WP 6.9 Abilities API, Angie, AI Engine). It is:
**governance** (approval → rollback → audit + hard-capped metered loops), the **brand-crystal** (on-brand,
not slop), **sovereignty** (the customer's own Cloudflare, no per-seat tax, no platform-hostage risk), and
**WP-native breadth** (Woo / LearnPress / 60k plugins vs GHL's closed set). The price is anchored to the
marketing labor it replaces — not to the plugin it is built as. The wedge is cheap so the org can be
valuable.
