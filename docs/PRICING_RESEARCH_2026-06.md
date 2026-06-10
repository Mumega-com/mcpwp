# Pricing research — market evidence for T87 (June 2026)

> Five-angle web research run 2026-06-10 (official pricing pages fetched where possible).
> This is the evidence behind the decisions recorded in `docs/PRICING_TIERS.md` §5.
> Full per-source citations live in the session transcripts; key figures reproduced here.

---

## A. WordPress AI/SEO plugin pricing (validates the Pro toll)

| Product | Entry | Mid | High |
|---|---|---|---|
| AI Engine (Meow) | $59/yr/1 site | $79/yr/5 | $149/yr/20 · lifetimes $449–$1,499 |
| Yoast SEO Premium | $118.80/yr/1 site | — | — |
| Rank Math | $95.88–107.88/yr unlimited personal | $299.88/yr/100 | $659.88/yr/500 |
| Elementor Pro | $59/yr/1 | **$99/yr/3** | $199/yr/25 · $399/yr/1,000 |
| WP Rocket | $59/yr/1 | $119/yr/3 | $299/yr/50 |
| ACF Pro | $49/yr/1 | $149/yr/10 | $249/yr/unlimited |
| **Novamira Pro (direct MCP rival)** | **€49/yr/3 sites** | €99/yr/1,000 | €199 lifetime/1,000 |

**Read:** entry cluster $49–59; $99 buys 3 sites at Elementor; the nascent WP-MCP category
is pricing *low*; lifetime tiers are a proven WP segment (~3–4× annual).

## B. Marketplace take rates (validates the cut)

| Platform | Rate | Note |
|---|---|---|
| Shopify | 0% first $1M **lifetime**, then 15% | annual reset removed Jun 2025 |
| Apple / Google small-business | 15% | the recognized "fair" benchmark |
| Atlassian Forge | **0% first $1M lifetime** (from Jan 2026), then 16–17% | migration incentive |
| GoHighLevel | **0% through Dec 31, 2026** | explicit cold-start subsidy |
| Envato | flat 50% from Jul 2026 | the ceiling nobody should copy |
| WordPress.com marketplace | 30% — **closed to new submissions** (May 2025) | the WP-native slot is open |
| Freemius (sellers' fallback) | ~7% + ~3.5% gateway ≈ 10.5% all-in | repriced Oct 2025 |
| OpenAI GPT Store / Anthropic MCP | no rev-share program / no official marketplace | first-mover window open |

**Read:** 15/20/30 is inside norms, but every successful challenger launched at 0%.

## C. WP agency tool pricing (anchors the proxy SKU)

Management-only market: **$2–5/site/mo typical** (WP Umbrella $2.19, WP Remote $1.99–4.99,
ManageWP bundle ≈$1.50@100); **$5–15/site/mo band is nearly empty** — AI orchestration +
audit/rollback has no established public price. Closest comp: Master Control Press
(MCP-based, ≈$3.32/site/mo @50). Agencies prefer flat-base + per-site overage (Patchstack
pattern). Commoditization signals: official WordPress MCP Adapter (Feb 2026), Pressable
bundles MCP management at $0.

## D. AI-agent work pricing (designs the energy SKU)

| Product | Unit | Price |
|---|---|---|
| Intercom Fin | resolution | $0.99 (the outcome-priced ceiling) |
| Salesforce Agentforce | action (≤10k tokens) | $0.10 |
| Zapier | task / agent activity | ~$0.04–0.08 / $0.022 |
| Manus | credit (~$0.005), hard stop at 0 | ~$0.05–0.10/task |
| Lindy | credit, overage at 2×, pause at 0 | ~$0.01/credit |
| Microsoft Copilot Studio | credit $0.008, **disable at 125% of prepaid** | — |
| GoHighLevel AI Employee | $97/mo flat (voice still metered) | the one flat outlier |

**Converged cap pattern:** plan credits reset monthly (no rollover); purchased top-ups roll
over; hard stop / no auto-overage; per-task token ceiling; BYOK = platform fee, zero model
markup. **Market clears at $0.10–0.50 per content/marketing task** (managed keys).

## E. Checkout rails

Freemius all-in ≈10.5% on a $99 sale (MoR, VAT, licensing, updates, WP SDK) vs raw Stripe
≈5.6–6.6% **plus 3–6 weeks of build** (license server, dunning, VAT registration). EDD+Stripe
breaks even around 500+ sales/yr. **Stripe Connect is the de facto marketplace rail**
(platform absorbs ~3.5% processing out of its application fee).

---

## Recommendation table (adopted into PRICING_TIERS.md §5, 2026-06-10)

| Decision | Adopted |
|---|---|
| Marketplace cut | 0% founding window (to Jun 2027 or first $25K/builder), pre-announced step-up to 15/20/30 |
| Snapshot model | builder's choice; certified snapshots subscription-only |
| Pro toll | $59/yr/1 · **$99/yr/3 (headline)** · $199/yr/25 · lifetime $199/$349/$699 |
| Proxy/pot rent | $59/mo incl. 10 sites + $4/site/mo; white-label bundled |
| Energy caps | credits = measured-cost ×4 (**price blank until #449 runs**); monthly reset, top-ups roll over, hard stop at 0, ~10k-token/task ceiling on `cap_micro_usd` |
| Rails | Freemius (toll) + Stripe Connect (marketplace) |

**Flagged mispricings vs the earlier draft:** $99/single-site is mid-market, not an
"adoption toll" — the adoption story requires the $59 entry; and a 20% day-one marketplace
cut cannot compete with builders' 10.5% Freemius fallback — liquidity must be bought with
the 0% window first.
