# Server-Side MCP Tool Analytics Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Track MCP tool calls from customer WordPress sites to PostHog for adoption analysis and paid-customer support, with opt-in for free tier and opt-out for paid.

**Architecture:** New `Spai_Analytics` class fires non-blocking `wp_remote_post` to PostHog on `spai_tool_called` action. Hook added at end of `handle_tools_call` with timing + enum error classification. `analytics_enabled` added to existing `spai_settings` array. UUID stored in `wp_options`. Paid sites default to enabled via runtime `spai_get_fs_instance()->is_paying()` check.

**Tech Stack:** PHP 7.4+, WordPress options API, `wp_remote_post` (non-blocking), PostHog HTTP Capture API, Freemius SDK

**Repo:** `/mnt/HC_Volume_104325311/projects/sitepilotai/wp-ai-operator` (remote: `Mumega-com/mcpwp`, base: `origin/main`)

**Spec:** `docs/superpowers/specs/2026-06-07-server-side-analytics-design.md`

---

## Files

| Action | File | Purpose |
|--------|------|---------|
| Create | `site-pilot-ai/includes/core/class-spai-analytics.php` | PostHog gateway — capture, UUID, enabled check |
| Modify | `site-pilot-ai/includes/api/class-spai-rest-mcp.php` | Add timing, `classify_error()`, `do_action('spai_tool_called', ...)` |
| Modify | `site-pilot-ai/includes/admin/class-spai-settings.php` | Add `analytics_enabled` to defaults + sanitize |
| Modify | `site-pilot-ai/admin/partials/spai-settings-display.php` | Analytics toggle + UUID display UI |
| Modify | `site-pilot-ai/site-pilot-ai.php` | Register `Spai_Analytics` hook subscriber |
| Modify | `site-pilot-ai/readme.txt` | Privacy section + 2.8.45 changelog |
| Modify | `site-pilot-ai/site-pilot-ai.php` | Version → 2.8.45 |
| Modify | `version.json` | Version + changelog |

---

## Task 1: Create `Spai_Analytics` Class

**Files:**
- Create: `site-pilot-ai/includes/core/class-spai-analytics.php`

- [ ] **Step 1: Create the file**

Create `/path/to/worktree/site-pilot-ai/includes/core/class-spai-analytics.php` with this exact content:

```php
<?php
/**
 * Analytics gateway — sends anonymous usage events to PostHog.
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles server-side analytics via PostHog HTTP Capture API.
 */
class Spai_Analytics {

	/**
	 * wp_options key for the persistent site UUID.
	 */
	const UUID_OPTION = 'spai_site_uuid';

	/**
	 * Whether analytics is enabled for this site.
	 *
	 * Free tier: respects spai_settings['analytics_enabled'] (default false).
	 * Paid tier: defaults to true unless explicitly disabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$settings = get_option( 'spai_settings', array() );
		$explicit = isset( $settings['analytics_enabled'] ) ? (bool) $settings['analytics_enabled'] : null;

		// Explicit opt-out always wins.
		if ( false === $explicit ) {
			return false;
		}

		// Paid sites: default to enabled unless explicitly disabled above.
		$fs = function_exists( 'spai_get_fs_instance' ) ? spai_get_fs_instance() : null;
		if ( $fs && method_exists( $fs, 'is_paying' ) && $fs->is_paying() ) {
			return true;
		}

		// Free sites: only if explicitly enabled.
		return true === $explicit;
	}

	/**
	 * Get or create the persistent site UUID.
	 *
	 * @return string UUID prefixed with 'mcpwp-'.
	 */
	public static function get_site_uuid() {
		$uuid = get_option( self::UUID_OPTION, '' );
		if ( empty( $uuid ) ) {
			$uuid = 'mcpwp-' . wp_generate_uuid4();
			update_option( self::UUID_OPTION, $uuid, false );
		}
		return $uuid;
	}

	/**
	 * Send an event to PostHog. Fire-and-forget, never throws.
	 *
	 * @param string $event      PostHog event name.
	 * @param array  $properties Event properties.
	 */
	public static function capture( $event, array $properties = array() ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		$config = Spai_Integration_Manager::get_instance()->get_posthog_config();
		if ( empty( $config['token'] ) ) {
			return;
		}

		$payload = array(
			'api_key'     => $config['token'],
			'event'       => $event,
			'distinct_id' => self::get_site_uuid(),
			'properties'  => array_merge(
				array(
					'plugin_version' => defined( 'SPAI_VERSION' ) ? SPAI_VERSION : '',
					'wp_version'     => get_bloginfo( 'version' ),
					'php_version'    => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
					'$lib'           => 'mcpwp-php',
				),
				$properties
			),
		);

		try {
			wp_remote_post(
				rtrim( $config['host'], '/' ) . '/capture/',
				array(
					'blocking'    => false,
					'timeout'     => 5,
					'headers'     => array( 'Content-Type' => 'application/json' ),
					'body'        => wp_json_encode( $payload ),
					'data_format' => 'body',
				)
			);
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'MCPWP Analytics: capture failed — ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Hook subscriber for spai_tool_called action.
	 *
	 * @param string $tool_name  MCP tool name.
	 * @param string $category   Tool category slug.
	 * @param int    $duration_ms Execution time in milliseconds.
	 * @param string $error_code  Enum error code, empty string on success.
	 */
	public static function on_tool_called( $tool_name, $category, $duration_ms, $error_code ) {
		self::capture(
			'mcp_tool_called',
			array(
				'tool'        => $tool_name,
				'category'    => $category,
				'success'     => '' === $error_code,
				'duration_ms' => (int) $duration_ms,
				'error_code'  => $error_code,
			)
		);
	}
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l site-pilot-ai/includes/core/class-spai-analytics.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add site-pilot-ai/includes/core/class-spai-analytics.php
git commit -m "feat: add Spai_Analytics class for PostHog server-side capture"
```

