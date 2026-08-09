<?php

namespace App\Content;

use Illuminate\Support\Collection;

/**
 * The searchable shape of the corpus, written where a browser can fetch it.
 *
 * 157 Documents is small enough that the whole index can be handed to the
 * reader once and filtered in the page — which is why search needs no endpoint,
 * no query language and no server work at all.
 */
class SearchIndex
{
    public const PATH = 'search-index.json';

    /**
     * @param  Collection<int, Document>  $documents
     * @param  array<string, array<int, array{text: string, url: string}>>  $headings
     */
    public function json(Collection $documents, array $headings): string
    {
        $entries = $documents
            ->filter(fn (Document $d) => ! $d->frontmatter->draft)
            ->flatMap(fn (Document $d) => [
                [
                    'text' => $d->frontmatter->title,
                    'url' => '/posts/'.$d->slug,
                    'kind' => $d->type->value,
                    'excerpt' => $d->frontmatter->excerpt,
                ],
                ...array_map(fn (array $heading) => [
                    'text' => $heading['text'],
                    'url' => $heading['url'],
                    'kind' => 'heading',
                    'excerpt' => $d->frontmatter->title,
                ], $headings[$d->slug] ?? []),
            ])
            ->values();

        return (string) json_encode($entries, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
