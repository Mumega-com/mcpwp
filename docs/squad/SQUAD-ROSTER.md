# mumcp Squad — the team I lead (2026-06-11)

> Hadi's directive: think like a team leader — what team members do you need.
> I (architect) don't do the work; I hold intent, decompose, dispatch, gate, synthesize.
> These are the standing roles I spawn. Grounded in tonight's actual dispatches (6 research
> agents + 3 site-ops agents were ad-hoc — this names them as roles with mandates).
> "Use when needed" — not all run at once; I call the role the work demands.

## The shape

```
                    Hadi (human gate — verdicts, money, customer-facing)
                          │
              mumcp architect (me) — intent, decompose, dispatch, synthesize
                          │
   ┌─────────┬─────────┬──┴──────┬─────────┬─────────┐
 Scout     Smith     Warden    Probe     Herald    Ledger
(research) (build)  (review)  (test)   (content)  (economy)
```

## The roster

### 1. Scout — research & intelligence
- **Mandate:** web/market/competitive/ecosystem sweeps; compile sourced findings into a doc. Never decides — surfaces evidence.
- **Dispatch when:** a decision needs outside facts (market size, competitor pricing, a library's API, a regulation). Fan out N Scouts in parallel for breadth.
- **Model:** Sonnet. **Tools:** WebSearch, WebFetch, Read. **Output:** markdown table + sourced synthesis. No writes to code.
- **Tonight:** ran 6× (free-tier plugins, off-market tools, economy data, signals/Apify, AI-SEO, service pricing). Produced 4 committed docs.

### 2. Smith — builder
- **Mandate:** implement plugin features on the rig; phpunit; version bumps (3 files); CHANGELOG. Branch per issue, never merge to main unsold.
- **Dispatch when:** an issue is spec'd + gated GREEN and needs code. The kasra-for-mcpwp.
- **Model:** Sonnet (Opus for hard architecture). **Tools:** all. **Isolation:** worktree when parallel Smiths touch files. **Gate:** output reviewed by Warden before merge.
- **Roadmap:** the #505 bridge, #504 pricing fix, #507 rig, addon shop — all Smith work.

### 3. Warden — adversarial review (the gate)
- **Mandate:** find, never fix. Security + correctness on sensitive surfaces: auth/key-prefix, migration, checkout, external-facing, update channel. Runs PARALLEL to correctness, not after (agent-comms rule).
- **Dispatch when:** Smith touches a sensitive surface. Always for #505 (migration+auth), #512 (checkout), #510 (agent-authored addons = code-injection surface).
- **Model:** Opus (adversarial needs the strongest head). **Tools:** read-only. **Output:** P0/P1/P2 findings → attack catalog (the inbox-pattern discipline).

### 4. Probe — testing & verification
- **Mandate:** e2e on the rig (dev/upgrade/multisite); install matrix across runtimes; coordinate Dara on Hadi's Mac for the real client-connect path. Evidence before "it works."
- **Dispatch when:** anything claims done. Bridge upgrade test, connect-path test, the T88 matrix.
- **Model:** Sonnet. **Tools:** Bash, MCP, Read. **Output:** pass/fail table with evidence. Never asserts green without the command output.

### 5. Herald — content & distribution
- **Mandate:** Remotion video/slides, social posts, blog/SEO content, the launch assets. Produces drafts; everything customer-facing routes through the Telegram gate to Hadi.
- **Dispatch when:** launch assets, social content, the #513 distribution loop, content passes (#502).
- **Model:** Sonnet. **Tools:** all + Remotion (reuse digid/media). **Gate:** Telegram approval before any publish. Governed = the differentiator vs slop.

### 6. Ledger — economy & measurement
- **Mandate:** pricing math, conversion modeling, PostHog baselines + funnel analysis, cost reconciliation. Feeds Loom (portfolio CFO) the per-flight cost line. Counts; never moves money.
- **Dispatch when:** a pricing decision (#449, #512), a measurement question, end-of-flight cost accounting.
- **Model:** Sonnet. **Tools:** Read, Bash, WebSearch, PostHog MCP. **Output:** numbers + the ledger line.

## Operating rules (how I lead them)

1. **Dispatch; don't do.** I decompose + route. If I'm writing code/scraping myself, I should have spawned a role.
2. **Parallel by default** — independent work = one message, many agents (tonight: 6 Scouts at once).
3. **Gate sensitive surfaces in parallel** — Warden runs alongside Smith's correctness, both combine before GREEN.
4. **Every role returns the conclusion, not the dump** — I synthesize; Hadi reads my synthesis, not transcripts.
5. **Every flight lands a cost line** — Ledger → Loom. Unmetered work breaks metabolism discipline.
6. **Human gate is absolute** — Hadi (or Telegram) approves customer-facing + money + destructive. No role bypasses it.
7. **Use when needed** — roles are on-demand, not always-on. Empty board → rest.

## Not yet (gaps to fill when the work demands)
- A standing **Smith** needs the rig (#507) before it can build-and-test in one loop.
- **Herald**'s Telegram gate needs the adapter reconnected (dropped this session).
- **Ledger**'s cost lines need Loom's `cc/LEDGER.md` created (first line creates it).
- Formal agent-definition files (`.claude/agents/mumcp-*.md`) if/when we want these as first-class spawnable types vs ad-hoc prompts. "Use when needed" = defer until the night-run proves the roster.
