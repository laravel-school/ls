<?php

namespace Tests\Feature;

use App\Content\Document;
use App\Content\DocumentIndex;
use App\Content\DocumentType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeedTest extends TestCase
{
    #[Test]
    public function the_feed_shows_posts_and_snippets_but_not_pages(): void
    {
        $feed = app(DocumentIndex::class)->feed();

        $this->assertNotEmpty($feed);
        $this->assertEmpty(
            $feed->filter(fn (Document $d) => $d->type === DocumentType::Page),
            'A Page appeared in the chronological feed.'
        );
    }

    #[Test]
    public function the_feed_is_newest_first(): void
    {
        $dates = app(DocumentIndex::class)->feed()->map(fn (Document $d) => $d->createdAt->getTimestamp());

        $this->assertSame($dates->sortDesc()->values()->all(), $dates->values()->all());
    }

    #[Test]
    public function the_snippets_feed_contains_only_snippets(): void
    {
        $snippets = app(DocumentIndex::class)->feed(type: DocumentType::Snippet);

        $this->assertNotEmpty($snippets);
        $this->assertTrue($snippets->every(fn (Document $d) => $d->type === DocumentType::Snippet));
    }

    #[Test]
    public function filtering_by_category_narrows_the_feed(): void
    {
        $index = app(DocumentIndex::class);
        $category = $index->categories()->keys()->first();

        $filtered = $index->feed(category: $category);

        $this->assertNotEmpty($filtered);
        $this->assertTrue($filtered->every(fn (Document $d) => $d->frontmatter->category === $category));
        $this->assertLessThanOrEqual($index->feed()->count(), $filtered->count());
    }

    #[Test]
    public function filtering_by_tag_narrows_the_feed(): void
    {
        $index = app(DocumentIndex::class);
        $tag = $index->tags()->keys()->first();

        $filtered = $index->feed(tag: $tag);

        $this->assertNotEmpty($filtered);
        $this->assertTrue($filtered->every(fn (Document $d) => in_array($tag, $d->frontmatter->tags, true)));
    }

    #[Test]
    public function the_feed_paginates(): void
    {
        $perPage = (int) config('blog.per_page');

        $this->get('/')->assertOk();
        $this->get('/posts?page=2')->assertOk();

        $page = app(DocumentIndex::class)->paginate(app(DocumentIndex::class)->feed(), $perPage, 1);

        $this->assertCount($perPage, $page->items());
        $this->assertSame(app(DocumentIndex::class)->feed()->count(), $page->total());
    }

    #[Test]
    public function search_finds_documents_by_heading_or_title(): void
    {
        $response = $this->getJson('/posts/search?q=laravel');

        $response->assertOk();
        $this->assertNotEmpty($response->json());

        foreach ($response->json() as $result) {
            $this->assertArrayHasKey('text', $result);
            $this->assertArrayHasKey('url', $result);
        }
    }

    #[Test]
    public function search_with_no_query_returns_nothing_rather_than_everything(): void
    {
        $this->getJson('/posts/search?q=')->assertOk()->assertExactJson([]);
    }
}
