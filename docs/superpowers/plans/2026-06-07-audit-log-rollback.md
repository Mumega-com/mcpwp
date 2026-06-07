# AI Action Audit Log + Rollback Implementation Plan (v2.8.46)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Every MCPWP write-tool execution is logged to a DB table with before/after state snapshots. Rollback restores before-state for post/Elementor writes. Control Room shows the log with one-click rollback. CSV export and configurable retention satisfy EU AI Act compliance requirements.

**Architecture:** New `{prefix}spai_action_log` DB table (real DB, not wp_options). `Spai_Action_Log` static class hooks into MCP dispatcher via two new call sites in `handle_tools_call`: before-snapshot (before REST dispatch) and log-complete (after). REST endpoint class at `/action-log` provides list, rollback, and CSV export. Control Room admin page gains a paginated action log section.

**Tech Stack:** PHP 7.4+, WordPress DB API (wpdb), no extra libs.

---

## File Structure

```
site-pilot-ai/
├── includes/
│   ├── class-spai-activator.php              # MODIFY — add spai_action_log table
│   ├── core/
│   │   └── class-spai-action-log.php         # CREATE — core audit log class
│   ├── api/
│   │   ├── class-spai-rest-action-log.php    # CREATE — REST endpoints
│   │   └── class-spai-rest-mcp.php           # MODIFY — wire begin/complete calls
│   └── admin/
│       └── class-spai-admin.php              # MODIFY — add action_log to control room data + POST handler
├── admin/
│   └── partials/
│       └── spai-control-room-display.php     # MODIFY — add action log section
└── site-pilot-ai.php                         # MODIFY — require new classes, wire hooks, bump to 2.8.46
version.json                                  # MODIFY — version + changelog
readme.txt                                    # MODIFY — Stable tag + changelog
```

---

## DB Schema

```sql
CREATE TABLE {prefix}spai_action_log (
  id          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  log_id      varchar(44) NOT NULL,          -- 'act_' + uuid4 (no dashes)
  timestamp   datetime NOT NULL,
  tool_name   varchar(100) NOT NULL,
  category    varchar(50) NOT NULL DEFAULT '',
  api_key_id  varchar(100) NOT NULL DEFAULT '',
  arguments   longtext NOT NULL DEFAULT '',  -- JSON (api_key values stripped)
  before_snap longtext DEFAULT NULL,          -- JSON state before execution
  after_snap  longtext DEFAULT NULL,          -- JSON state after execution
  resource_type varchar(50) NOT NULL DEFAULT '',  -- post, elementor, option, other
  resource_id varchar(255) NOT NULL DEFAULT '',   -- post_id, option_key, etc.
  duration_ms int(11) DEFAULT NULL,
  success     tinyint(1) NOT NULL DEFAULT 0,
  error_code  varchar(50) NOT NULL DEFAULT '',
  rollback_supported tinyint(1) NOT NULL DEFAULT 0,
  rolled_back tinyint(1) NOT NULL DEFAULT 0,
  rolled_back_at datetime DEFAULT NULL,
  rolled_back_by varchar(44) DEFAULT NULL,   -- log_id of rollback-entry
  PRIMARY KEY (id),
  UNIQUE KEY log_id (log_id),
  KEY timestamp (timestamp),
  KEY tool_name (tool_name),
  KEY resource (resource_type, resource_id),
  KEY success (success)
) {charset_collate};
```

---

## Task 1: Add DB table to activator

**Files:**
- Modify: `site-pilot-ai/includes/class-spai-activator.php` — append to `create_tables()`

- [ ] **Step 1: Read class-spai-activator.php**

Read the existing `create_tables()` method to find the exact insertion point (after the last `dbDelta( $sql_feedback );` call, before the closing `}`).

- [ ] **Step 2: Add the table definition**

At the end of `create_tables()`, after the feedback table block, add:

```php
		// Action log table — immutable per-tool audit trail
		$action_log_table = $wpdb->prefix . 'spai_action_log';
		$sql_action_log   = "CREATE TABLE IF NOT EXISTS $action_log_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			log_id varchar(44) NOT NULL,
			timestamp datetime NOT NULL,
			tool_name varchar(100) NOT NULL,
			category varchar(50) NOT NULL DEFAULT '',
			api_key_id varchar(100) NOT NULL DEFAULT '',
			arguments longtext NOT NULL DEFAULT '',
			before_snap longtext DEFAULT NULL,
			after_snap longtext DEFAULT NULL,
			resource_type varchar(50) NOT NULL DEFAULT '',
			resource_id varchar(255) NOT NULL DEFAULT '',
			duration_ms int(11) DEFAULT NULL,
			success tinyint(1) NOT NULL DEFAULT 0,
			error_code varchar(50) NOT NULL DEFAULT '',
			rollback_supported tinyint(1) NOT NULL DEFAULT 0,
			rolled_back tinyint(1) NOT NULL DEFAULT 0,
			rolled_back_at datetime DEFAULT NULL,
			rolled_back_by varchar(44) DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY log_id (log_id),
			KEY timestamp (timestamp),
			KEY tool_name (tool_name),
			KEY resource (resource_type(50), resource_id(100)),
			KEY success (success)
		) $charset_collate;";
		dbDelta( $sql_action_log );
```

- [ ] **Step 3: Verify with dry-run**

There is no test harness here; verify by reading the edited file and confirming the table block follows the same pattern as the other four tables (feedback, webhooks, etc.).

- [ ] **Step 4: Commit**

```bash
git add site-pilot-ai/includes/class-spai-activator.php
git commit -m "feat(audit-log): add spai_action_log DB table to activator"
```

---

## Task 2: Implement Spai_Action_Log class

**Files:**
- Create: `site-pilot-ai/includes/core/class-spai-action-log.php`

This is the core class. Write-tool detection, before/after snapshots, DB insert, rollback, export, prune.

- [ ] **Step 1: Create the file**

Full content of `site-pilot-ai/includes/core/class-spai-action-log.php`:

