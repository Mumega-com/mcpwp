# Dogfood execution spec — mcpwp.net (P0c)

> The first dogfood. mcpwp.net runs itself as the proof of the flagship loop, in our own niche
> (**MCP · WordPress · WP plugins**). Strategy: `docs/STRATEGY.md` §4, §4b. This doc is the *how* —
> grounded in the site's actual current state and executable now via REST (`mumega-mcp-mcpwp`).
>
> **Last updated:** 2026-06-09.

---

## 1. Why mcpwp.net first

It is the **safest** dogfood and serves four goals at once:
- **Cold-start** — the first seeded shelf of the marketplace economy (we are the first player).
- **Distribution proof** — we publish where we are the genuine expert → real E-E-A-T, **zero slop risk** (the
  #1 distribution risk, scaled-content-abuse penalty, disappears when the content is actually authoritative).
- **Onboarding** — LearnPress courses teaching MCPWP cut support and lift activation.
- **Funnel** — content + courses in our exact buyer's lane (WP owners/devs evaluating MCP).

"MCPWP runs MCPWP's own education + marketing site" is the cleanest possible proof.

---

## 2. Current state (probed 2026-06-09 via REST)

| Asset | State | Implication |
|-------|-------|-------------|
| Elementor **Pro** 4.1.1 (section mode) | ✅ | full page-building surface |
| Yoast SEO 27.7 | ✅ | schema + sitemap scaffolding for distribution |
| WPForms 1.10.1.1 | ✅ | lead capture available |
| AI providers: OpenAI, Gemini, ElevenLabs | ✅ | content + alt-text + TTS for courses |
| 10 published posts — all in-niche (MCP/WP/plugins) | ✅ | content lane already coherent and live |
| **Brand-crystal (`spai_site_context`)** | ❌ **empty** | **gap #1 — the coherence foundation is unset** |
| **LearnPress** | ❌ not installed | **dependency — courses blocked until installed** |
| WooCommerce | ❌ not installed | the commerce dogfood is a *separate* site (P0d) |
| Pages | all drafts | site structure incomplete; homepage is a draft |

**Net:** the content half is already coherent and live; the missing foundations are the **brand-crystal**
(unset) and **LearnPress** (not installed). Everything else needed is present.

---

## 3. The brand-crystal to set (gap #1 — do first)

The brand-crystal is the coherence constraint everything conforms to — and per `docs/STRATEGY.md` it is the
economy's anti-inflation mechanism and Google's safe-harbor signal. It is currently empty. Proposed
`spai_site_context` (markdown), ready to apply via `wp_set_site_context`:

```markdown
# MCPWP — Brand Crystal

## Identity
MCPWP is the WordPress MCP plugin that lets AI agents safely operate a WordPress site — governed by
approval, rollback, and audit. We are the domain authority on connecting AI (Claude, ChatGPT, Codex,
Hermes) to WordPress through the Model Context Protocol.

## Voice
Expert, plain-spoken, evidence-led. We explain MCP/WP/plugin mechanics clearly to site owners and
developers. No hype, no slop. Every claim is grounded in how the software actually works.

## Audience
WordPress site owners, agencies, and developers evaluating or running AI-assisted site operations.

## Content lane (niche coherence — never leave it)
MCP, WordPress, WP plugins, Elementor/Gutenberg, WP SEO, AI-assisted site operations, governance/security
of AI-on-WP. We do NOT chase off-topic trends (the Feb-2026 Discover penalty criterion).

## Palette / type / spacing
(Inherit the live MCPWP admin design system — CSS tokens, dark product headers. Fill exact hex/type from
the kit before building pages.)

## Design rules
- Elementor section mode. Conform to the MCPWP design tokens.
- Every published piece carries a real author byline linking to a credentialed bio (E-E-A-T).
- NewsArticle/Article schema on every post; 1200px 16:9 lead image, no text overlay.

## Governance
Customer-facing publish goes through the approval gate. Originality + quality scored before publish; low
scores route back, never publish. Sane cadence in-lane — no floods.
```

This makes mcpwp.net coherent *and* distribution-eligible at the brand level. Reversible (a wp_option).
**Apply on Hadi's go** — it's the live product site.

---

## 4. The content loop (Loop A — distribution; already started)

The blog is live and in-lane. Formalize it into the governed loop:

- **Perceive:** pull Search Console (which posts get picked up / rank) + PostHog (on-site behavior).
- **Hypothesize:** the periodic expensive agent decides the next topic from real demand
  (`wp_keyword_research` + Search Console gaps), strictly within the lane.
- **Produce:** draft in brand voice; attach author byline + bio; NewsArticle schema; 1200px 16:9 image.
- **Gate:** originality + `wp_audit_content_quality` score **blocking**; then `/approvals`.
- **Publish + measure:** publish, then watch Search Console for pickup; feed back into the next topic.

**Distribution-eligibility build (one-time, then automatic):**
- News sitemap (48h rolling, offset-aware `publication_date`) — extend/verify alongside Yoast.
- `NewsArticle` JSON-LD per post (Yoast emits Article; verify dates carry timezone offset, headline ≤110,
  article-owned image, `author.url` → real bio).
- `<meta name="robots" content="max-image-preview:large">` site-wide.
- Real author entities (bio pages + credentials) — currently missing; build them (E-E-A-T + News transparency).

---

## 5. LearnPress courses (dogfood + onboarding) — blocked on LP install

**Dependency:** LearnPress is not installed. Needs install + activate (WP admin or `wp plugin install
learnpress --activate`) — Hadi/admin action; the REST API does not install plugins.

Once installed, build (via the content/CPT tools) a course track teaching MCPWP — each lesson dogfooding a
real capability:

1. **Connect AI to WordPress** — install MCPWP, generate a scoped key, connect Claude/ChatGPT/Codex.
2. **Safe operations** — scopes, approvals, rollback, the audit log.
3. **Build pages with AI** — Elementor via MCP, the brand-crystal, surgical edits.
4. **SEO + content** — audits, content-graph, keyword research.
5. **Going autonomous** — the loop, governance, the Fleet/representation layer.

Each lesson = short video (ElevenLabs TTS over a screen capture) + transcript (the published post doubles
as the lesson text → content loop and course share the same artifact). This *is* the dogfood: the courses
about operating WordPress with AI are themselves built by AI operating WordPress.

---

## 6. The two-speed cadence on this site

- **Continuous + cheap (Haiku):** pull Search Console + PostHog on the interval; draft candidate topics;
  log what ran. Never publishes (gated).
- **Periodic + expensive (Opus, once/interval):** read the interval's data + what the cheap agents drafted →
  pick the next piece, approve/revise, decide course updates → dispatch → sleep.

Keeps the energy sink small; the loop stays net-positive.

---

## 7. Agent representation on mcpwp.net (the security/governance layer)

When an agent connects to mcpwp.net it declares `{ identity, represents, scope_of_work, goal }` (AR1). The
admin Fleet page (AR2) shows who is operating the site; declared scope becomes the enforcement boundary on
top of the API key (AR3). On our own site this is also where we *dogfood and harden* the representation
layer before shipping it to customers. (Sensitive surface — adversarial-gate the build.)

---

## 8. Concrete first steps (sequenced)

**Can do now via REST (on Hadi's go — live product site):**
1. **Set the brand-crystal** (§3) — `wp_set_site_context`. Foundation, reversible.
2. **Author entities** — create credentialed author bio pages; wire bylines.
3. **Distribution eligibility** — verify/extend NewsArticle schema dates, `max-image-preview:large`, news
   sitemap; lead-image standards on the 10 existing posts.
4. **Publish the draft pages** that should be live (home, pricing, get-started) after a coherence pass.

**Needs Hadi / admin (not REST-doable):**
5. **Install + activate LearnPress** → unblocks the course track (§5).
6. Confirm PostHog + Search Console property access for the feedback loop.

**Then the loop runs:** cheap-continuous perceive → expensive-periodic decide → gated publish → measure.

---

## 9. Success metric

One number moves on our own site: **organic pickup (Search Console impressions/clicks in-niche) and
course-starts / signups.** When the loop lifts that without a human writing each piece — and the content
gets *distributed* (Google picks it up, no penalty) — the flagship loop is proven and the marketplace has
its first real, performance-ranked shelf.

---

## Access note
mcpwp.net is reachable now via REST (`MCPWP_NET_API_KEY` in `~/.env.secrets`, endpoint
`https://mcpwp.net/wp-json/site-pilot-ai/v1`). The `mumega-mcp-mcpwp` MCP server is configured in
`~/.claude.json` but loads as tools only after a session restart — not required; REST is the same surface.
