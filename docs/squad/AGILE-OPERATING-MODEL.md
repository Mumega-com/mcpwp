# Agile Operating Model — how the squad runs (2026-06-11)

> Yes, agile fits — the lightweight kind. Not Scrum ceremony (built to coordinate human teams at
> human speed); the valuable core: user stories, thin vertical slices, ship-and-learn, working
> software over docs. This doc defines the method and reframes the launch as stories.

## The honest frame

Agile's first value: **working software over comprehensive documentation.** We made a lot of docs
capturing strategy — right for that purpose. Agile now says: stop planning the cathedral, **ship the
thinnest slice that rings the bell, learn from a real buyer.** This doc exists to point at the slice,
then get out of the way.

## Two kinds of backlog item

1. **User story** (user-facing value): `As a [persona], I want [capability], so that [benefit].`
   Personas from the design-thinking pass: agency operator, site owner, **the AI agent** (first-class),
   WP-dev evaluator. INVEST: Independent, Negotiable, Valuable, Estimable, Small, Testable.
2. **Enabler** (technical, no direct user value): the bridge, the rig, the distribution cut, OAuth.
   Don't fake these into "As a user…" — frame as the outcome that unblocks stories.

## Increment 1 = "A US stranger can buy Pro and use it"

The first vertical slice, end-to-end. Everything not on this line waits.

```
pricing page renders → Freemius+Stripe checkout → license key → first tool call → PostHog records it
```

**Stories in Increment 1:**
- *As a WP agency owner, I want to buy Pro and get my license in one click, so that I unlock the business tools without a sales call.* → #504 (page) + #512 (checkout)
- *As a new user, I want the plugin to connect my AI and complete a first tool call in <10 min, so that setup doesn't fail before value.* → #418
- *As the founder, I want every step from ad-click to first-tool-call tracked, so that I know my CAC and conversion.* → PostHog funnel (Ledger)

**Enablers it rides on:** none strictly — the first *new-buyer* sale doesn't need the bridge. (The bridge protects the existing 10 customers — a parallel track, not a blocker for first dollar.)

## Increment 2 = "Our 10 customers cross to v3 safely"

- Enabler: #505 bridge (migration + slug + dual-key), #507 rig to verify, #506 distribution cut.
- *As an existing customer, I want to update without my site breaking or my AI connection dropping, so that I keep working.* ← the bridge makes this true.

## Increment 3 = "Strangers can find us"

Distribution (DISTRIBUTION-AND-TOOLS.md): the mcp-publisher afternoon pipeline + WP.org + Show HN + PH.
- *As an MCP user browsing a registry, I want to discover MCPWP, so that I can connect it to my site.*

## How we run the board (Kanban-lite, not Scrum)

- **Backlog = GitHub issues.** Feature issues reframed as user stories; enablers stay technical.
- **Board = GitHub Projects:** Backlog → Ready → In Progress → Review → Done.
- **WIP limit** on In Progress (finish, don't start). The squad pulls the next story when one clears Review.
- **Increments = milestones:** v3.1 Launch / v3.2 Economy / v4 Platform (created 2026-06-11; issues assigned).
- **Definition of Done** (from VERSIONING-AND-ROADMAP §6): tests green on rig + version/docs/CHANGELOG + adversarial review on sensitive surfaces + verified on dogfood before fleet before strangers.
- **No story points / velocity / standups.** Agents work at machine speed; estimation theater adds nothing. The signal is: is the slice shippable yet?
- **Review = the human gate + Warden.** Hadi approves customer-facing/money; Warden gates sensitive surfaces. Both before Done.

## The cadence

- **Per night-run / work session:** pull the top of the Ready column for the current increment, build on the rig, land with tests + a cost line. Morning: what moved to Review/Done, what's blocked.
- **Per increment:** ship → measure (PostHog) → learn → re-rank the backlog. The design-thinking §5 hypothesis table is the learn step.
- **Re-rank ruthlessly:** anything not on the current increment's slice is backlog, not work.

## Why this is the right amount of process

Too little = chaos (we'd lose the why behind 14 issues). Too much = the ceremony tax that agile itself
warns against. Kanban-lite + user stories + increments + DoD is the floor that keeps us shipping vertical
slices and learning, without standups for a team that doesn't need them. The architect pulls stories; the
squad builds; Hadi gates; PostHog tells us if it worked.
