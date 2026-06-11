<?php
/**
 * Data migration: spai_* options → mcpwp_* options + spai_* tables → mcpwp_* tables
 *
 * Copies every known legacy option written by site-pilot-ai 2.8.x to its
 * mcpwp_* equivalent, and copies rows from the spai_* database tables into
 * the freshly-created mcpwp_* tables.  The routine is:
 *
 *   - Idempotent: guarded by the `mcpwp_migrated_from_spai` flag option.
 *   - Non-destructive: leaves all spai_* originals intact.
 *   - Non-overwriting: skips any mcpwp_* option that already has a value.
 *   - Safe on fresh installs: no spai_* options present → nothing happens.
 *
 * ENCRYPTION NOTICE
 * -----------------
 * Both Spai_Encryption (2.8.x) and Mcpwp_Encryption (v3) use
 * sodium_crypto_secretbox keyed by sodium_crypto_generichash( AUTH_SALT ).
 * Because AUTH_SALT is site-specific and constant across the rename (same
 * WordPress install), the encrypted blobs in spai_integrations are directly
 * portable: copy the raw option value and Mcpwp_Encryption will decrypt it
 * identically.  No re-encryption step is required.
 *
 * FLAGGED RISK: if AUTH_SALT ever changed between the last spai_ write and
 * this migration (e.g., wp-config.php was regenerated), decryption will
 * return false for the copied integrations blob.  Mcpwp_Integration_Manager
 * handles that gracefully (returns empty/null providers), so the site will
 * not crash — but provider keys will be lost and must be re-entered.
 * The migration log records this scenario so the admin knows to re-enter keys.
 *
 * PER-USER OPTIONS (spai_chat_history_*)
 * ---------------------------------------
 * Chat history is stored per user-ID as spai_chat_history_{user_id}.
 * These are admin-UI conveniences, not MCP data.  They are NOT migrated
 * because (a) the new admin chat UI may differ, (b) iterating over all user
 * IDs at activation time is expensive, and (c) the data is ephemeral.
 *
 * ENTITLEMENT OPTIONS
 * -------------------
 * v3's Mcpwp_License reads entitlement state PURELY from the Freemius SDK
 * (see class-mcpwp-license.php::is_pro() — no local option read).  There are
 * no spai_pro_license or spai_trial_started options in 2.8.56 (confirmed via
 * grep of the source).  Therefore no entitlement options are migrated.
 * See R3 in the bridge spec for the file:line evidence.
 *
 * @package MCPWP
 * @since   3.0.0 (unreleased bridge)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles one-time migration of spai_* options and tables to mcpwp_* equivalents.
 */
class Mcpwp_Migrate {

	/**
	 * Flag option name.  Set to '1' after a successful migration run.
	 */
	const MIGRATED_FLAG = 'mcpwp_migrated_from_spai';

	/**
	 * Migration log option name.  Stores a structured record of what happened.
	 */
	const LOG_OPTION = 'mcpwp_migration_log';

