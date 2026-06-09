<?php
/**
 * Tests for Mcpwp_Module_Manifest.
 *
 * @package MCPWP
 */

use PHPUnit\Framework\TestCase;

final class KernelModuleManifestTest extends TestCase {

	public function test_minimal_manifest_gets_defaults(): void {
		$m = Mcpwp_Module_Manifest::from_array( array( 'id' => 'seo' ) );

		$this->assertSame( 'seo', $m->id );
		$this->assertSame( '0.0.0', $m->version );
		$this->assertSame( 'free', $m->tier );
		$this->assertSame( array(), $m->requires );
		$this->assertSame( array(), $m->depends_on );
		$this->assertSame( array(), $m->services );
		$this->assertSame( array(), $m->tools );
		$this->assertSame( array(), $m->routes );
		$this->assertSame( array(), $m->admin );
		$this->assertNull( $m->boot );
	}

	public function test_full_manifest_preserves_fields(): void {
		$boot = function () {};
		$svc  = function () {
			return 1;
		};
		$m = Mcpwp_Module_Manifest::from_array(
			array(
				'id'         => 'elementor',
				'version'    => '1.2.3',
				'requires'   => array( 'core' ),
				'tier'       => 'pro',
				'depends_on' => array( 'elementor/elementor.php' ),
				'services'   => array( 'elementor.reader' => $svc ),
				'tools'      => array( 'tool_a' ),
				'routes'     => array( 'Route_A' ),
				'admin'      => array( 'Page_A' ),
				'boot'       => $boot,
			)
		);

		$this->assertSame( '1.2.3', $m->version );
		$this->assertSame( array( 'core' ), $m->requires );
		$this->assertSame( 'pro', $m->tier );
		$this->assertSame( array( 'elementor/elementor.php' ), $m->depends_on );
		$this->assertArrayHasKey( 'elementor.reader', $m->services );
		$this->assertSame( array( 'tool_a' ), $m->tools );
		$this->assertSame( array( 'Route_A' ), $m->routes );
		$this->assertSame( array( 'Page_A' ), $m->admin );
		$this->assertSame( $boot, $m->boot );
	}

	public function test_missing_id_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		Mcpwp_Module_Manifest::from_array( array( 'version' => '1.0.0' ) );
	}

	public function test_invalid_tier_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		Mcpwp_Module_Manifest::from_array( array( 'id' => 'x', 'tier' => 'enterprise' ) );
	}

	public function test_non_callable_boot_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		Mcpwp_Module_Manifest::from_array( array( 'id' => 'x', 'boot' => 'not_a_real_callable_zzz' ) );
	}

	public function test_requires_coerced_to_string_list(): void {
		$m = Mcpwp_Module_Manifest::from_array(
			array(
				'id'       => 'x',
				'requires' => array( 'a', 'b' ),
			)
		);
		$this->assertSame( array( 'a', 'b' ), $m->requires );
	}
}
