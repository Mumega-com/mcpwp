<?php

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for the Pro upgrade prompt on tools/call.
 *
 * When a non-Pro site calls a tool that is gated to the Pro registry (#327),
 * the handler must return an actionable "requires a Pro license" error with an
 * upgrade URL, rather than the generic "Unknown tool" response.
 */
final class ProUpgradePromptTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['spai_test_options']      = array();
		$GLOBALS['spai_test_transients']   = array();
		$GLOBALS['spai_test_current_user'] = 2;
		$GLOBALS['spai_test_fs']           = null; // Non-Pro site.
	}

	private function call_tool( string $tool_name ): array {
		$controller = new Spai_REST_MCP();
		$request    = new WP_REST_Request(
			'POST',
			'/site-pilot-ai/v1/mcp',
			array(),
			array(),
			array(
				'jsonrpc' => '2.0',
				'id'      => 7,
				'method'  => 'tools/call',
				'params'  => array(
					'name'      => $tool_name,
					'arguments' => array(),
				),
			)
		);

		return $controller->handle_mcp( $request )->get_data();
	}

	public function test_pro_tool_called_by_free_user_returns_upgrade_prompt(): void {
		// wp_get_site_state is gated to Pro (#327).
		$data = $this->call_tool( 'wp_get_site_state' );

		$this->assertArrayHasKey( 'error', $data );
		$this->assertArrayNotHasKey( 'result', $data, 'A Pro-gated tool must not execute for a free site.' );
		$this->assertSame( -32003, $data['error']['code'] );
		$this->assertStringContainsString( 'requires a Pro license', $data['error']['message'] );
		$this->assertArrayHasKey( 'data', $data['error'] );
		$this->assertArrayHasKey( 'upgrade_url', $data['error']['data'] );
		$this->assertNotEmpty( $data['error']['data']['upgrade_url'] );
	}

	public function test_unknown_tool_still_returns_unknown_not_upgrade(): void {
		$data = $this->call_tool( 'wp_this_tool_does_not_exist' );

		$this->assertArrayHasKey( 'error', $data );
		$this->assertSame( -32602, $data['error']['code'] );
		$this->assertStringContainsString( 'Unknown tool', $data['error']['message'] );
	}
}
