<?php
/**
 * Data migration: spai_* options → mcpwp_* options
 *
 * Copies every known legacy option written by site-pilot-ai 2.8.x to its
 * mcpwp_* equivalent.  The routine is:
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
 * @package MCPWP
 * @since   3.0.0 (unreleased bridge)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles one-time migration of spai_* options to mcpwp_* options.
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
	 *
	 * @var array<string,string>
	 */
	const OPTION_MAP = array(
		// API keys — primary migration target; dual-key auth depends on these.
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
			'timestamp'   => function_exists( 'current_time' ) ? current_time( 'c' ) : gmdate( 'c' ),
			'from_version' => (string) get_option( 'spai_version', 'unknown' ),
			'copied'      => array(),
			'skipped_existing' => array(),
			'skipped_missing'  => array(),
			'encryption_warning' => false,
		);

		// Process the main option map.
		// spai_site_profile → mcpwp_site_context is handled specially below.
		$main_map = self::OPTION_MAP;
		unset( $main_map['spai_site_profile'] );

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

		// Encryption sanity check.
		// After copying spai_integrations → mcpwp_integrations, attempt to verify
		// the blob is still parseable.  Mcpwp_Encryption derives its key from
		// AUTH_SALT — if that constant is the same (it should be on the same WP
		// install), decryption will succeed.
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
						. 'ElevenLabs, etc.) must be re-entered in WP Admin > MCPWP > Integrations.';
				}
			} elseif ( false === $blob ) {
				// spai_integrations was an array (unencrypted, older builds).
				// This is safe — no re-entry needed.
				$log['copied'][] = 'spai_integrations was unencrypted array — copied as-is';
			}
		}

		// Persist the log and set the migration flag atomically.
		update_option( self::LOG_OPTION, $log );
		update_option( self::MIGRATED_FLAG, '1' );

		return true;
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