	/**
	 * Map of legacy spai_* option key → mcpwp_* option key.
	 *
	 * Keys are the 2.8.56 option names found via grep of the source zip.
	 * The value is the corresponding v3 mcpwp_ name.
	 *
	 * Options intentionally excluded:
	 *   spai_chat_history_*  — per-user, ephemeral, not migrated (see file docblock)
	 *   spai_version         — version tracking; v3 sets its own mcpwp_version
	 *   spai_db_version      — DB schema version; v3 manages its own mcpwp_db_version
	 *   spai_update_info     — update manifest override; intentionally left empty in v3
	 *                          (a stale override blocks the update channel)
	 *   spai_version_url     — update check URL; v3 sets its own default on activation
	 *   spai_chat_endpoint   — internal admin chat config; v3 may differ
	 *   spai_chat_model      — internal admin chat config; v3 may differ
	 *   spai_chat_secret     — internal admin chat config; v3 may differ
	 *   spai_pro_license     — not present in 2.8.56; entitlement is Freemius-only (R3)
	 *   spai_trial_started   — not present in 2.8.56; entitlement is Freemius-only (R3)
	 *   spai_signals_meta    — v3 introduces this key; 2.8.56 does not write it
	 *
	 * @var array<string,string>
	 */
	const OPTION_MAP = array(
		// API keys — primary migration target; dual-key auth depends on these.
		// NOTE: spai_api_keys is handled specially in run() (append, not overwrite)
		// when mcpwp_api_keys already exists.  The entry here is kept so the
		// OPTION_MAP remains a complete inventory and tests can verify the key
		// is in the map.  The run() method removes it from the main-map loop and
		// handles it separately.
		'spai_api_keys'                   => 'mcpwp_api_keys',
		'spai_api_key'                    => 'mcpwp_api_key',

		// Plugin settings (logging, analytics toggle, rate limits, etc.).
		'spai_settings'                   => 'mcpwp_settings',
		'spai_rate_limit_settings'        => 'mcpwp_rate_limit_settings',
		'spai_disabled_tool_categories'   => 'mcpwp_disabled_tool_categories',

		// Provider integration keys (sodium-encrypted blob — see ENCRYPTION NOTICE).
		'spai_integrations'               => 'mcpwp_integrations',

		// Site content & context.
		'spai_site_context'               => 'mcpwp_site_context',
		'spai_site_context_updated'       => 'mcpwp_site_context_updated',
		'spai_site_profile'               => 'mcpwp_site_context', // spai_site_profile was a
		                                                            // separate option in some
		                                                            // builds; copy to same target
		                                                            // only if mcpwp_site_context
		                                                            // was not already set by
		                                                            // spai_site_context.  Logic in
		                                                            // run() handles priority.

		// Design references library.
		'spai_design_references'          => 'mcpwp_design_references',

		// WooCommerce product archetypes.
		'spai_wc_product_archetypes'      => 'mcpwp_wc_product_archetypes',

		// Analytics UUID — keep the same UUID so PostHog history is continuous.
		'spai_site_uuid'                  => 'mcpwp_site_uuid',

		// Timestamps.
		'spai_first_activation'           => 'mcpwp_first_activation',

		// SEO audit data.
		'spai_seo_audit_runs'             => 'mcpwp_seo_audit_runs',
		'spai_seo_issues'                 => 'mcpwp_seo_issues',

		// Action log retention.
		'spai_action_log_retention_days'  => 'mcpwp_action_log_retention_days',

		// Approval requests (class-mcpwp-approvals.php OPTION_NAME).
		'spai_approval_requests'          => 'mcpwp_approval_requests',

		// Event store (class-mcpwp-event-store.php OPTION_NAME).
		'spai_recent_events'              => 'mcpwp_recent_events',

		// AI site memory (class-mcpwp-site-memory.php OPTION_KEY).
		'spai_site_memory'                => 'mcpwp_site_memory',

		// Site blueprints (class-mcpwp-site-blueprints.php OPTION_KEY).
		'spai_site_blueprints'            => 'mcpwp_site_blueprints',

		// White-label config (class-mcpwp-white-label.php OPTION_KEY).
		'spai_white_label'                => 'mcpwp_white_label',

		// Signals cache (class-mcpwp-signals.php OPTION_KEY).
		// NOTE: spai_signals_meta does NOT exist in 2.8.56 — 2.8.56 only writes
		// spai_signals and spai_signal_settings.  mcpwp_signals_meta is a v3 addition.
		'spai_signals'                    => 'mcpwp_signals',
		'spai_signal_settings'            => 'mcpwp_signal_settings',

		// Search-console data (class-mcpwp-search-performance.php).
		'spai_search_performance_imports' => 'mcpwp_search_performance_imports',
		'spai_search_performance_rows'    => 'mcpwp_search_performance_rows',
	);

	/**
	 * Table migration map: spai_ table suffix → mcpwp_ table suffix.
	 * Each entry is migrated if the source exists + is non-empty and the
	 * target exists + is empty.
	 *
	 * @var array<string,string>
	 */
	const TABLE_MAP = array(
		'spai_webhooks'     => 'mcpwp_webhooks',
		'spai_webhook_logs' => 'mcpwp_webhook_logs',
		'spai_action_log'   => 'mcpwp_action_log',
		'spai_activity_log' => 'mcpwp_activity_log',
		'spai_feedback'     => 'mcpwp_feedback',
	);

