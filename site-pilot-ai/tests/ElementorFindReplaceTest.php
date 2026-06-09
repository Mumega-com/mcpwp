<?php
/**
 * Regression tests for Elementor find/replace (wp_bulk_find_replace).
 *
 * Covers two defects fixed in v2.8.51:
 *  1. False negative: the old quick-check ran substr_count() on the RAW JSON,
 *     where Elementor stores '<' as <, '/' as \/. Any search containing HTML
 *     tags or URL slashes returned "0 replacements" even when the text was present.
 *  2. Structural corruption: recursive_str_replace replaced string values inside
 *     structural keys (elType, widgetType, id, ...), so searching an Elementor
 *     vocabulary word like "section" silently broke the page structure.
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/includes/api/class-spai-rest-elementor.php';

final class ElementorFindReplaceTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['spai_test_options']      = array();
		$GLOBALS['spai_test_transients']   = array();
		$GLOBALS['_spai_test_posts']       = array();
		$GLOBALS['_spai_test_meta']        = array();
		$GLOBALS['spai_test_current_user'] = 1;

		$GLOBALS['_spai_test_posts'][50] = (object) array(
			'ID'            => 50,
			'post_type'     => 'page',
			'post_title'    => 'Landing',
			'post_name'     => 'landing',
			'post_status'   => 'publish',
			'post_date'     => '2026-06-09 00:00:00',
			'post_modified' => '2026-06-09 00:00:00',
			'post_parent'   => 0,
			'menu_order'    => 0,
			'post_author'   => 1,
			'post_content'  => '',
		);

		// Plain JSON string in meta (real WP unslashes on read); the controller
		// reads it raw and json_decodes it, mirroring production.
		$GLOBALS['_spai_test_meta'][50]['_elementor_data'] = json_encode( $this->sample_tree() );
	}

	private function sample_tree(): array {
		return array(
			array(
				'id'       => 'sec00001',
				'elType'   => 'section',
				'settings' => array(),
				'elements' => array(
					array(
						'id'       => 'col00001',
						'elType'   => 'column',
						'settings' => array( '_column_size' => 100 ),
						'elements' => array(
							array(
								'id'         => 'head0001',
								'elType'     => 'widget',
								'widgetType' => 'heading',
								'settings'   => array( 'title' => '<strong>Old Brand</strong> grows crops' ),
							),
							array(
								'id'         => 'btn00001',
								'elType'     => 'widget',
								'widgetType' => 'button',
								'settings'   => array( 'link' => array( 'url' => 'https://old.example.com/start' ) ),
							),
						),
					),
				),
			),
		);
	}

	private function run_find_replace( string $search, string $replace ) {
		$controller = new Spai_REST_Elementor();
		$request    = new WP_REST_Request(
			'POST',
			'/site-pilot-ai/v1/elementor/50/find-replace',
			array(
				'id'      => 50,
				'search'  => $search,
				'replace' => $replace,
			)
		);
		return $controller->find_replace( $request );
	}

	private function stored_tree(): array {
		$raw = wp_unslash( get_post_meta( 50, '_elementor_data', true ) );
		return json_decode( $raw, true );
	}

	/** The old raw-JSON gate returned 0 here because '<\/strong>' never matched '</strong>'. */
	public function test_html_tag_search_is_found_and_replaced(): void {
		$response = $this->run_find_replace( '</strong>', '</em>' );
		$data     = $response->get_data();

		$this->assertSame( 1, $data['replacements'] );
		$tree = $this->stored_tree();
		$this->assertSame(
			'<strong>Old Brand</em> grows crops',
			$tree[0]['elements'][0]['elements'][0]['settings']['title']
		);
	}

	/** The old gate returned 0 here because raw JSON stored 'https:\/\/old.example.com'. */
	public function test_url_with_slashes_is_found_and_replaced(): void {
		$response = $this->run_find_replace( 'https://old.example.com/start', 'https://new.example.com/start' );
		$data     = $response->get_data();

		$this->assertSame( 1, $data['replacements'] );
		$tree = $this->stored_tree();
		$this->assertSame(
			'https://new.example.com/start',
			$tree[0]['elements'][0]['elements'][1]['settings']['link']['url']
		);
	}

	public function test_plain_text_search_still_works(): void {
		$response = $this->run_find_replace( 'Old Brand', 'New Brand' );
		$this->assertSame( 1, $response->get_data()['replacements'] );
	}

	/** Searching an Elementor vocabulary word must NOT touch structural keys. */
	public function test_structural_keys_are_protected(): void {
		$response = $this->run_find_replace( 'section', 'BROKEN' );
		$data     = $response->get_data();

		$this->assertSame( 0, $data['replacements'], 'elType "section" must not be replaced' );
		$tree = $this->stored_tree();
		$this->assertSame( 'section', $tree[0]['elType'] );
	}

	public function test_widget_type_is_protected(): void {
		$response = $this->run_find_replace( 'heading', 'BROKEN' );
		$data     = $response->get_data();

		$this->assertSame( 0, $data['replacements'], 'widgetType "heading" must not be replaced' );
		$tree = $this->stored_tree();
		$this->assertSame( 'heading', $tree[0]['elements'][0]['elements'][0]['widgetType'] );
	}

	public function test_no_match_reports_zero_without_error(): void {
		$response = $this->run_find_replace( 'nonexistent-string-xyz', 'whatever' );
		$data     = $response->get_data();
		$this->assertSame( 0, $data['replacements'] );
	}
}
