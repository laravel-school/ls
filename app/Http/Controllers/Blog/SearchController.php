<?php

namespace App\Http\Controllers\Blog;

use App\Content\Document;
use App\Content\DocumentIndex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SearchController
{
    private const LIMIT = 5;

    public function __construct(private readonly DocumentIndex $index) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        if ($query === '' || mb_strlen($query) > 255) {
            return response()->json([]);
        }

        $headings = collect($this->index->headings())
            ->filter(fn (array $heading) => Str::contains($heading['text'], $query, ignoreCase: true))
            ->take(self::LIMIT)
            ->values();

        $results = $headings->count() < self::LIMIT
            ? $headings->merge($this->titleMatches($query, self::LIMIT - $headings->count()))
            : $headings;

        return response()->json($results->values());
    }

    /**
     * Headings alone miss a Document whose title says the thing its headings
     * never repeat, so titles fill any space left over.
     *
     * @return Collection<int, array{text: string, url: string}>
     */
    private function titleMatches(string $query, int $limit): Collection
    {
        return $this->index->feed()
            ->filter(fn (Document $d) => Str::contains($d->frontmatter->title, $query, ignoreCase: true))
            ->take($limit)
            ->map(fn (Document $d) => ['text' => $d->frontmatter->title, 'url' => $d->url()])
            ->values();
    }
}
