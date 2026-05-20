<?php

use PHPUnit\Framework\TestCase;

final class EventStoreTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['spai_test_options'] = array();
        $GLOBALS['spai_test_actions'] = array();
        wp_set_current_user(2);
    }

    public function test_emit_stores_event_and_fires_specific_hook(): void
    {
        $captured = array();
        add_action(
            'spai_approval_created',
            function ($event) use (&$captured): void {
                $captured = $event;
            },
            10,
            1
        );

        $event = Spai_Event_Store::emit(
            'approval.created',
            array(
                'approval' => array(
                    'id'     => 'approval_1',
                    'status' => 'pending',
                ),
            ),
            array(
                'resource'           => array(
                    'type' => 'post',
                    'id'   => 123,
                ),
                'risk_level'         => 'medium',
                'approval_state'     => 'pending',
                'recommended_action' => 'Review approval request.',
            )
        );

        $this->assertSame('approval.created', $event['type']);
        $this->assertSame('spai_approval_created', $event['hook']);
        $this->assertSame('approval.created', $captured['type']);
        $this->assertSame('pending', $captured['approval_state']);

        $stored = Spai_Event_Store::list_events(array( 'type' => 'approval.created' ));
        $this->assertSame(1, $stored['total']);
        $this->assertSame($event['id'], $stored['events'][0]['id']);
    }

    public function test_schema_contains_first_slice_events(): void
    {
        $schema = Spai_Event_Store::get_schema();

        $this->assertArrayHasKey('approval.created', $schema);
        $this->assertArrayHasKey('approval.applied', $schema);
        $this->assertArrayHasKey('seo.audit_completed', $schema);
        $this->assertSame('spai_seo_audit_completed', $schema['seo.audit_completed']['hook']);
    }
}
