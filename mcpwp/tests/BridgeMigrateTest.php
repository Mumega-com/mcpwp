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

	public function test_migration_log_records_skipped_existing(): void {
		$this->seed_spai_options();
		update_option( 'mcpwp_settings', array( 'existing' => true ) );

		Mcpwp_Migrate::run();

		$log = Mcpwp_Migrate::get_log();
		$this->assertContains( 'mcpwp_settings', $log['skipped_existing'] );
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
}
