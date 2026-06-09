<?php
/**
 * Module interface — for class-based modules.
 *
 * A module may be expressed two ways:
 *  1. A `module.php` that returns an array or a Mcpwp_Module_Manifest, or
 *  2. A class implementing this interface (handy when the module needs its own
 *     state or helper methods to build its manifest).
 *
 * @package MCPWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anything the kernel can register as a module.
 */
interface Mcpwp_Module {

	/**
	 * The module's manifest.
	 *
	 * @return Mcpwp_Module_Manifest
	 */
	public function manifest();
}
