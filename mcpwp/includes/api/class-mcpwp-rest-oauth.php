<?php
/**
 * OAuth 2.1 Authorization Server — HTTP surface
 *
 * Implements the four public endpoints required for an MCP OAuth connector:
 *
 *   GET  /.well-known/oauth-protected-resource   (RFC 9728, served at domain root)
 *   GET  /.well-known/oauth-authorization-server (RFC 8414, served at domain root)
 *   GET  /wp-json/mcpwp/v1/oauth/authorize       (interactive consent — WP REST)
 *   POST /wp-json/mcpwp/v1/oauth/token           (token exchange — WP REST)
 *
 * The token backend (issue_oauth_access_token, authenticate_oauth_access_token,
 * get_oauth_settings, verify_oauth_client_credentials) lives in trait-mcpwp-api-auth.php
 * and is reused via the Mcpwp_REST_API base class.
 *
 * Security properties enforced here:
 *  - PKCE S256 mandatory; plain/absent rejected at authorize AND token steps.
 *  - redirect_uri must exact-match an entry in oauth_redirect_uris allow-list.
 *  - Auth codes are single-use (transient deleted on first use) with 60s TTL.
 *  - Code bound to client_id + redirect_uri + code_challenge + user_id.
 *  - Consent POST protected by a wp_nonce.
 *  - constant-time comparison (hash_equals) everywhere.
 *  - No token/secret material written to any log or query-string persistence.
 *  - All endpoints gated on oauth_enabled setting.
 *
 * @package MCPWP
 * @since   3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OAuth 2.1 REST controller.
 */
class Mcpwp_REST_OAuth extends Mcpwp_REST_API {

	// -------------------------------------------------------------------------
	// Route registration
	// -------------------------------------------------------------------------

	/**
	 * Register REST routes (authorize + token).
	 *
	 * The .well-known endpoints are served via the parse_request hook registered
	 * in Mcpwp_OAuth_Well_Known::init(), not as REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/oauth/authorize',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_authorize_get' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_authorize_post' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/oauth/token',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_token' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// GET /oauth/authorize — display consent screen (or redirect to WP login)
	// -------------------------------------------------------------------------

	/**
	 * Handle GET /oauth/authorize.
	 *
	 * Validates parameters, forces WP login if user is not authenticated, then
	 * renders a minimal consent screen.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|void May redirect (wp_redirect + exit).
	 */
	public function handle_authorize_get( $request ) {
		if ( ! $this->is_oauth_enabled() ) {
			return $this->oauth_error_response( 'server_error', 'OAuth is not enabled on this site.', 501 );
		}

		$params       = $this->extract_authorize_params( $request );
		$validate_err = $this->validate_authorize_params( $params );
		if ( is_wp_error( $validate_err ) ) {
			return $this->wpe_to_response( $validate_err );
		}

		// Require logged-in WP user.
		if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
			$callback_url = add_query_arg( $params, rest_url( $this->namespace . '/oauth/authorize' ) );
			$login_url    = wp_login_url( $callback_url );
			wp_redirect( $login_url );
			exit;
		}

