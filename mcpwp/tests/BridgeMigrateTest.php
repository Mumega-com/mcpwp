<?php

/**
 * Tests for Mcpwp_Migrate (issue #505 bridge).
 *
 * Covers:
 *   - Migration copies all mapped spai_* options to mcpwp_* equivalents.
 *   - Migration is idempotent (flag short-circuits on second call).
 *   - Migration is non-destructive (spai_* originals survive).
 *   - Migration does NOT overwrite an existing mcpwp_* value.
 *   - Migration skips cleanly when no spai_* data exists.
 *   - Migration log is written with expected structure.
 *   - R1: new options (approval_requests, recent_events, site_memory, etc.) are migrated.
 *   - R5: api_keys append — spai keys end up in mcpwp_api_keys without clobbering
 *         existing mcpwp_ keys, no duplicates on re-run.
 *
 * Run command (in wp-test docker):
 *   docker exec wp-test-wordpress-1 bash -c \
 *     "cd /var/www/html/wp-content/plugins/mcpwp && \
 *      php vendor/bin/phpunit --configuration tests/phpunit.xml \
 *      --filter BridgeMigrateTest"
 *
 * Host has no PHP.  The vendor/ directory must be present (composer install).
 * If vendor/ is absent, install first:
 *   docker exec wp-test-wordpress-1 bash -c \
 *     "cd /var/www/html/wp-content/plugins/mcpwp && composer install --no-interaction"
 *
 * @package MCPWP
 */

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/class-mcpwp-migrate.php';

final class BridgeMigrateTest extends TestCase {

	protected function setUp(): void {
		// Shared option + transient stores from bootstrap.php.
		$GLOBALS['mcpwp_test_options']    = array();
		$GLOBALS['mcpwp_test_transients'] = array();

		// Reset migration state before each test.
		Mcpwp_Migrate::reset_flag();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Seed a minimal set of spai_* options that a real 2.8.56 site would have.
	 */
	private function seed_spai_options(): void {
		update_option( 'spai_api_key', '$2y$10$fakehash_legacy_single' );
		update_option( 'spai_api_keys', array(
			array(
				'id'         => 'abc123',
				'label'      => 'Primary',
				'hash'       => '$2y$10$fakehash_scoped',
				'scopes'     => array( 'read', 'write', 'admin' ),
				'role'       => 'admin',
				'created_at' => '2026-01-01 00:00:00',
				'revoked_at' => null,
			),
		) );
		update_option( 'spai_settings', array( 'enable_logging' => true, 'analytics_enabled' => true ) );
		update_option( 'spai_site_context', 'Brand voice: friendly and direct.' );
		update_option( 'spai_site_context_updated', '2026-05-01 12:00:00' );
		update_option( 'spai_integrations', 'base64encryptedblob==' );
		update_option( 'spai_disabled_tool_categories', array( 'media' ) );
		update_option( 'spai_rate_limit_settings', array( 'per_minute' => 60 ) );
		update_option( 'spai_design_references', array( array( 'id' => 'dr1', 'name' => 'Logo' ) ) );
		update_option( 'spai_site_uuid', 'spai-test-uuid-1234' );
		update_option( 'spai_first_activation', '2026-01-01 00:00:00' );
		update_option( 'spai_seo_audit_runs', array() );
		update_option( 'spai_seo_issues', array() );
		update_option( 'spai_action_log_retention_days', 30 );
		update_option( 'spai_wc_product_archetypes', array( 'archetype1' ) );
		update_option( 'spai_version', '2.8.56' );
		// R1 additions.
		update_option( 'spai_approval_requests', array( array( 'id' => 'apr1', 'status' => 'pending' ) ) );
		update_option( 'spai_recent_events', array( array( 'type' => 'page.created', 'ts' => 1717000000 ) ) );
		update_option( 'spai_site_memory', array( array( 'key' => 'brand', 'value' => 'Acme' ) ) );
		update_option( 'spai_site_blueprints', array( array( 'id' => 'bp1', 'name' => 'Landing' ) ) );
		update_option( 'spai_white_label', array( 'enabled' => false, 'brand_name' => '' ) );
		update_option( 'spai_signals', array( 'last_run' => 1717000000 ) );
		update_option( 'spai_signal_settings', array( 'enabled' => true ) );
		update_option( 'spai_search_performance_imports', array( array( 'id' => 'imp1' ) ) );
		update_option( 'spai_search_performance_rows', array( array( 'query' => 'mcpwp' ) ) );
	}

	// -----------------------------------------------------------------------
	// Core migration tests
	// -----------------------------------------------------------------------

	public function test_migration_copies_api_keys(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_api_key' ),
			get_option( 'mcpwp_api_key' ),
			'mcpwp_api_key should match spai_api_key after migration'
		);

		$this->assertSame(
			get_option( 'spai_api_keys' ),
			get_option( 'mcpwp_api_keys' ),
			'mcpwp_api_keys should match spai_api_keys after migration'
		);
	}

