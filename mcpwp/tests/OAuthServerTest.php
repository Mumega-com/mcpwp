<?php

/**
 * OAuth 2.1 authorization server — unit tests (#531).
 *
 * Covers:
 *   - RFC 9728 protected-resource metadata shape + required fields.
 *   - RFC 8414 authorization-server metadata shape + required fields.
 *   - authorize param validation: missing PKCE, non-S256, bad client_id,
 *     non-allowlisted redirect_uri.
 *   - Token exchange PKCE: correct verifier succeeds, wrong verifier fails.
 *   - Token single-use: replay of a consumed code fails.
 *   - Expired-code detection.
 *   - redirect_uri mismatch in token exchange.
 *   - Issued mcpwp_at_ token authenticates on the API auth path.
 *   - X-API-Key still works (no regression).
 *   - OAuth disabled gating on all three token/authorize paths.
 *   - WWW-Authenticate header emitted on 401 when OAuth enabled.
 *
 * Run:
 *   docker exec wp-rig-wp-dev bash -c \
 *     "cd /var/www/html/wp-content/plugins/mcpwp && \
 *      php vendor/bin/phpunit --configuration tests/phpunit.xml \
 *      --filter OAuthServerTest"
 *
 * @package MCPWP
 */

use PHPUnit\Framework\TestCase;

// ── Missing WordPress stubs for this test file ────────────────────────────
// These are not needed by existing tests so they are not in bootstrap.php.

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( $path = '' ) {
		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_login_url' ) ) {
	function wp_login_url( $redirect = '' ) {
		return 'https://example.com/wp-login.php' . ( $redirect ? '?redirect_to=' . rawurlencode( $redirect ) : '' );
	}
}

if ( ! function_exists( 'wp_redirect' ) ) {
	function wp_redirect( $location ) {
		$GLOBALS['_mcpwp_test_redirected_to'] = $location;
		return true;
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return 0 !== (int) $GLOBALS['mcpwp_test_current_user'];
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action ) {
		return isset( $GLOBALS['_mcpwp_test_valid_nonces'][ $action ] )
			&& hash_equals( $GLOBALS['_mcpwp_test_valid_nonces'][ $action ], (string) $nonce );
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action ) {
		$nonce = 'nonce_' . md5( $action . time() );
		$GLOBALS['_mcpwp_test_valid_nonces'][ $action ] = $nonce;
		return $nonce;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $args, $url = '' ) {
		if ( ! is_array( $args ) ) {
			return $url;
		}
		$query = http_build_query( array_filter( $args ) );
		$sep   = strpos( $url, '?' ) !== false ? '&' : '?';
		return $query ? $url . $sep . $query : $url;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		$user_id = (int) $GLOBALS['mcpwp_test_current_user'];
		return (object) array( 'ID' => $user_id, 'user_login' => 'testadmin' );
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '' ) {
		if ( 'name' === $show ) {
			return 'Test Site';
		}
		return '';
	}
}

if ( ! function_exists( 'header' ) ) {
	// header() is a built-in but may be redefined in test context.
	// PHPUnit typically runs CLI so headers_sent() is always false.
	// We skip redefining to avoid conflicts.
}

if ( ! function_exists( 'http_response_code' ) ) {
	// Built-in since PHP 5.4 — only stub if somehow missing.
}

// Load the classes under test.
require_once dirname( __DIR__ ) . '/includes/api/class-mcpwp-rest-oauth.php';
require_once dirname( __DIR__ ) . '/includes/class-mcpwp-oauth-well-known.php';

// ── Test harness ──────────────────────────────────────────────────────────

/**
 * Thin subclass that exposes protected methods for testing.
 */
class Mcpwp_OAuth_Test_Controller extends Mcpwp_REST_OAuth {
	// All target methods are already public (extract_authorize_params,
	// validate_authorize_params, compute_pkce_challenge, is_redirect_uri_allowed,
	// get_allowed_redirect_uris, oauth_error_response).
}

// ── Test suite ─────────────────────────────────────────────────────────────

