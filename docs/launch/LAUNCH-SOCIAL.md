STATUS: DRAFT — routes through Hadi's Telegram gate before any publish.
Target launch day: Tue Jun 30 or Thu Jul 2, 2026 (TBC). Nothing here posts automatically.

---

# Launch-Day Social Posts — MCPWP

---

## 1. X / Twitter Thread (5–7 posts)

Platform voice: direct, technical, concrete. Show the thing, don't describe it.
Muted views are high on X — include the demo video and lead with the visual.

Post every 15–30 min through the morning. Do not fire all at once.

---

**Post 1 — The launch post (attach demo video, 1200x630 landscape)**

We just launched MCPWP on Product Hunt.

Install the plugin on any WordPress site. Generate an API key. Paste one config block.

Your AI (Claude, Cursor, GPT, Gemini) can now operate your entire site — pages,
Elementor, WooCommerce, SEO, menus, media — through 239 structured tools.

With an approval gate.

[Product Hunt link — add on launch day]

---

**Post 2 — The governance angle (post ~20 min after launch)**

Everyone building AI-for-WordPress has the same problem.

The AI makes changes directly to the live site. Something breaks. Nobody knows what
changed or how to undo it.

MCPWP's architecture works the other way:

Every write is staged first.
You approve (or reject).
Full audit log. One-click rollback.

Nothing touches the live site until you say so.

---

**Post 3 — The specificity post (post ~40 min after launch)**

What does 239 tools actually mean?

Content: create pages, posts, bulk ops, search, clone, template.
Elementor: build full pages, edit sections, patch widgets, find-replace.
WooCommerce: products, orders, categories.
SEO: audit, issues, autofix, search performance, structured data.
Media: upload file/URL/base64, AI alt text.
Menus, Gutenberg blocks, site memory, blueprints, approvals, webhooks.

All discoverable on connect. All role-scoped.

Compare: the official WordPress MCP adapter has ~15.

---

**Post 4 — The agency angle (post ~1 hr after launch)**

If you run a WordPress agency, the interesting part is the multi-site proxy.

One MCP token. N client sites.

Claude (or any agent) calls `wp_list_sites()`, addresses a client by domain, and operates
that site — pages, Elementor, SEO — without needing separate credentials per client.

Full audit trail. Approval queue across the fleet.

---

**Post 5 — Site memory (post ~2 hr after launch)**

One thing that surprised people in beta: the site memory.

The AI remembers your brand rules across every session:

```
wp_remember(namespace: "brand", key: "tone", value: "direct, no jargon")
```

Next session, fresh context window, same site:

```
wp_recall(namespace: "brand", key: "tone")
→ "direct, no jargon"
```

Brand voice, design decisions, client preferences — persistent.

---

**Post 6 — Free + CTA (post ~3 hr after launch, or end-of-day push)**

MCPWP free tier is on WordPress.org. [TK: confirm WP.org listing is live.]

Free includes: the full tool surface, approval gate, audit log, rollback, site memory,
Elementor read/write.

Pro ($59/yr): advanced Elementor builders, WooCommerce, SEO Pro, LMS, agency proxy, Figma.

Install in 5 minutes: mcpwp.net

---

