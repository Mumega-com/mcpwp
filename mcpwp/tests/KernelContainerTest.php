<?php
/**
 * Tests for Mcpwp_Container.
 *
 * @package MCPWP
 */

use PHPUnit\Framework\TestCase;

final class KernelContainerTest extends TestCase {

	public function test_resolves_registered_factory(): void {
		$c = new Mcpwp_Container();
		$c->set( 'greeter', function () {
			return 'hello';
		} );

		$this->assertTrue( $c->has( 'greeter' ) );
		$this->assertSame( 'hello', $c->get( 'greeter' ) );
	}

	public function test_factory_is_memoized(): void {
		$c     = new Mcpwp_Container();
		$count = 0;
		$c->set( 'obj', function () use ( &$count ) {
			$count++;
			return new stdClass();
		} );

		$first  = $c->get( 'obj' );
		$second = $c->get( 'obj' );

		$this->assertSame( $first, $second );
		$this->assertSame( 1, $count, 'Factory should run exactly once.' );
	}

	public function test_factory_receives_container_for_dependencies(): void {
		$c = new Mcpwp_Container();
		$c->set( 'a', function () {
			return 2;
		} );
		$c->set( 'b', function ( $container ) {
			return $container->get( 'a' ) * 21;
		} );

		$this->assertSame( 42, $c->get( 'b' ) );
	}

	public function test_unknown_service_throws(): void {
		$c = new Mcpwp_Container();
		$this->expectException( InvalidArgumentException::class );
		$c->get( 'missing' );
	}

	public function test_reset_via_reregister_clears_instance(): void {
		$c = new Mcpwp_Container();
		$c->set( 'x', function () {
			return 1;
		} );
		$this->assertSame( 1, $c->get( 'x' ) );

		$c->set( 'x', function () {
			return 2;
		} );
		$this->assertSame( 2, $c->get( 'x' ) );
	}

	public function test_null_value_is_memoized_not_rebuilt(): void {
		$c     = new Mcpwp_Container();
		$count = 0;
		$c->set( 'n', function () use ( &$count ) {
			$count++;
			return null;
		} );

		$this->assertNull( $c->get( 'n' ) );
		$this->assertNull( $c->get( 'n' ) );
		$this->assertSame( 1, $count, 'A null result must still be cached.' );
	}
}
