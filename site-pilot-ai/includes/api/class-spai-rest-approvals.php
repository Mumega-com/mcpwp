<?php
/**
 * REST controller for approval requests.
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Approval request REST endpoints.
 */
class Spai_REST_Approvals extends Spai_REST_API {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/approvals',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_approvals' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'status' => array(
							'description' => __( 'Optional approval status filter.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'limit'  => array(
							'description'       => __( 'Maximum approvals to return.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 50,
							'minimum'           => 1,
							'maximum'           => 100,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/approvals/(?P<id>[A-Za-z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_approval' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		foreach ( array( 'approve', 'reject', 'apply', 'rollback' ) as $action ) {
			register_rest_route(
				$this->namespace,
				'/approvals/(?P<id>[A-Za-z0-9_-]+)/' . $action,
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, $action . '_approval' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'note' => array(
								'description' => __( 'Optional human review note.', 'mumega-mcp' ),
								'type'        => 'string',
							),
						),
					),
				)
			);
		}
	}

	/**
	 * List approvals.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response Response.
	 */
	public function list_approvals( $request ) {
		$this->log_activity( 'list_approvals', $request );

		return $this->success_response(
			array(
				'approvals' => Spai_Approvals::list_requests(
					(string) $request->get_param( 'status' ),
					(int) $request->get_param( 'limit' )
				),
			)
		);
	}

	/**
	 * Get approval.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function get_approval( $request ) {
		$result = Spai_Approvals::get_request( $request->get_param( 'id' ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->log_activity( 'get_approval', $request, array( 'approval_id' => $result['id'] ) );
		return $this->success_response( $result );
	}

	/**
	 * Approve request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function approve_approval( $request ) {
		return $this->approval_transition_response( $request, 'approve_request', 'approve_approval' );
	}

	/**
	 * Reject request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function reject_approval( $request ) {
		return $this->approval_transition_response( $request, 'reject_request', 'reject_approval' );
	}

	/**
	 * Apply request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function apply_approval( $request ) {
		$result = Spai_Approvals::apply_request( $request->get_param( 'id' ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->log_activity( 'apply_approval', $request, array( 'approval_id' => $result['id'] ) );
		return $this->success_response( $result );
	}

	/**
	 * Roll back request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function rollback_approval( $request ) {
		$result = Spai_Approvals::rollback_request( $request->get_param( 'id' ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->log_activity( 'rollback_approval', $request, array( 'approval_id' => $result['id'] ) );
		return $this->success_response( $result );
	}

	/**
	 * Run approve/reject transition.
	 *
	 * @param WP_REST_Request $request  Request.
	 * @param string          $method   Spai_Approvals method.
	 * @param string          $activity Activity name.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	private function approval_transition_response( $request, $method, $activity ) {
		$result = call_user_func(
			array( 'Spai_Approvals', $method ),
			$request->get_param( 'id' ),
			(string) $request->get_param( 'note' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->log_activity( $activity, $request, array( 'approval_id' => $result['id'] ) );
		return $this->success_response( $result );
	}
}
