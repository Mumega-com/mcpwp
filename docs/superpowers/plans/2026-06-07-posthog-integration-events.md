# PostHog Integration + Events Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move PostHog from a hardcoded token to a proper admin-configurable integration, add 10 event captures from the codex branch, clean up README, and bump to v2.8.44.

**Architecture:** Four tasks run sequentially (each depends on prior). PostHog added to `Spai_Integration_Manager::PROVIDERS` as a multi-field provider (token + host). Constants in `site-pilot-ai.php` lose their hardcoded default. Admin classes read token from integration manager at enqueue time. JS init skips when no token. Event captures ported verbatim from codex branch.

**Tech Stack:** PHP 7.4+, WordPress options API, jQuery (existing admin JS)

**Repo:** `/mnt/HC_Volume_104325311/projects/sitepilotai/wp-ai-operator` (remote: `Mumega-com/mcpwp`, base: `origin/main`)

---

## Task 1: Register PostHog as an Integration Provider

**Files:**
- Modify: `site-pilot-ai/includes/core/class-spai-integration-manager.php`

- [ ] **Step 1: Open the PROVIDERS constant**

Read `site-pilot-ai/includes/core/class-spai-integration-manager.php` and find the `const PROVIDERS = array(` block (around line 39). Note the last provider entry so you know where to append.

- [ ] **Step 2: Add posthog to PROVIDERS**

After the last provider entry (before the closing `);` of the PROVIDERS array), add:

```php
'posthog'    => array(
    'label'       => 'PostHog',
    'url'         => 'https://us.posthog.com/settings/project-details',
    'description' => 'Product analytics for the MCPWP admin interface. Tracks key admin actions (API key copy, connection test, upgrade clicks) to help understand plugin adoption. Token is public — safe to expose in browser JS.',
    'fields'      => array(
        'token' => array(
            'label'       => 'Project API Key',
            'placeholder' => 'phc_...',
            'type'        => 'text',
        ),
        'host'  => array(
            'label'       => 'API Host',
            'placeholder' => 'https://us.i.posthog.com',
            'type'        => 'text',
        ),
    ),
),
```

- [ ] **Step 3: Verify syntax**

```bash
php -l site-pilot-ai/includes/core/class-spai-integration-manager.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add site-pilot-ai/includes/core/class-spai-integration-manager.php
git commit -m "feat: register PostHog as admin-configurable integration provider"
```

---

## Task 2: Remove Hardcoded Token, Read from Integration Manager

**Files:**
- Modify: `site-pilot-ai/site-pilot-ai.php`
- Modify: `site-pilot-ai/includes/admin/class-spai-admin.php`
- Modify: `site-pilot-ai/includes/admin/class-spai-integrations-admin.php`

- [ ] **Step 1: Remove hardcoded default token from site-pilot-ai.php**

Find these lines (around line 66–112):

```php
if ( ! defined( 'SPAI_POSTHOG_DEFAULT_TOKEN' ) ) {
	define( 'SPAI_POSTHOG_DEFAULT_TOKEN', 'phc_vdyUDJrQpNCCfHyi3EFUPDq9avBfZ87tUTYqNC6CiXwX' );
}
```

and:

```php
if ( ! defined( 'SPAI_POSTHOG_DEFAULT_HOST' ) ) {
	define( 'SPAI_POSTHOG_DEFAULT_HOST', 'https://us.i.posthog.com' );
}
```

and:

```php
if ( ! defined( 'SPAI_POSTHOG_TOKEN' ) ) {
	define( 'SPAI_POSTHOG_TOKEN', spai_env_var( 'POSTHOG_PUBLIC_TOKEN', SPAI_POSTHOG_DEFAULT_TOKEN ) );
}

if ( ! defined( 'SPAI_POSTHOG_HOST' ) ) {
	define( 'SPAI_POSTHOG_HOST', spai_env_var( 'POSTHOG_HOST', SPAI_POSTHOG_DEFAULT_HOST ) );
}
```

Replace all of the above with:

```php
if ( ! defined( 'SPAI_POSTHOG_DEFAULT_HOST' ) ) {
	define( 'SPAI_POSTHOG_DEFAULT_HOST', 'https://us.i.posthog.com' );
}
```

(Remove the token default constant and the SPAI_POSTHOG_TOKEN/SPAI_POSTHOG_HOST define blocks entirely — the admin classes will read from the integration manager directly.)

- [ ] **Step 2: Verify site-pilot-ai.php syntax**

