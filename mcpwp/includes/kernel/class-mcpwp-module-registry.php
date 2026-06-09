<?php
/**
 * Module registry — collects manifests and orders them by dependency.
 *
 * Holds the set of registered manifests and produces a load order that
 * respects each module's `requires`. SOS gives us no ordering to copy, so this
 * is a small, explicit Kahn topological sort with cycle + missing-dependency
 * detection.
 *
 * @package MCPWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of module manifests.
 */
class Mcpwp_Module_Registry {

	/**
	 * Registered manifests, keyed by module id, in insertion order.
	 *
	 * @var array<string, Mcpwp_Module_Manifest>
	 */
	private $modules = array();

	/**
	 * Register a manifest.
	 *
	 * @param Mcpwp_Module_Manifest $manifest Manifest.
	 * @return void
	 * @throws InvalidArgumentException When a module id is registered twice.
	 */
	public function register( Mcpwp_Module_Manifest $manifest ) {
		if ( isset( $this->modules[ $manifest->id ] ) ) {
			throw new InvalidArgumentException( 'Duplicate module id: ' . $manifest->id );
		}
		$this->modules[ $manifest->id ] = $manifest;
	}

	/**
	 * Whether a module id is registered.
	 *
	 * @param string $id Module id.
	 * @return bool
	 */
	public function has( $id ) {
		return isset( $this->modules[ $id ] );
	}

	/**
	 * Get a manifest by id.
	 *
	 * @param string $id Module id.
	 * @return Mcpwp_Module_Manifest|null
	 */
	public function get( $id ) {
		return isset( $this->modules[ $id ] ) ? $this->modules[ $id ] : null;
	}

	/**
	 * All manifests in insertion order.
	 *
	 * @return Mcpwp_Module_Manifest[]
	 */
	public function all() {
		return array_values( $this->modules );
	}

	/**
	 * Manifests ordered so every module appears after the modules it requires.
	 *
	 * Deterministic: among modules whose dependencies are already satisfied,
	 * insertion order is preserved.
	 *
	 * @return Mcpwp_Module_Manifest[]
	 * @throws RuntimeException On a missing dependency or a dependency cycle.
	 */
	public function in_dependency_order() {
		// Build in-degree + adjacency over the requires edges.
		$in_degree = array();
		foreach ( $this->modules as $id => $manifest ) {
			$in_degree[ $id ] = 0;
		}

		foreach ( $this->modules as $id => $manifest ) {
			foreach ( $manifest->requires as $dep ) {
				if ( ! isset( $this->modules[ $dep ] ) ) {
					throw new RuntimeException(
						'Module "' . $id . '" requires unknown module "' . $dep . '".'
					);
				}
				$in_degree[ $id ]++;
			}
		}

		// Queue modules with no unmet requirements, preserving insertion order.
		$ready = array();
		foreach ( $this->modules as $id => $manifest ) {
			if ( 0 === $in_degree[ $id ] ) {
				$ready[] = $id;
			}
		}

		$ordered = array();
		while ( ! empty( $ready ) ) {
			$id        = array_shift( $ready );
			$ordered[] = $this->modules[ $id ];

			// Anything that required $id now has one fewer unmet requirement.
			foreach ( $this->modules as $other_id => $manifest ) {
				if ( in_array( $id, $manifest->requires, true ) ) {
					$in_degree[ $other_id ]--;
					if ( 0 === $in_degree[ $other_id ] ) {
						$ready[] = $other_id;
					}
				}
			}
		}

		if ( count( $ordered ) !== count( $this->modules ) ) {
			throw new RuntimeException( 'Module dependency cycle detected.' );
		}

		return $ordered;
	}
}