```php
<?php
/**
 * Immutable action audit log for MCP tool calls.
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records every write-tool execution with before/after state for compliance and rollback.
 */
class Spai_Action_Log {

	/**
	 * DB table name (without prefix).
	 */
	const TABLE = 'spai_action_log';

	/**
	 * Default retention in days.
	 */
	const DEFAULT_RETENTION_DAYS = 90;

	/**
	 * Tool names that write post/page data and support rollback.
	 */
	private static $post_write_tools = array(
		'wp_update_post', 'wp_update_page',
		'wp_bulk_update_posts', 'wp_bulk_update_pages',
		'wp_set_featured_image',
	);

	/**
	 * Tool names that write Elementor data and support rollback.
	 */
	private static $elementor_write_tools = array(
		'wp_set_elementor', 'wp_edit_section', 'wp_add_section',
		'wp_remove_section', 'wp_edit_widget', 'wp_replace_section',
		'wp_patch_elementor', 'wp_edit_widget',
	);

	/**
	 * All write tool name prefixes — triggers logging for any tool matching these.
	 */
	private static $write_prefixes = array(
		'wp_create_', 'wp_update_', 'wp_delete_', 'wp_set_',
		'wp_add_', 'wp_remove_', 'wp_replace_', 'wp_edit_',
		'wp_bulk_', 'wp_patch_', 'wp_flush_', 'wp_trigger_',
		'wp_apply_', 'wp_assign_', 'wp_setup_', 'wp_configure_',
		'wp_import_', 'wp_regenerate_', 'wp_revoke_',
	);

	/**
	 * Pending log entry (before execution snapshot), keyed by log_id.
	 *
	 * @var array
	 */
	private static $pending = array();

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Called BEFORE the tool executes. Captures before-state and returns log_id.
	 * Returns null for non-write tools (caller should pass log_id=null to complete()).
	 *
	 * @param string $tool_name  MCP tool name.
	 * @param array  $arguments  Tool arguments (already remapped).
	 * @param string $api_key_id API key identifier (e.g. key label/id).
	 * @param string $category   Tool category.
	 * @return string|null log_id or null.
	 */
	public static function begin( $tool_name, $arguments, $api_key_id = '', $category = '' ) {
		if ( ! self::is_write_tool( $tool_name ) ) {
			return null;
		}

		$resource      = self::detect_resource( $tool_name, $arguments );
		$before_snap   = self::snapshot( $resource );
		$log_id        = self::generate_id();

		self::$pending[ $log_id ] = array(
			'resource'    => $resource,
			'before_snap' => $before_snap,
			'tool_name'   => $tool_name,
			'arguments'   => $arguments,
			'api_key_id'  => $api_key_id,
			'category'    => $category,
		);

		return $log_id;
	}

	/**
	 * Called AFTER the tool executes. Writes the full log row to DB.
	 *
	 * @param string|null $log_id      From begin(), null = skip.
	 * @param int         $duration_ms Execution duration.
	 * @param bool        $success     Whether the tool succeeded.
	 * @param string      $error_code  Error code or '' on success.
	 * @return void
	 */
	public static function complete( $log_id, $duration_ms, $success, $error_code = '' ) {
		if ( null === $log_id || ! isset( self::$pending[ $log_id ] ) ) {
			return;
		}

		$pending   = self::$pending[ $log_id ];
		$resource  = $pending['resource'];
		$tool_name = $pending['tool_name'];
		$arguments = $pending['arguments'];

		$after_snap         = $success ? self::snapshot( $resource ) : null;
		$rollback_supported = $success && self::is_rollback_supported( $tool_name, $resource );

		self::insert(
			$log_id,
			$tool_name,
			$pending['category'],
			$pending['api_key_id'],
			$arguments,
			$pending['before_snap'],
			$after_snap,
			$resource['type'],
			(string) $resource['id'],
			$duration_ms,
			$success ? 1 : 0,
			$error_code,
			$rollback_supported ? 1 : 0
		);

		unset( self::$pending[ $log_id ] );
	}

	/**
	 * Rollback a logged action by restoring its before-state.
	 *
	 * @param string $log_id Log entry ID.
	 * @return true|WP_Error
	 */
	public static function rollback( $log_id ) {
		$entry = self::get_entry( $log_id );

		if ( ! $entry ) {
			return new WP_Error( 'not_found', __( 'Log entry not found.', 'mumega-mcp' ), array( 'status' => 404 ) );
		}

		if ( ! $entry['rollback_supported'] ) {
			return new WP_Error( 'not_rollbackable', __( 'This action does not support rollback.', 'mumega-mcp' ), array( 'status' => 400 ) );
		}

		if ( $entry['rolled_back'] ) {
			return new WP_Error( 'already_rolled_back', __( 'This action has already been rolled back.', 'mumega-mcp' ), array( 'status' => 409 ) );
		}

		if ( empty( $entry['before_snap'] ) ) {
			return new WP_Error( 'no_snapshot', __( 'No before-snapshot available for rollback.', 'mumega-mcp' ), array( 'status' => 500 ) );
		}

		$snap = json_decode( $entry['before_snap'], true );
		if ( ! is_array( $snap ) ) {
			return new WP_Error( 'corrupt_snapshot', __( 'Before-snapshot is corrupt.', 'mumega-mcp' ), array( 'status' => 500 ) );
		}

		$result = self::apply_snapshot( $entry['resource_type'], $snap );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Log the rollback action itself.
		$rollback_log_id = self::begin( 'spai_rollback', array( 'target_log_id' => $log_id ), '', 'audit' );

		// Mark original entry as rolled back.
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . self::TABLE,
			array(
				'rolled_back'    => 1,
				'rolled_back_at' => current_time( 'mysql', true ),
				'rolled_back_by' => $rollback_log_id ?? '',
			),
			array( 'log_id' => $log_id ),
			array( '%d', '%s', '%s' ),
			array( '%s' )
		);

		// Finalize rollback log entry.
		if ( $rollback_log_id ) {
			self::complete( $rollback_log_id, 0, true, '' );
		}

		return true;
	}

	/**
	 * List log entries.
	 *
	 * @param array $args Query args: limit, offset, tool_name, success, resource_type, resource_id.
	 * @return array{ entries: array[], total: int }
	 */
	public static function list_entries( $args = array() ) {
		global $wpdb;

		$table  = $wpdb->prefix . self::TABLE;
		$limit  = min( 100, max( 1, (int) ( $args['limit'] ?? 50 ) ) );
		$offset = max( 0, (int) ( $args['offset'] ?? 0 ) );

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['tool_name'] ) ) {
			$where[]  = 'tool_name = %s';
			$values[] = sanitize_key( (string) $args['tool_name'] );
		}

		if ( isset( $args['success'] ) && '' !== $args['success'] ) {
			$where[]  = 'success = %d';
			$values[] = (int) $args['success'];
		}

		if ( ! empty( $args['resource_type'] ) ) {
			$where[]  = 'resource_type = %s';
			$values[] = sanitize_key( (string) $args['resource_type'] );
		}

		if ( ! empty( $args['resource_id'] ) ) {
			$where[]  = 'resource_id = %s';
			$values[] = sanitize_text_field( (string) $args['resource_id'] );
		}

		$where_sql = implode( ' AND ', $where );

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where_sql" ); // phpcs:ignore

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE $where_sql ORDER BY timestamp DESC LIMIT %d OFFSET %d", array_merge( $values, array( $limit, $offset ) ) ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE 1=1 ORDER BY timestamp DESC LIMIT %d OFFSET %d", $limit, $offset ), ARRAY_A );
		}

		return array(
			'entries' => array_map( array( __CLASS__, 'public_entry' ), $rows ?: array() ),
			'total'   => $total,
		);
	}

	/**
	 * Get one log entry.
	 *
	 * @param string $log_id Log entry ID.
	 * @return array|null Public entry or null.
	 */
	public static function get_entry_public( $log_id ) {
		$entry = self::get_entry( $log_id );
		return $entry ? self::public_entry( $entry ) : null;
	}

	/**
	 * Export log entries as CSV string.
	 *
	 * @param array $args Same as list_entries args, max 1000.
	 * @return string CSV content.
	 */
	public static function export_csv( $args = array() ) {
		$args['limit']  = min( 1000, max( 1, (int) ( $args['limit'] ?? 1000 ) ) );
		$args['offset'] = 0;
		$result         = self::list_entries( $args );
		$entries        = $result['entries'];

		$cols = array(
			'log_id', 'timestamp', 'tool_name', 'category', 'api_key_id',
			'resource_type', 'resource_id', 'duration_ms', 'success',
			'error_code', 'rollback_supported', 'rolled_back', 'rolled_back_at',
		);

		ob_start();
		$handle = fopen( 'php://output', 'w' );
		fputcsv( $handle, $cols );

		foreach ( $entries as $entry ) {
			$row = array();
			foreach ( $cols as $col ) {
				$row[] = isset( $entry[ $col ] ) ? $entry[ $col ] : '';
			}
			fputcsv( $handle, $row );
		}

		fclose( $handle );
		return ob_get_clean();
	}

	/**
	 * Delete entries older than $days days.
	 *
	 * @param int $days Retention window.
	 * @return int Rows deleted.
	 */
	public static function prune( $days = 0 ) {
		global $wpdb;

		if ( $days <= 0 ) {
			$days = (int) get_option( 'spai_action_log_retention_days', self::DEFAULT_RETENTION_DAYS );
		}

		if ( $days <= 0 ) {
			return 0;
		}

		$table    = $wpdb->prefix . self::TABLE;
		$cutoff   = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE timestamp < %s", $cutoff ) );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Return true if the tool name matches any write prefix.
	 *
	 * @param string $tool_name Tool name.
	 * @return bool
	 */
	private static function is_write_tool( $tool_name ) {
		foreach ( self::$write_prefixes as $prefix ) {
			if ( 0 === strpos( $tool_name, $prefix ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return true if this tool + resource combination supports 1-click rollback.
	 *
	 * @param string $tool_name Tool name.
	 * @param array  $resource  Detected resource array.
	 * @return bool
	 */
	private static function is_rollback_supported( $tool_name, $resource ) {
		if ( 'post' === $resource['type'] && in_array( $tool_name, self::$post_write_tools, true ) ) {
			return true;
		}

		if ( 'elementor' === $resource['type'] && in_array( $tool_name, self::$elementor_write_tools, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Detect resource type and ID from tool name + arguments.
	 *
	 * Returns array{ type: string, id: string|int }.
	 *
	 * @param string $tool_name Tool name.
	 * @param array  $arguments Tool arguments.
	 * @return array
	 */
	private static function detect_resource( $tool_name, $arguments ) {
		// Elementor write tools — use 'id' or 'page_id'
		if ( in_array( $tool_name, self::$elementor_write_tools, true ) ) {
			$id = $arguments['id'] ?? $arguments['page_id'] ?? '';
			return array( 'type' => 'elementor', 'id' => (int) $id );
		}

		// Post/page write tools — use 'id'
		if ( in_array( $tool_name, self::$post_write_tools, true ) ) {
			$id = $arguments['id'] ?? '';
			return array( 'type' => 'post', 'id' => (int) $id );
		}

		// Generic post-targeting tools (create/delete/clone)
		if ( preg_match( '/^wp_(create|delete|clone)_(post|page)$/', $tool_name ) ) {
			$id = $arguments['id'] ?? '';
			return array( 'type' => 'post', 'id' => (int) $id );
		}

		// Option writes
		if ( in_array( $tool_name, array( 'wp_update_option', 'wp_update_plugin_settings', 'wp_update_rate_limit' ), true ) ) {
			$key = $arguments['option'] ?? $arguments['option_key'] ?? '';
			return array( 'type' => 'option', 'id' => sanitize_key( (string) $key ) );
		}

		return array( 'type' => 'other', 'id' => '' );
	}

	/**
	 * Capture a state snapshot for a resource.
	 *
	 * @param array $resource Resource descriptor { type, id }.
	 * @return string|null JSON-encoded snapshot or null.
	 */
	private static function snapshot( $resource ) {
		if ( empty( $resource['id'] ) && 'option' !== $resource['type'] ) {
			return null;
		}

		switch ( $resource['type'] ) {
			case 'post':
				return self::snapshot_post( (int) $resource['id'] );

			case 'elementor':
				return self::snapshot_elementor( (int) $resource['id'] );

			case 'option':
				return self::snapshot_option( (string) $resource['id'] );

			default:
				return null;
		}
	}

	/**
	 * Snapshot a post's key fields.
	 *
	 * @param int $post_id Post ID.
	 * @return string|null JSON.
	 */
	private static function snapshot_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return null;
		}

		return wp_json_encode(
			array(
				'post_id'      => $post_id,
				'post_title'   => $post->post_title,
				'post_content' => $post->post_content,
				'post_status'  => $post->post_status,
				'post_name'    => $post->post_name,
				'post_excerpt' => $post->post_excerpt,
				'post_type'    => $post->post_type,
				'thumbnail_id' => (int) get_post_thumbnail_id( $post_id ),
			)
		);
	}

	/**
	 * Snapshot Elementor data for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string|null JSON.
	 */
	private static function snapshot_elementor( $post_id ) {
		if ( ! $post_id ) {
			return null;
		}

		return wp_json_encode(
			array(
				'post_id'        => $post_id,
				'elementor_data' => get_post_meta( $post_id, '_elementor_data', true ),
			)
		);
	}

	/**
	 * Snapshot a WordPress option value.
	 *
	 * @param string $option_key Option name.
	 * @return string|null JSON.
	 */
	private static function snapshot_option( $option_key ) {
		if ( ! $option_key ) {
			return null;
		}

		return wp_json_encode(
			array(
				'option_key' => $option_key,
				'value'      => get_option( $option_key ),
			)
		);
	}

	/**
	 * Apply a before-snapshot to restore state.
	 *
	 * @param string $resource_type Resource type.
	 * @param array  $snap          Decoded snapshot.
	 * @return true|WP_Error
	 */
	private static function apply_snapshot( $resource_type, $snap ) {
		switch ( $resource_type ) {
			case 'post':
				if ( empty( $snap['post_id'] ) ) {
					return new WP_Error( 'invalid_snap', __( 'Snapshot missing post_id.', 'mumega-mcp' ), array( 'status' => 500 ) );
				}

				$result = wp_update_post(
					array(
						'ID'           => (int) $snap['post_id'],
						'post_title'   => $snap['post_title'] ?? '',
						'post_content' => $snap['post_content'] ?? '',
						'post_status'  => $snap['post_status'] ?? 'publish',
						'post_name'    => $snap['post_name'] ?? '',
						'post_excerpt' => $snap['post_excerpt'] ?? '',
					),
					true
				);

				if ( is_wp_error( $result ) ) {
					return $result;
				}

				if ( isset( $snap['thumbnail_id'] ) ) {
					if ( $snap['thumbnail_id'] ) {
						set_post_thumbnail( (int) $snap['post_id'], (int) $snap['thumbnail_id'] );
					} else {
						delete_post_thumbnail( (int) $snap['post_id'] );
					}
				}

				return true;

			case 'elementor':
				if ( empty( $snap['post_id'] ) ) {
					return new WP_Error( 'invalid_snap', __( 'Snapshot missing post_id.', 'mumega-mcp' ), array( 'status' => 500 ) );
				}

				update_post_meta( (int) $snap['post_id'], '_elementor_data', $snap['elementor_data'] ?? '' );
				// Bust Elementor CSS cache so frontend reflects rollback.
				delete_post_meta( (int) $snap['post_id'], '_elementor_css' );

				return true;

			case 'option':
				if ( empty( $snap['option_key'] ) ) {
					return new WP_Error( 'invalid_snap', __( 'Snapshot missing option_key.', 'mumega-mcp' ), array( 'status' => 500 ) );
				}

				update_option( sanitize_key( $snap['option_key'] ), $snap['value'] ?? '' );
				return true;

			default:
				return new WP_Error( 'unsupported_type', __( 'Rollback not supported for this resource type.', 'mumega-mcp' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Insert a log row into the DB.
	 */
	private static function insert(
		$log_id, $tool_name, $category, $api_key_id, $arguments,
		$before_snap, $after_snap, $resource_type, $resource_id,
		$duration_ms, $success, $error_code, $rollback_supported
	) {
		global $wpdb;

		// Strip any api_key values from arguments before storing.
		$safe_args = $arguments;
		unset( $safe_args['api_key'], $safe_args['X-API-Key'] );

		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'log_id'              => (string) $log_id,
				'timestamp'           => current_time( 'mysql', true ),
				'tool_name'           => (string) $tool_name,
				'category'            => (string) $category,
				'api_key_id'          => (string) $api_key_id,
				'arguments'           => wp_json_encode( $safe_args ) ?: '',
				'before_snap'         => $before_snap,
				'after_snap'          => $after_snap,
				'resource_type'       => (string) $resource_type,
				'resource_id'         => (string) $resource_id,
				'duration_ms'         => (int) $duration_ms,
				'success'             => (int) $success,
				'error_code'          => (string) $error_code,
				'rollback_supported'  => (int) $rollback_supported,
				'rolled_back'         => 0,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d' )
		);
	}

	/**
	 * Get a raw DB row by log_id.
	 *
	 * @param string $log_id Log entry ID.
	 * @return array|null
	 */
	private static function get_entry( $log_id ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE;
		$log_id = sanitize_text_field( (string) $log_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE log_id = %s", $log_id ), ARRAY_A );

		return $row ?: null;
	}

	/**
	 * Strip snapshots from a public-facing entry (keep log + metadata, drop raw content).
	 *
	 * @param array $row DB row.
	 * @return array
	 */
	private static function public_entry( $row ) {
		if ( ! is_array( $row ) ) {
			return array();
		}

		return array(
			'log_id'             => $row['log_id'],
			'timestamp'          => $row['timestamp'],
			'tool_name'          => $row['tool_name'],
			'category'           => $row['category'],
			'api_key_id'         => $row['api_key_id'],
			'resource_type'      => $row['resource_type'],
			'resource_id'        => $row['resource_id'],
			'duration_ms'        => (int) $row['duration_ms'],
			'success'            => (bool) $row['success'],
			'error_code'         => $row['error_code'],
			'rollback_supported' => (bool) $row['rollback_supported'],
			'rolled_back'        => (bool) $row['rolled_back'],
			'rolled_back_at'     => $row['rolled_back_at'],
		);
	}

	/**
	 * Generate a log entry ID.
	 *
	 * @return string
	 */
	private static function generate_id() {
		return 'act_' . str_replace( '-', '', wp_generate_uuid4() );
	}
}
```

