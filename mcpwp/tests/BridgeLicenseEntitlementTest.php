<?php

/**
 * Tests for the #505 bridge local-entitlement fallback in Mcpwp_License.
 *
 * SECURITY MODEL (Warden 3.1.0 regression sweep — entitlement-bypass P0):
 * The bridge entitlement decision reads the ORIGINAL spai_ options only:
 *   - spai_pro_license   (stored license blob)
 *   - spai_trial_started (Unix trial timestamp)
 * The spai_ namespace cannot be written through any REST/MCP surface (the
 * options allow-list has no spai_ prefix), so a migrated spai_ original is an
 * un-forgeable proof of a real 2.8.x entitlement. The migrated mcpwp_ COPIES and
 * the mcpwp_migrated_from_spai flag live in the writable mcpwp_ namespace and are
 * therefore NOT trusted for entitlement — an earlier 3.1.0 draft trusted them and
 * any write-scope token could forge Pro on any install. These tests lock that out.
 *
 * The fallback (is_pro() local path) grants Pro only when Freemius is NOT
 * paying/trial AND either spai_pro_license is a valid non-expired blob OR
 * spai_trial_started is within the 14-day window.
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
	 * NOTE: this flag is NOT trusted for entitlement; genuine tests also seed a
	 * spai_ original. It is set here only to prove it neither helps nor is needed.
	 */
	private function set_migrated_flag(): void {
		update_option( 'mcpwp_migrated_from_spai', '1' );
	}

	/**
	 * Seed a genuine, valid (non-expired, lifetime) license in the ORIGINAL
	 * spai_ option — the un-forgeable source the bridge actually reads.
	 */
	private function seed_valid_license(): void {
		update_option( 'spai_pro_license', array(
			'key'        => 'ls-test-key-abc123',
			'valid'      => true,
			'plan'       => 'pro',
			'expires_at' => null, // lifetime
			'activated'  => '2026-01-01 00:00:00',
		) );
	}

	/**
	 * Seed a genuine expired license in the spai_ original.
	 */
	private function seed_expired_license(): void {
		update_option( 'spai_pro_license', array(
			'key'        => 'ls-expired-key',
			'valid'      => true,
			'plan'       => 'pro',
			'expires_at' => '2020-01-01T00:00:00Z', // far in the past
			'activated'  => '2019-12-01 00:00:00',
		) );
	}

	/**
	 * Seed a genuine active trial (N days ago, within 14-day window).
	 *
	 * @param int $days_ago Number of days since trial started.
	 */
	private function seed_active_trial( int $days_ago = 3 ): void {
		update_option( 'spai_trial_started', time() - ( $days_ago * DAY_IN_SECONDS ) );
	}

	/**
	 * Seed a genuine lapsed trial (>14 days ago).
	 */
	private function seed_lapsed_trial(): void {
		update_option( 'spai_trial_started', time() - ( 15 * DAY_IN_SECONDS ) );
	}

	private function license(): Mcpwp_License {
		return Mcpwp_License::get_instance();
	}

	// -----------------------------------------------------------------------
	// Baseline: no Freemius, no migration, no local license
	// -----------------------------------------------------------------------

	public function test_no_freemius_no_flag_no_local_is_not_pro(): void {
		$this->assertFalse( $this->license()->is_pro(),
			'Fresh install with nothing set must not be pro' );
	}

	// -----------------------------------------------------------------------
	// ANTI-FORGERY (the 3.1.0 P0 regression lock): the writable mcpwp_ copies
	// and the migrated flag must NEVER grant Pro. These are exactly the options
	// a write-scope token can set via PUT /option / wp_update_option.
	// -----------------------------------------------------------------------

	public function test_forged_mcpwp_license_with_flag_does_not_grant_pro(): void {
		// Everything a write-scope attacker could set — and NO genuine spai_ source.
		$this->set_migrated_flag();
		update_option( 'mcpwp_pro_license', array(
			'key'        => 'forged',
			'valid'      => true,
			'plan'       => 'pro',
			'expires_at' => null,
		) );

		$this->assertFalse( $this->license()->is_pro(),
			'Forged mcpwp_pro_license + flag must NOT grant pro — entitlement reads spai_ only' );
	}

	public function test_forged_mcpwp_trial_with_flag_does_not_grant_pro(): void {
		$this->set_migrated_flag();
		update_option( 'mcpwp_trial_started', (string) time() );

		$this->assertFalse( $this->license()->is_pro(),
			'Forged mcpwp_trial_started + flag must NOT grant pro — entitlement reads spai_ only' );
	}

	public function test_migrated_flag_alone_does_not_grant_pro(): void {
		$this->set_migrated_flag();

		$this->assertFalse( $this->license()->is_pro(),
			'The self-settable migrated flag alone must NOT grant pro' );
	}

	// -----------------------------------------------------------------------
	// Genuine valid license (spai_ original) → is_pro() true.
	// No flag required: spai_ presence is itself the un-forgeable proof.
	// -----------------------------------------------------------------------

	public function test_valid_lifetime_license_is_pro(): void {
		$this->seed_valid_license();

		$this->assertTrue( $this->license()->is_pro(),
			'Genuine valid lifetime spai_ license must grant pro' );
	}

	public function test_valid_future_expiry_license_is_pro(): void {
		update_option( 'spai_pro_license', array(
			'key'        => 'ls-future-key',
			'valid'      => true,
			'plan'       => 'pro',
			'expires_at' => gmdate( 'Y-m-d\TH:i:s\Z', time() + ( 365 * DAY_IN_SECONDS ) ),
			'activated'  => '2026-01-01 00:00:00',
		) );

		$this->assertTrue( $this->license()->is_pro(),
			'Genuine spai_ license with future expiry must grant pro' );
	}

	// -----------------------------------------------------------------------
	// Genuine license edge cases → is_pro() false (fail-closed).
	// -----------------------------------------------------------------------

	public function test_expired_license_is_not_pro(): void {
		$this->seed_expired_license();

		$this->assertFalse( $this->license()->is_pro(),
			'Genuine expired spai_ license must NOT grant pro' );
	}

	public function test_license_missing_valid_flag_is_not_pro(): void {
		update_option( 'spai_pro_license', array(
			'key'  => 'ls-incomplete-key',
			// 'valid' key missing
			'plan' => 'pro',
		) );

		$this->assertFalse( $this->license()->is_pro(),
			'spai_ license blob without valid=true must NOT grant pro' );
	}

	public function test_license_with_valid_false_is_not_pro(): void {
		update_option( 'spai_pro_license', array(
			'key'   => 'ls-invalid-key',
			'valid' => false,
			'plan'  => 'pro',
		) );

		$this->assertFalse( $this->license()->is_pro(),
			'spai_ license blob with valid=false must NOT grant pro' );
	}

	// -----------------------------------------------------------------------
	// Genuine active trial (spai_ original) → is_pro() true.
	// -----------------------------------------------------------------------

	public function test_active_trial_is_pro(): void {
		$this->seed_active_trial( 5 ); // 5 days ago — within 14 days.

		$this->assertTrue( $this->license()->is_pro(),
			'Genuine active spai_ trial must grant pro' );
	}

	public function test_trial_started_today_is_pro(): void {
		$this->seed_active_trial( 0 ); // started now

		$this->assertTrue( $this->license()->is_pro(),
			'Genuine trial started today must grant pro' );
	}

	// -----------------------------------------------------------------------
	// Genuine lapsed / empty trial → is_pro() false.
	// -----------------------------------------------------------------------

	public function test_lapsed_trial_is_not_pro(): void {
		$this->seed_lapsed_trial();

		$this->assertFalse( $this->license()->is_pro(),
			'Genuine lapsed spai_ trial (>14 days) must NOT grant pro' );
	}

	public function test_empty_trial_started_is_not_pro(): void {
		update_option( 'spai_trial_started', '' );

		$this->assertFalse( $this->license()->is_pro(),
			'Empty spai_trial_started must NOT grant pro' );
	}

	// -----------------------------------------------------------------------
	// Freemius paying overrides local state (primary path, always takes priority)
	// -----------------------------------------------------------------------

	public function test_freemius_paying_is_pro_regardless_of_local(): void {
		// No local license — but Freemius says paying.
		$GLOBALS['mcpwp_test_fs'] = new Mcpwp_Test_Fs_Stub( true, true, false, 'pro' );

		$this->assertTrue( $this->license()->is_pro(),
			'Freemius paying must grant pro regardless of local state' );
	}

	public function test_freemius_paying_with_expired_local_license_is_pro(): void {
		$this->seed_expired_license();
		$GLOBALS['mcpwp_test_fs'] = new Mcpwp_Test_Fs_Stub( true, true, false, 'pro' );

		$this->assertTrue( $this->license()->is_pro(),
			'Freemius paying must grant pro even when local license is expired' );
	}

	// -----------------------------------------------------------------------
	// Freemius free + genuine valid local license → fallback applies
	// -----------------------------------------------------------------------

	public function test_freemius_free_with_valid_local_license_is_pro(): void {
		$this->seed_valid_license();
		// Freemius says free (not paying, not trial).
		$GLOBALS['mcpwp_test_fs'] = new Mcpwp_Test_Fs_Stub( false, false, false, '' );

		$this->assertTrue( $this->license()->is_pro(),
			'When Freemius says free but a genuine spai_ license is present, fallback must grant pro' );
	}
}