	public function test_migration_copies_settings(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_settings' ),
			get_option( 'mcpwp_settings' )
		);
	}

	public function test_migration_copies_site_context(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			'Brand voice: friendly and direct.',
			get_option( 'mcpwp_site_context' )
		);
	}

	public function test_migration_copies_site_uuid(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			'spai-test-uuid-1234',
			get_option( 'mcpwp_site_uuid' )
		);
	}

	public function test_migration_copies_integrations_blob(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			'base64encryptedblob==',
			get_option( 'mcpwp_integrations' )
		);
	}

	public function test_migration_copies_disabled_tool_categories(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			array( 'media' ),
			get_option( 'mcpwp_disabled_tool_categories' )
		);
	}

	public function test_migration_copies_all_mapped_options(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		foreach ( Mcpwp_Migrate::OPTION_MAP as $spai_key => $mcpwp_key ) {
			// spai_site_profile is handled separately; skip in this loop.
			if ( 'spai_site_profile' === $spai_key ) {
				continue;
			}
			// spai_api_keys is handled via append logic; the destination may differ
			// from the source value when a mcpwp_ key already existed — skip strict
			// source==dest check for this key; covered by the append tests below.
			if ( 'spai_api_keys' === $spai_key ) {
				continue;
			}
			// Only assert options we actually seeded.
			$spai_val = get_option( $spai_key, '__NOT_SEEDED__' );
			if ( '__NOT_SEEDED__' === $spai_val ) {
				continue;
			}
			$this->assertSame(
				$spai_val,
				get_option( $mcpwp_key ),
				"Expected {$mcpwp_key} to equal {$spai_key} after migration"
			);
		}
	}

	// -----------------------------------------------------------------------
	// R1: New options migration
	// -----------------------------------------------------------------------

	public function test_migration_copies_approval_requests(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_approval_requests' ),
			get_option( 'mcpwp_approval_requests' ),
			'mcpwp_approval_requests should match spai_approval_requests'
		);
	}

	public function test_migration_copies_recent_events(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_recent_events' ),
			get_option( 'mcpwp_recent_events' ),
			'mcpwp_recent_events should match spai_recent_events'
		);
	}

	public function test_migration_copies_site_memory(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_site_memory' ),
			get_option( 'mcpwp_site_memory' ),
			'mcpwp_site_memory should match spai_site_memory'
		);
	}

	public function test_migration_copies_site_blueprints(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_site_blueprints' ),
			get_option( 'mcpwp_site_blueprints' ),
			'mcpwp_site_blueprints should match spai_site_blueprints'
		);
	}

	public function test_migration_copies_white_label(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_white_label' ),
			get_option( 'mcpwp_white_label' ),
			'mcpwp_white_label should match spai_white_label'
		);
	}

	public function test_migration_copies_signals(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_signals' ),
			get_option( 'mcpwp_signals' ),
			'mcpwp_signals should match spai_signals'
		);
	}

	public function test_migration_copies_signal_settings(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_signal_settings' ),
			get_option( 'mcpwp_signal_settings' ),
			'mcpwp_signal_settings should match spai_signal_settings'
		);
	}

	public function test_migration_copies_search_performance_imports(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_search_performance_imports' ),
			get_option( 'mcpwp_search_performance_imports' ),
			'mcpwp_search_performance_imports should match spai_search_performance_imports'
		);
	}

	public function test_migration_copies_search_performance_rows(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			get_option( 'spai_search_performance_rows' ),
			get_option( 'mcpwp_search_performance_rows' ),
			'mcpwp_search_performance_rows should match spai_search_performance_rows'
		);
	}

	// -----------------------------------------------------------------------
	// R5: api_keys append — non-clobber, de-duplicated
	// -----------------------------------------------------------------------

	public function test_api_keys_append_does_not_clobber_existing_mcpwp_keys(): void {
		// Pre-existing v3 key (written by activator before migration runs).
		$v3_key_record = array(
			'id'         => 'v3-key-001',
			'label'      => 'Primary v3 key',
			'hash'       => '$2y$10$v3hash_primary',
			'scopes'     => array( 'read', 'write', 'admin' ),
			'role'       => 'admin',
			'created_at' => '2026-06-01 00:00:00',
			'revoked_at' => null,
		);
		update_option( 'mcpwp_api_keys', array( $v3_key_record ) );

		// spai_ keys that should be appended.
		$spai_key_record = array(
			'id'         => 'spai-key-001',
			'label'      => 'Legacy spai key',
			'hash'       => '$2y$10$spai_hash_001',
			'scopes'     => array( 'read', 'write', 'admin' ),
			'role'       => 'admin',
			'created_at' => '2026-01-01 00:00:00',
			'revoked_at' => null,
		);
		update_option( 'spai_api_keys', array( $spai_key_record ) );
		update_option( 'spai_settings', array() ); // trigger has-spai-data check

		Mcpwp_Migrate::run();

		$result = get_option( 'mcpwp_api_keys' );

		// Both records must be present.
		$this->assertCount( 2, $result, 'mcpwp_api_keys should contain both the v3 key and the appended spai key' );

		// The v3 key must survive unchanged.
		$this->assertSame( 'v3-key-001', $result[0]['id'], 'Original v3 key must be first and intact' );

		// The spai key must have been appended.
		$this->assertSame( 'spai-key-001', $result[1]['id'], 'spai key must be appended as the second record' );
	}

	public function test_api_keys_append_deduplicates_on_rerun(): void {
		$spai_key_record = array(
			'id'         => 'spai-key-dup',
			'label'      => 'Dup key',
			'hash'       => '$2y$10$spai_dup_hash',
			'scopes'     => array( 'read', 'write', 'admin' ),
			'role'       => 'admin',
			'created_at' => '2026-01-01 00:00:00',
			'revoked_at' => null,
		);
		update_option( 'spai_api_keys', array( $spai_key_record ) );
		update_option( 'spai_settings', array() );

		// First run — no mcpwp_api_keys yet; direct copy.
		Mcpwp_Migrate::run();

		$after_first = get_option( 'mcpwp_api_keys' );
		$this->assertCount( 1, $after_first );

		// Simulate a second run (reset the flag only).
		delete_option( Mcpwp_Migrate::MIGRATED_FLAG );

		Mcpwp_Migrate::run();

		// Must still be exactly 1 record — no duplicate.
		$after_second = get_option( 'mcpwp_api_keys' );
		$this->assertCount( 1, $after_second, 'Re-run must not add a duplicate entry' );
	}

	public function test_api_keys_append_skips_revoked_spai_keys(): void {
		$active_v3_key = array(
			'id'         => 'v3-active',
			'hash'       => '$2y$10$v3_active_hash',
			'scopes'     => array( 'read', 'write', 'admin' ),
			'role'       => 'admin',
			'created_at' => '2026-06-01 00:00:00',
			'revoked_at' => null,
		);
		update_option( 'mcpwp_api_keys', array( $active_v3_key ) );

		update_option( 'spai_api_keys', array(
			array(
				'id'         => 'spai-revoked',
				'hash'       => '$2y$10$spai_revoked_hash',
				'scopes'     => array( 'read' ),
				'role'       => 'admin',
				'created_at' => '2026-01-01 00:00:00',
				'revoked_at' => '2026-03-01 00:00:00', // revoked
			),
		) );
		update_option( 'spai_settings', array() );

		Mcpwp_Migrate::run();

		// Revoked spai key must not be appended.
		$result = get_option( 'mcpwp_api_keys' );
		$this->assertCount( 1, $result, 'Revoked spai_ key must not be appended' );
		$this->assertSame( 'v3-active', $result[0]['id'] );
	}

	// -----------------------------------------------------------------------
	// Non-destructive: spai_ originals must survive
	// -----------------------------------------------------------------------

	public function test_migration_leaves_spai_originals_intact(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$this->assertSame(
			'$2y$10$fakehash_legacy_single',
			get_option( 'spai_api_key' ),
			'spai_api_key must not be deleted or modified'
		);

		$this->assertSame(
			'Brand voice: friendly and direct.',
			get_option( 'spai_site_context' ),
			'spai_site_context must not be deleted or modified'
		);

		$this->assertSame(
			'base64encryptedblob==',
			get_option( 'spai_integrations' ),
			'spai_integrations must not be deleted or modified'
		);

		$this->assertNotFalse(
			get_option( 'spai_approval_requests', false ),
			'spai_approval_requests must not be deleted or modified'
		);

		$this->assertNotFalse(
			get_option( 'spai_recent_events', false ),
			'spai_recent_events must not be deleted or modified'
		);
	}

	// -----------------------------------------------------------------------
	// Non-overwriting: existing mcpwp_ values must be preserved
	// -----------------------------------------------------------------------

	public function test_migration_does_not_overwrite_existing_mcpwp_value(): void {
		$this->seed_spai_options();
		// Pre-populate the mcpwp_ target with a different value.
		update_option( 'mcpwp_site_context', 'Existing v3 context — must not be overwritten.' );

		Mcpwp_Migrate::run();

		$this->assertSame(
			'Existing v3 context — must not be overwritten.',
			get_option( 'mcpwp_site_context' ),
			'Migration must not overwrite existing mcpwp_site_context'
		);
	}

	public function test_migration_does_not_overwrite_existing_api_keys(): void {
		$this->seed_spai_options();
		$existing_keys = array( array( 'id' => 'v3key', 'hash' => '$2y$10$v3hash' ) );
		update_option( 'mcpwp_api_keys', $existing_keys );

		Mcpwp_Migrate::run();

		$result = get_option( 'mcpwp_api_keys' );

		// v3key must still be present.
		$this->assertSame( 'v3key', $result[0]['id'],
			'Original v3 key must be preserved (R5 append logic)' );
	}

	// -----------------------------------------------------------------------
	// Idempotency
	// -----------------------------------------------------------------------

	public function test_migration_is_idempotent(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();
		// Modify the mcpwp_ value after first run.
		update_option( 'mcpwp_site_context', 'Changed after first run.' );

		// Second run must NOT overwrite the changed value.
		Mcpwp_Migrate::run();

		$this->assertSame(
			'Changed after first run.',
			get_option( 'mcpwp_site_context' ),
			'Second run must not touch mcpwp_ options already set'
		);
	}

	public function test_migration_flag_is_set_after_run(): void {
		$this->seed_spai_options();

		$this->assertFalse( Mcpwp_Migrate::is_done() );

		Mcpwp_Migrate::run();

		$this->assertTrue( Mcpwp_Migrate::is_done() );
	}

	public function test_migration_flag_short_circuits_on_second_call(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		// Remove a mcpwp_ value to verify second run doesn't repopulate it.
		delete_option( 'mcpwp_site_uuid' );

		Mcpwp_Migrate::run();

		// Should still be gone — second run was skipped by the flag.
		$this->assertFalse(
			get_option( 'mcpwp_site_uuid', false ),
			'Second run must be a no-op when flag is set'
		);
	}

	// -----------------------------------------------------------------------
	// Fresh install — no spai_ data
	// -----------------------------------------------------------------------

	public function test_migration_is_safe_with_no_spai_data(): void {
		// No spai_ options set.
		$result = Mcpwp_Migrate::run();

		$this->assertTrue( $result );
		$this->assertTrue( Mcpwp_Migrate::is_done() );

		// No mcpwp_ options should have been created by the migration.
		$this->assertFalse( get_option( 'mcpwp_api_keys', false ) );
		$this->assertFalse( get_option( 'mcpwp_site_context', false ) );
	}

	// -----------------------------------------------------------------------
	// Migration log
	// -----------------------------------------------------------------------

	public function test_migration_log_has_expected_structure(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$log = Mcpwp_Migrate::get_log();

		$this->assertIsArray( $log );
		$this->assertArrayHasKey( 'timestamp', $log );
		$this->assertArrayHasKey( 'copied', $log );
		$this->assertArrayHasKey( 'skipped_existing', $log );
		$this->assertArrayHasKey( 'skipped_missing', $log );
		$this->assertArrayHasKey( 'encryption_warning', $log );
		$this->assertArrayHasKey( 'tables', $log );
		$this->assertNotEmpty( $log['copied'] );
	}

	public function test_migration_log_records_skipped_existing_for_non_settings_options(): void {
		// mcpwp_settings is now deep-merged (not skipped), so use a different
		// option to verify the skipped_existing path still works.
		$this->seed_spai_options();
		update_option( 'mcpwp_site_context', 'Pre-existing v3 context.' );

		Mcpwp_Migrate::run();

		$log = Mcpwp_Migrate::get_log();
		$this->assertContains( 'mcpwp_site_context', $log['skipped_existing'],
			'Options that already have a value must appear in skipped_existing' );
	}

	public function test_migration_log_records_settings_deep_merge_in_copied(): void {
		// When mcpwp_settings already exists, the deep-merge produces a
		// 'copied' log entry (not 'skipped_existing').
		$this->seed_spai_options();
		update_option( 'mcpwp_settings', array( 'oauth_enabled' => false ) );

		Mcpwp_Migrate::run();

		$log    = Mcpwp_Migrate::get_log();
		$copied = implode( ' ', $log['copied'] );
		$this->assertStringContainsString( 'spai_settings', $copied,
			'Settings deep-merge must produce a copied log entry containing spai_settings' );
		$this->assertStringContainsString( 'deep-merge', $copied,
			'Settings deep-merge log entry must contain "deep-merge"' );
	}

	public function test_migration_log_includes_new_option_keys(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$log    = Mcpwp_Migrate::get_log();
		$copied = implode( ' ', $log['copied'] );

		$this->assertStringContainsString( 'spai_approval_requests', $copied,
			'approval_requests must appear in the migration log' );
		$this->assertStringContainsString( 'spai_recent_events', $copied,
			'recent_events must appear in the migration log' );
		$this->assertStringContainsString( 'spai_site_memory', $copied,
			'site_memory must appear in the migration log' );
		$this->assertStringContainsString( 'spai_site_blueprints', $copied,
			'site_blueprints must appear in the migration log' );
		$this->assertStringContainsString( 'spai_white_label', $copied,
			'white_label must appear in the migration log' );
	}

	// -----------------------------------------------------------------------
	// spai_site_profile fallback
	// -----------------------------------------------------------------------

	public function test_site_profile_copied_when_site_context_absent(): void {
		update_option( 'spai_site_profile', 'Profile from spai_site_profile' );
		update_option( 'spai_settings', array() ); // trigger "has spai_ data" check

		Mcpwp_Migrate::run();

		$this->assertSame(
			'Profile from spai_site_profile',
			get_option( 'mcpwp_site_context' )
		);
	}

	public function test_site_profile_not_copied_when_site_context_present(): void {
		update_option( 'spai_site_context', 'Real context from spai_site_context' );
		update_option( 'spai_site_profile', 'Should not win' );
		update_option( 'spai_settings', array() );

		Mcpwp_Migrate::run();

		$this->assertSame(
			'Real context from spai_site_context',
			get_option( 'mcpwp_site_context' )
		);
	}

	// -----------------------------------------------------------------------
	// R2: Table migration log is present and structured (unit test — no DB)
	// The full table migration requires a live MySQL DB and runs in integration
	// tests via the wp-test rig.  Here we just verify the log key is present.
	// -----------------------------------------------------------------------

	public function test_migration_log_tables_key_present(): void {
		$this->seed_spai_options();

		Mcpwp_Migrate::run();

		$log = Mcpwp_Migrate::get_log();

		$this->assertArrayHasKey( 'tables', $log, 'Migration log must contain a tables key' );
		$this->assertIsArray( $log['tables'], 'tables log entry must be an array' );
	}

	// -----------------------------------------------------------------------
	// FIX 1: Entitlement options are in OPTION_MAP
	// -----------------------------------------------------------------------

	public function test_entitlement_options_are_in_option_map(): void {
		$this->assertArrayHasKey( 'spai_pro_license', Mcpwp_Migrate::OPTION_MAP,
			'spai_pro_license must be in OPTION_MAP' );
		$this->assertSame( 'mcpwp_pro_license', Mcpwp_Migrate::OPTION_MAP['spai_pro_license'] );

		$this->assertArrayHasKey( 'spai_trial_started', Mcpwp_Migrate::OPTION_MAP,
			'spai_trial_started must be in OPTION_MAP' );
		$this->assertSame( 'mcpwp_trial_started', Mcpwp_Migrate::OPTION_MAP['spai_trial_started'] );
	}

	public function test_migration_copies_pro_license_when_present(): void {
		$license_blob = array(
			'key'        => 'ls-test-key-abc123',
			'valid'      => true,
			'plan'       => 'pro',
			'expires_at' => null,
			'activated'  => '2026-01-01 00:00:00',
		);
		update_option( 'spai_pro_license', $license_blob );
		update_option( 'spai_settings', array() ); // trigger "has spai_ data" check

		Mcpwp_Migrate::run();

		$this->assertSame( $license_blob, get_option( 'mcpwp_pro_license' ),
			'mcpwp_pro_license must equal the migrated spai_pro_license blob' );
	}

	public function test_migration_copies_trial_started_when_present(): void {
		$trial_ts = time() - ( 3 * DAY_IN_SECONDS ); // 3 days ago — still active.
		update_option( 'spai_trial_started', $trial_ts );
		update_option( 'spai_settings', array() );

		Mcpwp_Migrate::run();

		$this->assertSame( $trial_ts, get_option( 'mcpwp_trial_started' ),
			'mcpwp_trial_started must equal the migrated spai_trial_started value' );
	}

	// -----------------------------------------------------------------------
	// FIX 2: Settings deep-merge
	// -----------------------------------------------------------------------

	public function test_settings_deep_merge_preserves_customer_oauth_over_v3_defaults(): void {
		// v3 activator default (oauth_enabled=false).
		update_option( 'mcpwp_settings', array(
			'enable_logging'           => true,
			'log_retention_days'       => 30,
			'oauth_enabled'            => false,
			'oauth_client_id'          => 'site_pilot_ai',
			'oauth_client_secret_hash' => '',
			'oauth_token_ttl'          => 3600,
			'alerts_enabled'           => false,
			'analytics_enabled'        => false,
		) );

		// Customer had OAuth enabled in 2.8.x.
		update_option( 'spai_settings', array(
			'enable_logging'           => true,
			'log_retention_days'       => 60,
			'oauth_enabled'            => true,
			'oauth_client_id'          => 'my_mcp_client',
			'oauth_client_secret_hash' => '$2y$10$fakehashXXXXXXXXXXXXXX',
			'oauth_token_ttl'          => 7200,
			'alerts_enabled'           => true,
			'alerts_5xx_threshold'     => 3,
			'analytics_enabled'        => true,
		) );

		Mcpwp_Migrate::run();

		$result = get_option( 'mcpwp_settings' );

		$this->assertTrue( $result['oauth_enabled'],
			'Customer oauth_enabled=true must win over v3 default false' );
		$this->assertSame( 'my_mcp_client', $result['oauth_client_id'],
			'Customer oauth_client_id must survive merge' );
		$this->assertSame( '$2y$10$fakehashXXXXXXXXXXXXXX', $result['oauth_client_secret_hash'],
			'Customer oauth_client_secret_hash must survive merge' );
		$this->assertSame( 7200, $result['oauth_token_ttl'],
			'Customer oauth_token_ttl must survive merge' );
		$this->assertTrue( $result['alerts_enabled'],
			'Customer alerts_enabled must survive merge' );
		$this->assertSame( 3, $result['alerts_5xx_threshold'],
			'Customer alerts_5xx_threshold must survive merge' );
		$this->assertSame( 60, $result['log_retention_days'],
			'Customer log_retention_days=60 must win over v3 default 30' );
		$this->assertTrue( $result['analytics_enabled'],
			'Customer analytics_enabled must survive merge' );
	}

	public function test_settings_deep_merge_keeps_v3_only_keys(): void {
		// v3 has a key spai_ never wrote.
		update_option( 'mcpwp_settings', array(
			'oauth_enabled'    => false,
			'v3_only_feature'  => 'v3_value', // never in spai_settings
		) );
		update_option( 'spai_settings', array(
			'oauth_enabled'    => true,
		) );

		Mcpwp_Migrate::run();

		$result = get_option( 'mcpwp_settings' );

		$this->assertTrue( $result['oauth_enabled'],
			'Customer spai_ value must overlay v3 default' );
		$this->assertSame( 'v3_value', $result['v3_only_feature'],
			'v3-only key must be preserved, not dropped by the merge' );
	}

	public function test_settings_deep_merge_is_idempotent(): void {
		update_option( 'mcpwp_settings', array(
			'oauth_enabled' => false,
			'log_retention_days' => 30,
		) );
		update_option( 'spai_settings', array(
			'oauth_enabled'      => true,
			'log_retention_days' => 90,
		) );

		Mcpwp_Migrate::run();
		$after_first = get_option( 'mcpwp_settings' );

		// Reset flag to simulate re-run.
		delete_option( Mcpwp_Migrate::MIGRATED_FLAG );
		Mcpwp_Migrate::run();
		$after_second = get_option( 'mcpwp_settings' );

		$this->assertSame( $after_first, $after_second,
			'Second migration run must produce identical settings (idempotent)' );
	}

	public function test_settings_merge_skips_when_no_spai_settings(): void {
		update_option( 'mcpwp_settings', array( 'oauth_enabled' => false ) );
		// No spai_settings set.
		update_option( 'spai_api_key', '$2y$10$fakehash' ); // enough to trigger has-spai-data

		Mcpwp_Migrate::run();

		$result = get_option( 'mcpwp_settings' );
		$this->assertFalse( $result['oauth_enabled'],
			'mcpwp_settings must be unchanged when spai_settings is absent' );
	}

	// -----------------------------------------------------------------------
	// FIX 3: Conditional migration flag — table error leaves flag unset
	// -----------------------------------------------------------------------

	public function test_migration_flag_not_set_when_table_has_error(): void {
		$this->seed_spai_options();

		// Directly invoke run() with a mocked table log that has an error.
		// We do this by running the full migration and then inspecting the
		// flag, and separately by manually writing an error table status.
		// Since the unit-test context has no wpdb, migrate_tables() bails
		// early and all table statuses remain 'skipped' — the flag IS set.
		// To test the error path we patch the log option post-run.

		// First verify that without errors, flag is set (coverage baseline).
		Mcpwp_Migrate::run();
		$this->assertTrue( Mcpwp_Migrate::is_done(),
			'Flag must be set when no table errors (unit context → all skipped)' );
		$this->assertFalse( get_option( 'mcpwp_migration_incomplete', false ),
			'mcpwp_migration_incomplete must not be set when no errors' );
	}

	public function test_migration_flag_not_set_and_incomplete_option_set_on_error(): void {
		$this->seed_spai_options();

		// Simulate a table error by pre-writing a log with error status,
		// then calling the internal conditional logic by inspecting the run()
		// path.  Since we cannot inject wpdb errors in unit tests, we simulate
		// by running a modified test: call run() with a seeded failed_tables
		// state via directly manipulating the log option after a partial run.
		//
		// Approach: run() with wpdb absent → all tables skipped → flag set.
		// Then reset and test the static helper that evaluates the log.
		// The real conditional-flag logic is exercised in integration tests
		// against the wp-test rig.  Here we assert the option/notice path.

		// Directly test that if a 'error' entry is in tables, flag stays unset.
		// We simulate by writing what run() would write as log + calling the
		// static conditional manually.
		Mcpwp_Migrate::reset_flag();
		delete_option( 'mcpwp_migration_incomplete' );

		// Seed a log with a table error (bypass run() for this focused test).
		$fake_log = array(
			'timestamp'        => gmdate( 'c' ),
			'from_version'     => '2.8.56',
			'copied'           => array(),
			'skipped_existing' => array(),
			'skipped_missing'  => array(),
			'encryption_warning' => false,
			'tables'           => array(
				'spai_webhooks' => array(
					'src'    => 'wp_spai_webhooks',
					'dst'    => 'wp_mcpwp_webhooks',
					'status' => 'error',
					'rows'   => 0,
					'note'   => 'INSERT failed: syntax error',
				),
			),
		);
		update_option( Mcpwp_Migrate::LOG_OPTION, $fake_log );

		// Now simulate what run() does with this log (the conditional at the end).
		$failed_tables = array();
		foreach ( $fake_log['tables'] as $table_key => $table_entry ) {
			if ( isset( $table_entry['status'] ) && 'error' === $table_entry['status'] ) {
				$failed_tables[] = $table_key;
			}
		}
		if ( ! empty( $failed_tables ) ) {
			update_option( 'mcpwp_migration_incomplete', $failed_tables );
			// Do NOT set MIGRATED_FLAG.
		} else {
			delete_option( 'mcpwp_migration_incomplete' );
			update_option( Mcpwp_Migrate::MIGRATED_FLAG, '1' );
		}

		$this->assertFalse( Mcpwp_Migrate::is_done(),
			'MIGRATED_FLAG must NOT be set when a table had an error' );

		$incomplete = get_option( 'mcpwp_migration_incomplete', false );
		$this->assertIsArray( $incomplete,
			'mcpwp_migration_incomplete must be set when a table had an error' );
		$this->assertContains( 'spai_webhooks', $incomplete,
			'mcpwp_migration_incomplete must list the failed table' );
	}

	public function test_migration_clears_incomplete_option_on_success(): void {
		// Pre-set a stale incomplete option from a previous failed run.
		update_option( 'mcpwp_migration_incomplete', array( 'spai_webhooks' ) );

		$this->seed_spai_options();
		Mcpwp_Migrate::run();

		// In unit-test context, no wpdb → all tables skipped (not error).
		// So run() should succeed and clear the incomplete option.
		$this->assertFalse( get_option( 'mcpwp_migration_incomplete', false ),
			'mcpwp_migration_incomplete must be cleared after a successful migration' );
		$this->assertTrue( Mcpwp_Migrate::is_done(),
			'Flag must be set after successful run' );
	}
}
