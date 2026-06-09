<?php
/**
 * Service container — lazy array-of-closures dependency injection.
 *
 * Mirrors the inkwell/SOS "service" idea in the idiom that fits a single PHP
 * process: a map of id => factory closure, each resolved at most once and
 * memoized. No reflection, no autowiring — modules declare what they provide
 * and how to build it; consumers resolve by id.
 *
 * @package MCPWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal lazy service container.
 */
class Mcpwp_Container {

	/**
	 * Registered factories, keyed by service id.
	 *
	 * @var array<string, callable>
	 */
	private $factories = array();

	/**
	 * Resolved (memoized) instances, keyed by service id.
	 *
	 * @var array<string, mixed>
	 */
	private $instances = array();

	/**
	 * Register a service factory. Re-registering an id replaces the factory and
	 * clears any previously-resolved instance.
	 *
	 * @param string   $id      Service id.
	 * @param callable $factory Receives the container, returns the service.
	 * @return void
	 */
	public function set( $id, callable $factory ) {
		$this->factories[ $id ] = $factory;
		unset( $this->instances[ $id ] );
	}

	/**
	 * Whether a service id is registered.
	 *
	 * @param string $id Service id.
	 * @return bool
	 */
	public function has( $id ) {
		return isset( $this->factories[ $id ] );
	}

	/**
	 * Resolve a service. Built once, then memoized.
	 *
	 * @param string $id Service id.
	 * @return mixed
	 * @throws InvalidArgumentException When the id is not registered.
	 */
	public function get( $id ) {
		if ( array_key_exists( $id, $this->instances ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new InvalidArgumentException( 'Unknown service: ' . $id );
		}

		$this->instances[ $id ] = call_user_func( $this->factories[ $id ], $this );

		return $this->instances[ $id ];
	}

	/**
	 * All registered service ids.
	 *
	 * @return string[]
	 */
	public function ids() {
		return array_keys( $this->factories );
	}
}
