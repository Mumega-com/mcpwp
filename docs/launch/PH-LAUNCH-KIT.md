STATUS: DRAFT — routes through Hadi's Telegram gate before any publish.
Target launch: Tue Jun 30 or Thu Jul 2, 2026 (TBC). Slot: 00:01 PT.

---

# Product Hunt Launch Kit — MCPWP

---

## 1. Tagline Options (≤60 chars, the PH "tagline" field)

Five options ranked by clarity + differentiation signal. The PH tagline is the hook
that determines whether someone clicks through.

| # | Tagline | Char count | Notes |
|---|---------|-----------|-------|
| **A (recommended)** | **Your WordPress site, operated by AI agents** | 46 | Specific, owned-surface framing, no hype verb |
| B | MCP server for WordPress — 239 tools, approval-gated | 54 | Tool count is a differentiator; "approval-gated" separates from autonomous slop |
| C | Give Claude, GPT, and Cursor the keys to WordPress | 51 | Names the AI clients directly — relevant to PH audience |
| D | The governance layer for AI-operated WordPress sites | 52 | Positions the moat (governance) but "governance" may feel dry to general PH |
| E | WordPress MCP server. AI builds pages. You approve. | 51 | Verb-driven; the three-beat rhythm works for skimmers |

**Recommendation: Option A as primary, Option E as fallback if A reads too abstract on PH.**

---

## 2. Product Description

### Short (≤260 chars — the PH "tagline/description" slot)

MCPWP turns any WordPress site into an MCP server. AI agents (Claude, GPT, Cursor, Gemini) manage content, Elementor layouts, WooCommerce, SEO, and media through 239 structured tools — with an approval gate and full audit log. Free on WP.org.

**Char count: 257**

### Long version (the PH product description body — ~800 words)

WordPress runs 43% of the web. AI agents can now do real work. The missing piece is a
structured, safe surface between the two — not a chatbot, not browser automation, not
cloud-only SaaS.

MCPWP is that surface.

Install the plugin on your WordPress site. Generate an API key. Add one config block to
your AI client. Your AI — Claude, ChatGPT, Cursor, Gemini, or any MCP-compatible runtime —
can now operate your site in natural language: build Elementor pages, publish posts, run
SEO audits, manage menus, upload media, handle WooCommerce, and more.

**239 tools across 16 categories.** Every category is a real surface: content, Elementor
page building, Elementor templates and theme builder, media, SEO, menus, WooCommerce,
site memory, blueprints, approvals, webhooks, Gutenberg blocks, taxonomy, LearnPress LMS,
analytics, and admin.

**The governance layer is the product.** Every write goes into a log. Any change can be
staged as an approval request, held for human review, applied with one click, and rolled
back with one click. Role-scoped API keys let you give a content agent different access
than a design agent. Nothing runs away.

