# Dogfood execution spec — mcpwp.net (P0c)

> The first dogfood. mcpwp.net runs itself as the proof of the flagship loop, in our own niche
> (**MCP · WordPress · WP plugins**). Strategy: `docs/STRATEGY.md` §4, §4b. This doc is the *how* —
> grounded in the site's actual current state and executable now via REST (`mcpwp`).
>
> **Last updated:** 2026-06-10 (added §2b live-site audit + nav/conversion fix plan).

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
| **Brand-crystal (`mcpwp_site_context`)** | ❌ **empty** | **gap #1 — the coherence foundation is unset** |
| **LearnPress** | ❌ not installed | **dependency — courses blocked until installed** |
| WooCommerce | ❌ not installed | the commerce dogfood is a *separate* site (P0d) |
| Pages | all drafts | site structure incomplete; homepage is a draft |

**Net:** the content half is already coherent and live; the missing foundations are the **brand-crystal**
(unset) and **LearnPress** (not installed). Everything else needed is present.

> **State change since this probe** (verified 2026-06-10 via MCP): LearnPress 4.3.9 and WooCommerce 10.8.1
> are now **installed and active** (§5's blocker is gone), 52 pages are published (52 posts too), and both
> plugins dropped their default template pages live. See §2b for the full audit.

---

## 2b. Live-site audit — 2026-06-10 (probed via MCP)

Read-only audit through the plugin's own tools (`wp_site_info`, `wp_get_site_health`, `wp_list_menus`,
`wp_list_menu_items`, `wp_get_page_by_slug`, `wp_get_signals`). Two classes of findings: **site fixes**
(this plan) and **plugin bugs** (GH issues).

### The conversion path is broken in three places

1. **The pricing page is a placeholder** — `/pricing/` (id 502) is 62 words, no tiers, no numbers, no buy
   button. Blocked on T87 sign-off; once numbers are confirmed the page writes itself (see
   `docs/PRICING_TIERS.md` + the 2026-06 market research).
2. **Pricing is not in the header menu** — 27 nav items, none is "Pricing". The page is orphaned.
3. **Header "Demo" and "Integrations" link off-site to the pre-rebrand domain**
   (`sitepilotai.mumega.com`) while an on-site demo page (id 506) sits orphaned. The rebrand never
   reached the nav.

### Nav / content debris

- Homepage menu item uses a raw `?page_id=95` URL and a 40-char SEO title as its nav label.
- "Brand Canon" (internal doc) sits in the public header under Features.
- Stale pre-rebrand **"SPAI Header Nav"** menu (6 items) + empty "Primary Menu" linger unassigned.
- LearnPress/Woo default template pages are **published**: Instructor, Become an Instructor, Instructors,
  Courses, Cart, Checkout ×2, Shop, My account, Profile, Terms and Conditions (auto-generated).
- **52 published pages have no featured image** → broken OG/social previews site-wide (matters for the
  AI-attention thesis and §4's image standards).
- 24 orphan pages total; the six SEO landing pages (connect-claude/-chatgpt/-cursor, alternatives,
  secure-mcp, wordpress-mcp-plugin) are intentionally nav-free but should get internal links from posts.

### Fix plan (writes to production — execute on Hadi's go, all reversible)

- **A. Nav repair:** add Pricing + on-site Demo to the header; repoint Integrations on-site; fix the
  homepage item label/URL; move Brand Canon out of public nav; delete the stale SPAI menu + empty
  Primary Menu.
- **B. Content cleanup:** unpublish (→ draft) the LearnPress/Woo template-debris pages; LP/Woo regenerate
  them on demand. Keep the LP core pages (checkout/profile) only once courses ship (§5).
- **C. Featured images:** set lead images on the ~20 marketing/docs pages first (the 1200px 16:9 standard
  from §3); template/system pages excluded.

### Plugin bugs found while auditing (filed as GH issues, not site ops)

- **Signals feed empty + `refresh:true` 502s the origin** on a site with obvious signal conditions
  (14 page drafts, 24 orphans, 52 missing thumbnails). The flagship proactive-signals feature does not
  work on our own site — likely the cron never ran and the synchronous recompute exceeds origin limits.
- **Entitlement display contradiction in production:** `plan: unlicensed, is_pro: false` while five AI
  integrations are configured and active. The repo-side consistency fix landed 2026-06-10
  (Freemius single-source-of-truth port); the dogfood site should also run a real Pro license —
  we should be customer #1 of our own paid plan.

---

## 3. The brand-crystal to set (gap #1 — do first)

The brand-crystal is the coherence constraint everything conforms to — and per `docs/STRATEGY.md` it is the
economy's anti-inflation mechanism and Google's safe-harbor signal. It is currently empty. Proposed
`mcpwp_site_context` (markdown), ready to apply via `wp_set_site_context`:

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

**Can do now via REST/MCP (on Hadi's go — live product site):**
1. **Nav repair + content cleanup** (§2b A/B) — fixes the broken conversion path; smallest, do first.
2. **Set the brand-crystal** (§3) — `wp_set_site_context`. Foundation, reversible.
3. **Author entities** — create credentialed author bio pages; wire bylines.
4. **Distribution eligibility** — verify/extend NewsArticle schema dates, `max-image-preview:large`, news
   sitemap; lead-image standards on existing posts + the §2b-C featured-image pass.
5. **Real pricing page content** — blocked on T87 numbers; ship the page the same day the numbers sign.

**Needs Hadi / admin (not REST-doable):**
6. ~~Install + activate LearnPress~~ ✅ done (LP 4.3.9 active as of 2026-06-10) → course track (§5) unblocked.
7. Confirm PostHog + Search Console property access for the feedback loop.
8. Put mcpwp.net on a real Pro license (dogfood the paid plan — §2b).

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
`https://mcpwp.net/wp-json/mcpwp/v1`). The `mcpwp` MCP server is configured in
`~/.claude.json` but loads as tools only after a session restart — not required; REST is the same surface.
