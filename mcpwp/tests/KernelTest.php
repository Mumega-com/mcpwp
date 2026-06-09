<?php
/**
 * Tests for Mcpwp_Kernel.
 *
 * @package MCPWP
 */

use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase {

	private function manifest( array $over = array() ): Mcpwp_Module_Manifest {
		return Mcpwp_Module_Manifest::from_array( array_merge( array( 'id' => 'm' ), $over ) );
	}

	public function test_boots_modules_in_dependency_order(): void {
		$order  = array();
		$kernel = new Mcpwp_Kernel();

		// Register out of order; b requires a, c requires b.
		$kernel->register_module(
			$this->manifest(
				array(
					'id'       => 'c',
					'requires' => array( 'b' ),
					'boot'     => function () use ( &$order ) {
						$order[] = 'c';
					},
				)
			)
		);
		$kernel->register_module(
			$this->manifest(
				array(
					'id'   => 'a',
					'boot' => function () use ( &$order ) {
						$order[] = 'a';
					},
				)
			)
		);
		$kernel->register_module(
			$this->manifest(
				array(
					'id'       => 'b',
					'requires' => array( 'a' ),
					'boot'     => function () use ( &$order ) {
						$order[] = 'b';
					},
				)
			)
		);

		$kernel->boot();

		$this->assertSame( array( 'a', 'b', 'c' ), $order );
	}

	public function test_missing_dependency_throws_on_boot(): void {
		$kernel = new Mcpwp_Kernel();
		$kernel->register_module( $this->manifest( array( 'id' => 'a', 'requires' => array( 'ghost' ) ) ) );

		$this->expectException( RuntimeException::class );
		$kernel->boot();
	}

	public function test_dependency_cycle_throws_on_boot(): void {
		$kernel = new Mcpwp_Kernel();
		$kernel->register_module( $this->manifest( array( 'id' => 'a', 'requires' => array( 'b' ) ) ) );
		$kernel->register_module( $this->manifest( array( 'id' => 'b', 'requires' => array( 'a' ) ) ) );

		$this->expectException( RuntimeException::class );
		$kernel->boot();
	}

	public function test_duplicate_module_id_throws_on_register(): void {
		$kernel = new Mcpwp_Kernel();
		$kernel->register_module( $this->manifest( array( 'id' => 'dup' ) ) );

		$this->expectException( InvalidArgumentException::class );
		$kernel->register_module( $this->manifest( array( 'id' => 'dup' ) ) );
	}

	public function test_pro_module_skipped_on_free_tier(): void {
		$booted = array();
		$kernel = new Mcpwp_Kernel();
		// Default tier resolver returns 'free'.
		$kernel->register_module(
			$this->manifest(
				array(
					'id'   => 'pro-feature',
					'tier' => 'pro',
					'boot' => function () use ( &$booted ) {
						$booted[] = 'pro-feature';
					},
				)
			)
		);

		$kernel->boot();

		$this->assertSame( array(), $booted );
		$this->assertSame( array( 'pro-feature' ), $kernel->get_skipped() );
	}

	public function test_pro_module_booted_on_pro_tier(): void {
		$booted = array();
		$kernel = new Mcpwp_Kernel();
		$kernel->set_tier_resolver(
			function () {
				return 'pro';
			}
		);
		$kernel->register_module(
			$this->manifest(
				array(
					'id'   => 'pro-feature',
					'tier' => 'pro',
					'boot' => function () use ( &$booted ) {
						$booted[] = 'pro-feature';
					},
				)
			)
		);

		$kernel->boot();

		$this->assertSame( array( 'pro-feature' ), $booted );
		$this->assertSame( array(), $kernel->get_skipped() );
	}

	public function test_module_skipped_when_depends_on_unsatisfied(): void {
		$kernel = new Mcpwp_Kernel();
		// Default plugin checker returns false for everything.
		$kernel->register_module(
			$this->manifest(
				array(
					'id'         => 'woo',
					'depends_on' => array( 'woocommerce/woocommerce.php' ),
				)
			)
		);

		$kernel->boot();

		$this->assertSame( array( 'woo' ), $kernel->get_skipped() );
	}

	public function test_module_booted_when_depends_on_satisfied(): void {
		$kernel = new Mcpwp_Kernel();
		$kernel->set_plugin_checker(
			function ( $key ) {
				return 'woocommerce/woocommerce.php' === $key;
			}
		);
		$kernel->register_module(
			$this->manifest(
				array(
					'id'         => 'woo',
					'depends_on' => array( 'woocommerce/woocommerce.php' ),
				)
			)
		);

		$kernel->boot();

		$this->assertSame( array(), $kernel->get_skipped() );
	}

	public function test_services_registered_into_container_and_lazy(): void {
		$built  = 0;
		$kernel = new Mcpwp_Kernel();
		$kernel->register_module(
			$this->manifest(
				array(
					'id'       => 'svc-mod',
					'services' => array(
						'thing' => function () use ( &$built ) {
							$built++;
							return 'the-thing';
						},
					),
				)
			)
		);

		$kernel->boot();

		// Registered but not yet built (lazy).
		$this->assertTrue( $kernel->container()->has( 'thing' ) );
		$this->assertSame( 0, $built );

		$this->assertSame( 'the-thing', $kernel->container()->get( 'thing' ) );
		$this->assertSame( 1, $built );
	}

	public function test_skipped_module_services_not_registered(): void {
		$kernel = new Mcpwp_Kernel();
		$kernel->register_module(
			$this->manifest(
				array(
					'id'       => 'pro-svc',
					'tier'     => 'pro',
					'services' => array(
						'pro.thing' => function () {
							return 'x';
						},
					),
				)
			)
		);

		$kernel->boot();

		$this->assertFalse( $kernel->container()->has( 'pro.thing' ) );
	}

	public function test_tools_routes_admin_collected_from_booted_modules(): void {
		$kernel = new Mcpwp_Kernel();
		$kernel->register_module(
			$this->manifest(
				array(
					'id'     => 'a',
					'tools'  => array( 'tool_a' ),
					'routes' => array( 'Route_A' ),
					'admin'  => array( 'Page_A' ),
				)
			)
		);
		$kernel->register_module(
			$this->manifest(
				array(
					'id'     => 'b',
					'tools'  => array( 'tool_b1', 'tool_b2' ),
				)
			)
		);

		$kernel->boot();

		$this->assertSame( array( 'tool_a', 'tool_b1', 'tool_b2' ), $kernel->get_tools() );
		$this->assertSame( array( 'Route_A' ), $kernel->get_routes() );
		$this->assertSame( array( 'Page_A' ), $kernel->get_admin_pages() );
	}

	public function test_boot_is_idempotent(): void {
		$count  = 0;
		$kernel = new Mcpwp_Kernel();
		$kernel->register_module(
			$this->manifest(
				array(
					'id'   => 'once',
					'boot' => function () use ( &$count ) {
						$count++;
					},
				)
			)
		);

		$kernel->boot();
		$kernel->boot();

		$this->assertSame( 1, $count );
		$this->assertTrue( $kernel->is_booted() );
	}

	public function test_register_from_array_and_module_instance(): void {
		$kernel = new Mcpwp_Kernel();
		$kernel->register_module( array( 'id' => 'from-array' ) );
		$kernel->register_module( new Mcpwp_Legacy_Core_Module() );

		$kernel->boot();

		$this->assertTrue( $kernel->registry()->has( 'from-array' ) );
		$this->assertTrue( $kernel->registry()->has( 'legacy-core' ) );
		$this->assertSame( array(), $kernel->get_skipped() );
	}

	public function test_legacy_core_module_boot_is_noop(): void {
		$kernel = new Mcpwp_Kernel();
		$kernel->register_module( new Mcpwp_Legacy_Core_Module() );
		$kernel->boot();

		// No tools/routes/admin/services contributed by the placeholder.
		$this->assertSame( array(), $kernel->get_tools() );
		$this->assertSame( array(), $kernel->get_routes() );
		$this->assertSame( array(), $kernel->get_admin_pages() );
		$this->assertSame( array(), $kernel->container()->ids() );
	}

	public function test_unusable_module_value_throws(): void {
		$kernel = new Mcpwp_Kernel();
		$this->expectException( InvalidArgumentException::class );
		$kernel->register_module( 42 );
	}
}
