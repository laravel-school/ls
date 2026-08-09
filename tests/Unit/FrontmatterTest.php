<?php

namespace Tests\Unit;

use App\Content\DocumentType;
use App\Content\Frontmatter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontmatterTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function valid(array $overrides = []): array
    {
        return [...[
            'title' => 'A Title',
            'slug' => 'a-title-1',
            'date' => '2024-01-01',
            'excerpt' => 'What this is about.',
            'category' => 'Laravel',
        ], ...$overrides];
    }

    #[Test]
    public function it_reads_a_complete_block(): void
    {
        [$fm, $errors] = Frontmatter::fromArray($this->valid(['tags' => ['a', 'b']]), DocumentType::Post);

        $this->assertSame([], $errors);
        $this->assertSame('A Title', $fm->title);
        $this->assertSame(['a', 'b'], $fm->tags);
        $this->assertSame('2024-01-01', $fm->date->toDateString());
        $this->assertFalse($fm->draft);
    }

    #[Test]
    public function it_accepts_the_unix_timestamp_the_yaml_parser_produces(): void
    {
        // An unquoted ISO date in YAML arrives as an integer, not a string.
        [$fm, $errors] = Frontmatter::fromArray($this->valid(['date' => 1704067200]), DocumentType::Post);

        $this->assertSame([], $errors);
        $this->assertSame('2024-01-01', $fm->date->toDateString());
    }

    #[Test]
    public function a_post_must_declare_its_slug(): void
    {
        // Deriving a slug from the title would mean fixing a typo in a headline
        // silently changed a published URL.
        [$fm, $errors] = Frontmatter::fromArray($this->valid(['slug' => null]), DocumentType::Post);

        $this->assertNull($fm);
        $this->assertContains("missing required field 'slug'", $errors);
    }

    #[Test]
    public function a_page_need_not_declare_a_slug(): void
    {
        [$fm, $errors] = Frontmatter::fromArray($this->valid(['slug' => null]), DocumentType::Page);

        $this->assertSame([], $errors);
        $this->assertNull($fm->slug);
    }

    #[Test]
    public function it_reports_every_problem_at_once(): void
    {
        [$fm, $errors] = Frontmatter::fromArray(['title' => 'Only a title'], DocumentType::Post);

        $this->assertNull($fm);
        $this->assertCount(4, $errors, 'Errors should not stop at the first missing field.');
    }

    #[Test]
    public function it_rejects_a_slug_containing_characters_a_url_cannot_carry(): void
    {
        foreach (['has space', 'has–endash', 'HasCapitals'] as $slug) {
            [$fm, $errors] = Frontmatter::fromArray($this->valid(['slug' => $slug]), DocumentType::Post);

            $this->assertNull($fm, "Accepted a bad slug: {$slug}");
            $this->assertNotEmpty($errors);
        }
    }

    #[Test]
    public function it_allows_underscores_in_a_slug(): void
    {
        // snippets/37.md publishes 'utf8mb4_0900_ai_ci'. Underscores are
        // perfectly legal in a URL; rejecting them would mean changing a
        // published address to satisfy a rule that was simply wrong.
        [$fm, $errors] = Frontmatter::fromArray($this->valid(['slug' => 'unknown-collation-utf8mb4_0900_ai_ci']), DocumentType::Post);

        $this->assertSame([], $errors);
        $this->assertNotNull($fm);
    }

    #[Test]
    public function it_rejects_a_field_it_does_not_understand(): void
    {
        [$fm, $errors] = Frontmatter::fromArray($this->valid(['catgeory' => 'Laravel']), DocumentType::Post);

        $this->assertNull($fm);
        $this->assertContains("unknown field 'catgeory'", $errors);
    }
}
