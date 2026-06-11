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

		$this->assertSame(
			$existing_keys,
			get_option( 'mcpwp_api_keys' ),
			'Migration must not overwrite existing mcpwp_api_keys'
		);
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
		$this->assertNotEmpty( $log['copied'] );
	}

	public function test_migration_log_records_skipped_existing(): void {
		$this->seed_spai_options();
		update_option( 'mcpwp_settings', array( 'existing' => true ) );

		Mcpwp_Migrate::run();

		$log = Mcpwp_Migrate::get_log();
		$this->assertContains( 'mcpwp_settings', $log['skipped_existing'] );
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
}
