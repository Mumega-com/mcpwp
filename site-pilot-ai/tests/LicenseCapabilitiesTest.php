<?php

/**
 * Tests for the canonical license/entitlement state (issue #319).
 *
 * Verifies that plan and pro_active are always mutually consistent across the
 * single source of truth (Spai_License::get_license_info) and the capabilities
 * builder (Spai_Core::get_capabilities), for every plan state.
 */

use PHPUnit\Framework\TestCase;

final class LicenseCapabilitiesTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['spai_test_options']    = array();
        $GLOBALS['spai_test_transients'] = array();
        $GLOBALS['spai_test_post_types'] = array();
        $GLOBALS['spai_test_is_multisite'] = false;
        $GLOBALS['spai_test_filters']    = array();

        $this->reset_license_singleton();
    }

    protected function tearDown(): void
    {
        $this->reset_license_singleton();
    }

    /**
     * Reset the Spai_License singleton and its cached license data so each test
     * starts from a clean entitlement state.
     */
    private function reset_license_singleton(): void
    {
        $ref      = new ReflectionClass('Spai_License');
        $instance = $ref->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    /**
     * Store a Lemon Squeezy style local license.
     *
     * @param string      $plan       Plan slug.
     * @param string|null $expires_at Expiration date or null for lifetime.
     */
    private function store_license(string $plan, ?string $expires_at = null): void
    {
        update_option(Spai_License::OPTION_KEY, array(
            'key'        => 'TEST-LICENSE-KEY-1234',
            'valid'      => true,
            'plan'       => $plan,
            'expires_at' => $expires_at,
        ));
    }

    /**
     * Assert that a license_info / capabilities pair is internally consistent.
     *
     * @param array $info Result of get_license_info() or capabilities.
     */
    private function assertConsistent(array $info): void
    {
        $plan      = $info['plan'];
        $pro       = (bool) ($info['pro_active'] ?? $info['is_pro']);

        if ($pro) {
            $this->assertNotContains(
                $plan,
                array('unlicensed', 'free', ''),
                'pro_active=true must never report a free/unlicensed plan.'
            );
        } else {
            $this->assertContains(
                $plan,
                array('unlicensed', 'free'),
                'pro_active=false must report unlicensed/free plan.'
            );
        }
    }

    private function capabilities(): array
    {
        $core = new Spai_Core();
        return $core->get_capabilities();
    }

    // -- get_license_info() matrix -------------------------------------------

    public function test_unlicensed_is_consistent(): void
    {
        $info = Spai_License::get_instance()->get_license_info();
        $this->assertSame('unlicensed', $info['plan']);
        $this->assertFalse($info['is_pro']);
        $this->assertFalse($info['is_paying']);
        $this->assertFalse($info['is_agency']);
        $this->assertConsistent($info);
    }

    public function test_trial_is_consistent(): void
    {
        update_option(Spai_License::TRIAL_KEY, time());

        $info = Spai_License::get_instance()->get_license_info();
        $this->assertSame('trial', $info['plan']);
        $this->assertTrue($info['is_pro']);
        $this->assertFalse($info['is_paying']);
        $this->assertFalse($info['is_agency']);
        $this->assertConsistent($info);
    }

    public function test_pro_is_consistent(): void
    {
        $this->store_license('pro');

        $info = Spai_License::get_instance()->get_license_info();
        $this->assertSame('pro', $info['plan']);
        $this->assertTrue($info['is_pro']);
        $this->assertTrue($info['is_paying']);
        $this->assertFalse($info['is_agency']);
        $this->assertConsistent($info);
    }

    public function test_agency_is_consistent(): void
    {
        $this->store_license('agency');

        $info = Spai_License::get_instance()->get_license_info();
        $this->assertSame('agency', $info['plan']);
        $this->assertTrue($info['is_pro']);
        $this->assertTrue($info['is_paying']);
        $this->assertTrue($info['is_agency']);
        $this->assertConsistent($info);
    }

    public function test_expired_license_is_consistent(): void
    {
        // Expired one day ago.
        $this->store_license('agency', gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS));

        $info = Spai_License::get_instance()->get_license_info();
        $this->assertSame('unlicensed', $info['plan']);
        $this->assertFalse($info['is_pro']);
        $this->assertFalse($info['is_paying']);
        $this->assertFalse($info['is_agency']);
        $this->assertConsistent($info);
    }

    public function test_partial_license_does_not_report_free_plan(): void
    {
        // A stored license marked valid/paying but with a missing/free plan slug
        // (the legacy-key / partial-state case from #319). The accessor must
        // coerce this to a sane paid plan rather than reporting a contradiction.
        update_option(Spai_License::OPTION_KEY, array(
            'key'   => 'TEST-LICENSE-KEY-1234',
            'valid' => true,
            'plan'  => 'free',
        ));

        $info = Spai_License::get_instance()->get_license_info();
        $this->assertTrue($info['is_pro']);
        $this->assertSame('pro', $info['plan'], 'A paying entitlement must not collapse to a free plan.');
        $this->assertConsistent($info);
    }

    // -- capabilities() matrix (full pipeline incl. pro bootstrap filter) ----

    public function test_capabilities_unlicensed_is_consistent(): void
    {
        $caps = $this->capabilities();
        $this->assertSame('unlicensed', $caps['plan']);
        $this->assertFalse($caps['pro_active']);
        $this->assertConsistent($caps);
    }

    public function test_capabilities_pro_is_consistent(): void
    {
        $this->store_license('pro');
        $caps = $this->capabilities();
        $this->assertSame('pro', $caps['plan']);
        $this->assertTrue($caps['pro_active']);
        $this->assertConsistent($caps);
    }

    public function test_capabilities_agency_is_consistent(): void
    {
        $this->store_license('agency');
        $caps = $this->capabilities();
        $this->assertSame('agency', $caps['plan']);
        $this->assertTrue($caps['pro_active']);
        $this->assertConsistent($caps);
    }

    public function test_capabilities_trial_is_consistent(): void
    {
        update_option(Spai_License::TRIAL_KEY, time());
        $caps = $this->capabilities();
        $this->assertSame('trial', $caps['plan']);
        $this->assertTrue($caps['pro_active']);
        $this->assertConsistent($caps);
    }

    public function test_capabilities_expired_is_consistent(): void
    {
        $this->store_license('agency', gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS));
        $caps = $this->capabilities();
        $this->assertSame('unlicensed', $caps['plan']);
        $this->assertFalse($caps['pro_active']);
        $this->assertConsistent($caps);
    }

    /**
     * The pro bootstrap filter (which only loads when licensed) must NOT be the
     * thing that defines plan/pro_active. Even if it runs while an agency
     * license is active, the canonical accessor wins and stays consistent.
     */
    public function test_pro_bootstrap_filter_does_not_override_canonical_plan(): void
    {
        $this->store_license('agency');
        add_filter('spai_site_capabilities', array('Spai_Pro_Bootstrap', 'add_pro_capabilities'));

        $caps = $this->capabilities();

        // Pro-module-only flags are still added by the filter.
        $this->assertArrayHasKey('learnpress', $caps);
        $this->assertArrayHasKey('tp_events', $caps);

        // Canonical plan/pro_active remain consistent and reflect the license.
        $this->assertSame('agency', $caps['plan']);
        $this->assertTrue($caps['pro_active']);
        $this->assertConsistent($caps);
    }

    /**
     * WP.org build: licensing is always off, so plan/pro_active must report the
     * free/unlicensed state consistently regardless of stored data.
     *
     * Runs in a separate process because SPAI_WPORG_BUILD is a constant.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_wporg_build_is_unlicensed_and_consistent(): void
    {
        define('SPAI_WPORG_BUILD', true);

        // Even with a stored agency license, the WP.org build must report free.
        update_option(Spai_License::OPTION_KEY, array(
            'key'   => 'TEST-LICENSE-KEY-1234',
            'valid' => true,
            'plan'  => 'agency',
        ));

        $info = Spai_License::get_instance()->get_license_info();
        $this->assertFalse($info['is_pro']);
        $this->assertSame('unlicensed', $info['plan']);
        $this->assertConsistent($info);

        $core = new Spai_Core();
        $caps = $core->get_capabilities();
        $this->assertFalse($caps['pro_active']);
        $this->assertSame('unlicensed', $caps['plan']);
        $this->assertConsistent($caps);
    }
}
