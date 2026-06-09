<?php

use PHPUnit\Framework\TestCase;

final class ContentCoherenceTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['mcpwp_test_options'] = array();
        $GLOBALS['_mcpwp_test_posts'] = array();
        $GLOBALS['_mcpwp_test_menu_page_ids'] = array();
    }

    public function test_report_returns_score_dimensions_and_recommendations(): void
    {
        $GLOBALS['_mcpwp_test_posts'][1] = (object) array(
            'ID'           => 1,
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Thin Page',
            'post_content' => '<p>Short.</p>',
            'modified_ts'  => time() - (500 * DAY_IN_SECONDS),
        );

        $report = Mcpwp_Content_Coherence::get_report(array( 'graph_limit' => 10 ));

        $this->assertSame('2026-05-20', $report['schema_version']);
        $this->assertArrayHasKey('score', $report);
        $this->assertArrayHasKey('graph', $report['dimensions']);
        $this->assertArrayHasKey('seo', $report['dimensions']);
        $this->assertNotEmpty($report['recommended_actions']);
    }
}