---

## Task 2: Add Timing + Hook to `handle_tools_call`

**Files:**
- Modify: `site-pilot-ai/includes/api/class-spai-rest-mcp.php`

- [ ] **Step 1: Read the file**

Read `site-pilot-ai/includes/api/class-spai-rest-mcp.php` and locate `handle_tools_call` (around line 768). Note: the method signature is `private function handle_tools_call( $id, $params, $request )`.

- [ ] **Step 2: Add `$start` timer at top of `handle_tools_call`**

Find the first line inside `handle_tools_call`:
```php
$tool_name = isset( $params['name'] ) ? $params['name'] : '';
```

Add `$start = microtime( true );` on the line before it:
```php
$start     = microtime( true );
$tool_name = isset( $params['name'] ) ? $params['name'] : '';
```

- [ ] **Step 3: Add `classify_error()` private method**

After the closing `}` of `handle_tools_call`, add this new private method:

```php
	/**
	 * Map a JSON-RPC result to a fixed error code enum.
	 * Returns empty string on success. Never returns free-text content.
	 *
	 * @param array $result JSON-RPC result array.
	 * @return string Error code enum or ''.
	 */
	private function classify_error( array $result ) {
		if ( empty( $result['error'] ) ) {
			return '';
		}
		$code = isset( $result['error']['code'] ) ? (int) $result['error']['code'] : 0;
		$map  = array(
			-32602 => 'tool_not_found',    // invalid params / unknown tool
			-32003 => 'scope_denied',      // category disabled or key scope
			-32603 => 'execution_error',   // internal error
		);
		if ( isset( $map[ $code ] ) ) {
			return $map[ $code ];
		}
		// Auth failures come back as WP_REST errors before this point, but guard anyway.
		if ( $code >= -32099 && $code <= -32000 ) {
			return 'execution_error';
		}
		return 'unknown_error';
	}
```

- [ ] **Step 4: Fire `spai_tool_called` before each `return` in `handle_tools_call`**

`handle_tools_call` has multiple return points (early returns for errors + the final tool execution return). The cleanest approach is to wrap the entire function body in a pattern that fires the action before returning.

Read the current end of `handle_tools_call` (the section that actually dispatches the tool and returns). Find the final `return` statement (after the tool map dispatch runs). Before that final return, add:

```php
		$duration_ms = (int) round( ( microtime( true ) - $start ) * 1000 );
		$error_code  = $this->classify_error( $result );
		$all_cats    = $this->get_all_tool_categories();
		$category    = isset( $all_cats[ $tool_name ] ) ? $all_cats[ $tool_name ] : 'site';

		/**
		 * Fires after an MCP tool call completes.
		 *
		 * @param string $tool_name   The tool that was called.
		 * @param string $category    Tool category slug.
		 * @param int    $duration_ms Execution time in milliseconds.
		 * @param string $error_code  Enum error code, '' on success.
		 */
		do_action( 'spai_tool_called', $tool_name, $category, $duration_ms, $error_code );
```

Note: For early-return error paths (tool_not_found, scope_denied, etc.) the action should also fire. Read each early return, capture `$result` before the return, and add the same do_action block. The `$start` timer is set at function entry so duration is always valid.

- [ ] **Step 5: Verify syntax**

```bash
php -l site-pilot-ai/includes/api/class-spai-rest-mcp.php
```

Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add site-pilot-ai/includes/api/class-spai-rest-mcp.php
git commit -m "feat: fire spai_tool_called action after every MCP tool execution"
```

---

## Task 3: Add `analytics_enabled` to Settings + Register Hook

**Files:**
- Modify: `site-pilot-ai/includes/admin/class-spai-settings.php`
- Modify: `site-pilot-ai/site-pilot-ai.php`

- [ ] **Step 1: Add `analytics_enabled` to `get_defaults()`**

In `class-spai-settings.php`, find `get_defaults()` (around line 393). Add to the returned array:

```php
			'analytics_enabled'  => false,
```

Add it after `'alerts_5xx_threshold'    => 5,` and before the closing `);`.

- [ ] **Step 2: Add `analytics_enabled` sanitization to `sanitize_settings()`**

In `sanitize_settings()` (around line 467), add sanitization for the new key. Find where other boolean settings are sanitized and add:

```php
		$output['analytics_enabled'] = ! empty( $input['analytics_enabled'] );
```

- [ ] **Step 3: Add settings field registration**

In `register_settings()`, add a new field after the existing fields. Find `add_settings_section` for `'spai_general_section'` and add a new field:

```php
		add_settings_field(
			'analytics_enabled',
			__( 'Usage Analytics', 'mumega-mcp' ),
			array( $this, 'render_analytics_enabled_field' ),
			'spai_settings',
			'spai_general_section'
		);