- [ ] **Step 2: Run a static analysis sanity check**

Read the file back and verify:
- No `return` statements missing in public methods
- All switch cases have `return` or `break`
- `self::$pending` is used consistently (begin sets, complete reads+unsets)
- The `prune()` method uses `$days` variable (not accidentally 0)

- [ ] **Step 3: Commit**

```bash
git add site-pilot-ai/includes/core/class-spai-action-log.php
git commit -m "feat(audit-log): implement Spai_Action_Log — snapshots, rollback, export, prune"
```

---

## Task 3: Wire action log into MCP dispatcher

**Files:**
- Modify: `site-pilot-ai/includes/api/class-spai-rest-mcp.php`

The `handle_tools_call()` method needs two new call sites: `Spai_Action_Log::begin()` just before `rest_do_request()` and `Spai_Action_Log::complete()` at each of the 3 exit points (error, execution_error, success).

- [ ] **Step 1: Read `handle_tools_call()` in class-spai-rest-mcp.php**

Read lines 768–1025 to understand the exact structure. There are four exit points:
1. Line ~808: `tool_not_found` — no change (tool failed validation, not executed)
2. Line ~824: `category_disabled` — no change
3. Line ~847: `scope_denied` — no change
4. Line ~882: `scope_denied` (capability) — no change
5. Line ~990: `execution_error` — AFTER rest_do_request, IS logged
6. Line ~1000: success path — AFTER rest_do_request, IS logged