```bash
php -l site-pilot-ai/site-pilot-ai.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Update class-spai-admin.php to read from integration manager**

Find the `wp_localize_script` call that passes `posthogToken` and `posthogHost` (around line 234–242). It currently reads:

```php
'posthogToken' => SPAI_POSTHOG_TOKEN,
'posthogHost'  => SPAI_POSTHOG_HOST,
```

Add a variable above the `wp_localize_script` call to read from integration manager. Find the enqueue function block and add before the localize call:

```php
$posthog_manager = Spai_Integration_Manager::get_instance();
$posthog_config  = $posthog_manager->get_provider_config( 'posthog' );
$posthog_token   = isset( $posthog_config['token'] ) ? $posthog_config['token'] : '';
$posthog_host    = ! empty( $posthog_config['host'] ) ? $posthog_config['host'] : SPAI_POSTHOG_DEFAULT_HOST;
```

Then change the localize lines to:

```php
'posthogToken' => $posthog_token,
'posthogHost'  => $posthog_host,
```

- [ ] **Step 4: Verify class-spai-admin.php syntax**

```bash
php -l site-pilot-ai/includes/admin/class-spai-admin.php
```

Expected: `No syntax errors detected`

- [ ] **Step 5: Update class-spai-integrations-admin.php the same way**

Find the `wp_localize_script` for integrations (around line 81–88) with:

```php
'posthogToken' => SPAI_POSTHOG_TOKEN,
'posthogHost'  => SPAI_POSTHOG_HOST,
```

Add the same variable block before it and replace with:

```php
$posthog_manager = Spai_Integration_Manager::get_instance();
$posthog_config  = $posthog_manager->get_provider_config( 'posthog' );
$posthog_token   = isset( $posthog_config['token'] ) ? $posthog_config['token'] : '';
$posthog_host    = ! empty( $posthog_config['host'] ) ? $posthog_config['host'] : SPAI_POSTHOG_DEFAULT_HOST;
```

and:

```php
'posthogToken' => $posthog_token,
'posthogHost'  => $posthog_host,
```

- [ ] **Step 6: Verify syntax**

```bash
php -l site-pilot-ai/includes/admin/class-spai-integrations-admin.php
```

Expected: `No syntax errors detected`

- [ ] **Step 7: Commit**

```bash
git add site-pilot-ai/site-pilot-ai.php \
        site-pilot-ai/includes/admin/class-spai-admin.php \
        site-pilot-ai/includes/admin/class-spai-integrations-admin.php
