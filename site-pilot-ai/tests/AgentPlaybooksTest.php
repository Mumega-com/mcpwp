<?php

use PHPUnit\Framework\TestCase;

final class AgentPlaybooksTest extends TestCase
{
    public function test_lists_playbooks_when_name_is_empty(): void
    {
        $result = Spai_Agent_Playbooks::get_playbook('');

        $this->assertSame('2026-05-20', $result['schema_version']);
        $this->assertNotEmpty($result['playbooks']);
    }

    public function test_build_gutenberg_page_has_required_contract_gates(): void
    {
        $playbook = Spai_Agent_Playbooks::get_playbook('build_gutenberg_page');

        $this->assertSame('build_gutenberg_page', $playbook['name']);
        $this->assertContains('wp_get_site_state', $playbook['global_rules']['read_first']);
        $this->assertContains('wp_validate_blocks', $playbook['validation_gates']);
        $this->assertContains('content_update', $playbook['approval_gates']);
        $this->assertContains('wp_rollback_approval', $playbook['rollback']);
        $this->assertContains('block_validation_error', $playbook['stop_conditions']);
    }
}