And one line BEFORE `rest_do_request` (line ~944):
```php
$response = rest_do_request( $internal_request );
```

- [ ] **Step 2: Locate the api_key_id for the log**

In `handle_tools_call()`, `$this->get_current_api_key_record()` is called around line 838. That returns `$key_record` which has an `id` field. We need this for the log. Check the exact call and use `$key_record['id'] ?? ''` — but this is only called in the scope-check block. We need it at the before-begin point.

Look for `$key_record` — it's set at line ~838 as:
```php
$key_record = $this->get_current_api_key_record();
```

This call is already made before `rest_do_request`. So `$key_record` is in scope. Use `$key_record['id'] ?? ''` for the api_key_id.

Also get `$category` from `$this->get_all_tool_categories()[$tool_name] ?? ''`.

- [ ] **Step 3: Add begin() call before rest_do_request**

Find the exact line in `handle_tools_call()`:
```php
		// Dispatch internally
		$response = rest_do_request( $internal_request );
```

Insert before it:

```php
		// Capture before-state for audit log (no-op for non-write tools).
		$all_cats_for_log = $this->get_all_tool_categories();
		$log_category     = isset( $all_cats_for_log[ $tool_name ] ) ? $all_cats_for_log[ $tool_name ] : 'site';
		$log_api_key_id   = isset( $key_record['id'] ) ? (string) $key_record['id'] : '';
		$audit_log_id     = class_exists( 'Spai_Action_Log' )
			? Spai_Action_Log::begin( $tool_name, $arguments, $log_api_key_id, $log_category )
			: null;

		// Dispatch internally
		$response = rest_do_request( $internal_request );
```