git commit -m "refactor: read PostHog token from integration manager, remove hardcoded default"
```

---

## Task 3: Port PostHog Init + 10 Event Captures from Codex Branch

**Files:**
- Modify: `site-pilot-ai/admin/js/spai-admin.js`
- Modify: `site-pilot-ai/admin/partials/spai-setup-display.php`
- Modify: `site-pilot-ai/admin/partials/spai-integrations-display.php`
- Modify: `site-pilot-ai/admin/partials/spai-tools-display.php`

### spai-admin.js

- [ ] **Step 1: Add PostHog init snippet to spai-admin.js**

Open `site-pilot-ai/admin/js/spai-admin.js`. Find the top of the file (after the file comment block, before `(function($) {`). Insert:

```javascript
// PostHog analytics initialization
(function() {
	var cfg = (typeof spaiAdmin !== 'undefined' && spaiAdmin.posthogToken) ? spaiAdmin
		: (typeof spaiIntegrations !== 'undefined' && spaiIntegrations.posthogToken) ? spaiIntegrations
		: null;
	if (!cfg) return;
	!function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
	posthog.init(cfg.posthogToken, { api_host: cfg.posthogHost, defaults: '2026-01-30' });
})();
```

- [ ] **Step 2: Add api_key_copied capture**

Find the `copyToClipboard(text).then(function()` block. After `showCopyFeedback(btn);`, add:

```javascript
				if (window.posthog) {
					posthog.capture('api_key_copied', { source: 'setup_page' });
				}
```

- [ ] **Step 3: Add connection_tested captures**

Find the `result.css('background', '#d4edda');` line (success path). After it, add:

```javascript
					if (window.posthog) {
						posthog.capture('connection_tested', { result: 'success' });
					}
```

Find the two failure paths (`.css('background', '#f8d7da')`). After each, add:

```javascript
					if (window.posthog) {
						posthog.capture('connection_tested', { result: 'failure' });
					}
```

- [ ] **Step 4: Add welcome_banner_dismissed capture**

Find the `initDismissWelcome` function and the `.prop('disabled', true)` line. After it, add:

```javascript
			if (window.posthog) {
				posthog.capture('welcome_banner_dismissed');
			}
```

- [ ] **Step 5: Add upgrade tracking function**

Find the `initDismissWelcome` function. After its closing `}`, add a new function:

```javascript
	/**
	 * Track upgrade link clicks
	 */
	function initUpgradeTracking() {
		$(document).on('click', 'a[href*="mcpwp.net/pricing"], a[href*="mcpwp.net/account"]', function() {
			if (window.posthog) {
				posthog.capture('upgrade_link_clicked', { href: $(this).attr('href') });
			}
		});
	}
```

Then in the `$(document).ready` block, find `initDismissWelcome();` and add after it:

```javascript
		initUpgradeTracking();
```

- [ ] **Step 6: Verify JS has no obvious syntax errors**

```bash
node --check site-pilot-ai/admin/js/spai-admin.js 2>&1 || echo "node not available — manual review"
```

### spai-setup-display.php

- [ ] **Step 7: Add scoped_key_created capture**

Find the `updateRoleUI();` call at the end of the role UI init block. After it (still inside the same IIFE), add:

```javascript
				// Track scoped key creation
				var createForm = document.querySelector('form [name="spai_create_scoped_key"]');
				if (createForm) {
					createForm.closest('form').addEventListener('submit', function() {
						if (window.posthog) {
							posthog.capture('scoped_key_created', {
								role: roleSelect ? roleSelect.value : 'unknown'
							});
						}
					});
				}
```

- [ ] **Step 8: Add scoped_key_revoked capture**

Find the scoped keys table section (search for `spai_revoke_scoped_key`). After the table closing tag `</table>` and `<?php endif; ?>`, add:

```php
			<script>
			(function() {
				document.querySelectorAll('[name="spai_revoke_scoped_key"]').forEach(function(btn) {
					btn.closest('form').addEventListener('submit', function() {
						if (window.posthog) {
							posthog.capture('scoped_key_revoked');
						}
					});
				});
			})();
			</script>
```

- [ ] **Step 9: Add ai_client_tab_switched capture**

Find the tab switch JS (search for `spai-tab-`). After `p.style.display = p.id === 'spai-tab-' + target ? '' : 'none';`, add:

```javascript
						if (window.posthog) {
							posthog.capture('ai_client_tab_switched', { client: target });
						}
```

### spai-integrations-display.php

- [ ] **Step 10: Add integration_saved capture**

Find `$status.text('Saved! Reloading...').css('color', '#00a32a');`. After it, add:

```javascript
				if (window.posthog) {
					posthog.capture('integration_saved', { provider: provider });
				}
```

- [ ] **Step 11: Add integration_removed capture**

Find `$status.text('Removed! Reloading...').css('color', '#00a32a');`. After it, add:

```javascript
				if (window.posthog) {
					posthog.capture('integration_removed', { provider: provider });
				}
```

### spai-tools-display.php

- [ ] **Step 12: Add tool_category_toggled capture**

Find `$status.text(enabled === '1' ? 'Enabled' : 'Disabled').css('color', '#00a32a');`. After it, add:

```javascript
				if (window.posthog) {
					posthog.capture('tool_category_toggled', { category: category, enabled: enabled === '1' });
				}
```

- [ ] **Step 13: Verify PHP files**

```bash
php -l site-pilot-ai/admin/partials/spai-setup-display.php
php -l site-pilot-ai/admin/partials/spai-integrations-display.php
php -l site-pilot-ai/admin/partials/spai-tools-display.php
```

Expected: `No syntax errors detected` for all three

- [ ] **Step 14: Commit**

```bash
git add site-pilot-ai/admin/js/spai-admin.js \
        site-pilot-ai/admin/partials/spai-setup-display.php \
        site-pilot-ai/admin/partials/spai-integrations-display.php \
        site-pilot-ai/admin/partials/spai-tools-display.php
git commit -m "feat: add PostHog init and 10 admin event captures

Ports analytics from codex branch: api_key_copied, connection_tested
(success/failure), welcome_banner_dismissed, upgrade_link_clicked,
scoped_key_created, scoped_key_revoked, ai_client_tab_switched,
integration_saved, integration_removed, tool_category_toggled."
```

---

## Task 4: README Cleanup + Version Bump

**Files:**
- Modify: `README.md`
- Modify: `site-pilot-ai/site-pilot-ai.php`
- Modify: `site-pilot-ai/readme.txt`
- Modify: `version.json`

- [ ] **Step 1: Apply README changes from codex branch**

```bash
git checkout origin/codex/preserve-main-local-edits-20260607 -- README.md
```

Then immediately open README.md and revert these two lines (the codex branch had stale install command and Claude Code plugin text — update to correct current values):

Find:
```
wp plugin install /path/to/mcpwp.zip --activate
```
Replace with:
```
wp plugin install https://mcpwp.net/download/mcpwp.zip --activate
```

Find the Claude Code Plugin section and verify it reads:
```
MCPWP can be used from Claude Code through MCP configuration. A Claude Code helper plugin is available separately for setup and builder workflows; check the website/docs for the current package name and install command.
```
(This is already correct from the codex branch — leave as-is.)

- [ ] **Step 2: Verify README looks correct**

```bash
head -50 README.md
```

Check: no hardcoded tool counts (239, 24), badges say "dynamic discovery" / "reusable patterns", comparison table uses general descriptions not specific numbers.

- [ ] **Step 3: Bump version to 2.8.44**

In `site-pilot-ai/site-pilot-ai.php`, update:
```
 * Version:           2.8.44
```
and:
```php
define( 'SPAI_VERSION', '2.8.44' );
```

In `site-pilot-ai/readme.txt`, update:
```
Stable tag: 2.8.44
```

Add changelog entry at the top of the changelog section:
```
= 2.8.44 =
* New: PostHog analytics integration — configure token via WP Admin > Integrations. Tracks 10 key admin actions (API key copy, connection test, upgrade click, key create/revoke, integration save/remove, tool toggle, client tab switch).
* Fix: PostHog token no longer hardcoded — removed default public token, must be configured per-site.
```

In `version.json`, update `"version": "2.8.44"` and prepend the changelog entry.

- [ ] **Step 4: Verify version consistency**

```bash
grep -E "Version:|SPAI_VERSION|Stable tag" site-pilot-ai/site-pilot-ai.php site-pilot-ai/readme.txt
python3 -c "import json; d=json.load(open('version.json')); print(d['version'])"
```

Expected: all four reads show `2.8.44`

- [ ] **Step 5: Commit**

```bash
git add README.md \
        site-pilot-ai/site-pilot-ai.php \
        site-pilot-ai/readme.txt \
        version.json
git commit -m "chore: README cleanup + bump to v2.8.44"
```

---

## Task 5: Push Branch and Open PR

- [ ] **Step 1: Create and push branch**

All 4 tasks above should have been done on a feature branch. If working directly, create the branch now:

```bash
git checkout -b feat/posthog-integration-events origin/main
# then cherry-pick or re-apply the 4 commits above
```

Or if commits are already on the current branch:

```bash
git push origin HEAD:feat/posthog-integration-events
```

- [ ] **Step 2: Open PR**

```bash
gh pr create \
  --repo Mumega-com/mcpwp \
  --base main \
  --title "feat: PostHog as admin integration + 10 event captures (v2.8.44)" \
  --body "$(cat <<'EOF'
## Summary
- Adds PostHog to the Integrations admin page — configure token + host per-site, no hardcoded default
- Removes `phc_vdyUDJrQpNCCfHyi3EFUPDq9avBfZ87tUTYqNC6CiXwX` hardcoded public token from plugin source
- Ports 10 admin event captures from codex branch: `api_key_copied`, `connection_tested`, `welcome_banner_dismissed`, `upgrade_link_clicked`, `scoped_key_created`, `scoped_key_revoked`, `ai_client_tab_switched`, `integration_saved`, `integration_removed`, `tool_category_toggled`
- README: removes stale hardcoded tool counts, updates badges and comparison table
- Bumps to v2.8.44

## Test plan
- [ ] Go to WP Admin > Integrations, verify PostHog row appears with Token + Host fields
- [ ] Enter a PostHog project token, save — verify saved successfully
- [ ] Go to Setup page, open browser devtools Network tab, verify PostHog script loads
- [ ] Copy an API key — verify `api_key_copied` event fires in PostHog Live Events
- [ ] Click Test Connection — verify `connection_tested` event fires
- [ ] Remove the PostHog token — verify PostHog script does NOT load (no token = no init)
EOF
)"
```