**Optional Post 7 — Affiliate/Gavin angle [TK: activate only after affiliate links are
live per #509 — do not post before affiliate infrastructure is confirmed]**

If you write about AI tools for WordPress builders, we have an affiliate program.

[TK: insert affiliate link and commission rate once #509 is resolved.]

---

## 2. LinkedIn Post (single post, launch day)

Platform voice: professional, founder-led, slightly longer form than X.
Attach the 1200x630 landscape video or the carousel (6 slides).

---

Today we launched MCPWP on Product Hunt.

It turns any WordPress site into an MCP server — so AI agents (Claude, ChatGPT, Cursor,
Gemini) can operate the site in natural language through 239 structured tools.

I want to explain the part we think is most important, because it's different from every
other AI-for-WordPress tool:

The governance layer.

Every change an AI makes goes into an approval queue. Nothing touches the live site until
a human approves it. If you approve and then change your mind, one click rolls it back.
Full audit log with before/after state on every write.

The reason this matters: AI makes mistakes. The question is whether your architecture
assumes that or ignores it. MCPWP assumes it. That's the design choice.

We built this after a year of running AI agents against client WordPress sites for our
agency. The recurring problem wasn't capability — it was control. The agents could do
the work. The risk was that nobody knew what had changed or how to undo it.

This is the tool we built for ourselves. Now it's free to install.

Free tier on WordPress.org. [TK: confirm listing is live.]
Pro from $59/yr.

If you work with WordPress sites and AI agents, I'd love your feedback — especially on
the install experience. We're targeting under 5 minutes from download to first tool call.

[Product Hunt link — add on launch day]
[mcpwp.net]

---

## 3. Show HN — Hacker News

**Post title:**
"Show HN: MCPWP — MCP server for WordPress with 239 tools, approval gate, and audit log"

**Opening comment (the first comment, posted immediately by OP):**

I've been running AI agents against WordPress sites for about a year for client work.
The recurring problem: the agents could do the work (build Elementor pages, run SEO
audits, manage content at scale) but there was no governance layer. Changes landed
directly on the live site. Nothing was auditable. Nothing was reversible.

MCPWP is what we built to fix that.

The core idea: expose WordPress as an MCP server (239 tools across content, Elementor,
WooCommerce, SEO, media, menus, and more) with a human-in-the-loop approval gate baked
into the architecture. Every write goes into an approval queue. You approve, the change
applies. You reject, nothing happens. Either way, it's in the log with before/after state
and you can roll it back.

Technical details:
- Plugin installs on self-hosted WordPress, exposes a Streamable HTTP MCP endpoint at
  `wp-json/mcpwp/v1/mcp`.
- Works with any MCP client (Claude Desktop, Claude Code, Cursor, Hermes, ChatGPT Custom
  GPT, Gemini via proxy).
- Role-scoped API keys: content agent gets different access than design agent.
- Persistent site memory across sessions (`wp_remember`/`wp_recall`).
- Proactive signals: the site surfaces broken Elementor data, stale content, pending
  updates — without being asked.
- The compare: official WordPress MCP adapter has ~15 tools. MCPWP has 239. The
  difference is Elementor depth, governance, memory, and the agency proxy.

Free tier on WordPress.org. [TK: confirm WP.org listing live.]
Source: github.com/Mumega-com/mcpwp
Site: mcpwp.net

Happy to answer technical questions — especially on the MCP transport layer, the Elementor
data format, or how the approval gate is implemented.

---

### HN tone notes

- HN rewards specificity and intellectual honesty. The comparison to the official adapter
  must be accurate (verify tool counts before posting).
- If someone asks "why not just use the official WP MCP adapter," the answer is already in
  the comment. Don't be defensive — be specific.
- If someone asks about the governance model: explain the `wp_create_approval_request` →
  `wp_apply_approval` / `wp_rollback_approval` flow.
- If someone pushes back on "239 tools is too many for LLMs to handle": acknowledge the
  context-window tension and explain tool discovery + role-scoped key scoping reduces the
  effective surface per agent.
- Do not engage with off-topic trolling. Do engage with every sincere technical question.

---

## 4. Affiliate/Gavin Angle Note

[TK: Do not activate until #509 is resolved and affiliate links are generated through
Freemius. Once live, the X Thread Post 7 above can fire, and the LinkedIn post can add
an affiliate partner mention. Gavin's network is the first outreach target once links
exist. Herald will draft the affiliate-partner outreach email as a separate deliverable
once #509 closes.]

---

## 5. Reddit Note (not a launch-day post — slower burn)

Reddit requires the 90/10 rule: 9 contributions before any promo post. Do not post to
r/WordPress or r/ClaudeAI on launch day as a cold account. If Hadi or a team member has
standing in those communities, the appropriate post is a demo comment in a relevant
existing thread (e.g., someone asking "how do I use AI to manage WordPress") — not a
self-post announcement. This is a post-launch, community-warming activity.

r/ClaudeAI (911k members) and r/WordPress (700k) are both viable surfaces but only
through demonstrated value first.
