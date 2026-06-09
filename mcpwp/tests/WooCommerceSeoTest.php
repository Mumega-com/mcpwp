<?php

use PHPUnit\Framework\TestCase;

final class WooCommerceSeoTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['mcpwp_test_options'] = array();
        $GLOBALS['_mcpwp_test_posts'] = array();
        $GLOBALS['_mcpwp_test_meta'] = array();
    }

    public function test_report_flags_product_seo_gaps_and_search_evidence(): void
    {
        $GLOBALS['_mcpwp_test_posts'][21] = (object) array(
            'ID'           => 21,
            'post_type'    => 'product',
            'post_status'  => 'publish',
            'post_title'   => 'AI WordPress Operator',
            'post_content' => '<p>Short product copy.</p>',
            'post_excerpt' => '',
            'modified_ts'  => time(),
        );

        update_post_meta(21, '_price', '49');
        update_post_meta(21, '_sku', 'SPAI-001');
        update_post_meta(21, '_stock_status', 'instock');
        update_post_meta(21, '_thumbnail_id', '99');

        Mcpwp_Search_Performance::import_rows(
            array(
                'provider' => 'google_search_console',
                'rows'     => array(
                    array(
                        'date'        => gmdate('Y-m-d'),
                        'url'         => 'https://example.com/?p=21',
                        'query'       => 'wordpress ai operator',
                        'clicks'      => 2,
                        'impressions' => 80,
                        'position'    => 7,
                    ),
                ),
            )
        );

        $report = Mcpwp_WooCommerce_SEO::get_report(array( 'limit' => 10 ));

        $this->assertSame('2026-05-20', $report['schema_version']);
        $this->assertSame(1, $report['summary']['products_inspected']);
        $this->assertSame(2, $report['summary']['search_clicks']);
        $this->assertSame(80, $report['summary']['search_impressions']);
        $this->assertSame(3, $report['summary']['warning_count']);
        $issue_codes = array_map(
            static function ($issue) {
                return $issue['code'];
            },
            $report['products'][0]['issues']
        );
        $this->assertContains('thin_product_description', $issue_codes);
        $this->assertContains('missing_short_description', $issue_codes);
        $this->assertContains('missing_product_category', $issue_codes);
        $this->assertNotEmpty($report['products'][0]['next_steps']);
    }

    public function test_empty_catalog_returns_empty_report(): void
    {
        $report = Mcpwp_WooCommerce_SEO::get_report(array());

        $this->assertSame(0, $report['summary']['products_inspected']);
        $this->assertSame(array(), $report['products']);
    }
}
