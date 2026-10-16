<?php

namespace Tests\Unit;

use App\Services\Records\RecordSearchService;
use Tests\TestCase;

/**
 * The LIKE escape clause has to parse on every engine this app will run on.
 *
 * The register originally escaped wildcards with a backslash and wrote
 * `ESCAPE '\'` into its raw SQL. That parses on SQLite and fails on MySQL and
 * MariaDB with error 1064, because a backslash is itself an escape character
 * inside a MySQL string literal: `'\'` swallows the closing quote and the literal
 * never terminates. The whole register search returned a 500 on every MySQL
 * connection and the suite never saw it, because the suite runs on SQLite.
 *
 * These assert the generated SQL rather than the search results, because the bug
 * was in the statement's shape, not in what it should return.
 */
class RecordSearchEscapeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // scoped() refuses to build without an active company, which is the correct
        // behaviour and just means the fixture has to supply one.
        $this->actingInCompany($this->seedTenantAndCompany());
    }

    public function test_the_like_clause_names_an_escape_character_sql_can_parse(): void
    {
        $query = $this->service()->search(['q' => 'anything']);

        $sql = $query->toSql();

        $this->assertStringContainsString("ESCAPE '!'", $sql);

        // The specific failure: a backslash as the escape character is unparseable
        // on MySQL and MariaDB. Asserted explicitly so reverting to it is a failure
        // rather than a silent 500 in production.
        $this->assertStringNotContainsString(
            "ESCAPE '\\'",
            $sql,
            'A backslash as the LIKE escape character produces a syntax error on MySQL and MariaDB.',
        );
    }

    /**
     * Every LIKE in the builder, not just the first: the free-text filter applies
     * six and the OCR filter a seventh, and they were not all rewritten in step.
     */
    public function test_every_like_in_the_builder_carries_the_same_escape_character(): void
    {
        $sql = $this->service()->search(['q' => 'a', 'ocr' => 'b'])->toSql();

        $likes = substr_count($sql, 'LIKE ?');

        $this->assertGreaterThan(0, $likes);
        $this->assertSame(
            $likes,
            substr_count($sql, "ESCAPE '!'"),
            'Every LIKE needs the same explicit ESCAPE clause.',
        );
    }

    /**
     * The wildcard still has to be neutralised, or the escape character was
     * changed for nothing.
     */
    public function test_a_wildcard_in_a_search_term_is_escaped_not_treated_as_a_wildcard(): void
    {
        $this->assertSame(
            '%100!%%',
            $this->searchBinding('100%'),
            'The % must carry the escape character or it still matches any run of characters.',
        );
    }

    /**
     * The escape character itself has to be escaped, or a search for "a!b" would
     * match "a" followed by any single character and then "b".
     */
    public function test_the_escape_character_is_itself_escaped(): void
    {
        $this->assertSame('%a!!b%', $this->searchBinding('a!b'));
    }

    /**
     * The underscore, which is the other LIKE wildcard.
     */
    public function test_an_underscore_in_a_search_term_is_escaped(): void
    {
        $this->assertSame('%a!_b%', $this->searchBinding('a_b'));
    }

    /**
     * An ordinary term is untouched apart from the surrounding wildcards.
     */
    public function test_an_ordinary_term_is_still_searched_literally(): void
    {
        $this->assertSame('%Board Resolution%', $this->searchBinding('Board Resolution'));
    }

    /**
     * The binding the search term itself reached the query as.
     *
     * scoped() puts tenant_id and company_id in first, so the term is not the
     * first binding — which is an easy thing to read wrong and would make these
     * assertions compare a ULID against a LIKE pattern.
     */
    private function searchBinding(string $term): mixed
    {
        $bindings = $this->service()->search(['q' => $term])->getBindings();

        return $bindings[2] ?? null;
    }

    private function service(): RecordSearchService
    {
        return $this->app->make(RecordSearchService::class);
    }
}