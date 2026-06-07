<?php

use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'is_multisite' ) ) {
	function is_multisite() {
		return false;
	}
}

final class McpProToolsTest extends TestCase {
	private Spai_MCP_Pro_Tools $registry;

	protected function setUp(): void {
		$this->registry = new Spai_MCP_Pro_Tools();
	}

	public function test_seo_tools_are_registered_with_normalized_fields_and_aliases(): void {
		$tools = $this->registry->get_tools();
		$map   = $this->registry->get_tool_map();

		$by_name = array();
		foreach ( $tools as $tool ) {
			$by_name[ $tool['name'] ] = $tool;
		}

		foreach ( array( 'wp_get_seo', 'wp_set_seo', 'wp_analyze_seo', 'wp_bulk_seo', 'wp_seo_status' ) as $tool_name ) {
			$this->assertArrayHasKey( $tool_name, $by_name, "Missing SEO tool: {$tool_name}" );
			$this->assertArrayHasKey( $tool_name, $map, "Missing SEO tool map: {$tool_name}" );
		}

		$set_props = $by_name['wp_set_seo']['inputSchema']['properties'];
		foreach ( array( 'title', 'description', 'seo_title', 'seo_description', 'canonical', 'canonical_url' ) as $field ) {
			$this->assertArrayHasKey( $field, $set_props, "wp_set_seo missing field: {$field}" );
		}

		$this->assertSame( 'title', $map['wp_set_seo']['param_remap']['seo_title'] );
		$this->assertSame( 'description', $map['wp_set_seo']['param_remap']['seo_description'] );
		$this->assertSame( 'canonical', $map['wp_set_seo']['param_remap']['canonical_url'] );

		$bulk_props = $by_name['wp_bulk_seo']['inputSchema']['properties'];
		$this->assertArrayHasKey( 'updates', $bulk_props );
		$this->assertArrayHasKey( 'items', $bulk_props );
		$this->assertSame( 'updates', $map['wp_bulk_seo']['param_remap']['items'] );
	}

	public function test_all_returned_pro_tools_have_route_mappings(): void {
		$tools = $this->registry->get_tools();
		$map   = $this->registry->get_tool_map();

		$missing = array();
		foreach ( $tools as $tool ) {
			if ( ! isset( $map[ $tool['name'] ] ) ) {
				$missing[] = $tool['name'];
			}
		}

		$this->assertEmpty( $missing, 'Pro tools missing from tool map: ' . implode( ', ', $missing ) );
	}
}
