<?php
/**
 * Custom Tool Registry — spai_register_tools hook API
 *
 * Lets third-party plugins register MCP tools without extending a PHP class.
 * Tools are collected via the `spai_register_tools` WordPress filter.
 *
 * Usage in any plugin or theme:
 *
 *     add_filter( 'spai_register_tools', function( $tools ) {
 *         $tools[] = [
 *             'name'        => 'digid_list_listings',
 *             'description' => 'List active real estate listings.',
 *             'category'    => 'listings',
 *             'rest_path'   => '/digid/v1/listings',
 *             'method'      => 'GET',
 *             'input_props' => [
 *                 'per_page' => [ 'type' => 'integer', 'description' => 'Items per page.' ],
 *                 'status'   => [ 'type' => 'string',  'description' => 'Filter by status.' ],
 *             ],
 *         ];
 *         return $tools;
 *     } );
 *
 * Tool definition keys:
 *   name        (string, required) — unique tool name, use plugin_prefix_action format
 *   description (string, required) — one sentence, plain English, no jargon
 *   rest_path   (string, required) — full WP REST route, e.g. '/digid/v1/listings'
 *                                    or MCPWP-relative, e.g. '/my-endpoint' (gets /site-pilot-ai/v1 prepended)
 *   method      (string)          — HTTP method: GET, POST, PUT, DELETE (default: GET)
 *   category    (string)          — tool category slug for scope/toggle (default: 'custom')
 *   input_props (array)           — tool parameters, same format as Spai_MCP_Tool_Registry::define_tool()
 *   destructive (bool)            — hint: tool deletes or irreversibly modifies data (default: false)
 *   open_world  (bool)            — hint: tool calls external services (default: false)
 *   param_remap (array)           — map MCP param names to REST param names if they differ
 *
 * @package MumegaMCP
 * @since   2.8.49
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collects and exposes tools registered via the spai_register_tools filter.
 */
class Spai_Custom_Tool_Registry extends Spai_MCP_Tool_Registry {

	/**
	 * Resolved tool registrations cache.
	 *
	 * @var array[]|null
	 */
	private $registrations = null;

	/**
	 * Resolve and cache tool registrations from the filter.
	 *
	 * @return array[] Raw registration arrays from spai_register_tools filter.
	 */
	private function get_registrations() {
		if ( $this->registrations === null ) {
			/**
			 * Register custom MCP tools.
			 *
			 * Third-party plugins append tool definition arrays to this filter.
			 * Each definition must include 'name', 'description', and 'rest_path'.
			 *
			 * @param array[] $tools Existing tool registrations (starts empty).
			 * @return array[] Modified tool registrations.
			 */
			$raw = apply_filters( 'spai_register_tools', array() );

			if ( ! is_array( $raw ) ) {
				$raw = array();
			}

			$this->registrations = array_filter(
				$raw,
				function ( $entry ) {
					return is_array( $entry )
						&& ! empty( $entry['name'] )
						&& ! empty( $entry['description'] )
						&& ! empty( $entry['rest_path'] );
				}
			);
		}

		return $this->registrations;
	}

	/**
	 * Build MCP tool definitions from filter registrations.
	 *
	 * @return array Tool definitions.
	 */
	public function get_tools() {
		$tools = array();

		foreach ( $this->get_registrations() as $reg ) {
			$name        = sanitize_key( $reg['name'] );
			$description = sanitize_text_field( $reg['description'] );
			$input_props = isset( $reg['input_props'] ) && is_array( $reg['input_props'] )
				? $reg['input_props']
				: array();

			$tools[] = $this->define_tool( $name, $description, $input_props );
		}

		return $tools;
	}

	/**
	 * Build tool → REST route map from filter registrations.
	 *
	 * Each entry includes 'rest_path' so the dispatcher knows the full route,
	 * bypassing the default '/site-pilot-ai/v1' namespace prepend.
	 *
	 * @return array Tool name → mapping array.
	 */
	public function get_tool_map() {
		$map = array();

		foreach ( $this->get_registrations() as $reg ) {
			$name      = sanitize_key( $reg['name'] );
			$rest_path = $reg['rest_path'];
			$method    = isset( $reg['method'] ) ? strtoupper( $reg['method'] ) : 'GET';

			// Validate method.
			if ( ! in_array( $method, array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
				$method = 'GET';
			}

			$map[ $name ] = array(
				'rest_path'   => $rest_path,
				'route'       => $rest_path,
				'method'      => $method,
				'param_remap' => isset( $reg['param_remap'] ) && is_array( $reg['param_remap'] )
					? $reg['param_remap']
					: array(),
			);
		}

		return $map;
	}

	/**
	 * Build tool → category map from filter registrations.
	 *
	 * @return array Tool name → category slug.
	 */
	public function get_tool_categories() {
		$cats = array();

		foreach ( $this->get_registrations() as $reg ) {
			$name     = sanitize_key( $reg['name'] );
			$category = isset( $reg['category'] ) && is_string( $reg['category'] )
				? sanitize_key( $reg['category'] )
				: 'custom';

			$cats[ $name ] = $category;
		}

		return $cats;
	}

	/**
	 * Collect destructive tool names from registrations.
	 *
	 * @return string[] Tool names flagged as destructive.
	 */
	protected function get_destructive_tools() {
		$destructive = array();

		foreach ( $this->get_registrations() as $reg ) {
			if ( ! empty( $reg['destructive'] ) ) {
				$destructive[] = sanitize_key( $reg['name'] );
			}
		}

		return $destructive;
	}

	/**
	 * Collect open-world tool names from registrations.
	 *
	 * @return string[] Tool names flagged as open_world.
	 */
	protected function get_open_world_tools() {
		$open_world = array();

		foreach ( $this->get_registrations() as $reg ) {
			if ( ! empty( $reg['open_world'] ) ) {
				$open_world[] = sanitize_key( $reg['name'] );
			}
		}

		return $open_world;
	}
}