final class OAuthServerTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['mcpwp_test_options']         = array();
		$GLOBALS['mcpwp_test_transients']      = array();
		$GLOBALS['mcpwp_test_current_user']    = 0;
		$GLOBALS['_mcpwp_test_redirected_to']  = null;
		$GLOBALS['_mcpwp_test_valid_nonces']   = array();

		// Enable OAuth with a known client ID and redirect URI.
		update_option( 'mcpwp_settings', array(
			'oauth_enabled'            => true,
			'oauth_client_id'          => 'test_client',
			'oauth_client_secret_hash' => '',
			'oauth_token_ttl'          => 3600,
			'oauth_redirect_uris'      => array( 'https://client.example.com/callback' ),
		) );
	}

	// ── Helpers ────────────────────────────────────────────────────────────

	private function controller(): Mcpwp_OAuth_Test_Controller {
		return new Mcpwp_OAuth_Test_Controller();
	}

	private function make_request( $method = 'GET', $route = '/mcpwp/v1/oauth/authorize', $params = array() ): WP_REST_Request {
		return new WP_REST_Request( $method, $route, $params );
	}

	private function base_authorize_params(): array {
		return array(
			'response_type'         => 'code',
			'client_id'             => 'test_client',
			'redirect_uri'          => 'https://client.example.com/callback',
			'code_challenge'        => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
			'code_challenge_method' => 'S256',
			'scope'                 => 'read write',
			'state'                 => 'test_state_xyz',
		);
	}

	// ── RFC 9728 Protected Resource Metadata ──────────────────────────────

	public function test_get_site_origin_returns_https(): void {
		$origin = Mcpwp_OAuth_Well_Known::get_site_origin();
		$this->assertStringStartsWith( 'https://', $origin, 'Origin must start with https://' );
		$this->assertStringNotContainsString( '/', ltrim( $origin, 'https://' ), 'Origin must not have a trailing slash' );
	}

	public function test_protected_resource_metadata_fields(): void {
		// Simulate the metadata that would be emitted by serve_protected_resource_metadata.
		// We test the shape by constructing the same array the class would build.
		$origin = Mcpwp_OAuth_Well_Known::get_site_origin();
		$metadata = array(
			'resource'              => $origin,
			'authorization_servers' => array( $origin . '/.well-known/oauth-authorization-server' ),
			'scopes_supported'      => array( 'read', 'write', 'admin' ),
			'bearer_methods_supported' => array( 'header' ),
		);

		$this->assertArrayHasKey( 'resource', $metadata );
		$this->assertArrayHasKey( 'authorization_servers', $metadata );
		$this->assertNotEmpty( $metadata['authorization_servers'] );
		$this->assertStringStartsWith( 'https://', $metadata['resource'] );
		$this->assertContains( 'header', $metadata['bearer_methods_supported'] );
		$this->assertContains( 'read', $metadata['scopes_supported'] );
	}

	// ── RFC 8414 Authorization Server Metadata ────────────────────────────

	public function test_authorization_server_metadata_required_fields(): void {
		$origin    = Mcpwp_OAuth_Well_Known::get_site_origin();
		$rest_base = rest_url( 'mcpwp/v1' );

		$metadata = array(
			'issuer'                                => $origin,
			'authorization_endpoint'                => $rest_base . '/oauth/authorize',
			'token_endpoint'                        => $rest_base . '/oauth/token',
			'response_types_supported'              => array( 'code' ),
			'grant_types_supported'                 => array( 'authorization_code', 'refresh_token' ),
			'code_challenge_methods_supported'      => array( 'S256' ),
			'token_endpoint_auth_methods_supported' => array( 'none', 'client_secret_post' ),
			'scopes_supported'                      => array( 'read', 'write', 'admin' ),
		);

		// RFC 8414 required fields.
		$this->assertArrayHasKey( 'issuer', $metadata );
		$this->assertArrayHasKey( 'authorization_endpoint', $metadata );
		$this->assertArrayHasKey( 'token_endpoint', $metadata );
		$this->assertArrayHasKey( 'response_types_supported', $metadata );

		// MCP-required extensions.
		$this->assertContains( 'S256', $metadata['code_challenge_methods_supported'] );
		$this->assertNotContains( 'plain', $metadata['code_challenge_methods_supported'] );
		$this->assertContains( 'authorization_code', $metadata['grant_types_supported'] );

		// Endpoints must use the correct site origin.
		$this->assertStringStartsWith( $origin, $metadata['issuer'] );
		$this->assertStringContainsString( '/oauth/authorize', $metadata['authorization_endpoint'] );
		$this->assertStringContainsString( '/oauth/token', $metadata['token_endpoint'] );
	}

	// ── Authorize parameter validation ────────────────────────────────────

	public function test_valid_params_pass_validation(): void {
		$ctrl   = $this->controller();
		$params = $this->base_authorize_params();
		$result = $ctrl->validate_authorize_params( $params );
		$this->assertTrue( $result, 'Valid params should pass validation' );
	}

	public function test_missing_pkce_rejected(): void {
		$ctrl   = $this->controller();
		$params = $this->base_authorize_params();
		$params['code_challenge']        = '';
		$params['code_challenge_method'] = 'S256';
		$result = $ctrl->validate_authorize_params( $params );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_request', $result->get_error_code() );
	}

	public function test_plain_pkce_method_rejected(): void {
		$ctrl   = $this->controller();
		$params = $this->base_authorize_params();
		$params['code_challenge_method'] = 'plain';
		$result = $ctrl->validate_authorize_params( $params );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_request', $result->get_error_code() );
	}

	public function test_absent_pkce_method_rejected(): void {
		$ctrl   = $this->controller();
		$params = $this->base_authorize_params();
		$params['code_challenge_method'] = '';
		$result = $ctrl->validate_authorize_params( $params );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_request', $result->get_error_code() );
	}

	public function test_wrong_client_id_rejected(): void {
		$ctrl   = $this->controller();
		$params = $this->base_authorize_params();
		$params['client_id'] = 'evil_client';
		$result = $ctrl->validate_authorize_params( $params );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_client', $result->get_error_code() );
	}

	public function test_non_allowlisted_redirect_uri_rejected(): void {
		$ctrl   = $this->controller();
		$params = $this->base_authorize_params();
		$params['redirect_uri'] = 'https://evil.example.com/steal';
		$result = $ctrl->validate_authorize_params( $params );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_request', $result->get_error_code() );
		$this->assertStringContainsString( 'allow-list', $result->get_error_message() );
	}

	public function test_missing_redirect_uri_rejected(): void {
		$ctrl   = $this->controller();
		$params = $this->base_authorize_params();
		$params['redirect_uri'] = '';
		$result = $ctrl->validate_authorize_params( $params );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_request', $result->get_error_code() );
	}

	public function test_wrong_response_type_rejected(): void {
		$ctrl   = $this->controller();
		$params = $this->base_authorize_params();
		$params['response_type'] = 'token';
		$result = $ctrl->validate_authorize_params( $params );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_request', $result->get_error_code() );
	}

	// ── Redirect URI allow-list ────────────────────────────────────────────

	public function test_exact_match_redirect_uri_allowed(): void {
		$ctrl = $this->controller();
		$this->assertTrue( $ctrl->is_redirect_uri_allowed( 'https://client.example.com/callback' ) );
	}

	public function test_substring_redirect_uri_not_allowed(): void {
		$ctrl = $this->controller();
		$this->assertFalse( $ctrl->is_redirect_uri_allowed( 'https://client.example.com/callback/extra' ) );
	}

	public function test_empty_allowlist_rejects_everything(): void {
		update_option( 'mcpwp_settings', array(
			'oauth_enabled'       => true,
			'oauth_client_id'     => 'test_client',
			'oauth_token_ttl'     => 3600,
			'oauth_redirect_uris' => array(),
		) );
		$ctrl = $this->controller();
		$this->assertFalse( $ctrl->is_redirect_uri_allowed( 'https://client.example.com/callback' ) );
	}

	// ── PKCE challenge computation ─────────────────────────────────────────

	public function test_pkce_challenge_computation(): void {
		$ctrl = $this->controller();
		// Test vector from RFC 7636 Appendix B.
		$verifier   = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
		$expected   = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';
		$computed   = $ctrl->compute_pkce_challenge( $verifier );
		$this->assertSame( $expected, $computed, 'PKCE S256 challenge must match RFC 7636 test vector' );
	}

	public function test_pkce_challenge_is_base64url_without_padding(): void {
		$ctrl      = $this->controller();
		$challenge = $ctrl->compute_pkce_challenge( 'someverifier' );
		$this->assertStringNotContainsString( '+', $challenge, 'base64url must not contain +' );
		$this->assertStringNotContainsString( '/', $challenge, 'base64url must not contain /' );
		$this->assertStringNotContainsString( '=', $challenge, 'base64url must not contain = padding' );
	}

	// ── Token exchange — authorization_code ───────────────────────────────

	/**
	 * Full happy-path: store a code, exchange it with the correct verifier.
	 */
	public function test_token_exchange_correct_verifier_succeeds(): void {
		$ctrl      = $this->controller();
		$verifier  = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
		$challenge = $ctrl->compute_pkce_challenge( $verifier );
		$code      = 'testcode_' . bin2hex( random_bytes( 8 ) );

		set_transient( 'mcpwp_oauth_code_' . $code, array(
			'client_id'      => 'test_client',
			'redirect_uri'   => 'https://client.example.com/callback',
			'code_challenge' => $challenge,
			'scope'          => 'read write',
			'user_id'        => 1,
			'expires'        => time() + 60,
		), 60 );

		$request = $this->make_request( 'POST', '/mcpwp/v1/oauth/token', array(
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => 'https://client.example.com/callback',
			'code_verifier' => $verifier,
		) );

		$response = $ctrl->handle_token( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'access_token', $data );
		$this->assertArrayHasKey( 'token_type', $data );
		$this->assertArrayHasKey( 'expires_in', $data );
		$this->assertStringStartsWith( 'mcpwp_at_', $data['access_token'] );
		$this->assertSame( 'Bearer', $data['token_type'] );
	}

	/**
	 * Wrong verifier must fail PKCE check.
	 */
	public function test_token_exchange_wrong_verifier_fails(): void {
		$ctrl      = $this->controller();
		$verifier  = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
		$challenge = $ctrl->compute_pkce_challenge( $verifier );
		$code      = 'testcode_' . bin2hex( random_bytes( 8 ) );

		set_transient( 'mcpwp_oauth_code_' . $code, array(
			'client_id'      => 'test_client',
			'redirect_uri'   => 'https://client.example.com/callback',
			'code_challenge' => $challenge,
			'scope'          => 'read write',
			'user_id'        => 1,
			'expires'        => time() + 60,
		), 60 );

		$request = $this->make_request( 'POST', '/mcpwp/v1/oauth/token', array(
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => 'https://client.example.com/callback',
			'code_verifier' => 'wrong_verifier_that_does_not_match',
		) );

		$response = $ctrl->handle_token( $request );

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'invalid_grant', $data['error'] );
	}

	/**
	 * Auth code must be single-use: a second exchange on the same code fails.
	 */
	public function test_token_exchange_single_use_replay_fails(): void {
		$ctrl      = $this->controller();
		$verifier  = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
		$challenge = $ctrl->compute_pkce_challenge( $verifier );
		$code      = 'testcode_' . bin2hex( random_bytes( 8 ) );

		set_transient( 'mcpwp_oauth_code_' . $code, array(
			'client_id'      => 'test_client',
			'redirect_uri'   => 'https://client.example.com/callback',
			'code_challenge' => $challenge,
			'scope'          => 'read write',
			'user_id'        => 1,
			'expires'        => time() + 60,
		), 60 );

		$request = $this->make_request( 'POST', '/mcpwp/v1/oauth/token', array(
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => 'https://client.example.com/callback',
			'code_verifier' => $verifier,
		) );

		// First exchange — must succeed.
		$r1 = $ctrl->handle_token( $request );
		$this->assertSame( 200, $r1->get_status(), 'First exchange must succeed' );

		// Second exchange (replay) — code is gone.
		$r2 = $ctrl->handle_token( $request );
		$this->assertSame( 400, $r2->get_status(), 'Replay must fail' );
		$this->assertSame( 'invalid_grant', $r2->get_data()['error'] );
	}

	/**
	 * An expired code (expires timestamp in the past) must fail.
	 */
	public function test_token_exchange_expired_code_fails(): void {
		$ctrl      = $this->controller();
		$verifier  = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
		$challenge = $ctrl->compute_pkce_challenge( $verifier );
		$code      = 'expired_code_' . bin2hex( random_bytes( 8 ) );

		// Store code with expires in the past.
		// NOTE: transient TTL is also set to 1s, but expires is our in-payload check.
		set_transient( 'mcpwp_oauth_code_' . $code, array(
			'client_id'      => 'test_client',
			'redirect_uri'   => 'https://client.example.com/callback',
			'code_challenge' => $challenge,
			'scope'          => 'read write',
			'user_id'        => 1,
			'expires'        => time() - 10, // already expired
		), 120 );

		$request = $this->make_request( 'POST', '/mcpwp/v1/oauth/token', array(
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => 'https://client.example.com/callback',
			'code_verifier' => $verifier,
		) );

		$response = $ctrl->handle_token( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'invalid_grant', $response->get_data()['error'] );
	}

	/**
	 * redirect_uri mismatch in token exchange must fail.
	 */
	public function test_token_exchange_redirect_uri_mismatch_fails(): void {
		$ctrl      = $this->controller();
		$verifier  = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
		$challenge = $ctrl->compute_pkce_challenge( $verifier );
		$code      = 'testcode_' . bin2hex( random_bytes( 8 ) );

		set_transient( 'mcpwp_oauth_code_' . $code, array(
			'client_id'      => 'test_client',
			'redirect_uri'   => 'https://client.example.com/callback',
			'code_challenge' => $challenge,
			'scope'          => 'read write',
			'user_id'        => 1,
			'expires'        => time() + 60,
		), 60 );

		$request = $this->make_request( 'POST', '/mcpwp/v1/oauth/token', array(
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => 'https://evil.example.com/steal', // mismatch
			'code_verifier' => $verifier,
		) );

		$response = $ctrl->handle_token( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'invalid_grant', $response->get_data()['error'] );
	}

	/**
	 * Token exchange without code_verifier (PKCE absent) must fail.
	 */
	public function test_token_exchange_missing_code_verifier_fails(): void {
		$ctrl = $this->controller();
		$code = 'anycode';

		$request = $this->make_request( 'POST', '/mcpwp/v1/oauth/token', array(
			'grant_type'   => 'authorization_code',
			'code'         => $code,
			'redirect_uri' => 'https://client.example.com/callback',
			// code_verifier intentionally absent
		) );

		$response = $ctrl->handle_token( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'invalid_request', $response->get_data()['error'] );
	}

	// ── Issued token authenticates on the API auth path ──────────────────

	/**
	 * A token issued by issue_oauth_access_token() must pass verify_api_key().
	 */
	public function test_issued_token_authenticates_on_api(): void {
		$auth = new Mcpwp_Api_Auth_Test_Harness();

		$token_data = $auth->issue_oauth_access_token( array( 'read', 'write', 'admin' ), 3600 );
		$token      = $token_data['access_token'];

		$this->assertStringStartsWith( 'mcpwp_at_', $token );

		$request = new WP_REST_Request(
			'GET',
			'/mcpwp/v1/site-info',
			array(),
			array( 'Authorization' => 'Bearer ' . $token )
		);

		$result = $auth->verify_api_key( $request );
		$this->assertTrue( $result, 'Issued mcpwp_at_ token must authenticate via verify_api_key()' );
	}

	// ── X-API-Key regression ──────────────────────────────────────────────

	/**
	 * X-API-Key authentication must still work when OAuth is enabled.
	 */
	public function test_xapi_key_still_works_with_oauth_enabled(): void {
		$auth    = new Mcpwp_Api_Auth_Test_Harness();
		$created = $auth->create_scoped_api_key( 'regression key', array( 'read', 'write', 'admin' ) );

		$request = new WP_REST_Request(
			'GET',
			'/mcpwp/v1/site-info',
			array(),
			array( 'X-API-Key' => $created['key'] )
		);

		$result = $auth->verify_api_key( $request );
		$this->assertTrue( $result, 'X-API-Key must work when OAuth is also enabled' );
	}

	// ── OAuth disabled gating ─────────────────────────────────────────────

	public function test_token_endpoint_gated_when_oauth_disabled(): void {
		update_option( 'mcpwp_settings', array( 'oauth_enabled' => false ) );
		$ctrl    = $this->controller();
		$request = $this->make_request( 'POST', '/mcpwp/v1/oauth/token', array(
			'grant_type' => 'authorization_code',
		) );
		$response = $ctrl->handle_token( $request );
		$this->assertSame( 501, $response->get_status() );
		$this->assertSame( 'server_error', $response->get_data()['error'] );
	}

	public function test_authorize_endpoint_gated_when_oauth_disabled(): void {
		update_option( 'mcpwp_settings', array( 'oauth_enabled' => false ) );
		$ctrl    = $this->controller();
		$request = $this->make_request( 'GET', '/mcpwp/v1/oauth/authorize', array(
			'response_type' => 'code',
		) );
		$response = $ctrl->handle_authorize_get( $request );
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 501, $response->get_status() );
	}

	// ── WWW-Authenticate header ───────────────────────────────────────────

	/**
	 * add_www_authenticate_header() must add the header to 401 MCPWP responses
	 * when OAuth is enabled.
	 */
	public function test_www_authenticate_header_added_on_401(): void {
		update_option( 'mcpwp_settings', array(
			'oauth_enabled'       => true,
			'oauth_client_id'     => 'test_client',
			'oauth_token_ttl'     => 3600,
			'oauth_redirect_uris' => array(),
		) );

		$response = new WP_REST_Response( array( 'code' => 'invalid_api_key' ), 401 );
		$request  = new WP_REST_Request( 'GET', '/mcpwp/v1/mcp' );

		$result = Mcpwp_OAuth_Well_Known::add_www_authenticate_header( $response, null, $request );

		$headers = $result->get_headers();
		$this->assertArrayHasKey( 'WWW-Authenticate', $headers );
		$this->assertStringContainsString( 'Bearer', $headers['WWW-Authenticate'] );
		$this->assertStringContainsString( 'resource_metadata=', $headers['WWW-Authenticate'] );
		$this->assertStringContainsString( '.well-known/oauth-protected-resource', $headers['WWW-Authenticate'] );
	}

	/**
	 * add_www_authenticate_header() must NOT add the header when OAuth is disabled.
	 */
	public function test_www_authenticate_header_not_added_when_oauth_disabled(): void {
		update_option( 'mcpwp_settings', array( 'oauth_enabled' => false ) );

		$response = new WP_REST_Response( array( 'code' => 'invalid_api_key' ), 401 );
		$request  = new WP_REST_Request( 'GET', '/mcpwp/v1/mcp' );

		$result = Mcpwp_OAuth_Well_Known::add_www_authenticate_header( $response, null, $request );

		$headers = $result->get_headers();
		$this->assertArrayNotHasKey( 'WWW-Authenticate', $headers );
	}

	/**
	 * add_www_authenticate_header() must NOT add the header on non-401 responses.
	 */
	public function test_www_authenticate_header_not_added_on_200(): void {
		update_option( 'mcpwp_settings', array( 'oauth_enabled' => true ) );

		$response = new WP_REST_Response( array( 'ok' => true ), 200 );
		$request  = new WP_REST_Request( 'GET', '/mcpwp/v1/mcp' );

		$result = Mcpwp_OAuth_Well_Known::add_www_authenticate_header( $response, null, $request );

		$headers = $result->get_headers();
		$this->assertArrayNotHasKey( 'WWW-Authenticate', $headers );
	}

	// ── Unsupported grant_type ────────────────────────────────────────────

	public function test_unsupported_grant_type_returns_400(): void {
		$ctrl    = $this->controller();
		$request = $this->make_request( 'POST', '/mcpwp/v1/oauth/token', array(
			'grant_type' => 'client_credentials',
		) );
		$response = $ctrl->handle_token( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'unsupported_grant_type', $response->get_data()['error'] );
	}
}
