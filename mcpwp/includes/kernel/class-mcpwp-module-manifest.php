<?php
/**
 * Module manifest — the capability descriptor each module declares.
 *
 * Mirrors inkwell's PluginManifest shape (name/version/requires/tier/services/
 * tools/routes/admin/boot) in PHP. A module is a directory with a `module.php`
 * that returns one of these (or an array this builds from). The kernel never
 * imports a module; it only reads manifests.
 *
 * @package MCPWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable-ish value object describing one module.
 */
class Mcpwp_Module_Manifest {

	/** @var string Unique module id (required). */
	public $id;

	/** @var string Version string. */
	public $version;

	/** @var string[] Module ids this module must load after. */
	public $requires;

	/** @var string 'free' | 'pro'. */
	public $tier;

	/**
	 * WordPress plugin basenames (or other gate keys) that must be active for
	 * this module to boot. Unsatisfied → module is skipped gracefully.
	 *
	 * @var string[]
	 */
	public $depends_on;

	/** @var array<string, callable> Service id => lazy factory. */
	public $services;

	/** @var array Tool providers (McpToolDef-shaped or class names). */
	public $tools;

	/** @var array REST controller class names / providers. */
	public $routes;

	/** @var array Admin page providers. */
	public $admin;

	/** @var callable|null Called with the kernel after registration. */
	public $boot;

	/**
	 * Build + validate a manifest from a plain array.
	 *
	 * @param array $data Manifest fields.
	 * @return self
	 * @throws InvalidArgumentException When required fields are missing/invalid.
	 */
	public static function from_array( array $data ) {
		if ( empty( $data['id'] ) || ! is_string( $data['id'] ) ) {
			throw new InvalidArgumentException( 'Module manifest requires a non-empty string "id".' );
		}

		$tier = isset( $data['tier'] ) ? $data['tier'] : 'free';
		if ( 'free' !== $tier && 'pro' !== $tier ) {
			throw new InvalidArgumentException( 'Module "' . $data['id'] . '" tier must be "free" or "pro".' );
		}

		if ( isset( $data['boot'] ) && null !== $data['boot'] && ! is_callable( $data['boot'] ) ) {
			throw new InvalidArgumentException( 'Module "' . $data['id'] . '" boot must be callable or null.' );
		}

		$manifest             = new self();
		$manifest->id         = $data['id'];
		$manifest->version    = isset( $data['version'] ) ? (string) $data['version'] : '0.0.0';
		$manifest->requires   = self::string_list( $data, 'requires' );
		$manifest->tier       = $tier;
		$manifest->depends_on = self::string_list( $data, 'depends_on' );
		$manifest->services   = isset( $data['services'] ) && is_array( $data['services'] ) ? $data['services'] : array();
		$manifest->tools      = isset( $data['tools'] ) && is_array( $data['tools'] ) ? $data['tools'] : array();
		$manifest->routes     = isset( $data['routes'] ) && is_array( $data['routes'] ) ? $data['routes'] : array();
		$manifest->admin      = isset( $data['admin'] ) && is_array( $data['admin'] ) ? $data['admin'] : array();
		$manifest->boot       = isset( $data['boot'] ) ? $data['boot'] : null;

		return $manifest;
	}

	/**
	 * Coerce a manifest field to a list of strings.
	 *
	 * @param array  $data Source.
	 * @param string $key  Field.
	 * @return string[]
	 */
	private static function string_list( array $data, $key ) {
		if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'strval', $data[ $key ] ) ) );
	}
}
