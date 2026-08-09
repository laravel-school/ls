<?php

namespace App\Content;

use Illuminate\Support\Collection;

/**
 * The sitemap, built from the Documents.
 *
 * Absolute URLs come from the application's configured URL rather than from
 * whatever host happens to be serving the build, because a sitemap generated on
 * a laptop once told search engines that all 157 pages lived on
 * http://localhost.
 */
class Sitemap
{
    public const PATH = 'sitemap.xml';

    public function __construct(private readonly RevisionDates $revisions) {}

    /**
     * @param  Collection<int, Document>  $documents
     */
    public function xml(Collection $documents): string
    {
        $origin = rtrim((string) config('app.url'), '/');

        $entries = $documents
            ->sortBy(fn (Document $d) => $d->slug)
            ->map(fn (Document $d) => sprintf(
                "    <url>\n        <loc>%s</loc>\n        <lastmod>%s</lastmod>\n    </url>",
                htmlspecialchars($origin.'/posts/'.$d->slug, ENT_XML1),
                $this->revisions->for($d)->toIso8601String(),
            ))
            ->implode("\n");

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        {$entries}
        </urlset>

        XML;
    }
}