		$this->render_consent_screen( $params );
		exit;
	}

	// -------------------------------------------------------------------------
	// POST /oauth/authorize — process consent form submission
	// -------------------------------------------------------------------------

	/**
	 * Handle POST /oauth/authorize (consent form submission).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|void May redirect (wp_redirect + exit).
	 */
	public function handle_authorize_post( $request ) {
		if ( ! $this->is_oauth_enabled() ) {
			return $this->oauth_error_response( 'server_error', 'OAuth is not enabled on this site.', 501 );
		}

		if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
			return $this->oauth_error_response( 'access_denied', 'Authentication required.', 401 );
		}

		// Verify CSRF nonce.
		$nonce = $request->get_param( '_mcpwp_oauth_nonce' );
		if ( ! wp_verify_nonce( (string) $nonce, 'mcpwp_oauth_consent' ) ) {
			return $this->oauth_error_response( 'invalid_request', 'Invalid or expired nonce.', 400 );
		}

		$params       = $this->extract_authorize_params( $request );
		$validate_err = $this->validate_authorize_params( $params );
		if ( is_wp_error( $validate_err ) ) {
			return $this->wpe_to_response( $validate_err );
		}

		// Denial path — redirect with error.
		$action = sanitize_key( (string) $request->get_param( 'action' ) );
		if ( 'deny' === $action ) {
			$redirect = add_query_arg(
				array(
					'error'             => 'access_denied',
					'error_description' => 'User denied access.',
					'state'             => $params['state'],
				),
				$params['redirect_uri']
			);
			wp_redirect( $redirect );
			exit;
		}

		// Approval path — generate auth code.
		$code          = bin2hex( random_bytes( 32 ) );
		$user_id       = get_current_user_id();
		$transient_key = 'mcpwp_oauth_code_' . $code;

		set_transient(
			$transient_key,
			array(
				'client_id'      => $params['client_id'],
				'redirect_uri'   => $params['redirect_uri'],
				'code_challenge' => $params['code_challenge'],
				'scope'          => $params['scope'],
				'user_id'        => $user_id,
				'expires'        => time() + 60,
			),
			60
		);

		$redirect = add_query_arg(
			array_filter( array(
				'code'  => $code,
				'state' => $params['state'],
			) ),
			$params['redirect_uri']
		);

		wp_redirect( $redirect );
		exit;
	}

	// -------------------------------------------------------------------------
	// POST /oauth/token — token exchange
	// -------------------------------------------------------------------------

	/**
	 * Handle POST /oauth/token.
	 *
	 * Supports grant_type=authorization_code and grant_type=refresh_token.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function handle_token( $request ) {
		if ( ! $this->is_oauth_enabled() ) {
			return $this->oauth_error_response( 'server_error', 'OAuth is not enabled on this site.', 501 );
		}

		$grant_type = sanitize_key( (string) $request->get_param( 'grant_type' ) );

		if ( 'authorization_code' === $grant_type ) {
			return $this->handle_token_authorization_code( $request );
		}

		if ( 'refresh_token' === $grant_type ) {
			return $this->handle_token_refresh( $request );
		}

		return $this->oauth_error_response( 'unsupported_grant_type', 'Only authorization_code and refresh_token are supported.', 400 );
	}

	// -------------------------------------------------------------------------
	// Token exchange — authorization_code
	// -------------------------------------------------------------------------

	/**
	 * Process the authorization_code token exchange.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	private function handle_token_authorization_code( $request ) {
		$code          = sanitize_text_field( (string) $request->get_param( 'code' ) );
		$redirect_uri  = esc_url_raw( (string) $request->get_param( 'redirect_uri' ) );
		$code_verifier = sanitize_text_field( (string) $request->get_param( 'code_verifier' ) );
		$client_id     = sanitize_key( (string) $request->get_param( 'client_id' ) );
		$client_secret = (string) $request->get_param( 'client_secret' );

		if ( empty( $code ) ) {
			return $this->oauth_error_response( 'invalid_request', 'code is required.', 400 );
		}
		if ( empty( $redirect_uri ) ) {
			return $this->oauth_error_response( 'invalid_request', 'redirect_uri is required.', 400 );
		}
		if ( empty( $code_verifier ) ) {
			return $this->oauth_error_response( 'invalid_request', 'code_verifier is required (PKCE mandatory).', 400 );
		}

		// Read + immediately delete the code transient (single-use).
		$transient_key = 'mcpwp_oauth_code_' . $code;
		$stored        = get_transient( $transient_key );
		delete_transient( $transient_key );

		if ( ! is_array( $stored ) || empty( $stored['code_challenge'] ) ) {
			return $this->oauth_error_response( 'invalid_grant', 'Authorization code is invalid or has expired.', 400 );
		}

		// Expiry double-check (transient TTL should cover this, but be explicit).
		if ( isset( $stored['expires'] ) && time() > (int) $stored['expires'] ) {
			return $this->oauth_error_response( 'invalid_grant', 'Authorization code has expired.', 400 );
		}

		// redirect_uri must match stored value exactly (constant-time).
		if ( ! hash_equals( $stored['redirect_uri'], $redirect_uri ) ) {
			return $this->oauth_error_response( 'invalid_grant', 'redirect_uri mismatch.', 400 );
		}

		// PKCE: base64url(SHA256(code_verifier)) must match stored code_challenge.
		$computed_challenge = $this->compute_pkce_challenge( $code_verifier );
		if ( ! hash_equals( (string) $stored['code_challenge'], $computed_challenge ) ) {
			return $this->oauth_error_response( 'invalid_grant', 'PKCE verification failed.', 400 );
		}

		// Optional client credential check (when client_secret is supplied).
		if ( ! empty( $client_id ) && ! empty( $client_secret ) ) {
			if ( ! $this->verify_oauth_client_credentials( $client_id, $client_secret ) ) {
				return $this->oauth_error_response( 'invalid_client', 'Invalid client credentials.', 401 );
			}
		} elseif ( ! empty( $client_id ) ) {
			// client_id present without secret — validate it matches configured id.
			$oauth_settings = $this->get_oauth_settings();
			if ( ! hash_equals( $oauth_settings['oauth_client_id'], sanitize_key( $client_id ) ) ) {
				return $this->oauth_error_response( 'invalid_client', 'Invalid client_id.', 401 );
			}
		}

		// Parse scopes from stored value.
		$scope_string = isset( $stored['scope'] ) ? (string) $stored['scope'] : 'read write';
		$scopes       = $this->parse_scope_string( $scope_string );

		// Issue token via existing backend.
		$oauth_settings = $this->get_oauth_settings();
		$ttl            = (int) $oauth_settings['oauth_token_ttl'];
		$token_response = $this->issue_oauth_access_token( $scopes, $ttl );

		return new WP_REST_Response( $token_response, 200 );
	}

	// -------------------------------------------------------------------------
	// Token exchange — refresh_token
	// -------------------------------------------------------------------------

	/**
	 * Process the refresh_token grant.
	 *
	 * The refresh token is the previous mcpwp_at_ access token.  We validate it
	 * is still alive in the transient store, then issue a fresh access token with
	 * the same scopes.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	private function handle_token_refresh( $request ) {
		$refresh_token = sanitize_text_field( (string) $request->get_param( 'refresh_token' ) );

		if ( empty( $refresh_token ) ) {
			return $this->oauth_error_response( 'invalid_request', 'refresh_token is required.', 400 );
		}

		if ( ! $this->looks_like_oauth_access_token( $refresh_token ) ) {
			return $this->oauth_error_response( 'invalid_grant', 'Invalid refresh token format.', 400 );
		}

		$record = get_transient( $this->get_oauth_token_transient_key( $refresh_token ) );
		if ( ! is_array( $record ) || empty( $record['scopes'] ) ) {
			return $this->oauth_error_response( 'invalid_grant', 'Refresh token is invalid or has expired.', 400 );
		}

		$scopes         = $this->sanitize_scopes( (array) $record['scopes'] );
		$oauth_settings = $this->get_oauth_settings();
		$ttl            = (int) $oauth_settings['oauth_token_ttl'];
		$token_response = $this->issue_oauth_access_token( $scopes, $ttl );

		return new WP_REST_Response( $token_response, 200 );
	}

	// -------------------------------------------------------------------------
	// Parameter extraction + validation
	// -------------------------------------------------------------------------

	/**
	 * Extract authorize endpoint parameters from request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array Param map.
	 */
	public function extract_authorize_params( $request ) {
		return array(
			'response_type'         => sanitize_key( (string) $request->get_param( 'response_type' ) ),
			'client_id'             => sanitize_key( (string) $request->get_param( 'client_id' ) ),
			'redirect_uri'          => esc_url_raw( (string) $request->get_param( 'redirect_uri' ) ),
			'code_challenge'        => sanitize_text_field( (string) $request->get_param( 'code_challenge' ) ),
			'code_challenge_method' => strtoupper( sanitize_key( (string) $request->get_param( 'code_challenge_method' ) ) ),
			'scope'                 => sanitize_text_field( (string) $request->get_param( 'scope' ) ),
			'state'                 => sanitize_text_field( (string) $request->get_param( 'state' ) ),
		);
	}

	/**
	 * Validate authorize endpoint parameters.
	 *
	 * Returns WP_Error on first validation failure.  Errors are returned to the
	 * caller rather than via redirect because we may not have a valid redirect_uri
	 * yet (and redirecting with an untrusted redirect_uri would be an open-redirect).
	 *
	 * @param array $params Extracted params.
	 * @return true|WP_Error
	 */
	public function validate_authorize_params( $params ) {
		if ( 'code' !== $params['response_type'] ) {
			return new WP_Error( 'invalid_request', 'response_type must be "code".', array( 'status' => 400 ) );
		}

		$oauth_settings = $this->get_oauth_settings();

		// Validate client_id (constant-time).
		if ( empty( $params['client_id'] ) || ! hash_equals( $oauth_settings['oauth_client_id'], $params['client_id'] ) ) {
			return new WP_Error( 'invalid_client', 'Unknown or missing client_id.', array( 'status' => 400 ) );
		}

		// Validate redirect_uri against allow-list.
		if ( empty( $params['redirect_uri'] ) ) {
			return new WP_Error( 'invalid_request', 'redirect_uri is required.', array( 'status' => 400 ) );
		}
		if ( ! $this->is_redirect_uri_allowed( $params['redirect_uri'] ) ) {
			return new WP_Error( 'invalid_request', 'redirect_uri is not on the allow-list.', array( 'status' => 400 ) );
		}

		// PKCE: code_challenge_method must be S256.
		if ( 'S256' !== $params['code_challenge_method'] ) {
			return new WP_Error( 'invalid_request', 'code_challenge_method must be S256.', array( 'status' => 400 ) );
		}

		// PKCE: code_challenge must be present.
		if ( empty( $params['code_challenge'] ) ) {
			return new WP_Error( 'invalid_request', 'code_challenge is required.', array( 'status' => 400 ) );
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// Consent screen HTML
	// -------------------------------------------------------------------------

	/**
	 * Render the minimal HTML consent screen and exit.
	 *
	 * All OAuth parameters are embedded as hidden fields.
	 * CSRF nonce is generated fresh per render.
	 *
	 * @param array $params Validated authorize parameters.
	 */
	private function render_consent_screen( $params ) {
		$nonce       = wp_create_nonce( 'mcpwp_oauth_consent' );
		$site_name   = function_exists( 'get_bloginfo' ) ? esc_html( get_bloginfo( 'name' ) ) : 'this site';
		$client_id   = esc_html( $params['client_id'] );
		$scope_label = esc_html( $params['scope'] ? $params['scope'] : 'read write' );
		$user_login  = '';
		if ( function_exists( 'wp_get_current_user' ) ) {
			$current_user = wp_get_current_user();
			$user_login   = esc_html( $current_user->user_login );
		}

		$post_url = esc_url( rest_url( $this->namespace . '/oauth/authorize' ) );

		$hidden_fields = '';
		foreach ( $params as $key => $value ) {
			$hidden_fields .= sprintf(
				'<input type="hidden" name="%s" value="%s">',
				esc_attr( $key ),
				esc_attr( $value )
			);
		}

		header( 'Content-Type: text/html; charset=utf-8' );
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
		   . '<meta name="viewport" content="width=device-width,initial-scale=1">'
		   . '<title>Authorize &mdash; ' . $site_name . '</title>'
		   . '<style>body{font-family:system-ui,sans-serif;max-width:480px;margin:60px auto;padding:0 16px}'
		   . 'h1{font-size:1.2rem}.scope{background:#f0f4ff;border:1px solid #c7d2fe;border-radius:6px;padding:10px 14px;margin:16px 0}'
		   . '.btn{display:inline-block;padding:10px 24px;border:none;border-radius:4px;cursor:pointer;font-size:1rem}'
		   . '.btn-allow{background:#2563eb;color:#fff;margin-right:8px}'
		   . '.btn-deny{background:#e5e7eb;color:#111}'
		   . '</style></head><body>'
		   . '<h1>Authorize access to <strong>' . $site_name . '</strong></h1>'
		   . '<p><strong>' . $client_id . '</strong> is requesting access'
		   . ( $user_login ? ' as <strong>' . $user_login . '</strong>' : '' ) . '.</p>'
		   . '<div class="scope"><strong>Scopes requested:</strong> ' . $scope_label . '</div>'
		   . '<form method="POST" action="' . $post_url . '">'
		   . $hidden_fields
		   . '<input type="hidden" name="_mcpwp_oauth_nonce" value="' . esc_attr( $nonce ) . '">'
		   . '<button type="submit" name="action" value="approve" class="btn btn-allow">Approve</button>'
		   . '<button type="submit" name="action" value="deny" class="btn btn-deny">Deny</button>'
		   . '</form></body></html>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Compute a PKCE S256 code challenge from a verifier.
	 *
	 * base64url( SHA256( code_verifier ) ) — no padding.
	 *
	 * @param string $verifier Raw code verifier string.
	 * @return string Base64url-encoded challenge without padding.
	 */
	public function compute_pkce_challenge( $verifier ) {
		$hash = hash( 'sha256', $verifier, true );
		return rtrim( strtr( base64_encode( $hash ), '+/', '-_' ), '=' );
	}

	/**
	 * Check whether a redirect_uri is in the configured allow-list.
	 *
	 * Comparison is exact-match on the full URI string (case-sensitive).
	 * Wildcard matching is never used.
	 *
	 * @param string $redirect_uri URI to check.
	 * @return bool True when on the allow-list.
	 */
	public function is_redirect_uri_allowed( $redirect_uri ) {
		$allowed = $this->get_allowed_redirect_uris();
		foreach ( $allowed as $allowed_uri ) {
			if ( hash_equals( $allowed_uri, $redirect_uri ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the configured redirect URI allow-list.
	 *
	 * Reads `oauth_redirect_uris` from mcpwp_settings.  Supports both an array
	 * (saved by a future settings UI) and a newline-separated string (admin
	 * option textarea).  Falls back to an empty array — no redirect_uri is
	 * allowed unless explicitly configured.
	 *
	 * @return string[]
	 */
	public function get_allowed_redirect_uris() {
		$settings = get_option( 'mcpwp_settings', array() );
		$raw      = isset( $settings['oauth_redirect_uris'] ) ? $settings['oauth_redirect_uris'] : array();

		if ( is_string( $raw ) ) {
			$raw = preg_split( '/[\r\n]+/', $raw );
		}

		if ( ! is_array( $raw ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'esc_url_raw', array_map( 'trim', $raw ) ) ) );
	}

	/**
	 * Parse a space-separated scope string into an array.
	 *
	 * @param string $scope_string Space-separated scopes.
	 * @return array Sanitized scope array.
	 */
	private function parse_scope_string( $scope_string ) {
		$scopes = preg_split( '/\s+/', trim( $scope_string ), -1, PREG_SPLIT_NO_EMPTY );
		return $this->sanitize_scopes( is_array( $scopes ) ? $scopes : array() );
	}

	/**
	 * Check if OAuth is enabled.
	 *
	 * @return bool
	 */
	private function is_oauth_enabled() {
		$settings = $this->get_oauth_settings();
		return ! empty( $settings['oauth_enabled'] );
	}

	/**
	 * Build an RFC 6749-compliant error REST response.
	 *
	 * @param string $error             RFC 6749 error code.
	 * @param string $error_description Human-readable description.
	 * @param int    $status            HTTP status code.
	 * @return WP_REST_Response
	 */
	public function oauth_error_response( $error, $error_description, $status = 400 ) {
		return new WP_REST_Response(
			array(
				'error'             => $error,
				'error_description' => $error_description,
			),
			$status
		);
	}

	/**
	 * Convert a WP_Error to a WP_REST_Response in OAuth error format.
	 *
	 * @param WP_Error $error WP_Error instance.
	 * @return WP_REST_Response
	 */
	private function wpe_to_response( $error ) {
		$data   = $error->get_error_data();
		$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;
		return $this->oauth_error_response( $error->get_error_code(), $error->get_error_message(), $status );
	}
}
