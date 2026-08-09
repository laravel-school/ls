<?php

namespace App\Content;

use App\Content\Markdown\ImageUrlExtension;
use Illuminate\Support\Facades\Cache;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\MarkdownConverter;

/**
 * Turns a Document's markdown into the HTML of its page body.
 *
 * Rendering happens per request and is cached forever under a key that includes
 * the file's modification time, so a changed Document can never serve stale
 * HTML and there is no cache anyone has to remember to clear. The build warms
 * this cache as its last step, which doubles as proof that every Document still
 * parses.
 */
class MarkdownRenderer
{
    public function __construct(private readonly DocumentReader $reader) {}

    public function render(Document $document): string
    {
        $path = $this->reader->absolute($document->filepath);
        $key = "blog:html:{$document->slug}:".(filemtime($path) ?: 0);

        return Cache::rememberForever($key, fn () => $this->convert((string) file_get_contents($path)));
    }

    /** Render without consulting or populating the cache. */
    public function renderFresh(Document $document): string
    {
        return $this->convert((string) file_get_contents($this->reader->absolute($document->filepath)));
    }

    private function convert(string $markdown): string
    {
        /** @var array<string, mixed> $config */
        $config = config('blog.commonmark.config');

        $environment = new Environment($config);

        /** @var array<int, class-string<ExtensionInterface>> $extensions */
        $extensions = config('blog.commonmark.extensions');

        foreach ($extensions as $extension) {
            $environment->addExtension(new $extension);
        }

        $environment->addExtension(new ImageUrlExtension);

        return (new MarkdownConverter($environment))->convert($markdown)->getContent();
    }
}
