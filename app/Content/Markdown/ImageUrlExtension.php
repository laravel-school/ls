<?php

namespace App\Content\Markdown;

use Illuminate\Support\Str;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * Points local image references at the image route and gives them a srcset.
 *
 * Images live outside the public directory, beside the Documents that use them,
 * so a relative markdown image has no directly servable URL. Remote images are
 * left exactly as written — they are somebody else's bytes and cannot be
 * resized.
 */
class ImageUrlExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->onDocumentParsed(...));
    }

    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        $walker = $event->getDocument()->walker();

        while ($item = $walker->next()) {
            $node = $item->getNode();

            if (! $node instanceof Image || ! $item->isEntering()) {
                continue;
            }

            if (Str::startsWith($node->getUrl(), ['http://', 'https://'])) {
                continue;
            }

            $original = $node->getUrl();
            $node->setUrl(route('blog.image', $original, false));

            $node->data->set('attributes', [
                'x-zoomable' => config('blog.image.zoomable'),
                'srcset' => $this->srcset($original),
                'sizes' => config('blog.image.sizes'),
                'loading' => 'lazy',
                'decoding' => 'async',
                'fetchpriority' => 'auto',
            ]);
        }
    }

    private function srcset(string $url): string
    {
        /** @var array<int, int> $widths */
        $widths = config('blog.image.widths');

        return collect($widths)
            ->map(fn (int $width) => $this->variant($url, $width)." {$width}w")
            ->implode(', ');
    }

    /** The image route understands a '-960w' suffix as a resize instruction. */
    private function variant(string $url, int $width): string
    {
        $name = pathinfo($url, PATHINFO_FILENAME)."-{$width}w.".pathinfo($url, PATHINFO_EXTENSION);

        return route('blog.image', $name, false);
    }
}
