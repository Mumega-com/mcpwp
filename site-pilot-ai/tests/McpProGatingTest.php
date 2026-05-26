<?php
/**
 * Tests that the agent-safety and SEO-intelligence tools are gated behind Pro.
 *
 * Issue #327: the SEO-intelligence layer, the event store / outbound webhook
 * tools, the approval/rollback (agent-safety) tools, and the site-state
 * snapshot tool must be removed from the free tier and only be available when
 * Pro is active.
 *
 * Gating in Spai_REST_MCP works by merging the Pro registry into the free
 * registry only when is_pro_active() is true (see get_all_tools(),
 * get_all_tool_categories(), get_all_tool_map()). These tests model that exact
 * merge so they assert the same availability a non-pro vs pro client would see,
 * without needing a live Freemius/WordPress entitlement.
 *
 * @package SitePilotAI\Tests
 */

class McpProGatingTest extends PHPUnit\Framework\TestCase
{
    /** @var Spai_MCP_Free_Tools */
    private $free;

    /** @var Spai_MCP_Pro_Tools */
    private $pro;

    /**
     * Tools that issue #327 relocated from free to pro.
     *
     * @var array<string, string[]>
     */
    private $moved = array(
        'seo' => array(
            'wp_validate_seo_readiness',
            'wp_validate_structured_data',
            'wp_audit_media_seo',
            'wp_seo_audit_site',
            'wp_audit_content_quality',
            'wp_get_seo_issues',
            'wp_run_seo_autofix_plan',
            'wp_import_search_performance',
            'wp_get_seo_trends',
            'wp_get_woocommerce_seo_report',
            'wp_get_content_coherence_report',
        ),
        'webhooks' => array(
            'wp_list_webhook_events',
            'wp_get_event_schema',
            'wp_list_mcp_events',
            'wp_list_webhooks',
            'wp_create_webhook',
            'wp_update_webhook',
            'wp_delete_webhook',
            'wp_test_webhook',
            'wp_list_webhook_logs',
        ),
        'approvals' => array(
            'wp_list_approvals',
            'wp_get_approval',
            'wp_apply_approval',
            'wp_rollback_approval',
        ),
        'site_state' => array(
            'wp_get_site_state',
        ),
    );

    protected function setUp(): void
    {
        $this->free = new Spai_MCP_Free_Tools();
        $this->pro  = new Spai_MCP_Pro_Tools();
    }

    /** @return string[] */
    private function flatMoved(): array
    {
        $names = array();
        foreach ($this->moved as $group) {
            foreach ($group as $name) {
                $names[] = $name;
            }
        }
        return $names;
    }

    /** Mirror Spai_REST_MCP::get_all_tools() names for a given pro state. */
    private function mergedToolNames(bool $isPro): array
    {
        $tools = $this->free->get_tools();
        if ($isPro) {
            $tools = array_merge($tools, $this->pro->get_tools());
        }
        return array_column($tools, 'name');
    }

    /** Mirror Spai_REST_MCP::get_all_tool_map() for a given pro state. */
    private function mergedToolMap(bool $isPro): array
    {
        $map = $this->free->get_tool_map();
        if ($isPro) {
            $map = array_merge($map, $this->pro->get_tool_map());
        }
        return $map;
    }

    /** Mirror Spai_REST_MCP::get_all_tool_categories() for a given pro state. */
    private function mergedCategories(bool $isPro): array
    {
        $cats = $this->free->get_tool_categories();
        if ($isPro) {
            $cats = array_merge($cats, $this->pro->get_tool_categories());
        }
        return $cats;
    }

    // ── Non-pro context: moved tools must be absent ────────────────

    public function test_moved_tools_absent_from_free_registry_tools()
    {
        $names = array_column($this->free->get_tools(), 'name');
        foreach ($this->flatMoved() as $tool) {
            $this->assertNotContains(
                $tool,
                $names,
                "Tool {$tool} should no longer be defined in the free registry."
            );
        }
    }

    public function test_moved_tools_absent_from_free_tool_map()
    {
        $map = $this->free->get_tool_map();
        foreach ($this->flatMoved() as $tool) {
            $this->assertArrayNotHasKey(
                $tool,
                $map,
                "Tool {$tool} should no longer be route-mapped in the free registry."
            );
        }
    }

    public function test_moved_tools_absent_from_free_categories()
    {
        $cats = $this->free->get_tool_categories();
        foreach ($this->flatMoved() as $tool) {
            $this->assertArrayNotHasKey(
                $tool,
                $cats,
                "Tool {$tool} should no longer be categorized in the free registry."
            );
        }
    }