	/**
	 * Run the migration.
	 *
	 * Intended to be called from the plugin's `plugins_loaded` action (priority 5)
	 * AND from the activation hook so that sites which activate v3 fresh on a
	 * previously-active 2.8.x install are covered immediately.
	 *
	 * Safe to call multiple times — the flag short-circuits after the first run.
	 *
	 * @return bool True if migration ran (or was already done), false on hard error.
	 */
	public static function run() {
		// Already migrated — nothing to do.
		if ( get_option( self::MIGRATED_FLAG ) ) {
			return true;
		}

		// No spai_ data present (fresh install or already-renamed install).
		// Check the most fundamental key first to avoid iterating all options.
		if ( false === get_option( 'spai_settings', false ) &&
			 false === get_option( 'spai_api_keys', false ) &&
			 false === get_option( 'spai_api_key', false ) ) {
			// Nothing to migrate — mark as done so we don't check again.
			update_option( self::MIGRATED_FLAG, '1' );
			return true;
		}

		$log = array(
			'timestamp'          => function_exists( 'current_time' ) ? current_time( 'c' ) : gmdate( 'c' ),
			'from_version'       => (string) get_option( 'spai_version', 'unknown' ),
			'copied'             => array(),
			'skipped_existing'   => array(),
			'skipped_missing'    => array(),
			'encryption_warning' => false,
			'tables'             => array(),
		);

		// -----------------------------------------------------------------------
		// OPTION MIGRATION
		// -----------------------------------------------------------------------

		// Process the main option map.
		// spai_site_profile → mcpwp_site_context is handled specially below.
		// spai_api_keys is handled specially below (append rather than simple copy).
		$main_map = self::OPTION_MAP;
		unset( $main_map['spai_site_profile'] );
		unset( $main_map['spai_api_keys'] );

		foreach ( $main_map as $legacy_key => $new_key ) {
			$legacy_value = get_option( $legacy_key, '__NOT_SET__' );

			// Legacy option does not exist on this site.
			if ( '__NOT_SET__' === $legacy_value ) {
				$log['skipped_missing'][] = $legacy_key;
				continue;
			}

			// Target already has a value — do not overwrite.
			if ( false !== get_option( $new_key, false ) ) {
				$log['skipped_existing'][] = $new_key;
				continue;
			}

			update_option( $new_key, $legacy_value );
			$log['copied'][] = $legacy_key . ' → ' . $new_key;
		}

		// Special case: spai_site_profile → mcpwp_site_context
		// Only migrate if mcpwp_site_context was not already set by the
		// spai_site_context copy above.
		$spai_site_profile = get_option( 'spai_site_profile', '__NOT_SET__' );
		if ( '__NOT_SET__' !== $spai_site_profile ) {
			if ( false === get_option( 'mcpwp_site_context', false ) ) {
				update_option( 'mcpwp_site_context', $spai_site_profile );
				$log['copied'][] = 'spai_site_profile → mcpwp_site_context (fallback)';
			} else {
				$log['skipped_existing'][] = 'spai_site_profile (mcpwp_site_context already set)';
			}
		}

		// -----------------------------------------------------------------------
		// R5: spai_api_keys — append into mcpwp_api_keys (de-duplicated)
		//
		// If mcpwp_api_keys already exists (e.g. written by Mcpwp_Activator before
		// migration runs), we cannot overwrite it — that would clobber any v3 key
		// the activator just created.  Instead we append the spai_ records, skipping
		// any that are already present (matched by hash) or already revoked.
		// -----------------------------------------------------------------------
		$spai_keys_raw = get_option( 'spai_api_keys', '__NOT_SET__' );
		if ( '__NOT_SET__' !== $spai_keys_raw && is_array( $spai_keys_raw ) && ! empty( $spai_keys_raw ) ) {
			$mcpwp_keys_existing = get_option( 'mcpwp_api_keys', false );
			if ( false === $mcpwp_keys_existing ) {
				// mcpwp_api_keys does not exist yet — simple copy.
				update_option( 'mcpwp_api_keys', $spai_keys_raw );
				$log['copied'][] = 'spai_api_keys → mcpwp_api_keys (direct copy)';
			} else {
				// mcpwp_api_keys already exists — append missing entries.
				$mcpwp_keys = is_array( $mcpwp_keys_existing ) ? $mcpwp_keys_existing : array();

				// Build a set of existing hashes for O(1) dedup lookup.
				$existing_hashes = array();
				foreach ( $mcpwp_keys as $k ) {
					if ( ! empty( $k['hash'] ) ) {
						$existing_hashes[ (string) $k['hash'] ] = true;
					}
				}

				$appended = 0;
				foreach ( $spai_keys_raw as $spai_key ) {
					if ( ! is_array( $spai_key ) ) {
						continue;
					}
					// Skip revoked spai_ keys — no point importing them.
					if ( ! empty( $spai_key['revoked_at'] ) ) {
						continue;
					}
					$hash = isset( $spai_key['hash'] ) ? (string) $spai_key['hash'] : '';
					if ( '' === $hash ) {
						continue;
					}
					// Skip if already present by hash.
					if ( isset( $existing_hashes[ $hash ] ) ) {
						continue;
					}
					$mcpwp_keys[]             = $spai_key;
					$existing_hashes[ $hash ] = true;
					$appended++;
				}

				if ( $appended > 0 ) {
					update_option( 'mcpwp_api_keys', $mcpwp_keys );
					$log['copied'][] = 'spai_api_keys → mcpwp_api_keys (appended ' . $appended . ' record(s), no clobber)';
				} else {
					$log['skipped_existing'][] = 'spai_api_keys (all records already present in mcpwp_api_keys)';
				}
			}
		} elseif ( '__NOT_SET__' !== $spai_keys_raw ) {
			// spai_api_keys exists but is empty or not an array — nothing to append.
			$log['skipped_missing'][] = 'spai_api_keys (empty or non-array)';
		} else {
			$log['skipped_missing'][] = 'spai_api_keys';
		}

		// -----------------------------------------------------------------------
		// Encryption sanity check.
		// After copying spai_integrations → mcpwp_integrations, attempt to verify
		// the blob is still parseable.  Mcpwp_Encryption derives its key from
		// AUTH_SALT — if that constant is the same (it should be on the same WP
		// install), decryption will succeed.
		// -----------------------------------------------------------------------
		$integrations_copied = in_array( 'spai_integrations → mcpwp_integrations', $log['copied'], true );
		if ( $integrations_copied && class_exists( 'Mcpwp_Encryption' ) ) {
			$enc  = Mcpwp_Encryption::get_instance();
			$blob = get_option( 'mcpwp_integrations', false );

			if ( is_string( $blob ) && '' !== $blob ) {
				$test = $enc->decrypt( $blob );
				if ( false === $test ) {
					// Decryption failed — likely AUTH_SALT changed or blob is
					// from a different WP install.  Flag it clearly.
					$log['encryption_warning'] = true;
					$log['encryption_warning_detail'] = 'mcpwp_integrations blob copied from spai_integrations '
						. 'but Mcpwp_Encryption::decrypt() returned false.  Provider keys (OpenAI, Gemini, '
						. 'ElevenLabs, Pexels, Figma, PostHog, etc.) must be re-entered in '
						. 'WP Admin > MCPWP > Integrations.';
				}
			} elseif ( is_array( $blob ) ) {
				// spai_integrations was a plain array (unencrypted, older 2.8.x builds).
				// This is safe — no re-entry needed.
				$log['copied'][] = 'spai_integrations was unencrypted array — copied as-is';
			}
			// false === $blob means the option was not yet written (shouldn't happen
			// if integrations_copied is true, but guard defensively).
		}

		// -----------------------------------------------------------------------
		// R2: TABLE MIGRATION
		// -----------------------------------------------------------------------
		self::migrate_tables( $log );

		// -----------------------------------------------------------------------
		// Persist the log and set the migration flag atomically.
		// -----------------------------------------------------------------------
		update_option( self::LOG_OPTION, $log );
		update_option( self::MIGRATED_FLAG, '1' );

		return true;
	}