Note: `$arguments` at this point has already been remapped (param_remap applied) and path-params substituted. That's fine — we want the final args that were actually sent upstream.

- [ ] **Step 4: Add complete() call at the two exit points after rest_do_request**

**Exit point A — execution_error:**

Find:
```php
			$this->fire_tool_called( $tool_name, $start, 'execution_error' );
			return $this->jsonrpc_error(
```

Change to:

```php
			if ( class_exists( 'Spai_Action_Log' ) ) {
				Spai_Action_Log::complete( $audit_log_id, (int) round( ( microtime( true ) - $start ) * 1000 ), false, 'execution_error' );
			}
			$this->fire_tool_called( $tool_name, $start, 'execution_error' );
			return $this->jsonrpc_error(
```

**Exit point B — success:**

Find:
```php
		// Return successful result.
		$this->fire_tool_called( $tool_name, $start, '' );
```

Change to:

```php
		// Return successful result.
		if ( class_exists( 'Spai_Action_Log' ) ) {
			Spai_Action_Log::complete( $audit_log_id, (int) round( ( microtime( true ) - $start ) * 1000 ), true, '' );
		}
		$this->fire_tool_called( $tool_name, $start, '' );
```

- [ ] **Step 5: Verify $key_record is in scope at the begin() call**

Read the function again from line 768. `$key_record` is set at line ~838 via `$this->get_current_api_key_record()`. The `begin()` call is inserted at line ~944 (before rest_do_request). Confirm `$key_record` is set unconditionally (not inside an if-branch that might skip it). If it IS inside an if-branch, add `$key_record = $key_record ?? null;` before the audit begin call to ensure it's always defined.

Looking at the code: `$key_record` is set at line 838:
```php
$key_record = $this->get_current_api_key_record();
if ( $key_record ) {
    ... scope check ...
}
```

`$key_record` is set unconditionally before the if-block. So it's in scope. Good.

- [ ] **Step 6: Commit**

