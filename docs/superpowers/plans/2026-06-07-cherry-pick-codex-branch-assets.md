# Cherry-Pick Codex Branch Assets Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 12 new files from `codex/preserve-main-local-edits-20260607` to `main` — PostHog integration skill and brand/docs assets — without touching any modified existing files (which are stale vs 2.8.43).

**Architecture:** Two parallel PRs from isolated worktrees. Task A: PostHog skill directory (9 files). Task B: Brand kit docs + posthog report (3 files). All via `git checkout <branch> -- <paths>`. No code changes — docs/skills only.

**Tech Stack:** Git (worktrees), GitHub CLI (`gh`)

**Repo:** `/mnt/HC_Volume_104325311/projects/sitepilotai/wp-ai-operator` (remote: `Mumega-com/mcpwp`)

**Source branch:** `origin/codex/preserve-main-local-edits-20260607`

---

## Task A: PostHog Integration Skill

**Files to add:**
- Create: `.claude/skills/integration-javascript_web/.posthog-wizard`
- Create: `.claude/skills/integration-javascript_web/SKILL.md`
- Create: `.claude/skills/integration-javascript_web/references/basic-integration-1.0-begin.md`
- Create: `.claude/skills/integration-javascript_web/references/basic-integration-1.1-edit.md`
- Create: `.claude/skills/integration-javascript_web/references/basic-integration-1.2-revise.md`
- Create: `.claude/skills/integration-javascript_web/references/basic-integration-1.3-conclude.md`
- Create: `.claude/skills/integration-javascript_web/references/identify-users.md`
- Create: `.claude/skills/integration-javascript_web/references/js.md`
- Create: `.claude/skills/integration-javascript_web/references/posthog-js.md`

- [ ] **Step 1: Create worktree**

```bash
cd /mnt/HC_Volume_104325311/projects/sitepilotai/wp-ai-operator
git fetch origin
git worktree add /tmp/wt-posthog-skill -b feat/posthog-integration-skill origin/main
```

Expected: `Preparing worktree (new branch 'feat/posthog-integration-skill')`

- [ ] **Step 2: Cherry-pick skill files**

```bash
cd /tmp/wt-posthog-skill
git checkout origin/codex/preserve-main-local-edits-20260607 -- \
  .claude/skills/integration-javascript_web/.posthog-wizard \
  .claude/skills/integration-javascript_web/SKILL.md \
  .claude/skills/integration-javascript_web/references/basic-integration-1.0-begin.md \
  .claude/skills/integration-javascript_web/references/basic-integration-1.1-edit.md \
  .claude/skills/integration-javascript_web/references/basic-integration-1.2-revise.md \
  .claude/skills/integration-javascript_web/references/basic-integration-1.3-conclude.md \
  .claude/skills/integration-javascript_web/references/identify-users.md \
  .claude/skills/integration-javascript_web/references/js.md \
  .claude/skills/integration-javascript_web/references/posthog-js.md
```

Expected: silent success, 9 files staged

- [ ] **Step 3: Verify files landed**

```bash
git status
```

Expected: 9 new files under `.claude/skills/integration-javascript_web/` all listed as `new file`

- [ ] **Step 4: Commit**

```bash
git commit -m "feat: add PostHog integration skill for JavaScript/web

Adds .claude/skills/integration-javascript_web/ with SKILL.md and
reference docs (posthog-js, identify-users, JS API, wizard examples).
Sourced from codex/preserve-main-local-edits-20260607."
```

- [ ] **Step 5: Push and open PR**

```bash
git push origin feat/posthog-integration-skill
gh pr create \
  --repo Mumega-com/mcpwp \
  --base main \
  --head feat/posthog-integration-skill \
  --title "feat: add PostHog integration skill" \
  --body "Adds \`.claude/skills/integration-javascript_web/\` with SKILL.md and 8 reference docs from the preserved codex branch. No code changes."
```

- [ ] **Step 6: Clean up worktree**

```bash
cd /mnt/HC_Volume_104325311/projects/sitepilotai/wp-ai-operator
git worktree remove /tmp/wt-posthog-skill
```

---

## Task B: Brand Kit Docs + PostHog Setup Report

**Files to add:**
- Create: `docs/BRAND_KIT.md`
- Create: `docs/brand-tokens.css`
- Create: `posthog-setup-report.md`

- [ ] **Step 1: Create worktree**

```bash
cd /mnt/HC_Volume_104325311/projects/sitepilotai/wp-ai-operator
git fetch origin
git worktree add /tmp/wt-brand-docs -b feat/brand-kit-docs origin/main
```

Expected: `Preparing worktree (new branch 'feat/brand-kit-docs')`

- [ ] **Step 2: Cherry-pick docs files**

```bash
cd /tmp/wt-brand-docs
git checkout origin/codex/preserve-main-local-edits-20260607 -- \
  docs/BRAND_KIT.md \
  docs/brand-tokens.css \
  posthog-setup-report.md
```

Expected: silent success, 3 files staged

- [ ] **Step 3: Verify files landed**

```bash
git status
```

Expected: 3 new files (`docs/BRAND_KIT.md`, `docs/brand-tokens.css`, `posthog-setup-report.md`) listed as `new file`

- [ ] **Step 4: Commit**

```bash
git commit -m "docs: add brand kit and PostHog setup report

Adds docs/BRAND_KIT.md (canonical brand reference — name, positioning,
messaging, visual identity), docs/brand-tokens.css (design tokens),
and posthog-setup-report.md (PostHog admin integration summary).
Sourced from codex/preserve-main-local-edits-20260607."
```

- [ ] **Step 5: Push and open PR**

```bash
git push origin feat/brand-kit-docs
gh pr create \
  --repo Mumega-com/mcpwp \
  --base main \
  --head feat/brand-kit-docs \
  --title "docs: add brand kit and PostHog setup report" \
  --body "Adds \`docs/BRAND_KIT.md\` (301-line canonical brand reference), \`docs/brand-tokens.css\` (design tokens), and \`posthog-setup-report.md\`. No code changes."
```

- [ ] **Step 6: Clean up worktree**

```bash
cd /mnt/HC_Volume_104325311/projects/sitepilotai/wp-ai-operator
git worktree remove /tmp/wt-brand-docs
```
