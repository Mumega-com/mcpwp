<?php
/**
 * Approval, diff, apply, and rollback store.
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central approval request store for agent mutations.
 */
class Spai_Approvals {

	/**
	 * Option name for stored approval requests.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'spai_approval_requests';

	/**
	 * Maximum stored requests.
	 *
	 * @var int
	 */
	const MAX_ITEMS = 200;

	/**
	 * Create a post-content approval request.
	 *
	 * @param int    $post_id      Post ID.
	 * @param string $after_content Proposed content.
	 * @param array  $args         Request metadata.
	 * @return array|WP_Error Approval request.
	 */
	public static function create_post_content_request( $post_id, $after_content, $args = array() ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'not_found', __( 'Post not found.', 'mumega-mcp' ), array( 'status' => 404 ) );
		}

		$before_content = (string) $post->post_content;
		$after_content  = (string) $after_content;
		$id             = self::generate_id();
		$now            = gmdate( 'c' );

		$request = array(
			'id'          => $id,
			'status'      => 'pending',
			'action'      => 'post_content_update',
			'title'       => isset( $args['title'] ) ? sanitize_text_field( (string) $args['title'] ) : sprintf( 'Update content for #%d', (int) $post_id ),
			'note'        => isset( $args['note'] ) ? sanitize_textarea_field( (string) $args['note'] ) : '',
			'tool'        => isset( $args['tool'] ) ? sanitize_key( (string) $args['tool'] ) : '',
			'resource'    => array(
				'type'   => 'post',
				'id'     => (int) $post_id,
				'title'  => get_the_title( $post ),
				'status' => $post->post_status,
				'url'    => get_permalink( $post ),
			),
			'diff'        => self::build_content_diff( $before_content, $after_content ),
			'payload'     => array(
				'after_content' => $after_content,
			),
			'rollback'    => array(
				'before_content' => $before_content,
				'before_hash'    => hash( 'sha256', $before_content ),
			),
			'metadata'    => isset( $args['metadata'] ) && is_array( $args['metadata'] ) ? $args['metadata'] : array(),
			'created_at'  => $now,
			'updated_at'  => $now,
			'approved_at' => null,
			'applied_at'  => null,
			'rejected_at' => null,
			'rolled_back_at' => null,
		);

		self::upsert( $request );

		return self::public_record( $request );
	}

	/**
	 * List approval requests.
	 *
	 * @param string $status Status filter.
	 * @param int    $limit  Maximum records.
	 * @return array Requests.
	 */
	public static function list_requests( $status = '', $limit = 50 ) {
		$items  = array_values( self::get_all() );
		$status = sanitize_key( (string) $status );
		$limit  = min( 100, max( 1, (int) $limit ) );

		usort(
			$items,
			function ( $a, $b ) {
				return strcmp( isset( $b['created_at'] ) ? $b['created_at'] : '', isset( $a['created_at'] ) ? $a['created_at'] : '' );
			}
		);

		if ( '' !== $status ) {
			$items = array_values(
				array_filter(
					$items,
					function ( $item ) use ( $status ) {
						return isset( $item['status'] ) && $status === $item['status'];
					}
				)
			);
		}

		return array_map( array( __CLASS__, 'public_record' ), array_slice( $items, 0, $limit ) );
	}

	/**
	 * Get one request.
	 *
	 * @param string $id Request ID.
	 * @return array|WP_Error Request.
	 */
	public static function get_request( $id ) {
		$items = self::get_all();
		$id    = sanitize_key( (string) $id );

		if ( empty( $items[ $id ] ) ) {
			return new WP_Error( 'approval_not_found', __( 'Approval request not found.', 'mumega-mcp' ), array( 'status' => 404 ) );
		}

		return self::public_record( $items[ $id ] );
	}

	/**
	 * Approve a request.
	 *
	 * @param string $id   Request ID.
	 * @param string $note Approval note.
	 * @return array|WP_Error Request.
	 */
	public static function approve_request( $id, $note = '' ) {
		return self::transition_request( $id, 'approved', 'approved_at', $note );
	}

	/**
	 * Reject a request.
	 *
	 * @param string $id   Request ID.
	 * @param string $note Rejection note.
	 * @return array|WP_Error Request.
	 */
	public static function reject_request( $id, $note = '' ) {
		return self::transition_request( $id, 'rejected', 'rejected_at', $note );
	}

	/**
	 * Apply an approved request.
	 *
	 * @param string $id Request ID.
	 * @return array|WP_Error Request.
	 */
	public static function apply_request( $id ) {
		$request = self::get_private_request( $id );
		if ( is_wp_error( $request ) ) {
			return $request;
		}

		if ( 'approved' !== $request['status'] ) {
			return new WP_Error( 'approval_not_ready', __( 'Approval request must be approved before it can be applied.', 'mumega-mcp' ), array( 'status' => 409 ) );
		}

		$result = self::apply_payload( $request, 'payload' );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$request['status']     = 'applied';
		$request['applied_at'] = gmdate( 'c' );
		$request['updated_at'] = $request['applied_at'];
		self::upsert( $request );

		return self::public_record( $request );
	}

	/**
	 * Roll back an applied request.
	 *
	 * @param string $id Request ID.
	 * @return array|WP_Error Request.
	 */
	public static function rollback_request( $id ) {
		$request = self::get_private_request( $id );
		if ( is_wp_error( $request ) ) {
			return $request;
		}

		if ( 'applied' !== $request['status'] ) {
			return new WP_Error( 'approval_not_applied', __( 'Only applied approval requests can be rolled back.', 'mumega-mcp' ), array( 'status' => 409 ) );
		}

		$result = self::apply_payload( $request, 'rollback' );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$request['status']         = 'rolled_back';
		$request['rolled_back_at'] = gmdate( 'c' );
		$request['updated_at']     = $request['rolled_back_at'];
		self::upsert( $request );

		return self::public_record( $request );
	}

	/**
	 * Build content diff metadata.
	 *
	 * @param string $before Before content.
	 * @param string $after  After content.
	 * @return array Diff data.
	 */
	private static function build_content_diff( $before, $after ) {
		return array(
			'type'           => 'content',
			'changed'        => $before !== $after,
			'before_hash'    => hash( 'sha256', $before ),
			'after_hash'     => hash( 'sha256', $after ),
			'before_length'  => strlen( $before ),
			'after_length'   => strlen( $after ),
			'before_excerpt' => wp_trim_words( wp_strip_all_tags( $before ), 40 ),
			'after_excerpt'  => wp_trim_words( wp_strip_all_tags( $after ), 40 ),
		);
	}

	/**
	 * Transition request status.
	 *
	 * @param string $id         Request ID.
	 * @param string $status     New status.
	 * @param string $time_field Timestamp field.
	 * @param string $note       Human note.
	 * @return array|WP_Error Request.
	 */
	private static function transition_request( $id, $status, $time_field, $note ) {
		$request = self::get_private_request( $id );
		if ( is_wp_error( $request ) ) {
			return $request;
		}

		if ( ! in_array( $request['status'], array( 'pending', 'approved' ), true ) ) {
			return new WP_Error( 'approval_closed', __( 'Approval request is already closed.', 'mumega-mcp' ), array( 'status' => 409 ) );
		}

		$now                     = gmdate( 'c' );
		$request['status']       = $status;
		$request[ $time_field ]  = $now;
		$request['updated_at']   = $now;
		$request['review_note']  = sanitize_textarea_field( (string) $note );

		self::upsert( $request );

		return self::public_record( $request );
	}

	/**
	 * Apply payload or rollback data.
	 *
	 * @param array  $request Request record.
	 * @param string $field   Data field.
	 * @return true|WP_Error True on success.
	 */
	private static function apply_payload( $request, $field ) {
		if ( 'post_content_update' !== $request['action'] || empty( $request['resource']['id'] ) ) {
			return new WP_Error( 'unsupported_approval_action', __( 'Approval action is not supported yet.', 'mumega-mcp' ), array( 'status' => 400 ) );
		}

		$post_id = (int) $request['resource']['id'];
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'not_found', __( 'Post not found.', 'mumega-mcp' ), array( 'status' => 404 ) );
		}

		$current_hash  = hash( 'sha256', (string) $post->post_content );
		$expected_hash = 'rollback' === $field
			? ( isset( $request['diff']['after_hash'] ) ? (string) $request['diff']['after_hash'] : '' )
			: ( isset( $request['rollback']['before_hash'] ) ? (string) $request['rollback']['before_hash'] : '' );

		if ( '' !== $expected_hash && $current_hash !== $expected_hash ) {
			return new WP_Error( 'approval_content_conflict', __( 'Post content changed after this approval request was created.', 'mumega-mcp' ), array( 'status' => 409 ) );
		}

		$content = 'rollback' === $field
			? ( isset( $request['rollback']['before_content'] ) ? (string) $request['rollback']['before_content'] : '' )
			: ( isset( $request['payload']['after_content'] ) ? (string) $request['payload']['after_content'] : '' );

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Get private request.
	 *
	 * @param string $id Request ID.
	 * @return array|WP_Error Request.
	 */
	private static function get_private_request( $id ) {
		$items = self::get_all();
		$id    = sanitize_key( (string) $id );

		if ( empty( $items[ $id ] ) ) {
			return new WP_Error( 'approval_not_found', __( 'Approval request not found.', 'mumega-mcp' ), array( 'status' => 404 ) );
		}

		return $items[ $id ];
	}

	/**
	 * Remove private payload content before returning a record.
	 *
	 * @param array $request Request.
	 * @return array Public request.
	 */
	private static function public_record( $request ) {
		unset( $request['payload']['after_content'], $request['rollback']['before_content'] );
		return $request;
	}

	/**
	 * Upsert request.
	 *
	 * @param array $request Request.
	 * @return void
	 */
	private static function upsert( $request ) {
		$items = self::get_all();
		$items[ $request['id'] ] = $request;

		uasort(
			$items,
			function ( $a, $b ) {
				return strcmp( isset( $b['created_at'] ) ? $b['created_at'] : '', isset( $a['created_at'] ) ? $a['created_at'] : '' );
			}
		);

		$items = array_slice( $items, 0, self::MAX_ITEMS, true );
		update_option( self::OPTION_NAME, $items, false );
	}

	/**
	 * Get all requests.
	 *
	 * @return array Requests.
	 */
	private static function get_all() {
		$items = get_option( self::OPTION_NAME, array() );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Generate request ID.
	 *
	 * @return string ID.
	 */
	private static function generate_id() {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return 'apr_' . str_replace( '-', '', wp_generate_uuid4() );
		}

		return 'apr_' . bin2hex( random_bytes( 12 ) );
	}
}
