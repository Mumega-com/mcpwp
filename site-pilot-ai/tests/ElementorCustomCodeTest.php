<?php

use PHPUnit\Framework\TestCase;

final class ElementorCustomCodeTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['spai_test_options']     = array();
		$GLOBALS['spai_test_transients']  = array();
		$GLOBALS['_spai_test_posts']      = array();
		$GLOBALS['_spai_test_meta']       = array();
		$GLOBALS['spai_test_current_user'] = 1;
	}

	public function test_pro_registry_exposes_custom_code_create_get_update_tools(): void {
		$registry = new Spai_MCP_Pro_Tools();
		$tools    = $registry->get_tools();
		$names    = array_column( $tools, 'name' );
		$map      = $registry->get_tool_map();

		$this->assertContains( 'wp_create_elementor_custom_code', $names );
		$this->assertContains( 'wp_get_elementor_custom_code', $names );
		$this->assertContains( 'wp_update_elementor_custom_code', $names );
		$this->assertSame( '/elementor/custom-code', $map['wp_create_elementor_custom_code']['route'] );
		$this->assertSame( '/elementor/custom-code/{id}', $map['wp_get_elementor_custom_code']['route'] );
		$this->assertSame( '/elementor/custom-code/{id}', $map['wp_update_elementor_custom_code']['route'] );
	}

	public function test_create_custom_code_preserves_raw_script_and_reads_back_conditions(): void {
		$controller = new Spai_REST_Elementor_Pro( new stdClass() );
		$request    = new WP_REST_Request(
			'POST',
			'/site-pilot-ai/v1/elementor/custom-code',
			array(
				'title'      => 'GA4 Funnel',
				'code'       => '<html><head><script>window.dataLayer = window.dataLayer || [];</script></head></html>',
				'location'   => 'body_end',
				'status'     => 'publish',
				'conditions' => array(
					array(
						'type' => 'include',
						'name' => 'general',
					),
				),
			)
		);

		$response = $controller->create_custom_code( $request );
		$data     = $response->get_data();
		$snippet  = $data['snippet'];

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'GA4 Funnel', $snippet['title'] );
		$this->assertSame( 'publish', $snippet['status'] );
		$this->assertSame( 'body_end', $snippet['location'] );
		$this->assertStringContainsString( '<script>window.dataLayer', $snippet['code'] );
		$this->assertStringNotContainsString( '<html>', $snippet['code'] );
		$this->assertTrue( $snippet['wrapper_tags_removed'] );
		$this->assertSame( array( array( 'type' => 'include', 'name' => 'general' ) ), $snippet['conditions'] );
		$this->assertSame( 'fallback_meta', $snippet['conditions_engine'] );
		$this->assertSame( array( 1 ), get_option( 'elementor_pro_theme_builder_conditions' )['include/general'] );
	}

	public function test_update_custom_code_dry_run_does_not_mutate_existing_snippet(): void {
		$GLOBALS['_spai_test_posts'][ 99 ] = (object) array(
			'ID'                => 99,
			'post_type'         => 'elementor_snippet',
			'post_title'        => 'Existing',
			'post_name'         => 'existing',
			'post_status'       => 'draft',
			'post_date'         => '2026-06-06 00:00:00',
			'post_modified'     => '2026-06-06 00:00:00',
			'post_modified_gmt' => '2026-06-06 00:00:00',
			'post_parent'       => 0,
			'menu_order'        => 0,
			'post_author'       => 1,
			'post_content'      => '<script>old()</script>',
			'post_excerpt'      => '',
		);
		update_post_meta( 99, '_elementor_location', 'head' );
		update_post_meta( 99, '_elementor_conditions', array( array( 'type' => 'include', 'name' => 'general' ) ) );

		$controller = new Spai_REST_Elementor_Pro( new stdClass() );
		$request    = new WP_REST_Request(
			'POST',
			'/site-pilot-ai/v1/elementor/custom-code/99',
			array(
				'id'       => 99,
				'code'     => '<script>new()</script>',
				'location' => 'body_start',
				'dry_run'  => true,
			)
		);

		$response = $controller->update_custom_code( $request );
		$data     = $response->get_data();

		$this->assertTrue( $data['dry_run'] );
		$this->assertTrue( $data['valid'] );
		$this->assertSame( '<script>old()</script>', $GLOBALS['_spai_test_posts'][99]->post_content );
		$this->assertSame( 'head', get_post_meta( 99, '_elementor_location', true ) );
	}
}
