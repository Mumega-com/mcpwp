<?php
/**
 * Admin functionality
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class Spai_Admin {

	use Spai_Api_Auth;

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'site-pilot-ai';

	/**
	 * Activity log page slug.
	 *
	 * @var string
	 */
	const ACTIVITY_LOG_PAGE_SLUG = 'site-pilot-ai-activity-log';

	/**
	 * Control room page slug.
	 *
	 * @var string
	 */
	const CONTROL_ROOM_PAGE_SLUG = 'site-pilot-ai-control-room';

	/**
	 * SVG icon for menu (base64 encoded).
	 *
	 * @var string
	 */
	const MENU_ICON = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCI+PHJlY3Qgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIiByeD0iNSIgZmlsbD0iIzBCMTIyMCIvPjxwYXRoIGZpbGw9IiMyRjdDRkYiIGQ9Ik0xMS41IDEuOCA0LjggMTBoNGwtMS4zIDguMiA3LTloLTQuMWwxLjEtNy40WiIvPjxwYXRoIGZpbGw9IiMyN0M0NkEiIGQ9Ik0xMy43IDEyLjhhMi4yIDIuMiAwIDEgMCAwIDQuNCAyLjIgMi4yIDAgMCAwIDAtNC40Wm0wIDEuNGEuOC44IDAgMSAxIDAgMS42LjguOCAwIDAgMSAwLTEuNloiLz48L3N2Zz4=';

	/**
	 * Library page slug.
	 *
	 * @var string
	 */
	const LIBRARY_PAGE_SLUG = 'site-pilot-ai-library';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const SETTINGS_PAGE_SLUG = 'site-pilot-ai-settings';

	/**
	 * Add admin menu - top-level with icon.
	 */
	public function add_admin_menu() {
		// Top-level menu + default submenu (Setup) share the same slug so
		// clicking "MCPWP" always lands on the Setup page.
		add_menu_page(
			__( 'MCPWP', 'mumega-mcp' ),
			__( 'MCPWP', 'mumega-mcp' ),
			'activate_plugins',
			self::PAGE_SLUG,
			array( $this, 'render_setup_page' ),
			self::MENU_ICON,
			80
		);

		// Setup — same slug as parent so it becomes the first visible item.
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Setup', 'mumega-mcp' ),
			__( 'Setup', 'mumega-mcp' ),
			'activate_plugins',
			self::PAGE_SLUG,
			array( $this, 'render_setup_page' )
		);

		// Control Room - human supervision for agent work.
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Control Room', 'mumega-mcp' ),
			__( 'Control Room', 'mumega-mcp' ),
			'activate_plugins',
			self::CONTROL_ROOM_PAGE_SLUG,
			array( $this, 'render_control_room_page' )
		);

		// Chat — AI assistant.
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Chat', 'mumega-mcp' ),
			__( 'Chat', 'mumega-mcp' ),
			'edit_posts',
			'site-pilot-ai-chat',
			array( $this, 'render_chat_page' )
		);

		// Library.
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Library', 'mumega-mcp' ),
			__( 'Library', 'mumega-mcp' ),
			'activate_plugins',
			self::LIBRARY_PAGE_SLUG,
			array( $this, 'render_library_page' )
		);

		// Integrations (already exists).
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Integrations', 'mumega-mcp' ),
			__( 'Integrations', 'mumega-mcp' ),
			'activate_plugins',
			Spai_Integrations_Admin::PAGE_SLUG,
			array( new Spai_Integrations_Admin(), 'render' )
		);

		// Tools (renamed from "MCP Tools").
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Tools', 'mumega-mcp' ),
			__( 'Tools', 'mumega-mcp' ),
			'activate_plugins',
			Spai_Tools_Admin::PAGE_SLUG,
			array( new Spai_Tools_Admin(), 'render' )
		);

		// Settings.
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Settings', 'mumega-mcp' ),
			__( 'Settings', 'mumega-mcp' ),
			'activate_plugins',
			self::SETTINGS_PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);

		// Activity Log.
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Activity Log', 'mumega-mcp' ),
			__( 'Activity Log', 'mumega-mcp' ),
			'activate_plugins',
			self::ACTIVITY_LOG_PAGE_SLUG,
			array( $this, 'render_activity_log_page' )
		);

	}

	/**
	 * Get the current MCPWP admin page slug.
	 *
	 * @return string
	 */
	private function get_current_admin_page_slug() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing value.
		return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	}

	/**
	 * Determine whether the current admin request is an MCPWP screen.
	 *
	 * @return bool
	 */
	private function is_mcpwp_admin_page() {
		$page = $this->get_current_admin_page_slug();

		return in_array(
			$page,
			array(
				self::PAGE_SLUG,
				self::CONTROL_ROOM_PAGE_SLUG,
				'site-pilot-ai-chat',
				self::LIBRARY_PAGE_SLUG,
				Spai_Integrations_Admin::PAGE_SLUG,
				Spai_Tools_Admin::PAGE_SLUG,
				self::SETTINGS_PAGE_SLUG,
				self::ACTIVITY_LOG_PAGE_SLUG,
			),
			true
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_styles( $hook ) {
		if ( ! $this->is_mcpwp_admin_page() ) {
			return;
		}

		wp_enqueue_style(
			'spai-admin',
			SPAI_PLUGIN_URL . 'admin/css/spai-admin.css',
			array(),
			SPAI_VERSION
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * Fires on ALL MCPWP admin pages under one canonical handle ('spai-admin').
	 * All PHP→JS data is passed via wp_localize_script — no inline <script> blocks.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( ! $this->is_mcpwp_admin_page() ) {
			return;
		}

		wp_enqueue_script(
			'spai-admin',
			SPAI_PLUGIN_URL . 'admin/js/spai-admin.js',
			array( 'jquery' ),
			SPAI_VERSION,
			true
		);

		$posthog       = Spai_Integration_Manager::get_instance()->get_posthog_config();
		$posthog_token = $posthog['token'];
		$posthog_host  = $posthog['host'];

		// Integrations nonce only needed on the Integrations page, but generating
		// it on every MCPWP page is harmless and keeps one unified data object.
		wp_localize_script(
			'spai-admin',
			'spaiAdmin',
			array(
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'spai_admin_nonce' ),
				'integrationsNonce'    => wp_create_nonce( 'spai_integrations_nonce' ),
				'toolsNonce'           => wp_create_nonce( 'spai_tools_nonce' ),
				'restUrl'              => rest_url( 'site-pilot-ai/v1/' ),
				'siteUrl'              => site_url(),
				'posthogToken'         => $posthog_token,
				'posthogHost'          => $posthog_host,
				// Category labels for the role-based key UI on the Setup page.
				'catLabels'            => self::get_all_tool_category_labels(),
				'chatGreeting'         => sprintf(
					/* translators: %s: site name */
					__( 'Hi! I can help you manage %s. Try: "Build a services page" or "List all pages" or "Add a testimonials section to the homepage."', 'mumega-mcp' ),
					get_bloginfo( 'name' )
				),
				'streamOk'             => ( function () {
					$manager    = Spai_Integration_Manager::get_instance();
					$chat_model = get_option( 'spai_chat_model', 'auto' );
					$has_openai = ! empty( $manager->get_provider_key( 'openai' ) );
					return ( $has_openai && in_array( $chat_model, array( 'openai', 'auto' ), true ) );
				} )(),
				'strings'              => array(
					'copied'             => __( 'Copied!', 'mumega-mcp' ),
					'copyFailed'         => __( 'Copy failed', 'mumega-mcp' ),
					'confirm'            => __( 'Are you sure you want to regenerate the API key? The old key will stop working immediately.', 'mumega-mcp' ),
					'testing'            => __( 'Testing...', 'mumega-mcp' ),
					'connected'          => __( 'Connected!', 'mumega-mcp' ),
					'testFailed'         => __( 'Connection failed', 'mumega-mcp' ),
					'saving'             => __( 'Saving...', 'mumega-mcp' ),
					'saved'              => __( 'Saved!', 'mumega-mcp' ),
					'saveFailed'         => __( 'Save failed', 'mumega-mcp' ),
					'removing'           => __( 'Removing...', 'mumega-mcp' ),
					'removed'            => __( 'Removed!', 'mumega-mcp' ),
					'requestFailed'      => __( 'Request failed', 'mumega-mcp' ),
					'confirmRemove'      => __( 'Are you sure you want to remove this API key?', 'mumega-mcp' ),
					'fillOneField'       => __( 'Please fill in at least one field.', 'mumega-mcp' ),
					'enterApiKey'        => __( 'Please enter an API key.', 'mumega-mcp' ),
					'revokeKey'          => __( 'Revoke this key?', 'mumega-mcp' ),
					'clearHistory'       => __( 'Clear all chat history?', 'mumega-mcp' ),
					'yesProceed'         => __( 'Yes, proceed', 'mumega-mcp' ),
					'cancel'             => __( 'Cancel', 'mumega-mcp' ),
					'allCategoriesLabel' => __( 'All categories (unrestricted)', 'mumega-mcp' ),
				),
			)
		);
	}

	/**
	 * Handle AJAX test connection.
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'spai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		// Verify REST API is reachable by checking site info directly.
		// We don't use rest_do_request() because the permission_callback
		// requires an API key header which isn't present in admin AJAX.
		$rest_url = rest_url( 'site-pilot-ai/v1/' );

		// Check that the API key exists.
		$stored_key = get_option( 'spai_api_key' );
		if ( empty( $stored_key ) ) {
			wp_send_json_error( array(
				'message' => __( 'No API key configured. Please generate one on the Setup tab.', 'mumega-mcp' ),
			) );
		}

		// Gather site info directly (same data the REST endpoint returns).
		global $wp_version;
		$site_name = get_bloginfo( 'name' );

		wp_send_json_success( array(
			'site_name'      => $site_name,
			'wp_version'     => $wp_version,
			'php_version'    => PHP_VERSION,
			'plugin_version' => SPAI_VERSION,
			'rest_url'       => $rest_url,
		) );
	}

	/**
	 * Handle AJAX dismiss welcome.
	 */
	public function ajax_dismiss_welcome() {
		check_ajax_referer( 'spai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		delete_option( 'spai_first_activation' );
		delete_transient( 'spai_new_api_key' );
		wp_send_json_success();
	}

	/**
	 * Render the Setup page (default landing page).
	 */
	public function render_setup_page() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mumega-mcp' ) );
		}

		// Handle API key actions.
		$new_key = null;
		if ( isset( $_POST['spai_regenerate_key'] ) ) {
			check_admin_referer( 'spai_regenerate_key', 'spai_nonce' );
			$new_key = $this->regenerate_api_key();
			add_settings_error(
				'spai_messages',
				'spai_key_regenerated',
				__( 'API key has been regenerated. Copy it now — it will not be shown again.', 'mumega-mcp' ),
				'updated'
			);
		}

		$new_scoped_key = null;
		if ( isset( $_POST['spai_create_scoped_key'] ) ) {
			check_admin_referer( 'spai_manage_scoped_keys', 'spai_scoped_keys_nonce' );

			$label  = isset( $_POST['spai_scoped_key_label'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_scoped_key_label'] ) ) : '';
			$scopes = isset( $_POST['spai_scoped_key_scopes'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['spai_scoped_key_scopes'] ) ) : array();
			if ( empty( $scopes ) ) {
				$scopes = array( 'read' ); // Least privilege — unchecking all scopes = read-only.
			}

			$role            = isset( $_POST['spai_scoped_key_role'] ) ? sanitize_key( wp_unslash( $_POST['spai_scoped_key_role'] ) ) : 'admin';
			$tool_categories = isset( $_POST['spai_scoped_key_categories'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['spai_scoped_key_categories'] ) ) : array();

			$new_scoped_key = $this->create_scoped_api_key( $label, $scopes, $role, $tool_categories );

			$roles      = self::get_role_definitions();
			$role_label = isset( $roles[ $role ] ) ? $roles[ $role ]['label'] : $role;
			add_settings_error(
				'spai_messages',
				'spai_scoped_key_created',
				sprintf(
					/* translators: %s: role label */
					__( 'API key created (role: %s). Copy it now — it will not be shown again.', 'mumega-mcp' ),
					$role_label
				),
				'updated'
			);
		}

		if ( isset( $_POST['spai_revoke_scoped_key'] ) ) {
			check_admin_referer( 'spai_manage_scoped_keys', 'spai_scoped_keys_nonce' );

			$key_id = isset( $_POST['spai_scoped_key_id'] ) ? sanitize_key( wp_unslash( $_POST['spai_scoped_key_id'] ) ) : '';
			if ( '' !== $key_id ) {
				$revoked = $this->revoke_scoped_api_key( $key_id );
				if ( $revoked ) {
					add_settings_error(
						'spai_messages',
						'spai_scoped_key_revoked',
						__( 'Scoped API key revoked.', 'mumega-mcp' ),
						'updated'
					);
				} else {
					add_settings_error(
						'spai_messages',
						'spai_scoped_key_revoke_failed',
						__( 'Unable to revoke key (it may already be revoked).', 'mumega-mcp' ),
						'error'
					);
				}
			}
		}

		// Check for first-activation key.
		if ( ! $new_key ) {
			$first_key = get_transient( 'spai_new_api_key' );
			if ( $first_key ) {
				$new_key = $first_key;
			}
		}

		// Handle force update check.
		if ( isset( $_POST['spai_force_update_check'] ) ) {
			check_admin_referer( 'spai_check_update', 'spai_update_nonce' );
			delete_site_transient( 'update_plugins' );
			delete_transient( 'spai_update_check' );
			wp_update_plugins();
			add_settings_error(
				'spai_messages',
				'spai_update_checked',
				__( 'Update check complete.', 'mumega-mcp' ),
				'updated'
			);
		}

		$scoped_keys = $this->list_scoped_api_keys( true );

		include SPAI_PLUGIN_DIR . 'admin/partials/spai-setup-display.php';
	}

	/**
	 * Render the human control room page.
	 */
	public function render_control_room_page() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mumega-mcp' ) );
		}

		$this->handle_control_room_actions();

		$control_room = $this->get_control_room_data();

		include SPAI_PLUGIN_DIR . 'admin/partials/spai-control-room-display.php';
	}

	/**
	 * Handle control room form actions.
	 *
	 * @return void
	 */
	private function handle_control_room_actions() {
		if ( empty( $_POST['spai_control_room_action'] ) ) {
			return;
		}

		check_admin_referer( 'spai_control_room_actions', 'spai_control_room_nonce' );

		$action = sanitize_key( wp_unslash( $_POST['spai_control_room_action'] ) );
		$result = null;

		if ( 'run_seo_audit' === $action ) {
			$result = $this->run_control_room_seo_audit();
		} elseif ( 'refresh_signals' === $action ) {
			if ( class_exists( 'Spai_Signals' ) ) {
				$computed = Spai_Signals::compute();
				$result   = sprintf( __( 'Signals refreshed: %d signal(s) found.', 'mumega-mcp' ), count( $computed ) );
			}
		} elseif ( 'rollback_action_log' === $action ) {
			$log_id = isset( $_POST['action_log_id'] ) ? sanitize_text_field( wp_unslash( $_POST['action_log_id'] ) ) : '';
			if ( class_exists( 'Spai_Action_Log' ) && $log_id ) {
				$rollback = Spai_Action_Log::rollback( $log_id );
				$result   = is_wp_error( $rollback )
					? $rollback
					: __( 'Action rolled back successfully.', 'mumega-mcp' );
			} else {
				$result = new WP_Error( 'spai_action_log_unavailable', __( 'Action log unavailable or missing log ID.', 'mumega-mcp' ) );
			}
		} else {
			$approval_id = isset( $_POST['spai_approval_id'] ) ? sanitize_key( wp_unslash( $_POST['spai_approval_id'] ) ) : '';
			$result      = $this->handle_control_room_approval_action( $action, $approval_id );
		}

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'spai_messages',
				'spai_control_room_error',
				$result->get_error_message(),
				'error'
			);
			return;
		}

		if ( is_string( $result ) && '' !== $result ) {
			add_settings_error(
				'spai_messages',
				'spai_control_room_updated',
				$result,
				'updated'
			);
		}
	}

	/**
	 * Handle an approval transition from the control room.
	 *
	 * @param string $action      Action slug.
	 * @param string $approval_id Approval request ID.
	 * @return string|WP_Error Message on success.
	 */
	private function handle_control_room_approval_action( $action, $approval_id ) {
		if ( ! class_exists( 'Spai_Approvals' ) ) {
			return new WP_Error( 'spai_approvals_unavailable', __( 'Approval storage is unavailable.', 'mumega-mcp' ) );
		}

		if ( '' === $approval_id ) {
			return new WP_Error( 'spai_missing_approval_id', __( 'Approval request ID is required.', 'mumega-mcp' ) );
		}

		switch ( $action ) {
			case 'approve':
				$result = Spai_Approvals::approve_request( $approval_id );
				$message = __( 'Approval request approved.', 'mumega-mcp' );
				break;
			case 'reject':
				$result = Spai_Approvals::reject_request( $approval_id );
				$message = __( 'Approval request rejected.', 'mumega-mcp' );
				break;
			case 'apply':
				$result = Spai_Approvals::apply_request( $approval_id );
				$message = __( 'Approved change applied.', 'mumega-mcp' );
				break;
			case 'rollback':
				$result = Spai_Approvals::rollback_request( $approval_id );
				$message = __( 'Applied change rolled back.', 'mumega-mcp' );
				break;
			default:
				return new WP_Error( 'spai_unknown_control_action', __( 'Unknown control room action.', 'mumega-mcp' ) );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $message;
	}

	/**
	 * Run and store a compact SEO audit from the control room.
	 *
	 * @return string|WP_Error Message on success.
	 */
	private function run_control_room_seo_audit() {
		if ( ! class_exists( 'Spai_REST_Site' ) || ! class_exists( 'WP_REST_Request' ) ) {
			return new WP_Error( 'spai_seo_audit_unavailable', __( 'SEO audit tools are unavailable.', 'mumega-mcp' ) );
		}

		$request = new WP_REST_Request( 'GET', '/mumega-mcp/v1/seo/audit-site' );
		$request->set_param( 'post_types', 'post,page' );
		$request->set_param( 'limit', 20 );
		$request->set_param( 'include_drafts', false );
		$request->set_param( 'store', true );

		$response = ( new Spai_REST_Site() )->audit_seo_site( $request );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response instanceof WP_REST_Response ? $response->get_data() : array();
		if ( isset( $data['success'] ) && empty( $data['success'] ) ) {
			$message = isset( $data['message'] ) ? (string) $data['message'] : __( 'SEO audit failed.', 'mumega-mcp' );
			return new WP_Error( 'spai_seo_audit_failed', $message );
		}

		$payload = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : $data;
		$summary = isset( $payload['summary'] ) && is_array( $payload['summary'] ) ? $payload['summary'] : array();
		$count   = isset( $summary['audited_count'] ) ? absint( $summary['audited_count'] ) : 0;

		return sprintf(
			/* translators: %d: number of audited URLs */
			_n( 'Stored SEO audit completed for %d URL.', 'Stored SEO audit completed for %d URLs.', $count, 'mumega-mcp' ),
			$count
		);
	}

	/**
	 * Get summarized control room data.
	 *
	 * @return array
	 */
	public function get_control_room_data() {
		$approvals = class_exists( 'Spai_Approvals' ) ? Spai_Approvals::list_requests( '', 100 ) : array();
		$seo_filters   = $this->get_control_room_seo_filters();
		$event_filters = $this->get_control_room_event_filters();
		$approval_counts = array(
			'pending'     => 0,
			'approved'    => 0,
			'applied'     => 0,
			'rejected'    => 0,
			'rolled_back' => 0,
		);

		foreach ( $approvals as $approval ) {
			$status = isset( $approval['status'] ) ? sanitize_key( (string) $approval['status'] ) : '';
			if ( isset( $approval_counts[ $status ] ) ) {
				$approval_counts[ $status ]++;
			}
		}

		$seo_open = class_exists( 'Spai_SEO_Audit_Store' )
			? Spai_SEO_Audit_Store::list_issues(
				array_merge(
					$seo_filters,
					array( 'limit' => 8 )
				)
			)
			: array(
				'summary' => array( 'total' => 0, 'open' => 0, 'resolved' => 0, 'error' => 0, 'warning' => 0, 'info' => 0 ),
				'issues'  => array(),
			);

		$seo_all = class_exists( 'Spai_SEO_Audit_Store' )
			? Spai_SEO_Audit_Store::list_issues( array( 'limit' => 1 ) )
			: $seo_open;

		$recent_activity = $this->get_recent_activity_rows( 8 );
		$event_inbox     = $this->get_control_room_event_inbox( $event_filters );

		$data = array(
			'approval_counts'  => $approval_counts,
			'pending_approvals' => class_exists( 'Spai_Approvals' ) ? Spai_Approvals::list_requests( 'pending', 5 ) : array(),
			'approved_approvals' => class_exists( 'Spai_Approvals' ) ? Spai_Approvals::list_requests( 'approved', 5 ) : array(),
			'rollback_ready'   => array_slice(
				array_values(
					array_filter(
						$approvals,
						function ( $approval ) {
							return isset( $approval['status'] ) && 'applied' === $approval['status'];
						}
					)
				),
				0,
				5
			),
			'seo_summary'      => isset( $seo_all['summary'] ) && is_array( $seo_all['summary'] ) ? $seo_all['summary'] : array(),
			'open_seo_issues'  => isset( $seo_open['issues'] ) && is_array( $seo_open['issues'] ) ? $seo_open['issues'] : array(),
			'seo_filters'      => $seo_filters,
			'event_inbox'      => $event_inbox,
			'event_filters'    => $event_filters,
			'recent_activity'  => $recent_activity,
			'action_log'       => class_exists( 'Spai_Action_Log' )
				? Spai_Action_Log::list_entries( array( 'limit' => 20 ) )
				: array( 'entries' => array(), 'total' => 0 ),
		);

		$data['recommendations'] = $this->get_control_room_recommendations( $data );

		// Site Signals (#363).
		$data['signals'] = class_exists( 'Spai_Signals' )
			? Spai_Signals::get_signals( array(), '', 20 )
			: array();

		// Site Memory summary (#362).
		$data['memory_count'] = 0;
		if ( class_exists( 'Spai_Site_Memory' ) ) {
			Spai_Site_Memory::maybe_migrate_site_context();
			$mem_grouped = Spai_Site_Memory::list_all();
			foreach ( $mem_grouped as $entries ) {
				$data['memory_count'] += count( $entries );
			}
		}

		return $data;
	}

	/**
	 * Read and sanitize control room SEO filters.
	 *
	 * @return array
	 */
	private function get_control_room_seo_filters() {
		$status   = isset( $_GET['spai_seo_status'] ) ? sanitize_key( wp_unslash( $_GET['spai_seo_status'] ) ) : 'open';
		$severity = isset( $_GET['spai_seo_severity'] ) ? sanitize_key( wp_unslash( $_GET['spai_seo_severity'] ) ) : '';
		$category = isset( $_GET['spai_seo_category'] ) ? sanitize_key( wp_unslash( $_GET['spai_seo_category'] ) ) : '';

		if ( ! in_array( $status, array( 'open', 'resolved', '' ), true ) ) {
			$status = 'open';
		}

		if ( ! in_array( $severity, array( 'error', 'warning', 'info', '' ), true ) ) {
			$severity = '';
		}

		if ( ! in_array( $category, array( 'readiness', 'structured_data', 'media', 'content_quality', '' ), true ) ) {
			$category = '';
		}

		return array(
			'status'   => $status,
			'severity' => $severity,
			'category' => $category,
		);
	}

	/**
	 * Read and sanitize control room event filters.
	 *
	 * @return array
	 */
	private function get_control_room_event_filters() {
		$type       = isset( $_GET['spai_event_type'] ) ? sanitize_text_field( wp_unslash( $_GET['spai_event_type'] ) ) : '';
		$risk_level = isset( $_GET['spai_event_risk'] ) ? sanitize_key( wp_unslash( $_GET['spai_event_risk'] ) ) : '';

		$type = preg_replace( '/[^a-z0-9_.-]/', '', strtolower( $type ) );

		if ( ! in_array( $risk_level, array( 'high', 'medium', 'low', '' ), true ) ) {
			$risk_level = '';
		}

		return array(
			'type'       => $type,
			'risk_level' => $risk_level,
		);
	}

	/**
	 * Build Control Room event inbox data.
	 *
	 * @param array $filters Event filters.
	 * @return array
	 */
	private function get_control_room_event_inbox( $filters ) {
		if ( ! class_exists( 'Spai_Event_Store' ) ) {
			return array(
				'summary' => array( 'total' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'escalated' => 0 ),
				'events'  => array(),
			);
		}

		$result = Spai_Event_Store::list_events(
			array(
				'type'  => isset( $filters['type'] ) ? $filters['type'] : '',
				'limit' => 50,
			)
		);
		$events = isset( $result['events'] ) && is_array( $result['events'] ) ? $result['events'] : array();

		if ( ! empty( $filters['risk_level'] ) ) {
			$events = array_values(
				array_filter(
					$events,
					function ( $event ) use ( $filters ) {
						return isset( $event['risk_level'] ) && $filters['risk_level'] === $event['risk_level'];
					}
				)
			);
		}

		$summary = array(
			'total'     => count( $events ),
			'high'      => 0,
			'medium'    => 0,
			'low'       => 0,
			'escalated' => 0,
		);

		foreach ( $events as $index => $event ) {
			$risk = isset( $event['risk_level'] ) ? sanitize_key( (string) $event['risk_level'] ) : 'low';
			if ( isset( $summary[ $risk ] ) ) {
				$summary[ $risk ]++;
			}

			$events[ $index ]['escalation'] = $this->classify_control_room_event_escalation( $event );
			if ( ! empty( $events[ $index ]['escalation']['escalated'] ) ) {
				$summary['escalated']++;
			}
		}

		return array(
			'summary' => $summary,
			'events'  => array_slice( $events, 0, 10 ),
		);
	}

	/**
	 * Classify event urgency for the Control Room inbox.
	 *
	 * @param array $event Event record.
	 * @return array
	 */
	private function classify_control_room_event_escalation( $event ) {
		$risk           = isset( $event['risk_level'] ) ? sanitize_key( (string) $event['risk_level'] ) : 'low';
		$approval_state = isset( $event['approval_state'] ) ? sanitize_key( (string) $event['approval_state'] ) : '';
		$seo_state      = isset( $event['seo_state'] ) ? sanitize_key( (string) $event['seo_state'] ) : '';
		$type           = isset( $event['type'] ) ? sanitize_text_field( (string) $event['type'] ) : '';

		if ( 'high' === $risk || 'fail' === $seo_state ) {
			return array(
				'escalated' => true,
				'level'     => 'high',
				'label'     => __( 'Needs attention', 'mumega-mcp' ),
				'reason'    => __( 'High-risk event or failing SEO state.', 'mumega-mcp' ),
			);
		}

		if ( 'pending' === $approval_state || false !== strpos( $type, 'approval.' ) ) {
			return array(
				'escalated' => true,
				'level'     => 'medium',
				'label'     => __( 'Human decision', 'mumega-mcp' ),
				'reason'    => __( 'Approval lifecycle event requires human awareness.', 'mumega-mcp' ),
			);
		}

		return array(
			'escalated' => false,
			'level'     => 'low',
			'label'     => __( 'Informational', 'mumega-mcp' ),
			'reason'    => __( 'No escalation rule matched.', 'mumega-mcp' ),
		);
	}

	/**
	 * Build short human recommendations for the control room.
	 *
	 * @param array $data Control room data.
	 * @return array
	 */
	private function get_control_room_recommendations( $data ) {
		$recommendations = array();
		$approval_counts = isset( $data['approval_counts'] ) && is_array( $data['approval_counts'] ) ? $data['approval_counts'] : array();
		$seo_summary     = isset( $data['seo_summary'] ) && is_array( $data['seo_summary'] ) ? $data['seo_summary'] : array();

		if ( ! empty( $approval_counts['pending'] ) ) {
			$recommendations[] = array(
				'priority' => 'high',
				'title'    => __( 'Review pending approvals', 'mumega-mcp' ),
				'detail'   => sprintf(
					/* translators: %d: number of pending approvals */
					_n( '%d agent change is waiting for human review.', '%d agent changes are waiting for human review.', (int) $approval_counts['pending'], 'mumega-mcp' ),
					(int) $approval_counts['pending']
				),
			);
		}

		if ( ! empty( $approval_counts['applied'] ) ) {
			$recommendations[] = array(
				'priority' => 'medium',
				'title'    => __( 'Keep rollback handles visible', 'mumega-mcp' ),
				'detail'   => __( 'Applied approval requests can still be rolled back if production content needs to revert.', 'mumega-mcp' ),
			);
		}

		if ( ! empty( $seo_summary['error'] ) ) {
			$recommendations[] = array(
				'priority' => 'high',
				'title'    => __( 'Prioritize SEO errors', 'mumega-mcp' ),
				'detail'   => sprintf(
					/* translators: %d: number of SEO errors */
					_n( '%d stored SEO error needs attention before lower-priority warnings.', '%d stored SEO errors need attention before lower-priority warnings.', (int) $seo_summary['error'], 'mumega-mcp' ),
					(int) $seo_summary['error']
				),
			);
		}

		if ( ! empty( $data['event_inbox']['summary']['escalated'] ) ) {
			$recommendations[] = array(
				'priority' => 'high',
				'title'    => __( 'Review escalated events', 'mumega-mcp' ),
				'detail'   => sprintf(
					/* translators: %d: number of escalated events */
					_n( '%d recent event needs human attention.', '%d recent events need human attention.', (int) $data['event_inbox']['summary']['escalated'], 'mumega-mcp' ),
					(int) $data['event_inbox']['summary']['escalated']
				),
			);
		}

		if ( empty( $recommendations ) ) {
			$recommendations[] = array(
				'priority' => 'low',
				'title'    => __( 'Run the next supervised workflow', 'mumega-mcp' ),
				'detail'   => __( 'No urgent approval or stored SEO issue is visible. Run a stored SEO audit or create an approval-required draft change to populate the control room.', 'mumega-mcp' ),
			);
		}

		return $recommendations;
	}

	/**
	 * Render the Chat page.
	 */
	public function render_chat_page() {
		include SPAI_PLUGIN_DIR . 'admin/partials/spai-chat-display.php';
	}

	/**
	 * Build OpenAI-format message array from history + current message.
	 *
	 * @param string $message Current user message.
	 * @param array  $history Prior conversation turns.
	 * @return array{system: string, messages: array}
	 */
	private function build_chat_messages( string $message, array $history ): array {
		$parts = array(
			'Site: ' . get_bloginfo( 'name' ),
			'URL: ' . home_url(),
			'Description: ' . get_bloginfo( 'description' ),
			'Plugin: MCPWP v' . SPAI_VERSION,
		);
		$site_character = get_option( 'spai_site_context', '' );
		if ( ! empty( $site_character ) ) {
			$parts[] = 'Site Character: ' . wp_trim_words( $site_character, 200 );
		}
		$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 20, 'fields' => 'ids' ) );
		if ( ! empty( $pages ) ) {
			$list = array();
			foreach ( $pages as $pid ) {
				$list[] = sprintf( '%d: %s', $pid, get_the_title( $pid ) );
			}
			$parts[] = 'Published pages: ' . implode( ', ', $list );
		}
		$caps = array();
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$caps[] = 'Elementor ' . ELEMENTOR_VERSION;
		}
		if ( class_exists( 'WooCommerce' ) ) {
			$caps[] = 'WooCommerce';
		}
		if ( defined( 'WPSEO_VERSION' ) ) {
			$caps[] = 'Yoast SEO';
		}
		if ( ! empty( $caps ) ) {
			$parts[] = 'Active integrations: ' . implode( ', ', $caps );
		}
		$site_context = implode( "\n", $parts );

		$system = "You are MCPWP, an AI assistant embedded in a WordPress site. Help the user manage their site.\n\n"
			. "When the user asks you to DO something (build, edit, create, delete, update), respond with a JSON tool call:\n"
			. "{\"tool\": \"tool_name\", \"arguments\": {\"key\": \"value\"}}\n\n"
			. "Available tools: wp_build_page, wp_edit_widget, wp_edit_section, wp_add_section, wp_create_page, wp_update_page, "
			. "wp_list_pages, wp_upload_media_from_url, wp_search, wp_get_elementor_summary, wp_regenerate_elementor_css\n\n"
			. "Blueprint types: hero, features, cta, pricing, faq, testimonials, team, portfolio, blog_grid, services, about, "
			. "process_steps, social_proof, product_showcase, before_after, newsletter, stats, gallery, text, map, countdown, logo_grid, video, contact_form\n\n"
			. "If the user asks a QUESTION, respond normally as text.\n\n"
			. "Site context:\n" . $site_context;

		$messages = array( array( 'role' => 'system', 'content' => $system ) );
		foreach ( array_slice( $history, -10 ) as $msg ) {
			if ( isset( $msg['role'], $msg['content'] ) ) {
				$messages[] = array( 'role' => sanitize_key( $msg['role'] ), 'content' => wp_kses_post( $msg['content'] ) );
			}
		}
		$messages[] = array( 'role' => 'user', 'content' => $message );

		return array( 'system' => $system, 'messages' => $messages, 'site_context' => $site_context );
	}

	/**
	 * AJAX proxy for chat — multi-model: OpenAI, Gemini, Workers AI fallback.
	 */
	public function ajax_chat() {
		check_ajax_referer( 'spai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Chat requires administrator access.' ) );
		}

		$message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
		$history = isset( $_POST['history'] ) ? json_decode( wp_unslash( $_POST['history'] ), true ) : array();

		if ( empty( $message ) ) {
			wp_send_json_error( array( 'message' => 'Message is required' ) );
		}

		$manager    = Spai_Integration_Manager::get_instance();
		$openai_key = $manager->get_provider_key( 'openai' );
		$gemini_key = $manager->get_provider_key( 'gemini' );
		$pref       = get_option( 'spai_chat_model', 'auto' );

		$built         = $this->build_chat_messages( $message, is_array( $history ) ? $history : array() );
		$messages      = $built['messages'];
		$system        = $built['system'];
		$site_context  = $built['site_context'];

		$ai_response = '';
		$model_used  = '';

		// Decide provider based on preference + key availability.
		$use_openai  = ( 'openai' === $pref && ! empty( $openai_key ) )
			|| ( 'auto' === $pref && ! empty( $openai_key ) );
		$use_gemini  = ( 'gemini' === $pref && ! empty( $gemini_key ) )
			|| ( 'auto' === $pref && empty( $openai_key ) && ! empty( $gemini_key ) );

		if ( $use_openai ) {
			$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
				'timeout' => 60,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $openai_key,
				),
				'body'    => wp_json_encode( array( 'model' => 'gpt-4o-mini', 'messages' => $messages ) ),
			) );
			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			}
			$body        = json_decode( wp_remote_retrieve_body( $response ), true );
			$ai_response = $body['choices'][0]['message']['content'] ?? '';
			$model_used  = 'openai/' . ( $body['model'] ?? 'gpt-4o-mini' );

		} elseif ( $use_gemini ) {
			// Build Gemini multi-turn contents (system instruction + history + user turn).
			$contents = array();
			foreach ( array_slice( $messages, 1 ) as $msg ) { // skip system message
				$contents[] = array(
					'role'  => 'user' === $msg['role'] ? 'user' : 'model',
					'parts' => array( array( 'text' => $msg['content'] ) ),
				);
			}
			$gemini_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $gemini_key;
			$response   = wp_remote_post( $gemini_url, array(
				'timeout' => 60,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array(
					'systemInstruction' => array( 'parts' => array( array( 'text' => $system ) ) ),
					'contents'          => $contents,
				) ),
			) );
			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			}
			$body        = json_decode( wp_remote_retrieve_body( $response ), true );
			$ai_response = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
			$model_used  = 'gemini/gemini-2.5-flash';

		} else {
			// Fall back to free Cloudflare Workers AI.
			$chat_endpoint = get_option( 'spai_chat_endpoint', 'https://mumcp-chat.weathered-scene-2272.workers.dev' );
			$chat_secret   = get_option( 'spai_chat_secret', '' );

			$response = wp_remote_post( $chat_endpoint, array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $chat_secret,
				),
				'body'    => wp_json_encode( array(
					'message'      => $message,
					'history'      => array_slice( is_array( $history ) ? $history : array(), -10 ),
					'site_context' => $site_context,
				) ),
			) );
			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $body ) ) {
				wp_send_json_error( array( 'message' => 'Invalid response from AI' ) );
			}
			wp_send_json_success( array_merge( $body, array( 'model' => 'workers-ai' ) ) );
			return;
		}

		// Parse tool calls from the response.
		$tool_call = null;
		if ( preg_match( '/\{[\s\S]*"tool"\s*:\s*"[\s\S]*\}/', $ai_response, $match ) ) {
			$parsed = json_decode( $match[0], true );
			if ( isset( $parsed['tool'] ) ) {
				$tool_call = $parsed;
			}
		}

		wp_send_json_success( array(
			'response'  => $ai_response,
			'tool_call' => $tool_call,
			'model'     => $model_used,
		) );
	}

	/**
	 * AJAX handler — execute an MCP tool server-side (from chat).
	 */
	public function ajax_chat_execute_tool() {
		check_ajax_referer( 'spai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Tool execution requires administrator access.' ) );
		}

		$tool      = isset( $_POST['tool'] ) ? sanitize_text_field( wp_unslash( $_POST['tool'] ) ) : '';
		$arguments = isset( $_POST['arguments'] ) ? json_decode( wp_unslash( $_POST['arguments'] ), true ) : array();

		if ( empty( $tool ) ) {
			wp_send_json_error( array( 'message' => 'Tool name is required' ) );
		}

		// Gate destructive tools behind explicit confirmation.
		$destructive = array(
			'wp_delete_page', 'wp_delete_post', 'wp_delete_media', 'wp_delete_all_drafts',
			'wp_delete_menu', 'wp_delete_menu_item', 'wp_delete_webhook', 'wp_delete_content',
			'wp_delete_custom_css', 'wp_delete_term', 'wp_revoke_api_key', 'wp_rollback_approval',
		);
		$confirmed = isset( $_POST['confirmed'] ) && 'true' === $_POST['confirmed'];
		if ( in_array( $tool, $destructive, true ) && ! $confirmed ) {
			wp_send_json_success( array( 'needs_confirmation' => true, 'tool' => $tool ) );
		}

		// Execute via internal REST dispatch — no API key needed, runs as current user.
		$request = new WP_REST_Request( 'POST', '/site-pilot-ai/v1/mcp' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array(
			'jsonrpc' => '2.0',
			'id'      => time(),
			'method'  => 'tools/call',
			'params'  => array(
				'name'      => $tool,
				'arguments' => is_array( $arguments ) ? $arguments : array(),
			),
		) ) );

		// Bypass API key auth for internal requests.
		add_filter( 'spai_bypass_api_key_check', '__return_true' );
		$response = rest_do_request( $request );
		remove_filter( 'spai_bypass_api_key_check', '__return_true' );

		$data = rest_get_server()->response_to_data( $response, false );

		if ( isset( $data['result']['content'][0]['text'] ) ) {
			$text = $data['result']['content'][0]['text'];
			$parsed = json_decode( $text, true );
			wp_send_json_success( is_array( $parsed ) ? $parsed : array( 'text' => $text ) );
		} elseif ( isset( $data['error'] ) ) {
			wp_send_json_error( $data['error'] );
		} else {
			wp_send_json_success( $data );
		}
	}

	/**
	 * SSE streaming endpoint — proxies OpenAI token stream to the browser.
	 * Outputs text/event-stream; never calls wp_send_json_*.
	 */
	public function ajax_chat_stream() {
		if ( ! check_ajax_referer( 'spai_admin_nonce', 'nonce', false ) || ! current_user_can( 'activate_plugins' ) ) {
			http_response_code( 403 );
			exit;
		}

		$message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
		$history = isset( $_POST['history'] ) ? json_decode( wp_unslash( $_POST['history'] ), true ) : array();

		if ( empty( $message ) ) {
			http_response_code( 400 );
			exit;
		}

		$manager    = Spai_Integration_Manager::get_instance();
		$openai_key = $manager->get_provider_key( 'openai' );

		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'X-Accel-Buffering: no' );
		header( 'Connection: keep-alive' );

		if ( ob_get_level() ) {
			ob_end_flush();
		}
		flush();

		if ( empty( $openai_key ) ) {
			echo 'data: ' . wp_json_encode( array( 'error' => 'Streaming requires an OpenAI API key' ) ) . "\n\n";
			flush();
			exit;
		}

		$built    = $this->build_chat_messages( $message, is_array( $history ) ? $history : array() );
		$messages = $built['messages'];

		// Use cURL to forward OpenAI's SSE stream chunk-by-chunk.
		if ( ! function_exists( 'curl_init' ) ) {
			echo 'data: ' . wp_json_encode( array( 'error' => 'cURL not available' ) ) . "\n\n";
			flush();
			exit;
		}

		$ch = curl_init( 'https://api.openai.com/v1/chat/completions' );
		curl_setopt_array( $ch, array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => wp_json_encode( array(
				'model'    => 'gpt-4o-mini',
				'messages' => $messages,
				'stream'   => true,
			) ),
			CURLOPT_HTTPHEADER     => array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $openai_key,
			),
			CURLOPT_WRITEFUNCTION  => static function ( $curl, $data ) {
				$lines = explode( "\n", $data );
				foreach ( $lines as $line ) {
					$line = trim( $line );
					if ( '' === $line || 'data: ' !== substr( $line, 0, 6 ) ) {
						continue;
					}
					$payload = substr( $line, 6 );
					if ( '[DONE]' === $payload ) {
						echo "data: [DONE]\n\n";
					} else {
						$chunk = json_decode( $payload, true );
						$token = $chunk['choices'][0]['delta']['content'] ?? null;
						if ( null !== $token ) {
							echo 'data: ' . wp_json_encode( array( 'token' => $token ) ) . "\n\n";
						}
					}
					flush();
				}
				return strlen( $data );
			},
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_TIMEOUT        => 90,
		) );

		curl_exec( $ch );
		curl_close( $ch );
		exit;
	}

	/**
	 * Save chat conversation history for the current user.
	 */
	public function ajax_chat_save_history() {
		check_ajax_referer( 'spai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error();
		}

		$history = isset( $_POST['history'] ) ? json_decode( wp_unslash( $_POST['history'] ), true ) : array();
		update_option( 'spai_chat_history_' . get_current_user_id(), array_slice( (array) $history, -50 ), false );
		wp_send_json_success();
	}

	/**
	 * Clear saved chat history for the current user.
	 */
	public function ajax_chat_clear_history() {
		check_ajax_referer( 'spai_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error();
		}

		delete_option( 'spai_chat_history_' . get_current_user_id() );
		wp_send_json_success();
	}

	/**
	 * Render the Library page.
	 */
	public function render_library_page() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mumega-mcp' ) );
		}

		// Handle library form actions.
		if ( isset( $_POST['spai_promote_template_archetype'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_promote_template_to_archetype();
		}

		if ( isset( $_POST['spai_promote_template_part'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_promote_template_to_part();
		}

		if ( isset( $_POST['spai_extract_section_part'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_extract_section_to_part();
		}

		if ( isset( $_POST['spai_create_page_from_archetype'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_create_page_from_archetype();
		}

		if ( isset( $_POST['spai_apply_part_to_page'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_apply_part_to_page();
		}

		if ( isset( $_POST['spai_demote_archetype'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_demote_archetype();
		}

		if ( isset( $_POST['spai_demote_part'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_demote_part();
		}

		if ( isset( $_POST['spai_create_product_archetype'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_create_product_archetype();
		}

		if ( isset( $_POST['spai_create_product_from_archetype'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_create_product_from_archetype();
		}

		if ( isset( $_POST['spai_delete_product_archetype'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_delete_product_archetype();
		}

		if ( isset( $_POST['spai_create_design_reference'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_create_design_reference();
		}

		if ( isset( $_POST['spai_create_page_from_design_reference'] ) ) {
			check_admin_referer( 'spai_library_actions', 'spai_library_nonce' );
			$this->handle_create_page_from_design_reference();
		}

		$library_inventory      = $this->get_library_inventory();
		$library_filters        = $this->get_library_filters();
		$library_filter_options = $this->get_library_filter_options( $library_inventory );
		$library_inventory      = $this->filter_library_inventory( $library_inventory, $library_filters );

		include SPAI_PLUGIN_DIR . 'admin/partials/spai-library-display.php';
	}

	/**
	 * Render the Settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mumega-mcp' ) );
		}

		if ( isset( $_POST['spai_save_site_profile'] ) ) {
			check_admin_referer( 'spai_site_profile_actions', 'spai_site_profile_nonce' );
			$this->handle_save_site_profile();
		}

		$site_profile         = $this->get_site_profile();
		$site_context_preview = $this->get_site_context_preview();
		$llms_url             = $this->get_llms_url();
		$llms_preview         = $this->get_llms_preview();

		include SPAI_PLUGIN_DIR . 'admin/partials/spai-settings-display.php';
	}

	/**
	 * Get the stored structured site profile.
	 *
	 * @return array
	 */
	public function get_site_profile() {
		$profile = get_option( 'spai_site_profile', array() );
		return is_array( $profile ) ? $profile : array();
	}

	/**
	 * Get the canonical generated site context.
	 *
	 * @return string
	 */
	public function get_site_context_preview() {
		$context = get_option( 'spai_site_context', '' );
		return is_string( $context ) ? $context : '';
	}

	/**
	 * Get the live llms.txt URL.
	 *
	 * @return string
	 */
	public function get_llms_url() {
		return home_url( '/llms.txt' );
	}

	/**
	 * Generate the live llms.txt preview text.
	 *
	 * @return string
	 */
	public function get_llms_preview() {
		if ( ! class_exists( 'Spai_AI_Presence' ) ) {
			return '';
		}

		$presence = new Spai_AI_Presence();
		return $presence->generate_llms_txt();
	}

	/**
	 * Get update channel status and manual recovery info for admin.
	 *
	 * @return array
	 */
	public function get_update_channel_status() {
		$manifest_url    = get_option( 'spai_version_url', 'https://mumega.com/mcp-updates/version.json' );
		$manifest_url    = $manifest_url ? $manifest_url : 'https://mumega.com/mcp-updates/version.json';
		$current_version = defined( 'SPAI_VERSION' ) ? SPAI_VERSION : '0.0.0';
		$remote_version  = null;
		$download_url    = 'https://mumega.com/mcp-updates/mcpwp-latest.zip';
		$source          = 'remote';
		$option_version  = null;
		$warning         = '';

		$option_data = get_option( 'spai_update_info' );
		if ( is_string( $option_data ) ) {
			$option_data = json_decode( $option_data, true );
		}
		if ( is_array( $option_data ) ) {
			if ( ! empty( $option_data['version'] ) ) {
				$option_version = (string) $option_data['version'];
			}
			if ( ! empty( $option_data['download_url'] ) && empty( $download_url ) ) {
				$download_url = (string) $option_data['download_url'];
			}
		}

		$response = wp_remote_get(
			$manifest_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $body ) ) {
				if ( ! empty( $body['version'] ) ) {
					$remote_version = (string) $body['version'];
				}
				if ( ! empty( $body['download_url'] ) ) {
					$download_url = (string) $body['download_url'];
				}
			}
		} else {
			$warning = __( 'The update manifest could not be reached from this site. Manual download is still available below.', 'mumega-mcp' );
		}

		if ( $option_version && $remote_version && version_compare( $option_version, $remote_version, '!=' ) ) {
			$source  = 'mixed';
			$warning = __( 'A site-level update override is present and does not match the remote manifest. Clear stale override data if updates look wrong.', 'mumega-mcp' );
		} elseif ( $option_version ) {
			$source = 'option';
		}

		return array(
			'current_version'  => $current_version,
			'remote_version'   => $remote_version,
			'download_url'     => $download_url,
			'manifest_url'     => $manifest_url,
			'option_version'   => $option_version,
			'source'           => $source,
			'update_available' => ( $remote_version && version_compare( $remote_version, $current_version, '>' ) ),
			'warning'          => $warning,
			'manual_steps'     => array(
				__( 'Download the latest ZIP from the canonical package URL.', 'mumega-mcp' ),
				__( 'In WordPress admin, go to Plugins -> Add Plugin -> Upload Plugin.', 'mumega-mcp' ),
				__( 'Upload the ZIP and replace the installed version.', 'mumega-mcp' ),
			),
		);
	}

	/**
	 * Get first-run onboarding status for the operator workflow.
	 *
	 * @return array
	 */
	public function get_onboarding_status() {
		$profile         = $this->get_site_profile();
		$inventory       = $this->get_library_inventory();
		$has_site_profile = ! empty( array_filter( $profile ) );
		$has_api_key     = ! empty( get_option( 'spai_api_key', '' ) );
		$has_references  = ! empty( $inventory['design_references'] );
		$has_archetypes  = ! empty( $inventory['page_archetypes'] ) || ! empty( $inventory['product_archetypes'] );
		$has_parts       = ! empty( $inventory['parts'] );

		$steps = array(
			array(
				'title'       => __( 'Define site character', 'mumega-mcp' ),
				'description' => __( 'Save the brand voice, audience, and page rules in Settings.', 'mumega-mcp' ),
				'done'        => $has_site_profile,
				'url'         => admin_url( 'admin.php?page=' . self::SETTINGS_PAGE_SLUG ),
				'cta'         => __( 'Open Settings', 'mumega-mcp' ),
			),
			array(
				'title'       => __( 'Create an AI key', 'mumega-mcp' ),
				'description' => __( 'Generate or copy an API key so models can connect to the site.', 'mumega-mcp' ),
				'done'        => $has_api_key,
				'url'         => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
				'cta'         => __( 'Manage Keys', 'mumega-mcp' ),
			),
			array(
				'title'       => __( 'Store a design reference', 'mumega-mcp' ),
				'description' => __( 'Turn one approved screenshot or mockup into reusable design memory.', 'mumega-mcp' ),
				'done'        => $has_references,
				'url'         => admin_url( 'admin.php?page=' . self::LIBRARY_PAGE_SLUG ),
				'cta'         => __( 'Open Library', 'mumega-mcp' ),
			),
			array(
				'title'       => __( 'Create an archetype', 'mumega-mcp' ),
				'description' => __( 'Save at least one page or product structure so models stop starting from zero.', 'mumega-mcp' ),
				'done'        => $has_archetypes,
				'url'         => admin_url( 'admin.php?page=' . self::LIBRARY_PAGE_SLUG ),
				'cta'         => __( 'Review Archetypes', 'mumega-mcp' ),
			),
			array(
				'title'       => __( 'Build reusable parts', 'mumega-mcp' ),
				'description' => __( 'Keep heroes, proof blocks, FAQs, and CTAs in the parts library for future pages.', 'mumega-mcp' ),
				'done'        => $has_parts,
				'url'         => admin_url( 'admin.php?page=' . self::LIBRARY_PAGE_SLUG ),
				'cta'         => __( 'Review Parts', 'mumega-mcp' ),
			),
		);

		$completed = 0;
		foreach ( $steps as $step ) {
			if ( ! empty( $step['done'] ) ) {
				$completed++;
			}
		}

		$next_step = null;
		foreach ( $steps as $step ) {
			if ( empty( $step['done'] ) ) {
				$next_step = $step;
				break;
			}
		}

		return array(
			'completed' => $completed,
			'total'     => count( $steps ),
			'steps'     => $steps,
			'next_step' => $next_step,
		);
	}

	/**
	 * Handle promotion of an existing template into a page archetype.
	 *
	 * @return void
	 */
	private function handle_promote_template_to_archetype() {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			add_settings_error(
				'spai_messages',
				'spai_library_archetype_missing',
				__( 'Elementor Pro handler is not available.', 'mumega-mcp' ),
				'error'
			);
			return;
		}

		$template_id = isset( $_POST['spai_archetype_template_id'] ) ? absint( wp_unslash( $_POST['spai_archetype_template_id'] ) ) : 0;
		$title       = isset( $_POST['spai_archetype_title'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_archetype_title'] ) ) : '';
		$scope       = isset( $_POST['spai_archetype_scope'] ) ? sanitize_key( wp_unslash( $_POST['spai_archetype_scope'] ) ) : 'page';
		$class       = isset( $_POST['spai_archetype_class'] ) ? sanitize_key( wp_unslash( $_POST['spai_archetype_class'] ) ) : '';
		$style       = isset( $_POST['spai_archetype_style'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_archetype_style'] ) ) : '';
		$brief       = isset( $_POST['spai_archetype_brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['spai_archetype_brief'] ) ) : '';

		if ( $template_id <= 0 ) {
			add_settings_error(
				'spai_messages',
				'spai_library_archetype_invalid_id',
				__( 'Enter a valid Elementor template ID to promote as an archetype.', 'mumega-mcp' ),
				'error'
			);
			return;
		}

		$elementor = new Spai_Elementor_Pro();
		$result    = $elementor->update_template(
			$template_id,
			array(
				'title'           => $title,
				'is_archetype'    => true,
				'archetype_scope' => $scope ? $scope : 'page',
				'archetype_class' => $class,
				'archetype_style' => $style,
				'archetype_brief' => $brief,
			)
		);

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'spai_messages',
				'spai_library_archetype_failed',
				$result->get_error_message(),
				'error'
			);
			return;
		}

		add_settings_error(
			'spai_messages',
			'spai_library_archetype_saved',
			sprintf(
				/* translators: %s: template title */
				__( 'Saved archetype: %s', 'mumega-mcp' ),
				isset( $result['title'] ) ? $result['title'] : (string) $template_id
			),
			'updated'
		);
	}

	/**
	 * Handle promotion of an existing template into a reusable part.
	 *
	 * @return void
	 */
	private function handle_promote_template_to_part() {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			add_settings_error(
				'spai_messages',
				'spai_library_part_missing',
				__( 'Elementor Pro handler is not available.', 'mumega-mcp' ),
				'error'
			);
			return;
		}

		$template_id = isset( $_POST['spai_part_template_id'] ) ? absint( wp_unslash( $_POST['spai_part_template_id'] ) ) : 0;
		$title       = isset( $_POST['spai_part_title'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_part_title'] ) ) : '';
		$kind        = isset( $_POST['spai_part_kind'] ) ? sanitize_key( wp_unslash( $_POST['spai_part_kind'] ) ) : '';
		$style       = isset( $_POST['spai_part_style'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_part_style'] ) ) : '';
		$tags_raw    = isset( $_POST['spai_part_tags'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_part_tags'] ) ) : '';

		if ( $template_id <= 0 ) {
			add_settings_error(
				'spai_messages',
				'spai_library_part_invalid_id',
				__( 'Enter a valid Elementor template ID to promote as a reusable part.', 'mumega-mcp' ),
				'error'
			);
			return;
		}

		$elementor = new Spai_Elementor_Pro();
		$result    = $elementor->update_template(
			$template_id,
			array(
				'title'      => $title,
				'is_part'    => true,
				'part_kind'  => $kind,
				'part_style' => $style,
				'part_tags'  => $this->parse_tag_string( $tags_raw ),
			)
		);

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'spai_messages',
				'spai_library_part_failed',
				$result->get_error_message(),
				'error'
			);
			return;
		}

		add_settings_error(
			'spai_messages',
			'spai_library_part_saved',
			sprintf(
				/* translators: %s: template title */
				__( 'Saved reusable part: %s', 'mumega-mcp' ),
				isset( $result['title'] ) ? $result['title'] : (string) $template_id
			),
			'updated'
		);
	}

	/**
	 * Handle extraction of a live Elementor section into a reusable part.
	 *
	 * @return void
	 */
	private function handle_extract_section_to_part() {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			add_settings_error(
				'spai_messages',
				'spai_library_extract_missing',
				__( 'Elementor Pro handler is not available.', 'mumega-mcp' ),
				'error'
			);
			return;
		}

		$page_id    = isset( $_POST['spai_source_page_id'] ) ? absint( wp_unslash( $_POST['spai_source_page_id'] ) ) : 0;
		$element_id = isset( $_POST['spai_source_element_id'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_source_element_id'] ) ) : '';
		$title      = isset( $_POST['spai_extract_part_title'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_extract_part_title'] ) ) : '';
		$kind       = isset( $_POST['spai_extract_part_kind'] ) ? sanitize_key( wp_unslash( $_POST['spai_extract_part_kind'] ) ) : '';
		$style      = isset( $_POST['spai_extract_part_style'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_extract_part_style'] ) ) : '';
		$tags_raw   = isset( $_POST['spai_extract_part_tags'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_extract_part_tags'] ) ) : '';

		if ( $page_id <= 0 || '' === $element_id ) {
			add_settings_error(
				'spai_messages',
				'spai_library_extract_invalid',
				__( 'Enter a valid source page ID and Elementor element ID to extract a reusable part.', 'mumega-mcp' ),
				'error'
			);
			return;
		}

		$elementor = new Spai_Elementor_Pro();
		$result    = $elementor->create_part_from_section(
			$page_id,
			$element_id,
			array(
				'title'      => $title,
				'part_kind'  => $kind,
				'part_style' => $style,
				'part_tags'  => $this->parse_tag_string( $tags_raw ),
			)
		);

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'spai_messages',
				'spai_library_extract_failed',
				$result->get_error_message(),
				'error'
			);
			return;
		}

		add_settings_error(
			'spai_messages',
			'spai_library_extract_saved',
			sprintf(
				/* translators: %s: part title */
				__( 'Extracted reusable part: %s', 'mumega-mcp' ),
				isset( $result['title'] ) ? $result['title'] : ''
			),
			'updated'
		);
	}

	/**
	 * Parse a comma-separated tag string into a clean array.
	 *
	 * @param string $tags_raw Raw tag string.
	 * @return array
	 */
	private function parse_tag_string( $tags_raw ) {
		if ( '' === $tags_raw ) {
			return array();
		}

		$tags = array_map( 'trim', explode( ',', $tags_raw ) );
		$tags = array_filter( $tags, 'strlen' );

		return array_values( array_unique( $tags ) );
	}

	/**
	 * Parse a newline-separated list into a clean array.
	 *
	 * @param string $value Raw textarea value.
	 * @return array
	 */
	private function parse_line_list( $value ) {
		if ( '' === trim( $value ) ) {
			return array();
		}

		$lines = preg_split( '/\r\n|\r|\n/', (string) $value );
		$lines = array_map( 'trim', (array) $lines );
		$lines = array_filter( $lines, 'strlen' );

		return array_values( array_unique( $lines ) );
	}

	/**
	 * Create a new draft page from a saved archetype.
	 *
	 * @return void
	 */
	private function handle_create_page_from_archetype() {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			$this->add_library_error_notice( 'Elementor Pro handler is not available.' );
			return;
		}

		$archetype_id = isset( $_POST['spai_action_archetype_id'] ) ? absint( wp_unslash( $_POST['spai_action_archetype_id'] ) ) : 0;
		if ( $archetype_id <= 0 ) {
			$this->add_library_error_notice( 'Enter a valid archetype ID.' );
			return;
		}

		$elementor = new Spai_Elementor_Pro();
		$archetype = $elementor->get_archetype( $archetype_id );
		if ( is_wp_error( $archetype ) ) {
			$this->add_library_error_notice( $archetype->get_error_message() );
			return;
		}

		$title  = ! empty( $archetype['title'] ) ? $archetype['title'] . ' Draft' : 'Archetype Draft';
		$result = $elementor->create_landing_page(
			array(
				'title'       => $title,
				'status'      => 'draft',
				'template_id' => $archetype_id,
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->add_library_error_notice( $result->get_error_message() );
			return;
		}

		add_settings_error(
			'spai_messages',
			'spai_library_archetype_page_created',
			sprintf(
				/* translators: 1: page title 2: edit URL */
				__( 'Created draft page: %1$s. <a href="%2$s">Open in Elementor</a>.', 'mumega-mcp' ),
				esc_html( isset( $result['title'] ) ? $result['title'] : '' ),
				esc_url( isset( $result['edit_url'] ) ? $result['edit_url'] : admin_url() )
			),
			'updated'
		);
	}

	/**
	 * Apply or insert a reusable part onto a target page.
	 *
	 * @return void
	 */
	private function handle_apply_part_to_page() {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			$this->add_library_error_notice( 'Elementor Pro handler is not available.' );
			return;
		}

		$part_id  = isset( $_POST['spai_action_part_id'] ) ? absint( wp_unslash( $_POST['spai_action_part_id'] ) ) : 0;
		$page_id  = isset( $_POST['spai_target_page_id'] ) ? absint( wp_unslash( $_POST['spai_target_page_id'] ) ) : 0;
		$mode     = isset( $_POST['spai_part_apply_mode'] ) ? sanitize_key( wp_unslash( $_POST['spai_part_apply_mode'] ) ) : 'insert';
		$position = isset( $_POST['spai_part_apply_position'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_part_apply_position'] ) ) : 'end';

		if ( $part_id <= 0 || $page_id <= 0 ) {
			$this->add_library_error_notice( 'Enter a valid reusable part ID and target page ID.' );
			return;
		}

		$elementor = new Spai_Elementor_Pro();

		if ( 'replace' === $mode ) {
			$result = $elementor->apply_part_to_page( $part_id, $page_id );
		} else {
			if ( ! in_array( $position, array( 'start', 'end' ), true ) ) {
				$position = 'end';
			}
			$result = $elementor->insert_part_into_page( $part_id, $page_id, $position );
		}

		if ( is_wp_error( $result ) ) {
			$this->add_library_error_notice( $result->get_error_message() );
			return;
		}

		$page_title = get_the_title( $page_id );
		$page_edit  = admin_url( 'post.php?post=' . $page_id . '&action=edit' );

		add_settings_error(
			'spai_messages',
			'spai_library_part_applied',
			sprintf(
				/* translators: 1: page title 2: edit URL */
				__( 'Updated page: %1$s. <a href="%2$s">Open page</a>.', 'mumega-mcp' ),
				esc_html( $page_title ? $page_title : '#' . $page_id ),
				esc_url( $page_edit )
			),
			'updated'
		);
	}

	/**
	 * Remove archetype metadata from a template.
	 *
	 * @return void
	 */
	private function handle_demote_archetype() {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			$this->add_library_error_notice( 'Elementor Pro handler is not available.' );
			return;
		}

		$template_id = isset( $_POST['spai_action_archetype_id'] ) ? absint( wp_unslash( $_POST['spai_action_archetype_id'] ) ) : 0;
		if ( $template_id <= 0 ) {
			$this->add_library_error_notice( 'Enter a valid archetype ID.' );
			return;
		}

		$elementor = new Spai_Elementor_Pro();
		$result    = $elementor->update_template(
			$template_id,
			array(
				'is_archetype'    => false,
				'archetype_scope' => '',
				'archetype_class' => '',
				'archetype_style' => '',
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->add_library_error_notice( $result->get_error_message() );
			return;
		}

		add_settings_error(
			'spai_messages',
			'spai_library_archetype_demoted',
			__( 'Archetype metadata removed from the template.', 'mumega-mcp' ),
			'updated'
		);
	}

	/**
	 * Remove reusable part metadata from a template.
	 *
	 * @return void
	 */
	private function handle_demote_part() {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			$this->add_library_error_notice( 'Elementor Pro handler is not available.' );
			return;
		}

		$template_id = isset( $_POST['spai_action_part_id'] ) ? absint( wp_unslash( $_POST['spai_action_part_id'] ) ) : 0;
		if ( $template_id <= 0 ) {
			$this->add_library_error_notice( 'Enter a valid part ID.' );
			return;
		}

		$elementor = new Spai_Elementor_Pro();
		$result    = $elementor->update_template(
			$template_id,
			array(
				'is_part'           => false,
				'part_kind'         => '',
				'part_style'        => '',
				'part_tags'         => array(),
				'source_page_id'    => 0,
				'source_element_id' => '',
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->add_library_error_notice( $result->get_error_message() );
			return;
		}

		add_settings_error(
			'spai_messages',
			'spai_library_part_demoted',
			__( 'Reusable part metadata removed from the template.', 'mumega-mcp' ),
			'updated'
		);
	}

	/**
	 * Create a new WooCommerce product archetype from admin.
	 *
	 * @return void
	 */
	private function handle_create_product_archetype() {
		$controller = $this->get_woocommerce_archetype_controller();
		if ( is_wp_error( $controller ) ) {
			$this->add_library_error_notice( $controller->get_error_message() );
			return;
		}

		$request = new WP_REST_Request( 'POST', '/site-pilot-ai/v1/woocommerce/archetypes' );
		$request->set_body_params(
			array(
				'name'              => isset( $_POST['spai_product_archetype_name'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_product_archetype_name'] ) ) : '',
				'archetype_class'   => isset( $_POST['spai_product_archetype_class'] ) ? sanitize_key( wp_unslash( $_POST['spai_product_archetype_class'] ) ) : '',
				'archetype_style'   => isset( $_POST['spai_product_archetype_style'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_product_archetype_style'] ) ) : '',
				'brief'             => isset( $_POST['spai_product_archetype_brief'] ) ? sanitize_textarea_field( wp_unslash( $_POST['spai_product_archetype_brief'] ) ) : '',
				'product_type'      => isset( $_POST['spai_product_type'] ) ? sanitize_key( wp_unslash( $_POST['spai_product_type'] ) ) : 'simple',
				'status'            => isset( $_POST['spai_product_status'] ) ? sanitize_key( wp_unslash( $_POST['spai_product_status'] ) ) : 'draft',
				'description'       => isset( $_POST['spai_product_description'] ) ? wp_kses_post( wp_unslash( $_POST['spai_product_description'] ) ) : '',
				'short_description' => isset( $_POST['spai_product_short_description'] ) ? wp_kses_post( wp_unslash( $_POST['spai_product_short_description'] ) ) : '',
				'regular_price'     => isset( $_POST['spai_product_regular_price'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_product_regular_price'] ) ) : '',
				'sale_price'        => isset( $_POST['spai_product_sale_price'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_product_sale_price'] ) ) : '',
				'stock_status'      => isset( $_POST['spai_product_stock_status'] ) ? sanitize_key( wp_unslash( $_POST['spai_product_stock_status'] ) ) : 'instock',
				'virtual'           => ! empty( $_POST['spai_product_virtual'] ),
				'downloadable'      => ! empty( $_POST['spai_product_downloadable'] ),
				'categories'        => $this->parse_tag_string( isset( $_POST['spai_product_categories'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_product_categories'] ) ) : '' ),
				'tags'              => $this->parse_tag_string( isset( $_POST['spai_product_tags'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_product_tags'] ) ) : '' ),
			)
		);

		$response = $controller->create_product_archetype( $request );
		if ( is_wp_error( $response ) ) {
			$this->add_library_error_notice( $response->get_error_message() );
			return;
		}

		$data = $response instanceof WP_REST_Response ? $response->get_data() : $response;

		add_settings_error(
			'spai_messages',
			'spai_library_product_archetype_created',
			sprintf(
				/* translators: %s: archetype name */
				__( 'Saved product archetype: %s', 'mumega-mcp' ),
				isset( $data['name'] ) ? $data['name'] : __( 'Untitled archetype', 'mumega-mcp' )
			),
			'updated'
		);
	}

	/**
	 * Create a draft WooCommerce product from a stored archetype.
	 *
	 * @return void
	 */
	private function handle_create_product_from_archetype() {
		$controller = $this->get_woocommerce_archetype_controller();
		if ( is_wp_error( $controller ) ) {
			$this->add_library_error_notice( $controller->get_error_message() );
			return;
		}

		$archetype_id = isset( $_POST['spai_action_product_archetype_id'] ) ? absint( wp_unslash( $_POST['spai_action_product_archetype_id'] ) ) : 0;
		$name         = isset( $_POST['spai_product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_product_name'] ) ) : '';
		if ( $archetype_id <= 0 ) {
			$this->add_library_error_notice( 'Enter a valid product archetype ID.' );
			return;
		}

		$request = new WP_REST_Request( 'POST', '/site-pilot-ai/v1/woocommerce/archetypes/' . $archetype_id . '/apply' );
		$request->set_param( 'id', $archetype_id );
		$request->set_body_params(
			array(
				'name'   => $name ? $name : 'Archetype Product Draft',
				'status' => 'draft',
			)
		);

		$response = $controller->apply_product_archetype( $request );
		if ( is_wp_error( $response ) ) {
			$this->add_library_error_notice( $response->get_error_message() );
			return;
		}

		$data       = $response instanceof WP_REST_Response ? $response->get_data() : $response;
		$product    = isset( $data['product'] ) && is_array( $data['product'] ) ? $data['product'] : array();
		$product_id = isset( $product['id'] ) ? absint( $product['id'] ) : 0;
		$edit_url   = $product_id ? admin_url( 'post.php?post=' . $product_id . '&action=edit' ) : admin_url( 'edit.php?post_type=product' );

		add_settings_error(
			'spai_messages',
			'spai_library_product_created',
			sprintf(
				/* translators: 1: product name 2: edit URL */
				__( 'Created draft product: %1$s. <a href="%2$s">Open product</a>.', 'mumega-mcp' ),
				esc_html( isset( $product['name'] ) ? $product['name'] : __( 'New Product', 'mumega-mcp' ) ),
				esc_url( $edit_url )
			),
			'updated'
		);
	}

	/**
	 * Delete a WooCommerce product archetype from the library.
	 *
	 * @return void
	 */
	private function handle_delete_product_archetype() {
		$controller = $this->get_woocommerce_archetype_controller();
		if ( is_wp_error( $controller ) ) {
			$this->add_library_error_notice( $controller->get_error_message() );
			return;
		}

		$archetype_id = isset( $_POST['spai_action_product_archetype_id'] ) ? absint( wp_unslash( $_POST['spai_action_product_archetype_id'] ) ) : 0;
		if ( $archetype_id <= 0 ) {
			$this->add_library_error_notice( 'Enter a valid product archetype ID.' );
			return;
		}

		$items = get_option( 'spai_wc_product_archetypes', array() );
		if ( ! is_array( $items ) ) {
			$items = array();
		}

		$remaining = array_values(
			array_filter(
				$items,
				function ( $item ) use ( $archetype_id ) {
					return ! isset( $item['id'] ) || (int) $item['id'] !== $archetype_id;
				}
			)
		);

		update_option( 'spai_wc_product_archetypes', $remaining, false );

		add_settings_error(
			'spai_messages',
			'spai_library_product_archetype_deleted',
			__( 'Product archetype removed from the library.', 'mumega-mcp' ),
			'updated'
		);
	}

	/**
	 * Create a design reference from admin inputs.
	 *
	 * @return void
	 */
	private function handle_create_design_reference() {
		if ( ! class_exists( 'Spai_Design_References' ) ) {
			$this->add_library_error_notice( 'Design reference library is not available.' );
			return;
		}

		$payload = array(
			'title'            => isset( $_POST['spai_design_reference_title'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_design_reference_title'] ) ) : '',
			'image_url'        => isset( $_POST['spai_design_reference_url'] ) ? esc_url_raw( wp_unslash( $_POST['spai_design_reference_url'] ) ) : '',
			'media_id'         => isset( $_POST['spai_design_reference_media_id'] ) ? absint( wp_unslash( $_POST['spai_design_reference_media_id'] ) ) : 0,
			'page_intent'      => isset( $_POST['spai_design_reference_intent'] ) ? sanitize_key( wp_unslash( $_POST['spai_design_reference_intent'] ) ) : '',
			'archetype_class'  => isset( $_POST['spai_design_reference_class'] ) ? sanitize_key( wp_unslash( $_POST['spai_design_reference_class'] ) ) : '',
			'style'            => isset( $_POST['spai_design_reference_style'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_design_reference_style'] ) ) : '',
			'notes'            => isset( $_POST['spai_design_reference_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['spai_design_reference_notes'] ) ) : '',
			'analysis_summary' => isset( $_POST['spai_design_reference_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['spai_design_reference_summary'] ) ) : '',
			'tags'             => $this->parse_tag_string( isset( $_POST['spai_design_reference_tags'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_design_reference_tags'] ) ) : '' ),
			'must_keep'        => $this->parse_line_list( isset( $_POST['spai_design_reference_must_keep'] ) ? wp_unslash( $_POST['spai_design_reference_must_keep'] ) : '' ),
			'avoid'            => $this->parse_line_list( isset( $_POST['spai_design_reference_avoid'] ) ? wp_unslash( $_POST['spai_design_reference_avoid'] ) : '' ),
			'section_outline'  => $this->parse_line_list( isset( $_POST['spai_design_reference_outline'] ) ? wp_unslash( $_POST['spai_design_reference_outline'] ) : '' ),
			'source_type'      => 'manual',
		);

		if ( ! empty( $_FILES['spai_design_reference_file']['tmp_name'] ) ) {
			$media  = new Spai_Media();
			$upload = $media->upload_file(
				$_FILES['spai_design_reference_file'],
				array(
					'title' => $payload['title'],
					'alt'   => $payload['title'],
				)
			);

			if ( is_wp_error( $upload ) ) {
				$this->add_library_error_notice( $upload->get_error_message() );
				return;
			}

			$payload['media_id']    = isset( $upload['id'] ) ? absint( $upload['id'] ) : 0;
			$payload['image_url']   = '';
			$payload['source_type'] = 'upload';
		} elseif ( ! empty( $payload['image_url'] ) ) {
			$payload['source_type'] = 'url';
		} elseif ( ! empty( $payload['media_id'] ) ) {
			$payload['source_type'] = 'media';
		}

		$references = new Spai_Design_References();
		$result     = $references->create_reference( $payload );

		if ( is_wp_error( $result ) ) {
			$this->add_library_error_notice( $result->get_error_message() );
			return;
		}

		add_settings_error(
			'spai_messages',
			'spai_library_design_reference_created',
			sprintf(
				/* translators: %s: design reference title */
				__( 'Saved design reference: %s', 'mumega-mcp' ),
				isset( $result['title'] ) ? $result['title'] : __( 'Untitled reference', 'mumega-mcp' )
			),
			'updated'
		);
	}

	/**
	 * Register a consistent library action error notice.
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function add_library_error_notice( $message ) {
		add_settings_error(
			'spai_messages',
			'spai_library_action_failed',
			$message,
			'error'
		);
	}

	/**
	 * Save the guided site profile and regenerate canonical site context.
	 *
	 * @return void
	 */
	private function handle_save_site_profile() {
		$profile = $this->sanitize_site_profile_input(
			isset( $_POST['spai_site_profile'] ) ? (array) wp_unslash( $_POST['spai_site_profile'] ) : array()
		);

		update_option( 'spai_site_profile', $profile );

		$context = $this->generate_site_context_from_profile( $profile );
		update_option( 'spai_site_context', $context );
		update_option( 'spai_site_context_updated', gmdate( 'Y-m-d H:i:s' ) );

		add_settings_error(
			'spai_messages',
			'spai_site_profile_saved',
			__( 'Structured site profile saved and site context regenerated.', 'mumega-mcp' ),
			'updated'
		);
	}

	/**
	 * Sanitize raw site profile input.
	 *
	 * @param array $input Raw profile fields.
	 * @return array
	 */
	private function sanitize_site_profile_input( $input ) {
		$fields = array(
			'brand_name',
			'brand_summary',
			'target_audience',
			'brand_voice',
			'visual_style',
			'primary_colors',
			'typography',
			'header_rules',
			'footer_rules',
			'core_page_patterns',
			'reusable_sections',
			'conversion_goals',
			'claims_to_avoid',
			'ai_instructions',
		);

		$profile = array();
		foreach ( $fields as $field ) {
			$value = isset( $input[ $field ] ) ? (string) $input[ $field ] : '';
			$value = sanitize_textarea_field( $value );
			$profile[ $field ] = trim( $value );
		}

		return $profile;
	}

	/**
	 * Build canonical Markdown site context from structured profile fields.
	 *
	 * @param array $profile Sanitized profile.
	 * @return string
	 */
	private function generate_site_context_from_profile( $profile ) {
		$profile = is_array( $profile ) ? $profile : array();
		$brand_name = ! empty( $profile['brand_name'] ) ? $profile['brand_name'] : get_bloginfo( 'name' );

		$lines   = array();
		$lines[] = '# ' . $brand_name . ' - Site Character';

		$mapping = array(
			'brand_summary'      => 'Brand Summary',
			'target_audience'    => 'Target Audience',
			'brand_voice'        => 'Brand Voice',
			'visual_style'       => 'Visual Style',
			'primary_colors'     => 'Colors',
			'typography'         => 'Typography',
			'header_rules'       => 'Header Rules',
			'footer_rules'       => 'Footer Rules',
			'core_page_patterns' => 'Core Page Patterns',
			'reusable_sections'  => 'Reusable Sections',
			'conversion_goals'   => 'Conversion Goals',
			'claims_to_avoid'    => 'Claims To Avoid',
			'ai_instructions'    => 'Instructions For AI Systems',
		);

		foreach ( $mapping as $key => $heading ) {
			if ( empty( $profile[ $key ] ) ) {
				continue;
			}
			$lines[] = '';
			$lines[] = '## ' . $heading;
			$lines[] = $this->normalize_profile_multiline_value( $profile[ $key ] );
		}

		return trim( implode( "\n", $lines ) );
	}

	/**
	 * Normalize profile values into Markdown-friendly blocks.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function normalize_profile_multiline_value( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		$lines = preg_split( "/\r\n|\r|\n/", $value );
		$lines = array_map( 'trim', $lines );
		$lines = array_values( array_filter( $lines, 'strlen' ) );

		if ( empty( $lines ) ) {
			return '';
		}

		$has_bullets = false;
		foreach ( $lines as $line ) {
			if ( preg_match( '/^[-*]\s+/', $line ) ) {
				$has_bullets = true;
				break;
			}
		}

		if ( 1 === count( $lines ) && ! $has_bullets ) {
			return $lines[0];
		}

		$formatted = array();
		foreach ( $lines as $line ) {
			if ( preg_match( '/^[-*]\s+/', $line ) ) {
				$formatted[] = preg_replace( '/^\*\s+/', '- ', $line );
			} else {
				$formatted[] = '- ' . $line;
			}
		}

		return implode( "\n", $formatted );
	}

	/**
	 * Create a new draft page from a saved design reference.
	 *
	 * @return void
	 */
	private function handle_create_page_from_design_reference() {
		if ( ! class_exists( 'Spai_Design_References' ) ) {
			$this->add_library_error_notice( 'Design reference library is not available.' );
			return;
		}

		$reference_id = isset( $_POST['spai_action_design_reference_id'] ) ? sanitize_key( wp_unslash( $_POST['spai_action_design_reference_id'] ) ) : '';
		$page_title   = isset( $_POST['spai_design_reference_page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['spai_design_reference_page_title'] ) ) : '';

		if ( '' === $reference_id ) {
			$this->add_library_error_notice( 'Enter a valid design reference ID.' );
			return;
		}

		$references = new Spai_Design_References();
		$reference  = $references->get_reference( $reference_id );

		if ( is_wp_error( $reference ) ) {
			$this->add_library_error_notice( $reference->get_error_message() );
			return;
		}

		if ( '' === $page_title ) {
			$page_title = ! empty( $reference['title'] ) ? $reference['title'] . ' Draft' : 'Design Reference Draft';
		}

		$page_id  = 0;
		$edit_url = '';

		if ( class_exists( 'Spai_Elementor_Pro' ) ) {
			$elementor = new Spai_Elementor_Pro();
			$result    = $elementor->create_landing_page(
				array(
					'title'    => $page_title,
					'status'   => 'draft',
					'sections' => $this->build_design_reference_sections( $reference ),
				)
			);

			if ( ! is_wp_error( $result ) ) {
				$page_id  = isset( $result['id'] ) ? absint( $result['id'] ) : 0;
				$edit_url = isset( $result['edit_url'] ) ? (string) $result['edit_url'] : '';
			}
		}

		if ( $page_id <= 0 ) {
			$content_lines = array();
			if ( ! empty( $reference['analysis_summary'] ) ) {
				$content_lines[] = $reference['analysis_summary'];
			}
			if ( ! empty( $reference['section_outline'] ) && is_array( $reference['section_outline'] ) ) {
				$content_lines[] = '';
				$content_lines[] = 'Section outline:';
				foreach ( $reference['section_outline'] as $section ) {
					$content_lines[] = '- ' . $section;
				}
			}

			$page_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'draft',
					'post_title'   => $page_title,
					'post_content' => implode( "\n", $content_lines ),
				),
				true
			);

			if ( is_wp_error( $page_id ) ) {
				$this->add_library_error_notice( $page_id->get_error_message() );
				return;
			}

			$page_id  = absint( $page_id );
			$edit_url = admin_url( 'post.php?post=' . $page_id . '&action=edit' );
		}

		update_post_meta( $page_id, '_spai_design_reference_id', $reference_id );
		update_post_meta( $page_id, '_spai_design_reference_title', isset( $reference['title'] ) ? (string) $reference['title'] : '' );
		update_post_meta( $page_id, '_spai_design_reference_intent', isset( $reference['page_intent'] ) ? (string) $reference['page_intent'] : '' );
		update_post_meta( $page_id, '_spai_design_reference_class', isset( $reference['archetype_class'] ) ? (string) $reference['archetype_class'] : '' );
		update_post_meta( $page_id, '_spai_design_reference_style', isset( $reference['style'] ) ? (string) $reference['style'] : '' );
		update_post_meta( $page_id, '_spai_design_reference_summary', isset( $reference['analysis_summary'] ) ? (string) $reference['analysis_summary'] : '' );
		update_post_meta( $page_id, '_spai_design_reference_outline', isset( $reference['section_outline'] ) ? array_values( (array) $reference['section_outline'] ) : array() );
		update_post_meta( $page_id, '_spai_design_reference_source_image', isset( $reference['image_url'] ) ? (string) $reference['image_url'] : '' );

		$linked_part_ids = $this->create_parts_from_design_reference_page( $page_id, $reference );
		if ( ! empty( $linked_part_ids ) ) {
			$existing_part_ids = isset( $reference['linked_part_ids'] ) && is_array( $reference['linked_part_ids'] ) ? $reference['linked_part_ids'] : array();
			$references->update_reference(
				$reference_id,
				array(
					'linked_part_ids' => array_values( array_unique( array_map( 'absint', array_merge( $existing_part_ids, $linked_part_ids ) ) ) ),
				)
			);
		}

		add_settings_error(
			'spai_messages',
			'spai_library_design_reference_page_created',
			sprintf(
				/* translators: 1: page title 2: edit URL 3: reusable part count */
				__( 'Created draft page from design reference: %1$s. <a href="%2$s">Open page</a>. Saved %3$d reusable parts.', 'mumega-mcp' ),
				esc_html( get_the_title( $page_id ) ),
				esc_url( $edit_url ),
				count( $linked_part_ids )
			),
			'updated'
		);
	}

	/**
	 * Build starter Elementor section definitions from a design reference.
	 *
	 * @param array $reference Design reference payload.
	 * @return array
	 */
	private function build_design_reference_sections( $reference ) {
		$outline = isset( $reference['section_outline'] ) && is_array( $reference['section_outline'] ) && ! empty( $reference['section_outline'] )
			? array_values( $reference['section_outline'] )
			: array( 'hero', 'content', 'cta' );

		$summary      = isset( $reference['analysis_summary'] ) ? trim( (string) $reference['analysis_summary'] ) : '';
		$style        = isset( $reference['style'] ) ? trim( (string) $reference['style'] ) : '';
		$must_keep    = isset( $reference['must_keep'] ) && is_array( $reference['must_keep'] ) ? $reference['must_keep'] : array();
		$sections     = array();
		$total        = count( $outline );
		$primary_cta  = 'Get Started';

		foreach ( $outline as $index => $section_name ) {
			$label   = $this->humanize_design_reference_label( $section_name );
			$is_hero = ( 0 === $index ) || false !== strpos( strtolower( $section_name ), 'hero' );
			$is_cta  = false !== strpos( strtolower( $section_name ), 'cta' );

			$widgets = array();
			$widgets[] = array(
				'type'     => 'heading',
				'settings' => array(
					'title' => $label,
					'size'  => $is_hero ? 'xxl' : 'xl',
				),
			);

			$text_lines = array();
			if ( $is_hero && '' !== $summary ) {
				$text_lines[] = $summary;
			} else {
				$text_lines[] = sprintf( 'Starter block for %s based on the saved design reference.', strtolower( $label ) );
			}

			if ( ! empty( $must_keep ) ) {
				$text_lines[] = 'Keep: ' . implode( ', ', array_slice( $must_keep, 0, 3 ) );
			}

			if ( '' !== $style ) {
				$text_lines[] = 'Style: ' . $style;
			}

			$widgets[] = array(
				'type'     => 'text-editor',
				'settings' => array(
					'editor' => implode( "\n\n", array_filter( $text_lines, 'strlen' ) ),
				),
			);

			if ( $is_hero || $is_cta || ( $total - 1 ) === $index ) {
				$widgets[] = array(
					'type'     => 'button',
					'settings' => array(
						'text' => $is_cta ? 'Request Demo' : $primary_cta,
						'size' => 'md',
					),
				);
			}

			$sections[] = array(
				'settings' => array(
					'padding' => array(
						'unit'   => 'px',
						'top'    => $is_hero ? 96 : 72,
						'right'  => 24,
						'bottom' => $is_hero ? 96 : 72,
						'left'   => 24,
						'isLinked' => false,
					),
				),
				'columns'  => array(
					array(
						'widgets' => $widgets,
					),
				),
			);
		}

		return $sections;
	}

	/**
	 * Convert a design reference section label into a readable heading.
	 *
	 * @param string $label Raw label.
	 * @return string
	 */
	private function humanize_design_reference_label( $label ) {
		$label = str_replace( array( '_', '-' ), ' ', (string) $label );
		$label = preg_replace( '/\s+/', ' ', $label );
		$label = trim( $label );

		if ( '' === $label ) {
			return 'Section';
		}

		return ucwords( $label );
	}

	/**
	 * Save top-level generated sections back into the reusable parts library.
	 *
	 * @param int   $page_id    Draft page ID.
	 * @param array $reference  Design reference payload.
	 * @return array
	 */
	private function create_parts_from_design_reference_page( $page_id, $reference ) {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			return array();
		}

		$raw_elements = get_post_meta( $page_id, '_elementor_data', true );
		$elements     = json_decode( $raw_elements, true );
		if ( ! is_array( $elements ) || empty( $elements ) ) {
			return array();
		}

		$outline   = isset( $reference['section_outline'] ) && is_array( $reference['section_outline'] ) ? array_values( $reference['section_outline'] ) : array();
		$style     = isset( $reference['style'] ) ? sanitize_text_field( (string) $reference['style'] ) : '';
		$base_tags = array_filter(
			array_merge(
				array( 'design-reference', 'starter' ),
				isset( $reference['tags'] ) && is_array( $reference['tags'] ) ? $reference['tags'] : array()
			),
			'strlen'
		);
		$reference_title = isset( $reference['title'] ) ? (string) $reference['title'] : 'Design Reference';
		$elementor       = new Spai_Elementor_Pro();
		$part_ids        = array();

		foreach ( array_values( $elements ) as $index => $element ) {
			if ( empty( $element['id'] ) ) {
				continue;
			}

			$section_key = isset( $outline[ $index ] ) ? (string) $outline[ $index ] : 'section';
			$part_title  = sprintf(
				'%s / %s',
				$reference_title,
				$this->humanize_design_reference_label( $section_key )
			);

			$result = $elementor->create_part_from_section(
				$page_id,
				(string) $element['id'],
				array(
					'title'      => $part_title,
					'part_kind'  => sanitize_key( $section_key ),
					'part_style' => $style,
					'part_tags'  => array_values( array_unique( array_merge( $base_tags, array( sanitize_key( $section_key ) ) ) ) ),
				)
			);

			if ( is_wp_error( $result ) ) {
				continue;
			}

			if ( ! empty( $result['id'] ) ) {
				$part_ids[] = absint( $result['id'] );
			}
		}

		return array_values( array_unique( array_filter( $part_ids ) ) );
	}

	/**
	 * Render activity log page.
	 */
	public function render_activity_log_page() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mumega-mcp' ) );
		}

		$page = new Spai_Activity_Log_Page();
		$page->render();
	}

	/**
	 * Get recent API activity rows.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public function get_recent_activity_rows( $limit = 10 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'spai_activity_log';

		$limit = max( 1, min( 50, absint( $limit ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, action, endpoint, method, status_code, created_at
				 FROM {$table}
				 ORDER BY created_at DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Get reusable library inventory for admin display.
	 *
	 * @return array
	 */
	public function get_library_inventory() {
		return array(
			'parts'              => $this->get_elementor_parts_inventory(),
			'page_archetypes'    => $this->get_elementor_archetypes_inventory( 'page' ),
			'product_archetypes' => $this->get_product_archetypes_inventory(),
			'design_references'  => $this->get_design_references_inventory(),
			'site_blueprints'    => class_exists( 'Spai_Site_Blueprints' ) ? Spai_Site_Blueprints::list_all() : array(),
		);
	}

	/**
	 * Get normalized library filter values from the request.
	 *
	 * @return array
	 */
	public function get_library_filters() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin filtering.
		$search = isset( $_GET['library_search'] ) ? sanitize_text_field( wp_unslash( $_GET['library_search'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin filtering.
		$asset_type = isset( $_GET['library_asset_type'] ) ? sanitize_key( wp_unslash( $_GET['library_asset_type'] ) ) : 'all';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin filtering.
		$class_filter = isset( $_GET['library_class'] ) ? sanitize_key( wp_unslash( $_GET['library_class'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only admin filtering.
		$style_filter = isset( $_GET['library_style'] ) ? sanitize_text_field( wp_unslash( $_GET['library_style'] ) ) : '';

		if ( ! in_array( $asset_type, array( 'all', 'archetypes', 'products', 'parts', 'references' ), true ) ) {
			$asset_type = 'all';
		}

		return array(
			'search'      => $search,
			'asset_type'  => $asset_type,
			'class'       => $class_filter,
			'style'       => $style_filter,
		);
	}

	/**
	 * Apply library filters to the inventory.
	 *
	 * @param array $inventory Inventory arrays.
	 * @param array $filters   Filter values.
	 * @return array
	 */
	public function filter_library_inventory( $inventory, $filters ) {
		$inventory = is_array( $inventory ) ? $inventory : array();
		$filters   = is_array( $filters ) ? $filters : array();

		foreach ( array( 'parts', 'page_archetypes', 'product_archetypes', 'design_references' ) as $key ) {
			$items = isset( $inventory[ $key ] ) && is_array( $inventory[ $key ] ) ? $inventory[ $key ] : array();
			$inventory[ $key ] = array_values(
				array_filter(
					$items,
					function ( $item ) use ( $key, $filters ) {
						return $this->library_item_matches_filters( $item, $key, $filters );
					}
				)
			);
		}

		if ( isset( $filters['asset_type'] ) ) {
			if ( 'archetypes' === $filters['asset_type'] ) {
				$inventory['product_archetypes'] = array();
				$inventory['parts']              = array();
				$inventory['design_references']  = array();
			} elseif ( 'products' === $filters['asset_type'] ) {
				$inventory['page_archetypes']   = array();
				$inventory['parts']             = array();
				$inventory['design_references'] = array();
			} elseif ( 'parts' === $filters['asset_type'] ) {
				$inventory['page_archetypes']    = array();
				$inventory['product_archetypes'] = array();
				$inventory['design_references']  = array();
			} elseif ( 'references' === $filters['asset_type'] ) {
				$inventory['page_archetypes']    = array();
				$inventory['product_archetypes'] = array();
				$inventory['parts']              = array();
			}
		}

		return $inventory;
	}

	/**
	 * Get unique filter options derived from current inventory.
	 *
	 * @param array $inventory Inventory arrays.
	 * @return array
	 */
	public function get_library_filter_options( $inventory ) {
		$options = array(
			'classes' => array(),
			'styles'  => array(),
		);

		foreach ( array( 'page_archetypes', 'product_archetypes', 'parts', 'design_references' ) as $bucket ) {
			$items = isset( $inventory[ $bucket ] ) && is_array( $inventory[ $bucket ] ) ? $inventory[ $bucket ] : array();
			foreach ( $items as $item ) {
				$class_value = '';
				if ( isset( $item['archetype_class'] ) ) {
					$class_value = (string) $item['archetype_class'];
				} elseif ( isset( $item['part_kind'] ) ) {
					$class_value = (string) $item['part_kind'];
				} elseif ( isset( $item['page_intent'] ) ) {
					$class_value = (string) $item['page_intent'];
				}

				$style_value = '';
				if ( isset( $item['archetype_style'] ) ) {
					$style_value = (string) $item['archetype_style'];
				} elseif ( isset( $item['part_style'] ) ) {
					$style_value = (string) $item['part_style'];
				} elseif ( isset( $item['style'] ) ) {
					$style_value = (string) $item['style'];
				}

				if ( '' !== $class_value ) {
					$options['classes'][ $class_value ] = $class_value;
				}

				if ( '' !== $style_value ) {
					$options['styles'][ $style_value ] = $style_value;
				}
			}
		}

		ksort( $options['classes'] );
		natcasesort( $options['styles'] );

		return $options;
	}

	/**
	 * Determine whether one library item matches the active filters.
	 *
	 * @param array  $item    Item data.
	 * @param string $bucket  Inventory bucket.
	 * @param array  $filters Filter values.
	 * @return bool
	 */
	private function library_item_matches_filters( $item, $bucket, $filters ) {
		if ( ! is_array( $item ) ) {
			return false;
		}

		$search = isset( $filters['search'] ) ? strtolower( (string) $filters['search'] ) : '';
		if ( '' !== $search ) {
			$haystack_parts = array();
			foreach ( array( 'title', 'name', 'archetype_class', 'archetype_style', 'part_kind', 'part_style', 'product_type', 'source_page_title', 'page_intent', 'style', 'source_type', 'analysis_summary', 'notes' ) as $field ) {
				if ( ! empty( $item[ $field ] ) ) {
					$haystack_parts[] = (string) $item[ $field ];
				}
			}
			if ( ! empty( $item['part_tags'] ) && is_array( $item['part_tags'] ) ) {
				$haystack_parts[] = implode( ' ', $item['part_tags'] );
			}
			if ( ! empty( $item['tags'] ) && is_array( $item['tags'] ) ) {
				$haystack_parts[] = implode( ' ', $item['tags'] );
			}
			if ( ! empty( $item['must_keep'] ) && is_array( $item['must_keep'] ) ) {
				$haystack_parts[] = implode( ' ', $item['must_keep'] );
			}
			if ( ! empty( $item['avoid'] ) && is_array( $item['avoid'] ) ) {
				$haystack_parts[] = implode( ' ', $item['avoid'] );
			}
			if ( ! empty( $item['section_outline'] ) && is_array( $item['section_outline'] ) ) {
				$haystack_parts[] = implode( ' ', $item['section_outline'] );
			}
			$haystack = strtolower( implode( ' ', $haystack_parts ) );
			if ( false === strpos( $haystack, $search ) ) {
				return false;
			}
		}

		$class_filter = isset( $filters['class'] ) ? (string) $filters['class'] : '';
		if ( '' !== $class_filter ) {
			$item_class = '';
			if ( 'parts' === $bucket ) {
				$item_class = isset( $item['part_kind'] ) ? (string) $item['part_kind'] : '';
			} elseif ( 'design_references' === $bucket ) {
				$item_class = isset( $item['archetype_class'] ) && '' !== (string) $item['archetype_class']
					? (string) $item['archetype_class']
					: ( isset( $item['page_intent'] ) ? (string) $item['page_intent'] : '' );
			} else {
				$item_class = isset( $item['archetype_class'] ) ? (string) $item['archetype_class'] : '';
			}
			if ( $class_filter !== $item_class ) {
				return false;
			}
		}

		$style_filter = isset( $filters['style'] ) ? (string) $filters['style'] : '';
		if ( '' !== $style_filter ) {
			$item_style = '';
			if ( 'parts' === $bucket ) {
				$item_style = isset( $item['part_style'] ) ? (string) $item['part_style'] : '';
			} elseif ( 'design_references' === $bucket ) {
				$item_style = isset( $item['style'] ) ? (string) $item['style'] : '';
			} else {
				$item_style = isset( $item['archetype_style'] ) ? (string) $item['archetype_style'] : '';
			}
			if ( $style_filter !== $item_style ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get Elementor reusable parts inventory.
	 *
	 * @return array
	 */
	private function get_elementor_parts_inventory() {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			return array();
		}

		$elementor = new Spai_Elementor_Pro();
		$parts     = $elementor->get_parts(
			array(
				'posts_per_page' => 100,
			)
		);

		if ( ! is_array( $parts ) ) {
			return array();
		}

		return array_map(
			array( $this, 'format_library_part_item' ),
			$parts
		);
	}

	/**
	 * Get Elementor archetype inventory filtered by scope.
	 *
	 * @param string $scope Archetype scope.
	 * @return array
	 */
	private function get_elementor_archetypes_inventory( $scope ) {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			return array();
		}

		$elementor  = new Spai_Elementor_Pro();
		$archetypes = $elementor->get_archetypes(
			array(
				'scope'          => $scope,
				'posts_per_page' => 100,
			)
		);

		if ( ! is_array( $archetypes ) ) {
			return array();
		}

		return array_map(
			array( $this, 'format_library_archetype_item' ),
			$archetypes
		);
	}

	/**
	 * Get WooCommerce product archetype inventory.
	 *
	 * @return array
	 */
	private function get_product_archetypes_inventory() {
		$archetypes = get_option( 'spai_wc_product_archetypes', array() );
		if ( ! is_array( $archetypes ) ) {
			return array();
		}

		$result = array();
		foreach ( $archetypes as $archetype ) {
			if ( ! is_array( $archetype ) ) {
				continue;
			}

			$product_data = isset( $archetype['product_data'] ) && is_array( $archetype['product_data'] )
				? $archetype['product_data']
				: array();

			$result[] = array(
				'id'              => isset( $archetype['id'] ) ? absint( $archetype['id'] ) : 0,
				'name'            => isset( $archetype['name'] ) ? (string) $archetype['name'] : '',
				'archetype_class' => isset( $archetype['archetype_class'] ) ? (string) $archetype['archetype_class'] : '',
				'archetype_style' => isset( $archetype['archetype_style'] ) ? (string) $archetype['archetype_style'] : '',
				'brief'           => isset( $archetype['brief'] ) ? (string) $archetype['brief'] : '',
				'product_type'    => isset( $archetype['product_type'] ) ? (string) $archetype['product_type'] : '',
				'status'          => isset( $product_data['status'] ) ? (string) $product_data['status'] : '',
				'updated_at'      => isset( $archetype['updated_at'] ) ? (string) $archetype['updated_at'] : '',
			);
		}

		return $result;
	}

	/**
	 * Get design reference inventory.
	 *
	 * @return array
	 */
	private function get_design_references_inventory() {
		if ( ! class_exists( 'Spai_Design_References' ) ) {
			return array();
		}

		$references = new Spai_Design_References();
		$result     = $references->list_references(
			array(
				'per_page' => 100,
				'page'     => 1,
			)
		);

		$items = isset( $result['references'] ) && is_array( $result['references'] ) ? $result['references'] : array();

		return array_map(
			array( $this, 'format_library_design_reference_item' ),
			$items
		);
	}

	/**
	 * Format a reusable Elementor part for admin display.
	 *
	 * @param array $part Part data.
	 * @return array
	 */
	private function format_library_part_item( $part ) {
		$source_page_id = ! empty( $part['source_page_id'] ) ? absint( $part['source_page_id'] ) : 0;
		$part_id        = isset( $part['id'] ) ? absint( $part['id'] ) : 0;
		$linked_refs    = $part_id ? $this->get_design_reference_links_for_asset( $part_id, 'part' ) : array();

		return array(
			'id'                => $part_id,
			'title'             => isset( $part['title'] ) ? (string) $part['title'] : '',
			'type'              => isset( $part['type'] ) ? (string) $part['type'] : '',
			'part_kind'         => isset( $part['part_kind'] ) ? (string) $part['part_kind'] : '',
			'part_style'        => isset( $part['part_style'] ) ? (string) $part['part_style'] : '',
			'part_tags'         => isset( $part['part_tags'] ) && is_array( $part['part_tags'] ) ? $part['part_tags'] : array(),
			'source_page_id'    => $source_page_id,
			'source_page_title' => $source_page_id ? get_the_title( $source_page_id ) : '',
			'provenance_label'  => $source_page_id ? 'Elementor template from live page' : 'Elementor template',
			'reference_count'   => count( $linked_refs ),
			'linked_references' => $linked_refs,
			'edit_url'          => isset( $part['edit_url'] ) ? (string) $part['edit_url'] : '',
			'modified'          => isset( $part['modified'] ) ? (string) $part['modified'] : '',
		);
	}

	/**
	 * Format an Elementor archetype for admin display.
	 *
	 * @param array $archetype Archetype data.
	 * @return array
	 */
	private function format_library_archetype_item( $archetype ) {
		$archetype_id = isset( $archetype['id'] ) ? absint( $archetype['id'] ) : 0;
		$linked_refs  = $archetype_id ? $this->get_design_reference_links_for_asset( $archetype_id, 'archetype' ) : array();

		return array(
			'id'              => $archetype_id,
			'title'           => isset( $archetype['title'] ) ? (string) $archetype['title'] : '',
			'type'            => isset( $archetype['type'] ) ? (string) $archetype['type'] : '',
			'archetype_scope' => isset( $archetype['archetype_scope'] ) ? (string) $archetype['archetype_scope'] : '',
			'archetype_class' => isset( $archetype['archetype_class'] ) ? (string) $archetype['archetype_class'] : '',
			'archetype_style' => isset( $archetype['archetype_style'] ) ? (string) $archetype['archetype_style'] : '',
			'provenance_label'=> 'Elementor template with archetype metadata',
			'reference_count' => count( $linked_refs ),
			'linked_references' => $linked_refs,
			'edit_url'        => isset( $archetype['edit_url'] ) ? (string) $archetype['edit_url'] : '',
			'modified'        => isset( $archetype['modified'] ) ? (string) $archetype['modified'] : '',
		);
	}

	/**
	 * Format a design reference for admin display.
	 *
	 * @param array $reference Design reference data.
	 * @return array
	 */
	private function format_library_design_reference_item( $reference ) {
		$reference_id = isset( $reference['id'] ) ? (string) $reference['id'] : '';
		$linked_pages = $reference_id ? $this->get_pages_for_design_reference( $reference_id ) : array();

		return array(
			'id'                 => $reference_id,
			'title'              => isset( $reference['title'] ) ? (string) $reference['title'] : '',
			'media_id'           => isset( $reference['media_id'] ) ? absint( $reference['media_id'] ) : 0,
			'image_url'          => isset( $reference['image_url'] ) ? (string) $reference['image_url'] : '',
			'page_intent'        => isset( $reference['page_intent'] ) ? (string) $reference['page_intent'] : '',
			'archetype_class'    => isset( $reference['archetype_class'] ) ? (string) $reference['archetype_class'] : '',
			'style'              => isset( $reference['style'] ) ? (string) $reference['style'] : '',
			'source_type'        => isset( $reference['source_type'] ) ? (string) $reference['source_type'] : '',
			'notes'              => isset( $reference['notes'] ) ? (string) $reference['notes'] : '',
			'analysis_summary'   => isset( $reference['analysis_summary'] ) ? (string) $reference['analysis_summary'] : '',
			'tags'               => isset( $reference['tags'] ) && is_array( $reference['tags'] ) ? $reference['tags'] : array(),
			'must_keep'          => isset( $reference['must_keep'] ) && is_array( $reference['must_keep'] ) ? $reference['must_keep'] : array(),
			'avoid'              => isset( $reference['avoid'] ) && is_array( $reference['avoid'] ) ? $reference['avoid'] : array(),
			'section_outline'    => isset( $reference['section_outline'] ) && is_array( $reference['section_outline'] ) ? $reference['section_outline'] : array(),
			'linked_archetype_ids' => isset( $reference['linked_archetype_ids'] ) && is_array( $reference['linked_archetype_ids'] ) ? $reference['linked_archetype_ids'] : array(),
			'linked_part_ids'    => isset( $reference['linked_part_ids'] ) && is_array( $reference['linked_part_ids'] ) ? $reference['linked_part_ids'] : array(),
			'linked_archetype_count' => isset( $reference['linked_archetype_ids'] ) && is_array( $reference['linked_archetype_ids'] ) ? count( $reference['linked_archetype_ids'] ) : 0,
			'linked_part_count' => isset( $reference['linked_part_ids'] ) && is_array( $reference['linked_part_ids'] ) ? count( $reference['linked_part_ids'] ) : 0,
			'page_count'         => count( $linked_pages ),
			'linked_pages'       => $linked_pages,
			'updated_at'         => isset( $reference['updated_at'] ) ? (string) $reference['updated_at'] : '',
		);
	}

	/**
	 * Count draft/pages linked to a design reference.
	 *
	 * @param string $reference_id Reference ID.
	 * @return int
	 */
	private function count_pages_for_design_reference( $reference_id ) {
		global $wpdb;

		$reference_id = (string) $reference_id;
		if ( '' === $reference_id ) {
			return 0;
		}

		$postmeta = $wpdb->postmeta;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- lightweight admin inventory query.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$postmeta} WHERE meta_key = %s AND meta_value = %s",
				'_spai_design_reference_id',
				$reference_id
			)
		);

		return absint( $count );
	}

	/**
	 * Get page links for a design reference.
	 *
	 * @param string $reference_id Reference ID.
	 * @return array
	 */
	private function get_pages_for_design_reference( $reference_id ) {
		global $wpdb;

		$reference_id = (string) $reference_id;
		if ( '' === $reference_id ) {
			return array();
		}

		$postmeta = $wpdb->postmeta;
		$posts    = $wpdb->posts;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- lightweight admin inventory query.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title
				FROM {$posts} p
				INNER JOIN {$postmeta} pm ON p.ID = pm.post_id
				WHERE pm.meta_key = %s AND pm.meta_value = %s
				ORDER BY p.ID DESC",
				'_spai_design_reference_id',
				$reference_id
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static function ( $row ) {
				$page_id = isset( $row['ID'] ) ? absint( $row['ID'] ) : 0;
				return array(
					'id'    => $page_id,
					'title' => isset( $row['post_title'] ) ? (string) $row['post_title'] : '',
					'url'   => $page_id ? admin_url( 'post.php?post=' . $page_id . '&action=edit' ) : '',
				);
			},
			$rows
		);
	}

	/**
	 * Count design references linking to a given part or archetype.
	 *
	 * @param int    $asset_id Asset post ID.
	 * @param string $type     Asset type: part or archetype.
	 * @return int
	 */
	private function count_design_references_linking_asset( $asset_id, $type ) {
		return count( $this->get_design_reference_links_for_asset( $asset_id, $type ) );
	}

	/**
	 * Get design reference links for a given part or archetype.
	 *
	 * @param int    $asset_id Asset post ID.
	 * @param string $type     Asset type: part or archetype.
	 * @return array
	 */
	private function get_design_reference_links_for_asset( $asset_id, $type ) {
		$asset_id   = absint( $asset_id );
		$type       = 'archetype' === $type ? 'archetype' : 'part';
		$option_key = 'archetype' === $type ? 'linked_archetype_ids' : 'linked_part_ids';

		if ( $asset_id <= 0 ) {
			return array();
		}

		$references = get_option( 'spai_design_references', array() );
		if ( ! is_array( $references ) ) {
			return array();
		}

		$results = array();
		foreach ( $references as $reference ) {
			$ids = isset( $reference[ $option_key ] ) && is_array( $reference[ $option_key ] ) ? array_map( 'absint', $reference[ $option_key ] ) : array();
			if ( in_array( $asset_id, $ids, true ) ) {
				$reference_id = isset( $reference['id'] ) ? (string) $reference['id'] : '';
				$results[]    = array(
					'id'    => $reference_id,
					'title' => isset( $reference['title'] ) ? (string) $reference['title'] : '',
					'url'   => admin_url( 'admin.php?page=' . self::LIBRARY_PAGE_SLUG . '&library_asset_type=references&library_search=' . rawurlencode( $reference_id ) ),
				);
			}
		}

		return $results;
	}

	/**
	 * Display admin notices.
	 */
	public function admin_notices() {
		settings_errors( 'spai_messages' );
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
			__( 'Settings', 'mumega-mcp' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add network admin menu page for multisite.
	 */
	public function add_network_admin_menu() {
		add_menu_page(
			__( 'MCPWP — Network', 'mumega-mcp' ),
			__( 'MCPWP', 'mumega-mcp' ),
			'manage_network_plugins',
			'site-pilot-ai-network',
			array( $this, 'render_network_admin_page' ),
			self::MENU_ICON,
			80
		);
	}

	/**
	 * Handle "Setup All Sites" POST action from network admin page.
	 */
	private function handle_network_setup_all() {
		if ( ! isset( $_POST['spai_network_setup_all'] ) ) {
			return;
		}

		check_admin_referer( 'spai_network_setup_all', 'spai_network_nonce' );

		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			return;
		}

		require_once SPAI_PLUGIN_DIR . 'includes/class-spai-activator.php';

		$sites = get_sites( array( 'fields' => 'ids' ) );
		$count = 0;

		foreach ( $sites as $blog_id ) {
			switch_to_blog( $blog_id );
			Spai_Activator::activate();
			$count++;
			restore_current_blog();
		}

		add_settings_error(
			'spai_network_messages',
			'spai_network_setup_done',
			sprintf(
				/* translators: %d: number of sites */
				__( 'MCPWP activated on %d site(s).', 'mumega-mcp' ),
				$count
			),
			'updated'
		);
	}

	/**
	 * Render the network admin page.
	 */
	public function render_network_admin_page() {
		if ( ! current_user_can( 'manage_network_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mumega-mcp' ) );
		}

		$this->handle_network_setup_all();

		$sites      = get_sites( array( 'number' => 500 ) );
		$site_data  = array();

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );

			$version        = get_option( 'spai_version', '' );
			$has_api_key    = ! empty( get_option( 'spai_api_key' ) );
			$scoped_keys    = get_option( 'spai_api_keys', array() );
			$active_keys    = 0;
			if ( is_array( $scoped_keys ) ) {
				foreach ( $scoped_keys as $key ) {
					if ( empty( $key['revoked_at'] ) ) {
						$active_keys++;
					}
				}
			}

			$tool_count = 0;
			if ( class_exists( 'Spai_MCP_Tool_Registry' ) ) {
				$registry   = new Spai_MCP_Tool_Registry();
				$tool_count = count( $registry->get_all_tools() );
			}

			$site_data[] = array(
				'blog_id'     => $site->blog_id,
				'blogname'    => get_option( 'blogname', $site->domain . $site->path ),
				'siteurl'     => get_option( 'siteurl' ),
				'version'     => $version,
				'has_api_key' => $has_api_key,
				'active_keys' => $active_keys,
				'tool_count'  => $tool_count,
			);

			restore_current_blog();
		}

		settings_errors( 'spai_network_messages' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MCPWP — Network Overview', 'mumega-mcp' ); ?></h1>

			<form method="post">
				<?php wp_nonce_field( 'spai_network_setup_all', 'spai_network_nonce' ); ?>
				<p>
					<input type="submit" name="spai_network_setup_all" class="button button-primary"
						value="<?php esc_attr_e( 'Setup All Sites', 'mumega-mcp' ); ?>"
						onclick="return confirm('<?php echo esc_js( __( 'Run activation (tables, options, bot user) on every site in the network?', 'mumega-mcp' ) ); ?>');" />
					<span class="description"><?php esc_html_e( 'Runs activation on every site to ensure tables, options, and the bot user are provisioned.', 'mumega-mcp' ); ?></span>
				</p>
			</form>

			<table class="widefat striped" style="margin-top:20px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Site', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'URL', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Version', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'API Key', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Active Keys', 'mumega-mcp' ); ?></th>
						<th><?php esc_html_e( 'Tools', 'mumega-mcp' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $site_data as $s ) : ?>
					<tr>
						<td><?php echo esc_html( $s['blog_id'] ); ?></td>
						<td><?php echo esc_html( $s['blogname'] ); ?></td>
						<td><a href="<?php echo esc_url( $s['siteurl'] ); ?>" target="_blank"><?php echo esc_html( $s['siteurl'] ); ?></a></td>
						<td>
							<?php if ( $s['version'] ) : ?>
								<?php echo esc_html( $s['version'] ); ?>
							<?php else : ?>
								<span style="color:#b32d2e;"><?php esc_html_e( 'Not activated', 'mumega-mcp' ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $s['has_api_key'] ) : ?>
								<span style="color:#00a32a;">&#10003;</span>
							<?php else : ?>
								<span style="color:#b32d2e;">&#10007;</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $s['active_keys'] ); ?></td>
						<td><?php echo esc_html( $s['tool_count'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Get capabilities for display.
	 *
	 * @return array Capabilities.
	 */
	public function get_capabilities_display() {
		$core = new Spai_Core();
		$capabilities = $core->get_capabilities();

		$display = array();

		// Elementor
		$display['elementor'] = array(
			'label'  => __( 'Elementor', 'mumega-mcp' ),
			'active' => $capabilities['elementor'],
			'pro'    => $capabilities['elementor_pro'],
		);

		// SEO
		$seo_active = $capabilities['yoast'] || $capabilities['rankmath'] || $capabilities['aioseo'] || $capabilities['seopress'];
		$seo_name = '';
		if ( $capabilities['yoast'] ) {
			$seo_name = 'Yoast SEO';
		} elseif ( $capabilities['rankmath'] ) {
			$seo_name = 'RankMath';
		} elseif ( $capabilities['aioseo'] ) {
			$seo_name = 'All in One SEO';
		} elseif ( $capabilities['seopress'] ) {
			$seo_name = 'SEOPress';
		}
		$display['seo'] = array(
			'label'  => __( 'SEO Plugin', 'mumega-mcp' ),
			'active' => $seo_active,
			'name'   => $seo_name,
		);

		// Forms
		$forms_active = $capabilities['cf7'] || $capabilities['wpforms'] || $capabilities['gravityforms'] || $capabilities['ninjaforms'];
		$forms = array();
		if ( $capabilities['cf7'] ) {
			$forms[] = 'CF7';
		}
		if ( $capabilities['wpforms'] ) {
			$forms[] = 'WPForms';
		}
		if ( $capabilities['gravityforms'] ) {
			$forms[] = 'Gravity Forms';
		}
		if ( $capabilities['ninjaforms'] ) {
			$forms[] = 'Ninja Forms';
		}
		$display['forms'] = array(
			'label'  => __( 'Form Plugins', 'mumega-mcp' ),
			'active' => $forms_active,
			'names'  => $forms,
		);

		// WooCommerce
		$display['woocommerce'] = array(
			'label'  => __( 'WooCommerce', 'mumega-mcp' ),
			'active' => $capabilities['woocommerce'],
		);

		return $display;
	}

	/**
	 * Build the WooCommerce archetype controller used by admin actions.
	 *
	 * @return Spai_REST_WooCommerce|WP_Error
	 */
	private function get_woocommerce_archetype_controller() {
		if ( ! class_exists( 'Spai_REST_WooCommerce' ) || ! class_exists( 'Spai_WooCommerce' ) ) {
			return new WP_Error( 'missing_woocommerce_controller', __( 'WooCommerce archetype tools are not available.', 'mumega-mcp' ) );
		}

		$handler = new Spai_WooCommerce();
		if ( ! $handler->is_active() ) {
			return new WP_Error( 'wc_not_active', __( 'WooCommerce is not active on this site.', 'mumega-mcp' ) );
		}

		return new Spai_REST_WooCommerce( $handler );
	}
}