**Site memory across sessions.** The agent remembers your brand voice, your design
decisions, your client's preferences. `wp_remember(namespace: "brand", key: "tone",
value: "direct, no jargon")` — and it holds across every future session.

**Proactive signals.** The site tells the agent what's wrong without being asked: broken
Elementor data, stale content, missing featured images, pending plugin updates. The agent
operates from a live briefing, not a blank context window.

**Works with the AI clients you already have.** Claude Desktop, Claude Code, Cursor,
Windsurf, Hermes, ChatGPT Custom GPT, Gemini. The first call is always `wp_onboard` —
it returns a full site briefing so any agent can orient in seconds.

**Agency-ready.** One MCP token routes to N client sites through the multi-site proxy. One
audit trail, one approval queue, one rollback surface — across every client.

**Free on WP.org. Pro via Freemius.** Free includes the full tool surface and
governance (approval gate, audit log, rollback). Pro adds the advanced Elementor build
tools, templates, WooCommerce, SEO Pro, LMS, translation, Figma, and the Agency proxy.
Entry price: $59/yr for one site. [TK: confirm WP.org listing is live before launch.]

---

## 3. First Maker Comment (pinned — the comment that launches the conversation)

**Recommendation: this is the version to use.** 290 words.

---

Hey Product Hunt.

I'm Hadi, founder of Mumega. We've been running AI agents against WordPress sites for
clients for the past year and kept hitting the same wall: the tools were fragile, the
AI had no memory, and nothing was auditable. When a client's homepage changed at 2 AM,
nobody could say what happened or undo it.

That's what MCPWP is built to solve.

The idea is simple: WordPress is already an API. MCP is already a protocol. The plugin
connects them properly — 239 structured tools, a human approval gate, a full audit log,
and persistent memory across sessions. Your AI agent can build an Elementor landing page,
run an SEO audit on 50 pages, reorganize a menu, and queue a WooCommerce product update
for your approval — all in one session, all reversible.

What makes this different from the other WP+AI tools:

- The official WordPress MCP adapter has ~15 tools. We have 239.
- Elementor's Angie is cloud-only with credit limits. MCPWP is self-hosted, no cloud
  dependency.
- No other WP AI tool has an approval gate with rollback. That's not a feature — it's
  the architecture. Changes are proposals until a human approves them.
- The agent remembers your brand voice, your design rules, your decisions — across every
  future session.

Free tier is on WP.org [TK: confirm listing is live]. Pro is $59/yr for one site, $99/yr
for three.

I'd love to hear: which AI client are you running? What would you build on a governed WP
surface? What would make the install experience faster?

We're building in public — honest feedback is the best kind.

— Hadi

---

### Comment-section prep notes (for Hadi's reference, not published)

- Post the maker comment within 60 seconds of launch going live.
- Respond to every comment in the first 2 hours — engagement drives ranking more than upvotes.
- Pin a follow-up comment at hour 2 with the install link + one "what I just built with it"
  screenshot if available.

---

## 4. Gallery Plan (5 slides — spec for visual production)

PH gallery: 1270×760px recommended. Each slide below: headline, what the visual shows,
one-line caption for the image alt/overlay text.

### Slide 1 — The aha moment
**Headline:** "AI builds the page. You approve it."
**Visual:** Split screen. Left: Claude Desktop chat window showing a natural-language
prompt ("Build a landing page for a dental clinic with hero + 3 services + contact
section"). Right: the resulting Elementor page rendered in WordPress, with the MCPWP
Control Room approval panel visible below it.
**Caption:** "Every change is staged. One click applies it. One click undoes it."

### Slide 2 — The tool surface
**Headline:** "239 tools. Every surface of WordPress."
**Visual:** Dark background (Ink 950). A clean category grid showing the 16 categories
with tool counts — styled like a terminal output or structured card grid. Blue 600
accents on category names.
**Caption:** "Content, Elementor, WooCommerce, SEO, media, menus, LMS, and more —
all discoverable by the AI on connect."

### Slide 3 — Governance and audit
**Headline:** "Full audit log. One-click rollback."
**Visual:** MCPWP Control Room admin screen showing the approval queue with
pending/approved/rejected states. Show a "Rollback" button clearly visible next to an
applied change.
**Caption:** "Every write is logged with before/after state. EU AI Act ready."

### Slide 4 — Multi-agent, multi-site
**Headline:** "One token. N client sites."
**Visual:** Architecture diagram (dark, blue accent lines). One MCP connection fanning
out to 5 WordPress site icons, each labeled "client.com". Above: a single AI agent icon.
**Caption:** "Agency proxy routes one AI session across your whole client fleet."

### Slide 5 — Site memory
**Headline:** "The AI remembers your site."
**Visual:** Two terminal panels side by side. Left: a `wp_remember` call saving brand
tone and color palette. Right: a new session, same site — `wp_recall` returning those
values. A label between them: "Different session. Same memory."
**Caption:** "Brand rules, design decisions, and client preferences persist across
every session."

### Optional Slide 6 (if gallery allows 6+) — Connect in under 10 minutes
**Headline:** "Install. Generate key. Paste config. Done."
**Visual:** Three-step sequence on dark background. Step 1: WP Admin plugin screen with
MCPWP active. Step 2: the API key generation field. Step 3: the claude_desktop_config.json
snippet with the one-block connection config.
**Caption:** "Works with Claude, Cursor, ChatGPT, Gemini, and any MCP client out of the box."

---

## 5. Topics / Tags

File MCPWP under these PH topics (select up to 5):

1. Artificial Intelligence
2. WordPress
3. Developer Tools
4. Productivity
5. Open Source

**Hunter note:** [TK: identify a hunter with PH standing in the AI-tools / developer-tools
category. A recognized hunter materially improves day-1 ranking. Reach through Gavin / the
affiliate network (#509) or direct outreach. Target: secured by Jun 24 freeze date.]

**Maker note:** Hadi Servat as maker. Mumega as the maker company. Link to mcpwp.net.

---

## 6. Five Tough Questions + Crisp Answers (comment-section playbook)

### Q1: "The official WordPress MCP adapter exists. Why pay for this?"
The official adapter has ~15 tools, no Elementor, no approval gate, no audit log, no site
memory, no agency proxy. MCPWP is a governed operations platform, not a thin MCP bridge.
The free tier alone has more coverage than the official adapter.

### Q2: "This is autonomous AI touching a live website. How do I stop it from breaking things?"
Every write is gated. Nothing applies to the site unless you approve it. The audit log
captures before/after state for every change. The rollback is one click. The role-scoped
key system lets you give a content agent write access to posts only, not Elementor. The
architecture assumes the AI makes mistakes — it's designed for that.

### Q3: "Claude already has a Connector directory. Why isn't MCPWP there?"
Claude's Connector directory requires OAuth 2.1, which is on our roadmap (v2.9-class,
engineering keystone). Until then, API-key auth works in Claude Desktop, Claude Code,
and Claude in the browser via the manual MCP config. [TK: update if OAuth ships before launch.]

### Q4: "What's the difference between Free and Pro?"
Free: full tool surface (including approval gate, audit log, rollback, site memory,
Elementor read/write), published on WP.org. Pro ($59/yr/1 site): advanced Elementor
builders, template library, WooCommerce tools, SEO Pro, LearnPress LMS, multi-site
agency proxy, Figma integration. The free tier is genuinely useful — not a crippled demo.

### Q5: "What happens to my site if I uninstall?"
Nothing. MCPWP is read/write through WordPress's own REST API and data layer. Every
change it makes is a normal WordPress change — posts, pages, options, Elementor data.
Uninstall removes the plugin; the site stays exactly as the AI left it (or as you rolled
it back). No proprietary data format, no lock-in.
