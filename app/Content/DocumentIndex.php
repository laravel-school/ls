<?php

namespace App\Content;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Every Document's metadata, in memory.
 *
 * The whole corpus is a few hundred kilobytes of frontmatter, so this is a
 * generated PHP array that opcache keeps resident rather than a database. It
 * exists so that listing pages do not have to open 157 files.
 *
 * When the generated manifest is missing — which is the normal state on a
 * developer's machine — the Index reads the Documents directly instead. That
 * makes a freshly cloned repository work with no build step, and makes edits
 * visible on the next refresh.
 */
class DocumentIndex
{
    /** @var Collection<string, Document>|null */
    private ?Collection $documents = null;

    /** @var array<string, array<int, array{text: string, url: string}>> */
    private array $headings = [];

    public function __construct(private readonly DocumentReader $reader) {}

    public static function manifestPath(): string
    {
        return storage_path('app/blog/index.php');
    }

    /** @return Collection<string, Document> */
    public function all(): Collection
    {
        return $this->documents ??= $this->load();
    }

    public function find(string $slug): ?Document
    {
        return $this->all()->get($slug);
    }

    /**
     * The chronological feed, newest first, optionally narrowed.
     *
     * @return Collection<int, Document>
     */
    public function feed(?DocumentType $type = null, ?string $category = null, ?string $tag = null): Collection
    {
        return $this->all()
            ->filter(fn (Document $d) => $d->type->isFeedable() && ! $d->frontmatter->draft)
            ->when($type instanceof DocumentType, fn ($docs) => $docs->filter(fn (Document $d) => $d->type === $type))
            ->when(filled($category), fn ($docs) => $docs->filter(fn (Document $d) => $d->frontmatter->category === $category))
            ->when(filled($tag), fn ($docs) => $docs->filter(fn (Document $d) => in_array($tag, $d->frontmatter->tags, true)))
            ->sortByDesc(fn (Document $d) => $d->createdAt->getTimestamp())
            ->values();
    }

    /**
     * @param  Collection<int, Document>  $documents
     * @return LengthAwarePaginator<int, Document>
     */
    public function paginate(Collection $documents, int $perPage, int $page): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $documents->forPage($page, $perPage)->values(),
            $documents->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    /** @return Collection<string, int> Category name to Document count, most used first. */
    public function categories(): Collection
    {
        return $this->feed()
            ->countBy(fn (Document $d) => $d->frontmatter->category)
            ->sortDesc();
    }

    /** @return Collection<string, int> Tag name to Document count, most used first. */
    public function tags(): Collection
    {
        return $this->feed()
            ->flatMap(fn (Document $d) => $d->frontmatter->tags)
            ->countBy()
            ->sortDesc();
    }

    /**
     * Headings across every Document, for search.
     *
     * Only the build knows these, because finding them means rendering. Without
     * a manifest — a developer's machine, usually — search falls back to what
     * can be known without rendering 157 files on a keystroke.
     *
     * @return array<int, array{text: string, url: string}>
     */
    public function headings(): array
    {
        $this->all();

        return array_merge(...array_values($this->headings) ?: [[]]);
    }

    /** @return Collection<string, Document> */
    private function load(): Collection
    {
        if (is_file(self::manifestPath())) {
            /** @var array{documents: array<int, array<string, mixed>>, headings: array<string, array<int, array{text: string, url: string}>>} $manifest */
            $manifest = require self::manifestPath();

            $this->headings = $manifest['headings'];

            return collect($manifest['documents'])
                ->map(fn (array $values) => Document::fromArray($values))
                ->keyBy(fn (Document $d) => $d->slug);
        }

        [$documents, $failures] = $this->reader->readAll();

        if ($failures !== []) {
            report(new \RuntimeException('Unreadable Documents: '.implode('; ', array_keys($failures))));
        }

        return $documents->keyBy(fn (Document $d) => $d->slug);
    }
}
