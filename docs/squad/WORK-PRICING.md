# Pricing the Work — AI service / agent-labor pricing (2026-06-11)

> Separate from the PLUGIN toll (PRICING_TIERS.md, signed T87). This prices agent LABOR —
> the done-for-you operating of client sites/funnels. Evidence: deep research 2026.
> Fills the #449 blank cell (credit price = $/task × N). Figures = latest disclosed; full
> sources in agent report.

## The principle

Never price agent labor as hours — AI collapses a 20h job to 5h, so hourly self-destructs.
Price like the economy: thin platform fee + metered "agent actions" (hard-capped, BYOK) +
performance share only where attribution is clean. COGS≈$0 (client compute), margin in IP/flows.

## Benchmark anchors (what the market charges)

**AI automation agencies (SMB):** setup $1,500–$5,000 per deployment; retainers $500–$12,000/mo.
Case: $14k build + $900/mo for 1,200 content briefs.

**Per-outcome AI agents (enterprise reference):** Intercom Fin $0.99/resolution ($100M+ ARR),
Salesforce Agentforce $2/conversation (pushback → $0.10/action Flex Credits), HubSpot $0.50,
Sierra pure-outcome $150M ARR. Consensus: per-resolution dominant; 4× spread $0.50–$2.00;
"resolution" definition disputes = 30–50% billing variance. Only ~17% of vendors fully
outcome-based despite Gartner's 30% forecast — attribution is the hard part.

**Metered-credit markup over raw COGS:**
| Multiple | Context | Defensible? |
|---|---|---|
| 1–2× | bare reseller | no — customers BYOK |
| 2–5× | published credit-platform range | minimum viable |
| **4–8×** | **platform with workflow orchestration + IP** | **yes — our band** |
| 10× | "AI staff member" whole-system framing (GHL) | yes when buying the system |
| 20–40× | per-outcome ($0.99 on ~$0.03 raw) | highest, needs clean attribution |

AI-first SaaS gross margin 2026 averages 52%; BYOK gets us to 70–80%. Raw model cost drops
80–90%/yr → quarterly price review mandatory or margin bleeds.

**GHL agency economics (our adjacent comp):** GHL costs agency $97–497/mo; agencies rebill
clients $97/$197/$397/mo (up to 10× sub-account cost) + usage markup 2–10×. Breakeven ~3–4
clients. Snapshots sell $297 (1 niche) / $497 (3) / $997 (10); DFY bundle $1,500–5,000.

**Per-vertical outcome benchmarks (our actual verticals):**
| Vertical | CPL | Acquisition value | Pay-per-outcome norm |
|---|---|---|---|
| Medspa (dermalounge) | $5–10 ad CPL | $200–300 CPA; ticket $216; LTV $500–5,000 | $30 form / $75 qualified call / $150 booked-shown |
| Freight/logistics (viamar) | no public CPL (RFQ-gated) | per-RFQ | +$50–150/qualified shipper RFQ |
| Courses (LearnPress cust.) | $7–15/enrollment | course $29–99 | 10–15% enrollment rev; affiliate 10–50% |
| Home services (fleet) | $25–150 ($144–181 avg) | — | rev-share 10–30% |

## Recommended architecture (COGS≈$0, BYOK)

1. **Setup fee $1,500–$5,000 per vertical deployment.** Recovers build, filters tire-kickers,
   deters churn. Price 1–2× loaded cost (volume play), not 10×. Medspa $2,500 / freight $3,500
   / course $2,000.
2. **Platform fee $297–$597/mo per managed site.** "Your AI ops team, not software." Modest
   premium over GHL's $197–397. 20 clients × $397 = ~$7,940 MRR before usage.
3. **Agent actions $0.25–$1.00/action, prepaid blocks.** NEVER "AI credits" (commodity framing) —
   "operations/actions completed." BYOK clients $0.10–0.25 (flow fee); our-keys $0.50–1.00.
   This = the #449 cell: at ~$0.02–0.05 raw/task, $0.20 = 4–10×; **×4 is conservative-safe**.
   Hard-capped via mupot `cap_micro_usd` (no overage surprise — STRATEGY §6.5).
4. **Outcome kicker where attribution is clean.** Medspa +$25–50/booked-shown above baseline;
   freight +$50–150/qualified RFQ; courses 10–15% enrollment above baseline. ONE trackable
   number only — fuzzy attribution = dispute = churn.
5. **Vertical snapshot $297–$997 one-time** on marketplace (standalone IP + managed-service lead).
   Medspa funnel $497 / freight outreach $697.
6. **The "AI wrapper" answer = the 7-layer stack, not the output:** prompt flows + vertical KB +
   WP/GHL integrations + signal monitoring + SEO loop + approval gates + drift management.
   Quote the stack explicitly. Comp: DesignJoy $4,995/mo productized.
7. **Gross-margin floor 70%+** (BYOK, COGS = infra ~$50–100/client/mo). Protect: quarterly price
   reviews (model cost drops), never below $197/mo (kills positioning), always charge setup.

## How this maps to existing components

- **Credits** = mupot metered loop runtime (`cap_micro_usd`), hard-stop at 0, BYOK tier zero-markup.
- **Platform fee** = the Agency proxy ($59/mo + $4/site already signed) for white-label; the
  per-site managed fee sits on top as the SERVICE, not the software.
- **Outcome kicker** needs the PostHog+GHL attribution loop (GTM C5) live before it can be sold.
- **Snapshots** = the marketplace SKU (PLUGIN-MARKET-MAP §; #510 addon economy).

## Open / asks
- [ ] #449: lock the action price — recommend $0.20 our-keys / $0.10 BYOK as launch (×4 / ×2 of
      ~$0.05 raw), revisit quarterly.
- [ ] Decide: does mumcp sell the SERVICE directly, or only the platform (and agencies like Hadi's
      sell the service)? Two-sided: we're the platform, operators are the service. STRATEGY says
      target owners, let agencies fork — implies we sell platform + take marketplace cut, NOT
      compete with our own agency customers on done-for-you. Dogfood (our fleet) proves it; we
      don't scale it as our primary revenue.