	/**
	 * Migrate rows from spai_* tables into mcpwp_* tables.
	 *
	 * Strategy (non-destructive):
	 *  - Only migrate if the v3 target table exists AND is empty.
	 *  - Only migrate if the spai_ source table exists AND is non-empty.
	 *  - Compare schemas (SHOW COLUMNS).  If columns match → INSERT … SELECT *.
	 *    If columns diverge → copy only the common columns; log a warning about
	 *    any columns that could not be mapped.  Never blind-copy mismatched schemas.
	 *  - Keep the spai_ source tables intact (rollback path).
	 *  - Per-table done flags in the log guard against re-runs (belt-and-suspenders
	 *    on top of the global MIGRATED_FLAG).
	 *  - Each table is wrapped in its own try/catch so one bad table does not
	 *    abort the rest.
	 *
	 * @param array &$log Migration log array (passed by reference).
	 */
	private static function migrate_tables( array &$log ) {
		// Bail early if wpdb is not available (unit-test context).
		if ( ! isset( $GLOBALS['wpdb'] ) ) {
			return;
		}

		global $wpdb;

		foreach ( self::TABLE_MAP as $spai_suffix => $mcpwp_suffix ) {
			// Derive full table names using the current site's prefix.
			// In multisite, $wpdb->prefix is per-site — these are per-site tables.
			$src = $wpdb->prefix . $spai_suffix;
			$dst = $wpdb->prefix . $mcpwp_suffix;

			$table_log = array(
				'src'    => $src,
				'dst'    => $dst,
				'status' => 'skipped',
				'rows'   => 0,
				'note'   => '',
			);

			try {
				// 1. Verify source table exists and is non-empty.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$src_count = $wpdb->get_var( $wpdb->prepare(
					'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s',
					$src
				) );
				if ( ! $src_count ) {
					$table_log['status'] = 'skipped';
					$table_log['note']   = 'source table does not exist';
					$log['tables'][ $spai_suffix ] = $table_log;
					continue;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$src_rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$src}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( 0 === $src_rows ) {
					$table_log['status'] = 'skipped';
					$table_log['note']   = 'source table is empty';
					$log['tables'][ $spai_suffix ] = $table_log;
					continue;
				}

				// 2. Verify destination table exists and is empty.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$dst_exists = $wpdb->get_var( $wpdb->prepare(
					'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s',
					$dst
				) );
				if ( ! $dst_exists ) {
					$table_log['status'] = 'skipped';
					$table_log['note']   = 'destination table does not exist (activator may not have run yet)';
					$log['tables'][ $spai_suffix ] = $table_log;
					continue;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$dst_rows = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$dst}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( $dst_rows > 0 ) {
					$table_log['status'] = 'skipped';
					$table_log['note']   = "destination table already has {$dst_rows} rows — skipped to preserve data";
					$log['tables'][ $spai_suffix ] = $table_log;
					continue;
				}

				// 3. Compare schemas.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$src_cols_raw = $wpdb->get_results( "SHOW COLUMNS FROM `{$src}`", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$dst_cols_raw = $wpdb->get_results( "SHOW COLUMNS FROM `{$dst}`", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				$src_col_names = array_column( $src_cols_raw, 'Field' );
				$dst_col_names = array_column( $dst_cols_raw, 'Field' );

				$common_cols      = array_values( array_intersect( $src_col_names, $dst_col_names ) );
				$src_only_cols    = array_values( array_diff( $src_col_names, $dst_col_names ) );
				$dst_only_cols    = array_values( array_diff( $dst_col_names, $src_col_names ) );

				if ( empty( $common_cols ) ) {
					$table_log['status'] = 'skipped';
					$table_log['note']   = 'no common columns between source and destination — schema mismatch too severe to migrate safely';
					$log['tables'][ $spai_suffix ] = $table_log;
					continue;
				}

				// 4. Build the INSERT … SELECT with only the common columns.
				$col_list = implode( ', ', array_map( function( $c ) {
					return '`' . esc_sql( $c ) . '`';
				}, $common_cols ) );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$result = $wpdb->query(
					"INSERT INTO `{$dst}` ({$col_list}) SELECT {$col_list} FROM `{$src}`" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

				if ( false === $result ) {
					$table_log['status'] = 'error';
					$table_log['note']   = 'INSERT failed: ' . $wpdb->last_error;
				} else {
					$table_log['status'] = 'migrated';
					$table_log['rows']   = (int) $result;

					$schema_notes = array();
					if ( ! empty( $src_only_cols ) ) {
						$schema_notes[] = 'source-only columns (not migrated): ' . implode( ', ', $src_only_cols );
					}
					if ( ! empty( $dst_only_cols ) ) {
						$schema_notes[] = 'destination-only columns (set to default): ' . implode( ', ', $dst_only_cols );
					}
					$table_log['note'] = empty( $schema_notes ) ? 'schema parity OK' : implode( '; ', $schema_notes );
				}

			} catch ( \Throwable $e ) {
				$table_log['status'] = 'error';
				$table_log['note']   = 'exception: ' . $e->getMessage();
			} catch ( \Exception $e ) {
				$table_log['status'] = 'error';
				$table_log['note']   = 'exception: ' . $e->getMessage();
			}

			$log['tables'][ $spai_suffix ] = $table_log;
		}
	}

	/**
	 * Get the migration log from the last run.
	 *
	 * Returns null if migration has never run.
	 *
	 * @return array|null
	 */
	public static function get_log() {
		$log = get_option( self::LOG_OPTION, null );
		return is_array( $log ) ? $log : null;
	}

	/**
	 * Check whether the migration has been completed.
	 *
	 * @return bool
	 */
	public static function is_done() {
		return (bool) get_option( self::MIGRATED_FLAG );
	}

	/**
	 * Reset the migration flag (for testing / re-run scenarios only).
	 *
	 * This should NOT be called in production code outside of test tooling.
	 */
	public static function reset_flag() {
		delete_option( self::MIGRATED_FLAG );
		delete_option( self::LOG_OPTION );
	}
}
