<?php

use PHPUnit\Framework\TestCase;

final class SiteStateTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['mcpwp_test_options'] = array();
        $GLOBALS['_mcpwp_test_posts'] = array();
        $GLOBALS['_mcpwp_test_menu_page_ids'] = array();
    }

    public function test_snapshot_summarizes_state_and_recommendations(): void
    {
        $GLOBALS['_mcpwp_test_posts'][1] = (object) array(
            'ID'           => 1,
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => 'Services',
            'post_content' => '<p>Short service page.</p>',
            'modified_ts'  => time() - (400 * DAY_IN_SECONDS),
        );

        $snapshot = Mcpwp_Site_State::get_snapshot(array( 'graph_limit' => 10, 'event_limit' => 5 ));

        $this->assertSame('2026-05-20', $snapshot['schema_version']);
        $this->assertSame(1, $snapshot['content']['page']['publish']);
        $this->assertSame(1, $snapshot['graph']['orphan_pages']['count']);
        $this->assertSame(1, $snapshot['graph']['thin_content']['count']);
        $this->assertNotEmpty($snapshot['recommended_actions']);
        $this->assertSame('set_site_context', $snapshot['recommended_actions'][0]['code']);
    }
}
