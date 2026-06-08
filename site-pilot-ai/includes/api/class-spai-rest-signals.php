<?php
/**
 * REST endpoints for proactive site signals.
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles /signals/* REST routes.
 */
class Spai_REST_Signals extends Spai_REST_API {

	public function register_routes() {
		// Get current signal feed.
		register_rest_route(
			$this->namespace,
			'/signals',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_signals' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'types' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => 'Comma-separated signal types to filter.',
					),
					'since' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => 'ISO 8601 timestamp — return signals detected after this time.',
					),
					'limit' => array(
						'type'              => 'integer',
						'default'           => 50,
						'minimum'           => 1,
						'maximum'           => 200,
						'sanitize_callback' => 'absint',
					),
					'refresh' => array(
						'type'    => 'boolean',
						'default' => false,
						'description' => 'Recompute signals before returning.',
					),
				),
			)
		);

		// Trigger recompute.
		register_rest_route(
			$this->namespace,
			'/signals/refresh',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'refresh_signals' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'types' => array(
						'type'        => 'string',
						'default'     => '',
						'description' => 'Comma-separated signal types to recompute (empty = all).',
					),
				),
			)
		);

		// Get/update signal settings.
		register_rest_route(
			$this->namespace,
			'/signals/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	public function get_signals( WP_REST_Request $request ): WP_REST_Response {
		if ( $request->get_param( 'refresh' ) ) {
			Spai_Signals::compute();
		}

		$types = array_filter( array_map( 'trim', explode( ',', $request->get_param( 'types' ) ?? '' ) ) );
		$since = $request->get_param( 'since' ) ?? '';
		$limit = (int) $request->get_param( 'limit' );

		$signals = Spai_Signals::get_signals( $types, $since, $limit );

		$counts = array();
		foreach ( $signals as $s ) {
			$t = $s['type'] ?? 'unknown';
			$counts[ $t ] = ( $counts[ $t ] ?? 0 ) + 1;
		}

		return new WP_REST_Response(
			array(
				'signals'    => $signals,
				'count'      => count( $signals ),
				'by_type'    => $counts,
				'signal_types' => Spai_Signals::SIGNAL_TYPES,
			),
			200
		);
	}

	public function refresh_signals( WP_REST_Request $request ): WP_REST_Response {
		$types = array_filter( array_map( 'trim', explode( ',', $request->get_param( 'types' ) ?? '' ) ) );
		$types = array_values( array_intersect( $types, Spai_Signals::SIGNAL_TYPES ) );

		$signals = Spai_Signals::compute( $types );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'computed' => count( $signals ),
				'signals'  => $signals,
			),
			200
		);
	}

	public function get_settings( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( Spai_Signals::get_settings(), 200 );
	}

	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid JSON body' ), 400 );
		}
		Spai_Signals::save_settings( $body );
		return new WP_REST_Response( array( 'success' => true, 'settings' => Spai_Signals::get_settings() ), 200 );
	}
}
