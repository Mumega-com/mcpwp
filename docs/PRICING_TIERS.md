# MCPWP Pricing Tiers — Decision Spec

> **Status:** Proposal for Hadi sign-off (closes analysis half of T87 / #417).
> **Owner of the decision:** Hadi. This doc supplies the numbers + the economics that make them defensible. The public pricing page on mcpwp.net does not ship until the numbers below are confirmed.
> **Last updated:** 2026-06-09. Supersedes ad-hoc "$2/site" plugin framing.

---

## 1. The one decision this doc forces

Pick a billing model **before** a price. There are three, and only one is safe for managed autonomous-marketing compute:

| Model | What it is | Verdict |
|-------|-----------|---------|
| **Flat-unlimited pooled** | Flat $/mo, unlimited agent tasks on our compute | ❌ **Trap.** One power user on uncached Sonnet (~$6.7/task) wipes the margin of 6 light users. Never offer. |
| **Task/outcome-capped pooled** | Flat $/mo, **N agent tasks/mo** on our compute, overage or upgrade past cap | ✅ **Default.** Margin-safe *because* it is capped. This is the GHL/Relevance pattern. |
| **BYOK (bring your own key)** | Customer supplies their own model key; we charge flat for orchestration only | ✅ **Always offered as an option.** Sidesteps token risk entirely; required on Agency. |

**Rule:** charge flat for **orchestration + governance**, pass model cost at-cost or via BYOK, **never profit on tokens.** Agencies on the top tier rebill their own clients with markup — that is their business, not ours.

---

## 2. What a "task" is (the unit we cap and price on)

A **task** = one governed agent objective that ends in an approval-gated change or a delivered artifact. Examples, each = 1 task:

- Build/redesign one page (on-brand, from brand-crystal) → approval.
- Write + SEO-optimize one blog post or product description → approval.
- Run a site SEO audit and produce an autofix plan → approval.
- Generate + place one batch of internal links → approval.

Measured cost basis (issue #449, 2026-06-09): a page-build task ≈ **~30 model turns**. `tools/list` schema weight re-sent each turn unless cached: **~20k tokens (159 free tools) / ~35k tokens (all 269)**.

| Per-task cost | Uncached | Cached (prompt cache) | Cached + tool-subsetting (T102) |
|---------------|----------|----------------------|-------------------------------|
| Haiku | ~$2.7 | ~$0.9 | ~$0.3–0.5 |
| Sonnet | ~$6.7 | ~$2.5 | ~$0.6–1.2 |
| Opus | ~$15 | ~$5 | ~$1.5–2.5 |

**Two cost levers are mandatory before any pooled tier ships:**
1. **Prompt caching** — 3–4× reduction. (Cache the tool schema + system context.)
2. **Tool subsetting / semantic tool search** (T102, Vectorize) — loads ~30 of 269 tools per task → schema 35k → ~5k tokens.

Pooled-compute pricing below **assumes both levers live.** Until they are, pooled tiers run BYOK-only or stay in private beta.

---

## 3. Price anchor: marketing budgets, not plugin prices

We are not priced against "$2/site WP plugins." We are priced against the **marketing spend** a Woo store or agency already carries: **$300–3000/mo** (a fractional marketer, a content retainer, or a GHL/Jasper/HubSpot seat). Every tier below sits *under* the human-labor cost it displaces.

Competitive anchors at this altitude (not AI Engine — the real comps):
- **GoHighLevel:** $97 / $297 / $497 (agency SaaS-mode resell). Closest analog; our differentiator is sovereign + WP-native + living agents.
- **Jasper:** $49–$125/seat content-only.
- **HubSpot Marketing:** $800+/mo.

---

## 4. Proposed tiers

| Tier | Price | Sites | Task cap/mo | Compute | Who | Headline |
|------|-------|-------|-------------|---------|-----|----------|
| **Free** | $0 | 1 | BYOK only | BYOK | DIY, devs, funnel | "Connect your AI to WordPress." Full free tool surface, brand-crystal, approvals/rollback. |
| **Growth** | **$99/mo** | 1 | **~40 tasks** | Pooled (capped) **or** BYOK | Solo Woo store / course creator | "An on-brand marketer for your store." Content + SEO + images, governed. |
| **Pro** | **$299/mo** | 3 | **~150 tasks** | Pooled (capped) **or** BYOK | Growing store / power user | Everything in Growth + LearnPress, WooCommerce SEO, video (M4), multi-channel (M4). |
| **Agency** | **$499–997/mo** | Unlimited (fair-use) | BYOK (markup to clients) | **BYOK required** | Agencies reselling snapshots | White-label, sub-billing, per-client snapshots, compute markup is theirs. GHL SaaS-mode analog, sovereign. |

Notes:
- **Task caps are deliberate, not stingy.** They are the margin guardrail (§1). Overage = auto-upgrade prompt or per-task pack, never a silent flat-unlimited slide.
- **Agency is BYOK-required by design** — at unlimited sites we cannot pool-fund token cost; agencies want their own key + markup anyway. We charge them flat for white-label + sub-billing + governance.
- **Free is the funnel, not a loss leader on our tokens** — Free is BYOK, so it costs us ~$0 in compute. This is what makes a generous free tier safe.

### Margin proof (Growth $99, the riskiest pooled tier)
- Cap: ~40 tasks/mo. Blended model mix (Haiku-heavy for mechanical tasks, Sonnet for judgment), caching + subsetting live: **~$0.5–1.0/task → ~$20–40/mo COGS.**
- CF backend COGS ≈ $0.001/session → negligible.
- **Gross margin at cap ≈ 60–80%.** A user who hits the cap and wants more upgrades to Pro (better margin per dollar) or buys a task pack. Margin holds *because* of the cap.
- Without caching+subsetting the same 40 Sonnet tasks = ~$100–270 COGS → **negative.** This is why §2's two levers gate the pooled tiers.

---

## 5. What each tier may promise (staged — sell only what is built)

Discipline from the GTM thesis: **do not sell what isn't built.** Stage the promise to the roadmap.

| Capability | State | Earliest tier/phase |
|-----------|-------|---------------------|
| On-brand content (brand-crystal + coherence) | ✅ built | Growth, now |
| SEO audit / autofix / readiness | ✅ built | Growth, now |
| Content-graph + internal linking | ✅ built | Growth, now |
| Image generation (OpenAI/Gemini) | ✅ built | Growth, now |
| Approvals → apply → **rollback** + audit | ✅ built | all tiers, now |
| WooCommerce SEO | ✅ built | Pro, now |
| LearnPress LMS | ✅ built | Pro, now |
| Site-blueprints (snapshot half) | ✅ built | Agency, now |
| Keyword research (`wp_keyword_research`) | ✅ built (v2.8.52) | Growth, now |
| Hosted always-on agent loop (agent.mcpwp.net) | 🔶 specced (M4/M5) | Pro/Agency, phase 2 |
| Telegram / social distribution | 🔶 specced (M4) | Pro, phase 2 |
| Remotion video pipeline | 🔶 specced | Pro, phase 2 |
| Full mupot "alive" org loop | 🔴 horizon | phase 3 |

**Phase 1 (sellable now-ish):** on-brand autonomous content + SEO for Woo, human-governed, BYOK or capped-pooled. **Phase 2:** multi-channel + video. **Phase 3:** always-on. The pricing page launches on Phase-1 promises only; Pro lists Phase-2 items as "included as they ship," not as live today.

---

## 6. Open items for Hadi

1. **Confirm the four price points** ($0 / $99 / $299 / $499–997) or adjust. The economics in §4 hold anywhere in the $99–299 band for pooled tiers given the cost levers.
2. **Confirm task caps** (~40 / ~150). These are the margin guardrail — moving them moves COGS linearly.
3. **Agency top number:** $499, $697, or $997? GHL anchors $497; sovereign + WP-native justifies a premium. Recommend **$697** as the defensible middle.
4. **Gate check:** prompt caching + tool-subsetting (T102) must be live before pooled Growth/Pro leave private beta. Confirm that sequencing.
5. **Freemius mapping:** Growth/Pro/Agency → Freemius plans + license tiers. Free tier stays WP.org BYOK.

Once 1–3 are confirmed, the mcpwp.net pricing page copy (T87) can be written in one pass from this table.

---

## 7. Why this is defensible (the moat the price rides on)

Tool count is commoditizing (WP 6.9 Abilities API + MCP, Angie, AI Engine). The premium is **not** tool count. It is:

- **Governance:** approval → apply → **rollback** + audit. Nobody else at this price has rollback + audit.
- **Brand-crystal:** site-context (brand identity) + content-coherence (on-brand scoring) + site-memory (persistent brand decisions) → generated content is **on-brand, not slop.**
- **Sovereign:** runs on the customer's own Cloudflare (fork-it-own-it), no platform-hostage risk, no per-seat tax (scale-to-zero).
- **WP-native breadth:** Woo / LearnPress / membership / 60k plugins vs GHL's closed set.

Hold those four or it is a feature, not a company. The price is anchored to the marketing labor it replaces — not to the plugin it is built as.
