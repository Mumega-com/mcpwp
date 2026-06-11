<?php

/**
 * Tests for the #505 bridge local-entitlement fallback in Mcpwp_License.
 *
 * The bridge fallback (is_pro() local path) applies ONLY when:
 *   1. mcpwp_migrated_from_spai is set (real migration completed).
 *   2. Freemius is NOT paying/trial (not present or reports free).
 *   3. Either mcpwp_pro_license is a valid non-expired blob, OR
 *      mcpwp_trial_started is within the 14-day window.
 *
 * Covered scenarios:
 *   - Valid license (no expiry) → is_pro() true.
 *   - Valid license (future expiry) → is_pro() true.
 *   - Expired license → is_pro() false.
 *   - License missing 'valid' flag → is_pro() false.
 *   - Active trial (within 14 days) → is_pro() true.
 *   - Lapsed trial (>14 days ago) → is_pro() false.
 *   - Trial not set → is_pro() false.
 *   - Fallback does NOT fire when migrated-flag is absent (anti-injection).
 *   - Freemius paying → is_pro() true regardless of local state.
 *   - Both local license valid AND Freemius paying → is_pro() true (belt+suspenders).
 *
 * Run command (in wp-test docker):
 *   docker exec wp-test-wordpress-1 bash -c \
 *     "cd /var/www/html/wp-content/plugins/mcpwp && \
 *      php vendor/bin/phpunit --configuration tests/phpunit.xml \
 *      --filter BridgeLicenseEntitlementTest"
 *
 * @package MCPWP
 */

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/class-mcpwp-license.php';

final class BridgeLicenseEntitlementTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['mcpwp_test_options']    = array();
		$GLOBALS['mcpwp_test_transients'] = array();
		$GLOBALS['mcpwp_test_fs']         = null;
		$this->reset_license_singleton();
	}

	protected function tearDown(): void {
		$GLOBALS['mcpwp_test_fs'] = null;
		$this->reset_license_singleton();
	}

	private function reset_license_singleton(): void {
		$ref      = new ReflectionClass( 'Mcpwp_License' );
		$instance = $ref->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Set the migrated-from-spai flag (simulates completed bridge migration).
	 */
	private function set_migrated_flag(): void {
		update_option( 'mcpwp_migrated_from_spai', '1' );
	}

	/**
	 * Seed a valid (non-expired, lifetime) local license.
	 */
	private function seed_valid_license(): void {
		update_option( 'mcpwp_pro_license', array(
			'key'        => 'ls-test-key-abc123',
			'valid'      => true,
			'plan'       => 'pro',
			'expires_at' => null, // lifetime
			'activated'  => '2026-01-01 00:00:00',
		) );
	}

	/**
	 * Seed an expired local license.
	 */
	private function seed_expired_license(): void {
		update_option( 'mcpwp_pro_license', array(
			'key'        => 'ls-expired-key',
			'valid'      => true,
			'plan'       => 'pro',
			'expires_at' => '2020-01-01T00:00:00Z', // far in the past
			'activated'  => '2019-12-01 00:00:00',
		) );
	}

	/**
	 * Seed an active trial (N days ago, within 14-day window).
	 *
	 * @param int $days_ago Number of days since trial started.
	 */
	private function seed_active_trial( int $days_ago = 3 ): void {
		update_option( 'mcpwp_trial_started', time() - ( $days_ago * DAY_IN_SECONDS ) );
	}

	/**
	 * Seed a lapsed trial (>14 days ago).
	 */
	private function seed_lapsed_trial(): void {
		update_option( 'mcpwp_trial_started', time() - ( 15 * DAY_IN_SECONDS ) );
	}

	private function license(): Mcpwp_License {
		return Mcpwp_License::get_instance();
	}

	// -----------------------------------------------------------------------
	// Baseline: no Freemius, no migration flag, no local license
	// -----------------------------------------------------------------------

	public function test_no_freemius_no_flag_no_local_is_not_pro(): void {
		$this->assertFalse( $this->license()->is_pro(),
			'Fresh install with nothing set must not be pro' );
	}

	// -----------------------------------------------------------------------
	// Anti-injection: fallback must NOT fire without migrated-from-spai flag
	// -----------------------------------------------------------------------

	public function test_local_license_without_flag_does_not_grant_pro(): void {
		// Inject mcpwp_pro_license WITHOUT the migrated flag.
		$this->seed_valid_license();
		// No migrated flag set.

		$this->assertFalse( $this->license()->is_pro(),
			'Local license must NOT grant pro without the migrated-from-spai flag (anti-injection)' );
	}

	public function test_trial_started_without_flag_does_not_grant_pro(): void {
		$this->seed_active_trial( 2 );
		// No migrated flag.

		$this->assertFalse( $this->license()->is_pro(),
			'Trial timestamp must NOT grant pro without the migrated-from-spai flag (anti-injection)' );
	}

	// -----------------------------------------------------------------------
	// FIX 1a: Valid local license with migrated flag → is_pro() true
	// -----------------------------------------------------------------------

	public function test_valid_lifetime_license_with_flag_is_pro(): void {
		$this->set_migrated_flag();
		$this->seed_valid_license();

		$this->assertTrue( $this->license()->is_pro(),
			'Migrated valid lifetime license must grant pro' );
	}

	public function test_valid_future_expiry_license_with_flag_is_pro(): void {
		$this->set_migrated_flag();
		update_option( 'mcpwp_pro_license', array(
			'key'        => 'ls-future-key',
			'valid'      => true,
			'plan'       => 'pro',
			'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + ( 365 * DAY_IN_SECONDS ) ),
			'activated'  => '2026-01-01 00:00:00',
		) );

		$this->assertTrue( $this->license()->is_pro(),
			'Migrated license with future expiry must grant pro' );
	}

	// -----------------------------------------------------------------------
	// FIX 1a: Expired license → is_pro() false
	// -----------------------------------------------------------------------

	public function test_expired_license_with_flag_is_not_pro(): void {
		$this->set_migrated_flag();
		$this->seed_expired_license();

		$this->assertFalse( $this->license()->is_pro(),
			'Migrated expired license must NOT grant pro' );
	}

	public function test_license_missing_valid_flag_is_not_pro(): void {
		$this->set_migrated_flag();
		update_option( 'mcpwp_pro_license', array(
			'key'  => 'ls-incomplete-key',
			// 'valid' key missing
			'plan' => 'pro',
		) );

		$this->assertFalse( $this->license()->is_pro(),
			'License blob without valid=true must NOT grant pro' );
	}

	public function test_license_with_valid_false_is_not_pro(): void {
		$this->set_migrated_flag();
		update_option( 'mcpwp_pro_license', array(
			'key'   => 'ls-invalid-key',
			'valid' => false,
			'plan'  => 'pro',
		) );

		$this->assertFalse( $this->license()->is_pro(),
			'License blob with valid=false must NOT grant pro' );
	}

	// -----------------------------------------------------------------------
	// FIX 1b: Active trial with migrated flag → is_pro() true
	// -----------------------------------------------------------------------

	public function test_active_trial_with_flag_is_pro(): void {
		$this->set_migrated_flag();
		$this->seed_active_trial( 5 ); // 5 days ago — within 14 days.

		$this->assertTrue( $this->license()->is_pro(),
			'Migrated active trial must grant pro' );
	}

	public function test_trial_started_today_with_flag_is_pro(): void {
		$this->set_migrated_flag();
		$this->seed_active_trial( 0 ); // started now

		$this->assertTrue( $this->license()->is_pro(),
			'Trial started today must grant pro' );
	}

	// -----------------------------------------------------------------------
	// FIX 1b: Lapsed trial → is_pro() false
	// -----------------------------------------------------------------------

	public function test_lapsed_trial_with_flag_is_not_pro(): void {
		$this->set_migrated_flag();
		$this->seed_lapsed_trial();

		$this->assertFalse( $this->license()->is_pro(),
			'Migrated lapsed trial (>14 days) must NOT grant pro' );
	}

	public function test_empty_trial_started_with_flag_is_not_pro(): void {
		$this->set_migrated_flag();
		update_option( 'mcpwp_trial_started', '' );

		$this->assertFalse( $this->license()->is_pro(),
			'Empty trial_started must NOT grant pro' );
	}

	// -----------------------------------------------------------------------
	// Freemius paying overrides local state (primary path, always takes priority)
	// -----------------------------------------------------------------------

	public function test_freemius_paying_is_pro_regardless_of_local(): void {
		// No migration flag, no local license — but Freemius says paying.
		$GLOBALS['mcpwp_test_fs'] = new Mcpwp_Test_Fs_Stub( true, true, false, 'pro' );

		$this->assertTrue( $this->license()->is_pro(),
			'Freemius paying must grant pro regardless of local state' );
	}

	public function test_freemius_paying_with_expired_local_license_is_pro(): void {
		$this->set_migrated_flag();
		$this->seed_expired_license();
		$GLOBALS['mcpwp_test_fs'] = new Mcpwp_Test_Fs_Stub( true, true, false, 'pro' );

		$this->assertTrue( $this->license()->is_pro(),
			'Freemius paying must grant pro even when local license is expired' );
	}

	// -----------------------------------------------------------------------
	// Freemius free + valid local license → fallback applies
	// -----------------------------------------------------------------------

	public function test_freemius_free_with_valid_local_license_is_pro(): void {
		$this->set_migrated_flag();
		$this->seed_valid_license();
		// Freemius says free (not paying, not trial).
		$GLOBALS['mcpwp_test_fs'] = new Mcpwp_Test_Fs_Stub( false, false, false, '' );

		$this->assertTrue( $this->license()->is_pro(),
			'When Freemius says free but migration has a valid local license, fallback must grant pro' );
	}
}