```bash
git add site-pilot-ai/includes/api/class-spai-rest-mcp.php
git commit -m "feat(audit-log): wire begin/complete calls into MCP dispatcher"
```

---

## Task 4: REST endpoints for action log

**Files:**
- Create: `site-pilot-ai/includes/api/class-spai-rest-action-log.php`

New REST controller. Namespace: `site-pilot-ai/v1`. Endpoints:
- `GET /action-log` — list with pagination + filters
- `GET /action-log/{log_id}` — single entry
- `POST /action-log/{log_id}/rollback` — execute rollback
- `GET /action-log/export` — CSV download

- [ ] **Step 1: Create the file**

Full content of `site-pilot-ai/includes/api/class-spai-rest-action-log.php`:

```php
<?php
/**
 * REST endpoints for the AI action audit log.
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles /action-log/* REST routes.
 */
class Spai_Rest_Action_Log extends Spai_Rest_API {

	use Spai_API_Auth;

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'site-pilot-ai/v1',
			'/action-log',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'list_entries' ),
				'permission_callback' => array( $this, 'check_api_key' ),
			)
		);

		register_rest_route(
			'site-pilot-ai/v1',
			'/action-log/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_csv' ),
				'permission_callback' => array( $this, 'check_api_key' ),
			)
		);

		register_rest_route(
			'site-pilot-ai/v1',
			'/action-log/(?P<log_id>[a-z0-9_]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_entry' ),
				'permission_callback' => array( $this, 'check_api_key' ),
			)
		);

		register_rest_route(
			'site-pilot-ai/v1',
			'/action-log/(?P<log_id>[a-z0-9_]+)/rollback',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rollback' ),
				'permission_callback' => array( $this, 'check_api_key' ),
			)
		);
	}

	/**
	 * GET /action-log
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_entries( $request ) {
		if ( ! class_exists( 'Spai_Action_Log' ) ) {
			return new WP_REST_Response( array( 'entries' => array(), 'total' => 0 ), 200 );
		}

		$result = Spai_Action_Log::list_entries(
			array(
				'limit'         => (int) $request->get_param( 'limit' ) ?: 50,
				'offset'        => (int) $request->get_param( 'offset' ) ?: 0,
				'tool_name'     => sanitize_key( (string) ( $request->get_param( 'tool_name' ) ?: '' ) ),
				'success'       => $request->get_param( 'success' ) !== null ? (int) $request->get_param( 'success' ) : '',
				'resource_type' => sanitize_key( (string) ( $request->get_param( 'resource_type' ) ?: '' ) ),
				'resource_id'   => sanitize_text_field( (string) ( $request->get_param( 'resource_id' ) ?: '' ) ),
			)
		);

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * GET /action-log/{log_id}
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_entry( $request ) {
		if ( ! class_exists( 'Spai_Action_Log' ) ) {
			return new WP_Error( 'not_found', __( 'Log entry not found.', 'mumega-mcp' ), array( 'status' => 404 ) );
		}

		$log_id = sanitize_text_field( (string) $request->get_param( 'log_id' ) );
		$entry  = Spai_Action_Log::get_entry_public( $log_id );

		if ( ! $entry ) {
			return new WP_Error( 'not_found', __( 'Log entry not found.', 'mumega-mcp' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $entry, 200 );
	}

	/**
	 * POST /action-log/{log_id}/rollback
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rollback( $request ) {
		if ( ! class_exists( 'Spai_Action_Log' ) ) {
			return new WP_Error( 'unavailable', __( 'Action log not available.', 'mumega-mcp' ), array( 'status' => 503 ) );
		}

		$log_id = sanitize_text_field( (string) $request->get_param( 'log_id' ) );
		$result = Spai_Action_Log::rollback( $log_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array( 'status' => 'rolled_back', 'log_id' => $log_id ), 200 );
	}

	/**
	 * GET /action-log/export — streams CSV.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function export_csv( $request ) {
		if ( ! class_exists( 'Spai_Action_Log' ) ) {
			return new WP_REST_Response( '', 200 );
		}

		$csv = Spai_Action_Log::export_csv(
			array(
				'limit'         => (int) $request->get_param( 'limit' ) ?: 1000,
				'tool_name'     => sanitize_key( (string) ( $request->get_param( 'tool_name' ) ?: '' ) ),
				'resource_type' => sanitize_key( (string) ( $request->get_param( 'resource_type' ) ?: '' ) ),
			)
		);

		// Return as text/csv — WP REST framework will set JSON content-type,
		// but the endpoint is documented as text/csv for direct browser use.
		return new WP_REST_Response( $csv, 200 );
	}
}
```

- [ ] **Step 2: Verify the class extends Spai_Rest_API**

Read `class-spai-rest-api.php` (the base controller) to confirm it exists and that `Spai_API_Auth` trait provides `check_api_key()`. Spot-check that the file header and class structure match the pattern of another REST controller like `class-spai-rest-approvals.php`.

- [ ] **Step 3: Commit**

```bash
git add site-pilot-ai/includes/api/class-spai-rest-action-log.php
git commit -m "feat(audit-log): add REST endpoints — list, get, rollback, CSV export"
```

---

## Task 5: Plugin bootstrap, settings, version bump

**Files:**
- Modify: `site-pilot-ai/site-pilot-ai.php` — require new classes, register routes, wire prune cron, bump to 2.8.46
- Modify: `site-pilot-ai/includes/admin/class-spai-settings.php` — add retention_days setting
- Modify: `site-pilot-ai/version.json`
- Modify: `site-pilot-ai/readme.txt`

- [ ] **Step 1: Add class requires in site-pilot-ai.php**

After line `require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-approvals.php';` add:
```php
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-action-log.php';
```

After line `require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-approvals.php';` add:
```php
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-action-log.php';
```

- [ ] **Step 2: Register REST routes in site-pilot-ai.php**

Find the section where other REST controllers are registered. It will look like:
```php
add_action( 'rest_api_init', function() { ... } );
```
or a `Spai_Loader` hook. Search for `Spai_Rest_Approvals` or `register_rest_route` in the bootstrap file to find the pattern.

