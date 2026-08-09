<?php

namespace Tests\Feature;

use App\Content\Document;
use App\Content\DocumentIndex;
use App\Content\OgImage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SocialCardTest extends TestCase
{
    #[Test]
    public function it_draws_a_card_at_the_size_social_networks_expect(): void
    {
        $cards = app(OgImage::class);

        if (! $cards->available()) {
            $this->markTestSkipped('No font installed at '.$cards->fontPath().'.');
        }

        $png = $cards->render(app(DocumentIndex::class)->all()->first());

        $this->assertNotNull($png);

        $image = imagecreatefromstring($png);

        $this->assertSame(1200, imagesx($image));
        $this->assertSame(630, imagesy($image));
    }

    #[Test]
    public function a_long_title_shrinks_rather_than_losing_its_ending(): void
    {
        $cards = app(OgImage::class);

        if (! $cards->available()) {
            $this->markTestSkipped('No font installed.');
        }

        $title = 'Extraordinarily Verbose Headline About Something Involving Laravel And A Great Many Other Concerns Besides';

        $fit = new \ReflectionMethod($cards, 'fit');
        [$size, $lines] = $fit->invoke($cards, $title);

        $this->assertLessThanOrEqual(4, count($lines));
        $this->assertSame(
            preg_replace('/\s+/', ' ', trim($title)),
            implode(' ', $lines),
            'The card dropped words instead of shrinking to fit.'
        );
        $this->assertLessThanOrEqual(54, $size);
    }

    #[Test]
    public function a_document_with_its_own_image_keeps_it(): void
    {
        $document = app(DocumentIndex::class)
            ->all()
            ->first(fn (Document $d) => filled($d->frontmatter->image));

        $this->assertNotNull($document, 'No Document declares an image.');
        $this->assertStringContainsString(
            ltrim($document->frontmatter->image, '/'),
            (string) app(OgImage::class)->urlFor($document)
        );
    }

    #[Test]
    public function a_document_with_no_image_and_no_card_advertises_none(): void
    {
        // Never a placeholder that does not exist: that is exactly what left
        // 141 Documents with a broken card for years.
        $document = app(DocumentIndex::class)->all()->first();
        $missing = $document->withUpdatedAt($document->updatedAt);

        config()->set('blog.og.font', '/nonexistent/font.ttf');

        $this->assertNull(app(OgImage::class)->render($missing));
    }
}
