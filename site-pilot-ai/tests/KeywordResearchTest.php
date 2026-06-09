<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/core/class-spai-keyword-research.php';

final class KeywordResearchTest extends TestCase
{
    // -----------------------------------------------------------------------
    // expansions()
    // -----------------------------------------------------------------------

    public function test_expansions_includes_seed(): void
    {
        $result = Spai_Keyword_Research::expansions('coffee');
        $this->assertContains('coffee', $result);
    }

    public function test_expansions_includes_alphabet_variants(): void
    {
        $result = Spai_Keyword_Research::expansions('coffee');
        $this->assertContains('coffee a', $result);
        $this->assertContains('coffee z', $result);
        $this->assertContains('coffee m', $result);
    }

    public function test_expansions_includes_question_prefixes(): void
    {
        $result = Spai_Keyword_Research::expansions('coffee');
        $this->assertContains('how coffee', $result);
        $this->assertContains('what coffee', $result);
        $this->assertContains('why coffee', $result);
    }

    public function test_expansions_includes_suffix_modifiers(): void
    {
        $result = Spai_Keyword_Research::expansions('coffee');
        $this->assertContains('coffee best', $result);
        $this->assertContains('coffee vs', $result);
        $this->assertContains('coffee for', $result);
        $this->assertContains('coffee near me', $result);
    }

    public function test_expansions_returns_unique_strings(): void
    {
        $result = Spai_Keyword_Research::expansions('test');
        $lower  = array_map('strtolower', $result);
        $this->assertSame(count($lower), count(array_unique($lower)));
    }

    public function test_expansions_respects_cap(): void
    {
        // Default cap is MAX_EXPANSIONS = 60.
        $result = Spai_Keyword_Research::expansions('something');
        $this->assertLessThanOrEqual(Spai_Keyword_Research::MAX_EXPANSIONS, count($result));
    }

    public function test_expansions_returns_non_empty_array(): void
    {
        $result = Spai_Keyword_Research::expansions('x');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    // -----------------------------------------------------------------------
    // group()
    // -----------------------------------------------------------------------

    public function test_group_classifies_question_by_starter_word(): void
    {
        $result = Spai_Keyword_Research::group(
            'tomato',
            array( 'how to grow tomatoes', 'tomato fertilizer' )
        );

        $this->assertContains('how to grow tomatoes', $result['questions']);
        $this->assertContains('tomato fertilizer', $result['keywords']);
    }

    public function test_group_classifies_question_mark_as_question(): void
    {
        $result = Spai_Keyword_Research::group(
            'coffee',
            array( 'is coffee good for you?', 'coffee brands' )
        );

        $this->assertContains('is coffee good for you?', $result['questions']);
        $this->assertContains('coffee brands', $result['keywords']);
    }

    public function test_group_drops_bare_seed(): void
    {
        $result = Spai_Keyword_Research::group(
            'tomato',
            array( 'tomato', 'Tomato', 'TOMATO', 'tomato fertilizer' )
        );

        $this->assertNotContains('tomato', $result['keywords']);
        $this->assertNotContains('Tomato', $result['keywords']);
        $this->assertNotContains('TOMATO', $result['keywords']);
        $this->assertContains('tomato fertilizer', $result['keywords']);
    }

    public function test_group_deduplicates_case_insensitively(): void
    {
        $result = Spai_Keyword_Research::group(
            'coffee',
            array( 'coffee beans', 'Coffee Beans', 'COFFEE BEANS', 'coffee grinder' )
        );

        // Should only have one variant of "coffee beans" across both lists.
        $all = array_merge($result['keywords'], $result['questions']);
        $lower_all = array_map('strtolower', $all);
        $this->assertSame(count(array_unique($lower_all)), count($lower_all));
    }

    public function test_group_returns_sorted_arrays(): void
    {
        $result = Spai_Keyword_Research::group(
            'plant',
            array( 'zebra plant care', 'aloe vera', 'monstera plant', 'how to water plants', 'can plants feel pain' )
        );

        $sorted_kw = $result['keywords'];
        $expected_kw = $result['keywords'];
        sort($expected_kw);
        $this->assertSame($expected_kw, $sorted_kw);

        $sorted_q = $result['questions'];
        $expected_q = $result['questions'];
        sort($expected_q);
        $this->assertSame($expected_q, $sorted_q);
    }

    public function test_group_total_matches_sum(): void
    {
        $result = Spai_Keyword_Research::group(
            'coffee',
            array( 'coffee beans', 'how to brew coffee', 'best coffee maker' )
        );

        $this->assertSame(
            count($result['keywords']) + count($result['questions']),
            $result['total']
        );
    }

    public function test_group_seed_field_preserved(): void
    {
        $result = Spai_Keyword_Research::group('organic tea', array( 'organic tea brands' ));
        $this->assertSame('organic tea', $result['seed']);
    }

    // -----------------------------------------------------------------------
    // group() — edge cases
    // -----------------------------------------------------------------------

    public function test_group_empty_suggestions_returns_empty_arrays(): void
    {
        $result = Spai_Keyword_Research::group('anything', array());

        $this->assertSame(array(), $result['keywords']);
        $this->assertSame(array(), $result['questions']);
        $this->assertSame(0, $result['total']);
    }

    public function test_group_all_seeds_dropped_returns_empty(): void
    {
        $result = Spai_Keyword_Research::group(
            'coffee',
            array( 'coffee', 'Coffee', 'COFFEE' )
        );

        $this->assertSame(0, $result['total']);
    }

    public function test_group_question_starters_all_classified(): void
    {
        $starters = array( 'who', 'what', 'when', 'where', 'why', 'how', 'which', 'whose', 'whom',
                           'can', 'could', 'does', 'do', 'is', 'are', 'will', 'should', 'would' );
        $inputs = array();
        foreach ($starters as $s) {
            $inputs[] = $s . ' is this a test';
        }

        $result = Spai_Keyword_Research::group('test', $inputs);

        $this->assertSame(count($starters), count($result['questions']));
        $this->assertSame(0, count($result['keywords']));
    }
}