```

- [ ] **Step 4: Add field renderer method**

Add this public method to `Spai_Settings`:

```php
	/**
	 * Render analytics enabled field.
	 */
	public function render_analytics_enabled_field() {
		$settings = $this->get_settings();
		$enabled  = ! empty( $settings['analytics_enabled'] );
		$uuid     = Spai_Analytics::get_site_uuid();
		?>
		<label>
			<input type="checkbox" name="spai_settings[analytics_enabled]" value="1" <?php checked( $enabled ); ?> />
			<?php esc_html_e( 'Share anonymous usage data to help improve MCPWP', 'mumega-mcp' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Sends tool call counts and error rates to PostHog. No WordPress content or personal data is collected.', 'mumega-mcp' ); ?>
			<a href="https://mcpwp.net/privacy" target="_blank" rel="noopener"><?php esc_html_e( 'Privacy policy', 'mumega-mcp' ); ?></a>
		</p>
		<p class="description" style="margin-top:8px;">
			<?php esc_html_e( 'Your support ID:', 'mumega-mcp' ); ?>
			<code id="spai-site-uuid"><?php echo esc_html( $uuid ); ?></code>
			<button type="button" class="button button-small" onclick="navigator.clipboard.writeText('<?php echo esc_js( $uuid ); ?>');this.textContent='Copied!';"><?php esc_html_e( 'Copy', 'mumega-mcp' ); ?></button>
		</p>
		<?php
	}
```

- [ ] **Step 5: Verify syntax**

```bash
php -l site-pilot-ai/includes/admin/class-spai-settings.php
```

Expected: `No syntax errors detected`

- [ ] **Step 6: Register `Spai_Analytics` as hook subscriber in `site-pilot-ai.php`**

In `site-pilot-ai/site-pilot-ai.php`, find where other classes are loaded (the `require_once` section for core classes). Add:

```php
require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-analytics.php';
```

Then find the section where WordPress hooks are registered (after class loads). Add:

```php
add_action( 'spai_tool_called', array( 'Spai_Analytics', 'on_tool_called' ), 10, 4 );
```

- [ ] **Step 7: Verify syntax**

```bash
php -l site-pilot-ai/site-pilot-ai.php
```

Expected: `No syntax errors detected`

- [ ] **Step 8: Commit**

```bash
git add site-pilot-ai/includes/admin/class-spai-settings.php site-pilot-ai/site-pilot-ai.php
git commit -m "feat: add analytics_enabled setting, UUID display, register hook subscriber"
```

---

## Task 4: readme.txt Privacy Section + Version Bump

**Files:**
- Modify: `site-pilot-ai/readme.txt`
- Modify: `site-pilot-ai/site-pilot-ai.php`
- Modify: `version.json`

- [ ] **Step 1: Add Privacy section to readme.txt**

Open `site-pilot-ai/readme.txt`. Find `== Frequently Asked Questions ==` or similar section. Add a new `== Privacy ==` section before it:

```
== Privacy ==

MCPWP can collect anonymous usage data when the "Share anonymous usage data" setting is enabled in WP Admin > MCPWP > Settings.

**What is collected:** MCP tool names, success or failure status, execution duration, plugin version, WordPress version, PHP version.

**What is NOT collected:** Post content, page content, user data, API keys, or any personally identifiable information.

**Where data is sent:** PostHog (posthog.com). Your server's IP address is transmitted as part of the outbound HTTP request.

**Identifier:** Each site is assigned a random UUID (e.g. mcpwp-xxxxxxxx) that cannot be traced back to a domain or individual.

**Free tier default:** Disabled. Must be explicitly enabled in Settings.

**Paid tier default:** Enabled. Can be disabled at any time in Settings.

**How to opt out:** Uncheck "Share anonymous usage data" under WP Admin > MCPWP > Settings.
```

- [ ] **Step 2: Add 2.8.45 changelog entry to readme.txt**

Find `== Changelog ==` and add at the top:

```
= 2.8.45 =
* New: Server-side MCP tool analytics — anonymous tool call tracking sent to PostHog when enabled. Opt-in for free tier, opt-out for paid.
* New: Site support UUID displayed in Settings page for paid customer support.
* New: Privacy section added to readme.txt per WP.org requirements.
```

- [ ] **Step 3: Update Stable tag in readme.txt**

Change `Stable tag: 2.8.44` to `Stable tag: 2.8.45`.

- [ ] **Step 4: Bump version in site-pilot-ai.php**

Update both:
- Header: `* Version:           2.8.45`
- Constant: `define( 'SPAI_VERSION', '2.8.45' );`

- [ ] **Step 5: Bump version in version.json**

Update `"version": "2.8.45"` and prepend to changelog:

```
"<h4>2.8.45<\/h4><ul><li>New: Server-side MCP tool analytics — anonymous tool call tracking sent to PostHog when enabled. Opt-in for free tier, opt-out for paid.<\/li><li>New: Site support UUID in Settings page.<\/li><li>New: Privacy disclosure in readme.txt per WP.org guidelines.<\/li><\/ul>"
```

- [ ] **Step 6: Verify version consistency**

```bash
grep -E "Version:|SPAI_VERSION|Stable tag" site-pilot-ai/site-pilot-ai.php site-pilot-ai/readme.txt
python3 -c "import json; d=json.load(open('version.json')); print(d['version'])"
```

Expected: all show `2.8.45`

- [ ] **Step 7: Verify version.json is valid JSON**

```bash
python3 -c "import json; json.load(open('version.json')); print('OK')"
```

Expected: `OK`

- [ ] **Step 8: Commit**

```bash
git add site-pilot-ai/readme.txt site-pilot-ai/site-pilot-ai.php version.json
git commit -m "chore: add privacy section to readme, bump to v2.8.45"
```

---

## Task 5: Update mcpwp.net Privacy Policy and Terms of Use

**Context:** mcpwp.net is a WordPress site. Use available WP MCP tools to update the Privacy Policy and Terms of Use pages. If no MCP connection is available for mcpwp.net in this session, skip this task and note it for manual follow-up.

**Check connection first:**
```
Use wp_site_info or wp_onboard tool for mcpwp.net to verify connection.
If not connected: skip and leave a note.
```

- [ ] **Step 1: Find the Privacy Policy page**

Use `wp_get_page_by_slug` with slug `privacy-policy` (or search for it).

- [ ] **Step 2: Add analytics disclosure to Privacy Policy**

Append the following section to the Privacy Policy page content. Add it as a new section titled **"Plugin Usage Analytics"**:

```
## Plugin Usage Analytics

If you use the MCPWP plugin on your WordPress site, MCPWP may collect anonymous usage data from your WordPress server when the analytics setting is enabled.

**Data collected:** Names of MCP tools called, success or failure status, execution duration, plugin version, WordPress version, PHP version.

**Data not collected:** Post content, page content, user data, API keys, passwords, or any personally identifiable information.

**Identifier:** Each site is assigned a random anonymous UUID (e.g. mcpwp-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx) that cannot be reverse-traced to a domain name or individual.

**Third-party processor:** Data is sent to PostHog, Inc. (posthog.com). Your server's IP address is transmitted as part of this HTTP request.

**Control:** You can enable or disable analytics at any time under WP Admin > MCPWP > Settings > "Share anonymous usage data."

**Free tier:** Analytics is disabled by default and requires explicit opt-in.

**Paid tier:** Analytics is enabled by default but can be disabled at any time.
```

- [ ] **Step 3: Find the Terms of Use / Terms of Service page**

Use `wp_get_page_by_slug` with slug `terms` or `terms-of-service`.

- [ ] **Step 4: Add analytics clause to Terms**

Append the following section under a heading **"Telemetry and Analytics"**:

```
## Telemetry and Analytics

MCPWP may collect anonymous telemetry data from the WordPress sites where it is installed, as described in our Privacy Policy. This data is used solely to improve the plugin and provide support to paid customers.

By enabling the "Share anonymous usage data" setting (enabled by default for paid plans), you agree to the collection and processing of this anonymous telemetry. You may disable this at any time from the plugin settings.
```

- [ ] **Step 5: Publish both pages**

Ensure both pages are saved and published (status: `publish`).

---

## Task 6: Push Branch and Open PR

- [ ] **Step 1: Push branch**

```bash
git push origin feat/server-side-analytics
```

(Adjust branch name to whatever was used in the worktree.)

- [ ] **Step 2: Open PR**

```bash
gh pr create \
  --repo Mumega-com/mcpwp \
  --base main \
  --title "feat: server-side MCP tool analytics via PostHog (v2.8.45)" \
  --body "$(cat <<'EOF'
## Summary
- New `Spai_Analytics` class — fire-and-forget PostHog capture via `wp_remote_post(blocking=false)`
- `do_action('spai_tool_called', $tool, $category, $duration_ms, $error_code)` fired after every MCP tool call
- `classify_error()` maps results to fixed enum — never captures customer content
- `analytics_enabled` setting added to WP Admin > Settings with UUID display for support
- Paid sites default to enabled; free sites require opt-in
- Site UUID: random `mcpwp-<uuid4>`, stored in wp_options, shown in Settings for support use
- Privacy section added to readme.txt per WP.org requirements
- mcpwp.net Privacy Policy + ToS updated with analytics disclosure
- Bumps to v2.8.45

## PostHog event payload
\`\`\`json
{
  "event": "mcp_tool_called",
  "distinct_id": "mcpwp-<uuid>",
  "properties": {
    "tool": "wp_create_post",
    "category": "content",
    "success": true,
    "duration_ms": 142,
    "error_code": "",
    "plugin_version": "2.8.45",
    "wp_version": "6.9",
    "php_version": "8.2"
  }
}
\`\`\`

## Test plan
- [ ] Enable analytics in Settings — verify PostHog receives `mcp_tool_called` event via Live Events
- [ ] Disable analytics — verify no events sent
- [ ] Call a tool with a bad name — verify `error_code: "tool_not_found"` in event
- [ ] Site with no PostHog token configured — verify no HTTP calls made
- [ ] Check Settings page shows UUID + Copy button
EOF
)"
```
