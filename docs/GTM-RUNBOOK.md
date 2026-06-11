# GTM Runbook — sequenced backlog (2026-06-11)

> The order of operations from "10 friendly installs, channel pinned" to "strangers pay."
> Source of truth for each step = its GitHub issue. This file is sequence + dependencies only.
> Owners: H = Hadi (human-gated), A = agents (architect dispatches), K = Kasra gate.

## Phase A — Safety rail (this week; blocks everything customer-facing)

| # | Step | Issue | Owner | Gate |
|---|------|-------|-------|------|
| A1 | Update channel stays pinned 2.8.56 (done 2026-06-11, verify weekly) | #505 | A | — |
| A2 | Build 2.8.57 bridge: spai_→mcpwp_ migration + slug handover + dual-prefix key auth | #505 | A | K + adversarial (parallel) |
| A3 | Test bridge on wp-test docker, then mcpwp.net + crophelp.ai (our dogfood) | #505 | H clicks, A verifies | H |
| A4 | Cross the 10 customer sites (Hadi admin everywhere — bridge makes it one update click each) | #503/#505 | H | H |

## Phase B — Identity rail (parallel with A build)

| # | Step | Issue | Owner | Gate |
|---|------|-------|-------|------|
| B1 | Stand up updates.mcpwp.net (or chosen domain) on existing R2 bucket | #506 | A | H (domain choice) |
| B2 | Updater fallback URL + mcpwp_version_url → new domain (rides 2.8.57) | #506 | A | K |
| B3 | mumega.com/mcp-updates stays alias ≥2 release cycles, then dies | #506 | A | — |

## Phase C — Conversion surface (after own-site cutover, before launch)

| # | Step | Issue | Owner | Gate |
|---|------|-------|-------|------|
| C1 | Fix /pricing/ render (Elementor override) | #504 | A | H (visual check) |
| C2 | Content pass: stale spai_ endpoints in posts, 52 empty excerpts, timestamps, /agencies/ link | #502 | A | — |
| C3 | Email capture on mcpwp.net | T89/#419 | A | H |
| C4 | End-to-end install test: zero → first tool call on every major runtime (Cowork, ChatGPT Business, Codex, Antigravity, Hermes, OpenClaw) | T88/#418 | A | — |
| C5 | PostHog baseline funnel: download → first mcp_tool_called | charter KPI | A | — |

## Phase D — Launch rail

| # | Step | Issue | Owner | Gate |
|---|------|-------|-------|------|
| D1 | WP.org submission (audit already GREEN, #495) | T-WP.org | H submits | H |
| D2 | Freemius pricing config (signed T87 numbers) | T-Freemius | H | H |
| D3 | Discord community server | T91/#421 | H | H |
| D4 | PH launch date + assets (gallery, maker comment, teaser thread) | T46/T41–T44 | H + A | H |

## Phase E — Fleet product (Agency tier proof)

| # | Step | Issue | Owner | Gate |
|---|------|-------|-------|------|
| E1 | Fleet page spec (agent representation, STRATEGY §4) | new | A (spec) | H |
| E2 | ≥3 tenants operated via agency proxy, measured | charter KPI | A | H per act |
| E3 | digid pilot: gated content loop (pot → brand-crystal → MCPWP draft → approval → publish) — mupot v0.22 | mupot #42/#39 | A + kasra | H |

## Phase F — Measure & iterate (continuous from C5)

- Monthly: re-run GTM-DESIGN-THINKING.md §5 hypothesis table against PostHog.
- Every flight lands a cost line in Loom's ledger.
- Failed hypothesis → new HMW → next design pass.

## Dependency spine

```
A2 ─► A3 ─► A4 ─► C2 ─► D1..D4 ─► E
 │          ▲
 B1 ─► B2 ──┘        C1, C3, C4, C5 anytime after A3
```

Single biggest risk: skipping A and launching — every new install lands on the slug fault line.
Second: launching before C5 — no baseline means no proof the loop improves anything.