    public function test_non_pro_merged_tool_list_excludes_moved_tools()
    {
        $names = $this->mergedToolNames(false);
        foreach ($this->flatMoved() as $tool) {
            $this->assertNotContains(
                $tool,
                $names,
                "Tool {$tool} must not be visible to non-pro clients."
            );
        }
    }

    // ── Pro context: moved tools must be present with all 3 parts ──

    public function test_moved_tools_present_in_pro_registry_with_all_parts()
    {
        $proNames = array_column($this->pro->get_tools(), 'name');
        $proMap   = $this->pro->get_tool_map();
        $proCats  = $this->pro->get_tool_categories();

        foreach ($this->flatMoved() as $tool) {
            $this->assertContains($tool, $proNames, "Pro registry missing schema for {$tool}.");
            $this->assertArrayHasKey($tool, $proMap, "Pro registry missing route map for {$tool}.");
            $this->assertArrayHasKey($tool, $proCats, "Pro registry missing category for {$tool}.");
        }
    }

    public function test_pro_merged_tool_list_includes_moved_tools()
    {
        $names = $this->mergedToolNames(true);
        foreach ($this->flatMoved() as $tool) {
            $this->assertContains(
                $tool,
                $names,
                "Tool {$tool} must be visible to pro clients."
            );
        }
    }

    public function test_pro_merged_tool_map_includes_moved_tools()
    {
        $map = $this->mergedToolMap(true);
        foreach ($this->flatMoved() as $tool) {
            $this->assertArrayHasKey(
                $tool,
                $map,
                "Tool {$tool} route must resolve for pro clients."
            );
        }
    }

    // ── No duplicate registrations across registries ──────────────

    public function test_moved_tools_not_duplicated_across_registries()
    {
        $freeNames = array_column($this->free->get_tools(), 'name');
        $proNames  = array_column($this->pro->get_tools(), 'name');
        $freeMap   = $this->free->get_tool_map();
        $proMap    = $this->pro->get_tool_map();

        foreach ($this->flatMoved() as $tool) {
            $this->assertNotContains($tool, $freeNames, "Duplicate schema for {$tool} left in free.");
            $this->assertContains($tool, $proNames, "Schema for {$tool} missing from pro.");
            $this->assertArrayNotHasKey($tool, $freeMap, "Duplicate route for {$tool} left in free.");
            $this->assertArrayHasKey($tool, $proMap, "Route for {$tool} missing from pro.");
        }
    }

    // ── Approval workflow: approve/reject are gated to Pro ─────────

    public function test_approve_and_reject_request_gated_to_pro()
    {
        $freeNames = array_column($this->free->get_tools(), 'name');
        $freeMap   = $this->free->get_tool_map();
        $proNames  = array_column($this->pro->get_tools(), 'name');
        $proMap    = $this->pro->get_tool_map();

        foreach (array('wp_approve_request', 'wp_reject_request') as $tool) {
            $this->assertNotContains($tool, $freeNames, "{$tool} should be gated to Pro, not free.");
            $this->assertArrayNotHasKey($tool, $freeMap, "{$tool} route should be gated to Pro, not free.");
            $this->assertContains($tool, $proNames, "{$tool} should be in the pro registry.");
            $this->assertArrayHasKey($tool, $proMap, "{$tool} route should be in the pro registry.");
        }
    }

    // ── Core execution tools must stay free ────────────────────────

    public function test_core_execution_tools_stay_free()
    {
        $freeNames = array_column($this->free->get_tools(), 'name');
        $keepFree  = array(
            'wp_create_post',
            'wp_update_page',
            'wp_upload_media',
            'wp_list_menus',
            'wp_create_term',
            'wp_get_blocks',
            'wp_set_blocks',
        );
        foreach ($keepFree as $tool) {
            $this->assertContains(
                $tool,
                $freeNames,
                "Core tool {$tool} must remain free and was moved by mistake."
            );
        }
    }

    // ── Tool map / schema consistency in both registries ──────────

    public function test_pro_tool_map_covers_all_moved_tools_methods()
    {
        $map           = $this->pro->get_tool_map();
        $validMethods  = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE');

        foreach ($this->flatMoved() as $tool) {
            $this->assertArrayHasKey('method', $map[$tool], "No method for {$tool}.");
            $this->assertArrayHasKey('route', $map[$tool], "No route for {$tool}.");
            $this->assertContains($map[$tool]['method'], $validMethods, "Invalid method for {$tool}.");
        }
    }
}