Add registration for `Spai_Rest_Action_Log` following the same pattern as `Spai_Rest_Approvals`.

If registration is done via a loader hook:
```php
add_action( 'rest_api_init', function() {
    ( new Spai_Rest_Action_Log() )->register_routes();
} );
```

- [ ] **Step 3: Wire retention cron in site-pilot-ai.php**

After the `add_action( 'spai_tool_called', ...)` line, add:

```php
	// Schedule daily prune of old action log entries.
	add_action( 'spai_action_log_daily_prune', function() {
		if ( class_exists( 'Spai_Action_Log' ) ) {
			Spai_Action_Log::prune();
		}
	} );
	if ( ! wp_next_scheduled( 'spai_action_log_daily_prune' ) ) {
		wp_schedule_event( time(), 'daily', 'spai_action_log_daily_prune' );
	}
```

- [ ] **Step 4: Bump version to 2.8.46**

In `site-pilot-ai.php`:
- Change header: `Version: 2.8.45` → `Version: 2.8.46`
- Change constant: `define( 'SPAI_VERSION', '2.8.45' );` → `define( 'SPAI_VERSION', '2.8.46' );`

- [ ] **Step 5: Add retention setting to class-spai-settings.php**

Find where other settings are registered (`register_setting` calls). Add:

```php
register_setting(
    'spai_settings_group',
    'spai_action_log_retention_days',
    array(
        'type'              => 'integer',
        'sanitize_callback' => function( $val ) {
            $v = (int) $val;
            return $v > 0 ? $v : 90;
        },
        'default'           => 90,
    )
);
```

Find where the settings UI is rendered (look for an `<input` for another integer setting). Add after it:

```php
<tr>
    <th scope="row"><?php esc_html_e( 'Action log retention (days)', 'mumega-mcp' ); ?></th>
    <td>
        <input type="number" min="1" max="3650"
               name="spai_action_log_retention_days"
               value="<?php echo esc_attr( (string) get_option( 'spai_action_log_retention_days', 90 ) ); ?>" />
        <p class="description"><?php esc_html_e( 'Audit log entries older than this many days are automatically deleted. Default: 90. Minimum: 1.', 'mumega-mcp' ); ?></p>
    </td>
</tr>
```

- [ ] **Step 6: Update version.json**

Change `"version": "2.8.45"` to `"version": "2.8.46"` and prepend to changelog:

```json
"changelog": "<h4>2.8.46<\/h4><ul><li>New: AI action audit log — every write-tool call logged to DB with before\/after state snapshots (EU AI Act compliance).<\/li><li>New: One-click rollback for post\/page updates and Elementor writes in Control Room.<\/li><li>New: CSV export of action log for compliance reporting.<\/li><li>New: Configurable log retention (default 90 days, daily auto-prune).<\/li><\/ul><h4>2.8.45<\/h4>..."
```

- [ ] **Step 7: Update readme.txt**

Change `Stable tag: 2.8.45` → `Stable tag: 2.8.46`

Add changelog entry under `== Changelog ==`:
```
= 2.8.46 =
* New: AI action audit log — every write-tool call logged to DB with before/after state snapshots (EU AI Act compliance).
* New: One-click rollback for post/page updates and Elementor writes in Control Room.
* New: CSV export of action log for compliance reporting.
* New: Configurable log retention (default 90 days, daily auto-prune).
```

- [ ] **Step 8: Commit**

```bash
git add site-pilot-ai/site-pilot-ai.php \
        site-pilot-ai/includes/admin/class-spai-settings.php \
        site-pilot-ai/version.json \
        site-pilot-ai/readme.txt
git commit -m "feat(audit-log): wire bootstrap, retention setting, bump to v2.8.46"
```

---

## Task 6: Control Room UI — action log section

**Files:**
- Modify: `site-pilot-ai/includes/admin/class-spai-admin.php` — add action log to `get_control_room_data()` and handle rollback POST action
- Modify: `site-pilot-ai/admin/partials/spai-control-room-display.php` — add action log section

- [ ] **Step 1: Add action log data to get_control_room_data()**

In `class-spai-admin.php`, find `public function get_control_room_data()`.

At the end of the `$data = array(...)` block (before `return $data;`), add:

```php
			'action_log'         => class_exists( 'Spai_Action_Log' )
				? Spai_Action_Log::list_entries( array( 'limit' => 20 ) )
				: array( 'entries' => array(), 'total' => 0 ),
```

- [ ] **Step 2: Handle rollback POST action in handle_control_room_actions()**

In `handle_control_room_actions()`, in the `else` branch that handles approval actions (around line 440), add a case for rollback:

Find the existing pattern:
```php
		} else {
			$approval_id = sanitize_key( wp_unslash( $_POST['approval_id'] ?? '' ) );
			$result      = $this->handle_control_room_approval_action( $action, $approval_id );
		}
```

Replace with:
```php
		} elseif ( 'rollback_action_log' === $action ) {
			$log_id = sanitize_text_field( wp_unslash( $_POST['action_log_id'] ?? '' ) );
			$result = class_exists( 'Spai_Action_Log' ) ? Spai_Action_Log::rollback( $log_id ) : new WP_Error( 'unavailable', 'Action log not available.' );
		} else {
			$approval_id = sanitize_key( wp_unslash( $_POST['approval_id'] ?? '' ) );
			$result      = $this->handle_control_room_approval_action( $action, $approval_id );
		}
```

- [ ] **Step 3: Add action log section to spai-control-room-display.php**

Read the template to understand the section pattern. Find the last section (likely the event log section). After it, add a new section:

