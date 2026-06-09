<?php

use PHPUnit\Framework\TestCase;

final class SearchPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['mcpwp_test_options'] = array();
    }

    public function test_import_rows_and_report_aggregates_search_evidence(): void
    {
        $result = Mcpwp_Search_Performance::import_rows(
            array(
                'provider' => 'gsc',
                'source'   => 'unit export',
                'rows'     => array(
                    array(
                        'date'        => gmdate('Y-m-d'),
                        'url'         => 'https://example.com/services/',
                        'query'       => 'wordpress ai agent',
                        'clicks'      => 4,
                        'impressions' => 100,
                        'position'    => 8.2,
                    ),
                    array(
                        'date'        => gmdate('Y-m-d'),
                        'page'        => 'https://example.com/services/',
                        'query'       => 'wordpress ai agent',
                        'clicks'      => 1,
                        'impressions' => 50,
                        'position'    => 10,
                    ),
                    array(
                        'date' => '',
                        'url'  => '',
                    ),
                ),
            )
        );

        $this->assertSame('google_search_console', $result['provider']);
        $this->assertSame(2, $result['row_count']);
        $this->assertSame(1, $result['ignored']);

        $report = Mcpwp_Search_Performance::get_report(array( 'days' => 30 ));

        $this->assertSame('2026-05-20', $report['schema_version']);
        $this->assertSame(2, $report['summary']['rows']);
        $this->assertSame(5, $report['summary']['clicks']);
        $this->assertSame(150, $report['summary']['impressions']);
        $this->assertSame(0.0333, $report['summary']['ctr']);
        $this->assertSame('wordpress ai agent', $report['top_queries'][0]['query']);
        $this->assertSame(5, $report['top_queries'][0]['clicks']);
        $this->assertSame('https://example.com/services/', $report['top_urls'][0]['url']);
    }

    public function test_report_filters_by_query_and_provider(): void
    {
        Mcpwp_Search_Performance::import_rows(
            array(
                'provider' => 'bing',
                'rows'     => array(
                    array(
                        'date'        => gmdate('Y-m-d'),
                        'url'         => 'https://example.com/a/',
                        'query'       => 'commerce seo',
                        'clicks'      => 3,
                        'impressions' => 30,
                        'position'    => 4,
                    ),
                    array(
                        'date'        => gmdate('Y-m-d'),
                        'url'         => 'https://example.com/b/',
                        'query'       => 'unrelated',
                        'clicks'      => 9,
                        'impressions' => 90,
                        'position'    => 2,
                    ),
                ),
            )
        );

        $report = Mcpwp_Search_Performance::get_report(
            array(
                'provider' => 'bing_webmaster',
                'query'    => 'commerce',
            )
        );

        $this->assertSame(1, $report['summary']['rows']);
        $this->assertSame(3, $report['summary']['clicks']);
        $this->assertSame('bing_webmaster', array_key_first($report['summary']['providers']));
    }
}
