<?php

namespace App\Content;

use Illuminate\Support\Collection;

/**
 * Reads Documents off disk.
 *
 * Separated from the Index so that "what is on disk" and "what the site knows"
 * are different questions with different answers — the build compares them.
 */
class DocumentReader
{
    /**
     * Every Document in the content directories, plus the reasons any file
     * could not be read.
     *
     * Failures are returned rather than thrown so one malformed file can be
     * reported alongside all the others instead of hiding them.
     *
     * @return array{0: Collection<int, Document>, 1: array<string, array<int, string>>}
     */
    public function readAll(): array
    {
        $documents = collect();
        $failures = [];

        foreach ($this->paths() as $relativePath) {
            [$document, $errors] = Document::read($this->absolute($relativePath), $relativePath);

            if ($document instanceof Document) {
                $documents->push($document);

                continue;
            }

            $failures[$relativePath] = $errors;
        }

        $duplicates = $documents
            ->groupBy(fn (Document $d) => $d->slug)
            ->filter(fn (Collection $group) => $group->count() > 1);

        foreach ($duplicates as $slug => $group) {
            foreach ($group as $document) {
                $failures[$document->filepath][] = "slug '{$slug}' is already used by another Document";
            }
        }

        return [$documents, $failures];
    }

    /** @return array<int, string> Paths relative to the content root. */
    public function paths(): array
    {
        $root = $this->root();
        $paths = [];

        /** @var array<string, string> $types */
        $types = config('blog.types');

        foreach (array_keys($types) as $directory) {
            foreach (glob("{$root}/{$directory}/*.md") ?: [] as $absolute) {
                $paths[] = ltrim(str_replace($root, '', $absolute), '/');
            }
        }

        sort($paths);

        return $paths;
    }

    public function absolute(string $relativePath): string
    {
        return $this->root().'/'.ltrim($relativePath, '/');
    }

    public function root(): string
    {
        return rtrim((string) config('blog.path'), '/');
    }
}
