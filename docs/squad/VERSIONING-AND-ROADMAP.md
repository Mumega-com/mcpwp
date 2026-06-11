# Versioning, Release Discipline & Roadmap to Product Hunt (2026-06-11)

> The architect's release plan. How we version, what rides which train, deadlines to PH launch.

## 1. How real teams version (the method we adopt)

- **SemVer**: `MAJOR.MINOR.PATCH`. MAJOR = breaking (the slug change was 3.0.0). MINOR = new features, back-compat. PATCH = fixes.
- **Release trains, not "when done."** Ship on a cadence; a feature that misses the train waits for the next one. Prevents one laggard from holding the launch.
- **main is always releasable.** Feature branches merge only when green (tests + review). RC tag before GA.
- **Feature flags / dark ship.** Land code disabled; enable per-version. Lets unfinished work sit on main safely.
- **Milestones = versions.** Every GH issue assigned a milestone. The milestone's open issues ARE the version's scope. Closing them all = ship.
- **Definition of Done (release gate):** tests green on rig + docs/changelog updated + adversarial review on sensitive surfaces + version bumped in 3 files. No DoD, no merge.
- **Freeze → RC → QA → GA.** Code-freeze a few days pre-launch; only fixes land; QA on the rig; tag GA.
- **Launch on the coherent MVP, not the wishlist.** Cut scope to protect the date. Post-launch trains carry the rest.

## 2. Our scheme

| Train | Meaning | Channel |
|---|---|---|
| 3.0.x | current stable; 10 customers; **channel pinned 2.8.56** until bridge | self-hosted |
| **3.1.0** | **the LAUNCH version** — safe fleet crossing + checkout + conversion surface | the PH-ready build |
| 3.2.x | post-launch: addon shop, GEO suite, signal tools | fast-follow |
| 4.x | substrate-agnostic refactor (Shopify-ready, #511) | later |

Branch: `main` releasable; feature branches per issue; `release/3.1` cut at freeze.

## 3. Roadmap — feature → version allocation

### v3.1.0 — "Launch" (everything PH needs, nothing it doesn't)
The bar: a stranger finds us, installs free, connects in <10 min, can pay, and our 10 customers cross safely.
- **#505 bridge release (2.8.57→3.1)** — auto migration + slug handover + dual-prefix key auth. THE keystone; adversarial-reviewed. *(blocks fleet safety + new-install safety)*
- **#507 test rig** — dev/upgrade/multisite instances, second disk, on/off, local channel, tunnel. *(precondition for verifying everything)*
- **#504 pricing page** renders signed tiers + Freemius buy buttons. *(the checkout surface)*
- **#512 Freemius + connected Stripe** configured (T87 numbers). *(the ring)*
- **#502 content pass** — stale spai_ endpoints, excerpts, links. *(credibility)*
- **#506 distribution off mumega.com** → updates.mcpwp.net. *(brand separation; rides the bridge)*
- e2e install matrix (T88/#418): Cowork, ChatGPT Business, Codex, Cursor each <10 min.
- PostHog funnel: download → first tool call (attribution for ads/affiliates).
- WP.org submission (#495 GREEN) — free listing = passive funnel.

### v3.2.x — "Economy" (fast-follow, 2–6 wks post-launch)
- #510 addon shop + agent-authored addons (declarative-first, gated)
- GEO suite: citation-decay queue, AI share-of-voice, earned-media pipeline (INTELLIGENCE-STACK §C)
- Signal tools: Apify-MCP-backed competitor/review/ad monitoring, gated+capped
- #508 education vertical surface (events/membership tools)
- Snapshot #1 (dropship/Prefrontal Club) productized

### v4.x — "Platform" (later)
- #511 substrate-agnostic operator layer (Shopify adapter)
- Marketplace Connect/split payments, certified snapshots, performance ranking

## 4. Deadlines to Product Hunt

Today = Thu 2026-06-11. PH launches best Tue–Thu, 00:01 PT. Target a **3-week runway** (realistic with the fleet building nightly; PH first-timers who rush a broken launch waste the one-shot).

| Phase | Window | Gate to exit |
|---|---|---|
| **Build** (rig + bridge + features) | Thu Jun 11 → Wed Jun 17 | rig green; bridge passes upgrade e2e on rig; pricing page + Freemius live |
| **Cross + QA** | Thu Jun 18 → Tue Jun 23 | 10 customers crossed to 3.1 safely; e2e install matrix green; PostHog funnel firing; dogfood sites on 3.1 |
| **Freeze + RC** | Wed Jun 24 → Fri Jun 26 | code freeze; RC tag; only fixes; PH assets ready (gallery, maker comment, demo video, hunter lined up) |
| **Launch week** | **Tue Jun 30 or Thu Jul 2** | GA tag 3.1.0; WP.org live; PH goes up 00:01 PT |

Slip rule: if bridge isn't green by Jun 17, launch slips a week — never ship the bridge unverified to customers. The date serves the launch; the launch doesn't break the fleet.

## 5. What tonight's run actually produces (honest scope)

Not "all features." Tonight = the **foundation that unblocks the whole train**:
1. **#507 rig** stood up (dev + upgrade + multisite, second disk, on/off, local channel).
2. **#505 bridge** built on a branch + tested on the rig's upgrade instance (migration + slug + dual-key). Adversarial review queued.
3. **#504 pricing page** render fixed on the rig's WP.
4. Draft PH launch assets (maker comment, gallery copy, demo script) as documents.
5. Morning report: what's green, what's blocked, cost line to Loom's ledger.

Dara tests the client-connect path from the Mac through the tunnel. Everything stays on the rig — zero production/customer writes overnight. Gates hold.

## 6. Release gates (every version, non-negotiable)
1. Tests green on rig (phpunit + e2e where applicable)
2. Version bumped in 3 files (mcpwp.php, readme.txt, version.json)
3. CHANGELOG + docs updated
4. Adversarial review on sensitive surfaces (auth, migration, checkout, external)
5. Channel manifest change = two-person rule (architect proposes, Hadi approves)
6. Verified on dogfood (mcpwp.net + crophelp.ai) before fleet, before strangers
