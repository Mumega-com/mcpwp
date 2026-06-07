# Server-Side MCP Tool Analytics Design

**Goal:** Track how customers use MCPWP's MCP tools from their WordPress servers to PostHog, enabling adoption analysis and proactive paid-customer support.

**Scope:** Server-side PHP analytics only. Admin UI analytics (PR #358) is separate.

---

## Architecture

A new `Spai_Analytics` class acts as the single outbound analytics gateway. It registers a WordPress action listener on `spai_tool_called` — a new hook fired at the end of `handle_tools_call`. The MCP handler stays clean; analytics is a decoupled subscriber.

```
handle_tools_call()
  └─ do_action('spai_tool_called', $tool_name, $category, $duration_ms, $error_code)
       └─ Spai_Analytics::on_tool_called()
            └─ wp_remote_post(PostHog /capture/, non-blocking)
```

---

## Components

### `includes/core/class-spai-analytics.php` (new)

Single responsibility: send analytics events to PostHog via HTTP.

**Public API:**
- `Spai_Analytics::capture( string $event, array $properties )` — static, fire-and-forget
- `Spai_Analytics::on_tool_called( string $tool, string $category, int $duration_ms, string $error_code )` — hook subscriber
- `Spai_Analytics::get_site_uuid()` — returns or generates the persistent site UUID
- `Spai_Analytics::is_enabled()` — checks `spai_analytics_enabled` option

**Behaviour:**
- Returns immediately if `is_enabled()` is false or PostHog token is empty
- Uses `wp_remote_post` with `blocking => false` (fire-and-forget, no response wait)
- Wraps all outbound calls in `try/catch` — analytics must never affect tool execution
- `distinct_id` is the site UUID (see below)

### Hook in `includes/api/class-spai-rest-mcp.php` (modify)

At the end of `handle_tools_call`, after the tool executes and before returning:

```php
$error_code = $this->classify_error( $result ); // enum string or ''
do_action( 'spai_tool_called', $tool_name, $category, $duration_ms, $error_code );
```

`$duration_ms` = `(int) round( ( microtime(true) - $start ) * 1000 )` where `$start` is captured at the top of `handle_tools_call`.

### `classify_error()` (private, in `class-spai-rest-mcp.php`)

Maps any failure result to a fixed enum — never free text, never customer content.

| Condition | `$error_code` |
|-----------|--------------|
| Tool executed successfully | `''` (empty) |
| Tool name not found | `'tool_not_found'` |
| Category disabled by admin | `'category_disabled'` |
| API key scope insufficient | `'scope_denied'` |
| Tool threw PHP exception | `'execution_exception'` |
| Tool returned WP_Error | `'execution_error'` |
| Any other non-success | `'unknown_error'` |

---

## PostHog Event Payload

**Event name:** `mcp_tool_called`

```json
{
  "api_key": "<posthog_project_token>",
  "event": "mcp_tool_called",
  "distinct_id": "mcpwp-<uuid>",
  "properties": {
    "tool": "wp_create_post",
    "category": "content",
    "success": true,
    "duration_ms": 142,
    "error_code": "",
    "plugin_version": "2.8.44",
    "wp_version": "6.9",
    "php_version": "8.2",
    "$lib": "mcpwp-php"
  }
}
```

`success` is `true` when `error_code` is empty.

---

## Site UUID

- Stored in `wp_options` as `spai_site_uuid`
- Generated once on first analytics call: `'mcpwp-' . wp_generate_uuid4()`
- Never changes after generation
- Displayed in WP Admin > Settings with a "Copy for support" button so paying customers can share it
- Cannot be derived back to site URL — fully anonymous

---

## Opt-In / Opt-Out

| Tier | Default | Where toggled |
|------|---------|--------------|
| WP.org free | **Disabled** | WP Admin > Settings > Analytics toggle |
| Paid (Freemius) | **Enabled** on activation | Same toggle, can disable |

Freemius `after_install` hook sets `spai_analytics_enabled = true` for paid activations.

The toggle label: **"Share anonymous usage data to help improve MCPWP"** with a link to the privacy notice.

---

## Privacy

- `distinct_id` is a random UUID — cannot identify site owner
- Server IP is sent to PostHog as part of HTTP request (inherent to outbound calls) — disclosed in readme
- No tool arguments captured
- No post/page content captured
- Error codes are enum values only — never customer content
- `readme.txt` privacy section (required by WP.org):

```
= Privacy =
MCPWP collects anonymous usage data (tool names, success/failure, plugin version) when
the "Share anonymous usage data" setting is enabled. Data is sent to PostHog
(posthog.com). Your server's IP address is transmitted as part of this request.
No WordPress content, post data, or personally identifiable information is collected.
To opt out, disable the setting under WP Admin > MCPWP > Settings.
```

---

## Admin UI Changes

**WP Admin > Settings page** — add below existing settings:

```
[ ] Share anonymous usage data to help improve MCPWP
    Sends tool call counts and error rates to PostHog. No content or personal data.
    Your site's support ID: mcpwp-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx  [Copy]
```

UUID only shown when analytics is enabled (or always, so customers can share it for support even if disabled).

---

## Error Handling

- PostHog unreachable → silent failure, `wp_remote_post` non-blocking swallows it
- Token empty → `is_enabled()` check catches before any HTTP call
- `WP_HTTP_BLOCK_EXTERNAL` or firewall blocks outbound → silent failure
- PHP exception in capture() → caught, logged to WP debug log at `debug` level only, tool result unaffected
- PostHog rate limit (429) → ignored, non-blocking means we never see the response

---

## Files

| Action | File |
|--------|------|
| Create | `site-pilot-ai/includes/core/class-spai-analytics.php` |
| Modify | `site-pilot-ai/includes/api/class-spai-rest-mcp.php` |
| Modify | `site-pilot-ai/includes/admin/class-spai-settings-admin.php` (or equivalent settings page) |
| Modify | `site-pilot-ai/admin/partials/spai-settings-display.php` |
| Modify | `site-pilot-ai/site-pilot-ai.php` (register hook + Freemius activation) |
| Modify | `site-pilot-ai/readme.txt` (privacy section) |
| Bump | Version → 2.8.45 |

---

## Out of Scope

- Batching / wp_cron deferred sends (not needed at current scale)
- Google Analytics (separate concern — mcpwp.net website traffic only)
- Tracking `tools/list` calls (too noisy, low signal)
- Tracking REST API calls outside MCP (separate feature)
- Self-hosted PostHog (future option if scale demands)
