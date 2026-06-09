<?php
/**
 * Legacy-core module — placeholder for the not-yet-carved monolith.
 *
 * In v5.0-a the kernel does not own the live boot path; the existing
 * `mcpwp_load_plugin()` still loads and wires everything. This module is the
 * seam where that legacy surface will be represented once the kernel enters the
 * boot path (v5.0-b onward). Today its boot is intentionally a no-op so that
 * registering it with the kernel changes nothing at runtime.
 *
 * As real modules are carved out (site/discovery, elementor, tools, admin, …)
 * their responsibilities move OUT of this legacy module and into their own,
 * until legacy-core is empty and removed.
 *
 * @package MCPWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents the as-yet-unmodularized core as a single module.
 */
class Mcpwp_Legacy_Core_Module implements Mcpwp_Module {

	/**
	 * @return Mcpwp_Module_Manifest
	 */
	public function manifest() {
		return Mcpwp_Module_Manifest::from_array(
			array(
				'id'      => 'legacy-core',
				'version' => defined( 'MCPWP_VERSION' ) ? MCPWP_VERSION : '0.0.0',
				'tier'    => 'free',
				// No-op in v5.0-a: the legacy bootstrap still owns the live path.
				'boot'    => function ( $kernel ) {
					unset( $kernel );
				},
			)
		);
	}
}