```php
	<!-- ============================================================ -->
	<!-- AI Action Log -->
	<!-- ============================================================ -->
	<?php
	$action_log_data    = isset( $control_room['action_log'] ) && is_array( $control_room['action_log'] ) ? $control_room['action_log'] : array( 'entries' => array(), 'total' => 0 );
	$action_log_entries = is_array( $action_log_data['entries'] ?? null ) ? $action_log_data['entries'] : array();
	$action_log_total   = (int) ( $action_log_data['total'] ?? 0 );
	?>
	<div class="spai-card" style="margin-top:2rem">
		<div class="spai-card-header">
			<h2 class="spai-card-title">
				<span class="dashicons dashicons-list-view"></span>
				<?php esc_html_e( 'AI Action Log', 'mumega-mcp' ); ?>
			</h2>
			<div class="spai-card-actions">
				<a href="<?php echo esc_url( rest_url( 'site-pilot-ai/v1/action-log/export' ) . '?_wpnonce=' . wp_create_nonce( 'wp_rest' ) ); ?>"
				   class="button button-small"
				   target="_blank">
					<?php esc_html_e( 'Export CSV', 'mumega-mcp' ); ?>
				</a>
			</div>
		</div>

		<?php if ( empty( $action_log_entries ) ) : ?>
			<p class="spai-empty-state"><?php esc_html_e( 'No write-tool actions logged yet. Actions appear here the first time an AI agent calls a write tool.', 'mumega-mcp' ); ?></p>
		<?php else : ?>
			<p class="description" style="padding:0 1rem">
				<?php
				printf(
					/* translators: 1: number shown, 2: total */
					esc_html__( 'Showing %1$d of %2$d logged actions (most recent first).', 'mumega-mcp' ),
					(int) count( $action_log_entries ),
					$action_log_total
				);
				?>
			</p>
			<table class="wp-list-table widefat fixed striped" style="margin:0">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Tool', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Resource', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Time', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Duration', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Result', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Action', 'mumega-mcp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $action_log_entries as $entry ) : ?>
						<?php
						$is_success          = ! empty( $entry['success'] );
						$rollback_supported  = ! empty( $entry['rollback_supported'] );
						$already_rolled_back = ! empty( $entry['rolled_back'] );
						$log_id              = esc_attr( (string) ( $entry['log_id'] ?? '' ) );
						$resource_label      = esc_html(
							( $entry['resource_type'] ?? '' )
							? ( $entry['resource_type'] . ':' . $entry['resource_id'] )
							: '—'
						);
						$ts = $entry['timestamp'] ?? '';
						?>
						<tr>
							<td>
								<code><?php echo esc_html( (string) ( $entry['tool_name'] ?? '' ) ); ?></code>
							</td>
							<td><?php echo $resource_label; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td>
								<abbr title="<?php echo esc_attr( $ts ); ?>">
									<?php echo esc_html( $ts ? human_time_diff( (int) strtotime( $ts ) ) . ' ago' : '—' ); ?>
								</abbr>
							</td>
							<td><?php echo esc_html( isset( $entry['duration_ms'] ) ? $entry['duration_ms'] . ' ms' : '—' ); ?></td>
							<td>
								<?php if ( $is_success ) : ?>
									<span class="spai-badge is-good"><?php esc_html_e( 'OK', 'mumega-mcp' ); ?></span>
								<?php else : ?>
									<span class="spai-badge is-critical"><?php echo esc_html( (string) ( $entry['error_code'] ?: 'error' ) ); ?></span>
								<?php endif; ?>
								<?php if ( $already_rolled_back ) : ?>
									<span class="spai-badge is-info"><?php esc_html_e( 'Rolled back', 'mumega-mcp' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $rollback_supported && ! $already_rolled_back && $is_success ) : ?>
									<form method="post">
										<?php wp_nonce_field( 'spai_control_room_actions', 'spai_control_room_nonce' ); ?>
										<input type="hidden" name="spai_control_room_action" value="rollback_action_log" />
										<input type="hidden" name="action_log_id" value="<?php echo $log_id; ?>" />
										<button type="submit" class="button button-small button-link-delete"
											onclick="return confirm('<?php esc_attr_e( 'Roll back this action? This will restore the before-state and cannot be undone.', 'mumega-mcp' ); ?>')">
											<?php esc_html_e( 'Rollback', 'mumega-mcp' ); ?>
										</button>
									</form>
								<?php else : ?>
									<span class="spai-muted">—</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
```

- [ ] **Step 4: Verify template variables**

Read `get_control_room_data()` to confirm `action_log` key is now returned and will be available in the template as `$control_room['action_log']`.

- [ ] **Step 5: Commit**

```bash
git add site-pilot-ai/includes/admin/class-spai-admin.php \
        site-pilot-ai/admin/partials/spai-control-room-display.php
git commit -m "feat(audit-log): add action log section to Control Room with rollback UI"
```

---

## Smoke Test (manual — no test file for this feature)

After all tasks are implemented, verify in Docker WP:

1. Generate an API key (see CLAUDE.md).
2. Call a write tool:
   ```bash
   curl -s -X POST http://localhost:8080/wp-json/site-pilot-ai/v1/mcp \
     -H "X-API-Key: $KEY" \
     -H "Content-Type: application/json" \
     -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"wp_update_page","arguments":{"id":1,"content":"<p>Test audit log</p>"}}}'
   ```
3. List the action log:
   ```bash
   curl -s http://localhost:8080/wp-json/site-pilot-ai/v1/action-log -H "X-API-Key: $KEY" | jq '.entries[0]'
   ```
4. Verify `rollback_supported: true` for a post write.
5. Rollback:
   ```bash
   LOG_ID=$(curl -s http://localhost:8080/wp-json/site-pilot-ai/v1/action-log -H "X-API-Key: $KEY" | jq -r '.entries[0].log_id')
   curl -s -X POST "http://localhost:8080/wp-json/site-pilot-ai/v1/action-log/$LOG_ID/rollback" -H "X-API-Key: $KEY" | jq
   ```
6. Verify the page content was restored.
7. Export CSV:
   ```bash
   curl -s "http://localhost:8080/wp-json/site-pilot-ai/v1/action-log/export" -H "X-API-Key: $KEY"
   ```
8. Visit WP Admin > Control Room > AI Action Log section — verify entries appear.
9. Click Rollback on an entry — confirm confirmation dialog and action.
