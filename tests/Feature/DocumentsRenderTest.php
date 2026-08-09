<?php

namespace Tests\Feature;

use App\Content\Document;
use App\Content\DocumentIndex;
use App\Content\MarkdownRenderer;
use App\Content\TableOfContents;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The real corpus, rendered.
 *
 * These run against the actual Documents rather than fixtures, because the
 * thing worth protecting is this site: 157 real files, some written years ago,
 * with the frontmatter habits of whoever was writing that month.
 */
class DocumentsRenderTest extends TestCase
{
    #[Test]
    public function every_document_is_reachable_at_its_slug(): void
    {
        $documents = app(DocumentIndex::class)->all();

        $this->assertGreaterThan(150, $documents->count(), 'The corpus looks suspiciously small.');

        $failed = $documents
            ->reject(fn (Document $d) => $this->get("/posts/{$d->slug}")->status() === 200)
            ->map(fn (Document $d) => $d->filepath)
            ->all();

        $this->assertSame([], $failed, 'These Documents did not render.');
    }

    #[Test]
    public function every_document_declares_a_unique_slug(): void
    {
        $slugs = app(DocumentIndex::class)->all()->map(fn (Document $d) => $d->slug);

        $this->assertSame($slugs->count(), $slugs->unique()->count(), 'Two Documents claim the same URL.');
    }

    #[Test]
    public function heading_anchors_keep_the_content_prefix(): void
    {
        // Every heading link ever shared points at '#content-...'. If the
        // permalink prefix ever changes, those links stop scrolling anywhere
        // and nothing 404s to tell us.
        $document = app(DocumentIndex::class)
            ->all()
            ->first(fn (Document $d) => str_contains(app(MarkdownRenderer::class)->render($d), '<h2'));

        $this->assertNotNull($document, 'No Document has an h2 to check.');

        $html = app(MarkdownRenderer::class)->render($document);
        $outline = app(TableOfContents::class)->from($html);

        $this->assertNotEmpty($outline);

        foreach ($outline as $heading) {
            $this->assertStringStartsWith('content-', $heading['id']);
            $this->assertStringContainsString('id="'.$heading['id'].'"', $html, "Outline names an anchor the page does not contain: {$heading['id']}");
        }
    }

    #[Test]
    public function a_document_that_does_not_exist_is_not_found(): void
    {
        $this->get('/posts/no-such-document-exists-here')->assertNotFound();
    }

    #[Test]
    public function repaired_slugs_still_redirect_from_their_published_form(): void
    {
        $this->get('/posts/handling-rounding-millisecond-issue-with-diffinseconds-in%20time')
            ->assertRedirect('/posts/handling-rounding-millisecond-issue-with-diffinseconds-in-time');

        $this->get('/posts/get-5-laravel-books-for-free%E2%80%93download-now')
            ->assertRedirect('/posts/get-5-laravel-books-for-free-download-now');
    }
}
