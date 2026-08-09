<?php

namespace App\Content;

use Illuminate\Support\Collection;

/**
 * The hand-written sidebar, read from SUMMARY.md.
 *
 * This is the one piece of site structure the author controls directly rather
 * than deriving from the Documents: a list of '## Section' headings, each
 * followed by markdown links.
 */
class Navigation
{
    public function __construct(private readonly DocumentReader $reader) {}

    /**
     * @return Collection<int, array{title: string, links: array<int, array{title: string, slug: string}>}>
     */
    public function sections(): Collection
    {
        $path = $this->reader->root().'/SUMMARY.md';

        if (! is_file($path)) {
            return collect();
        }

        preg_match_all('/##.*?(?=##|\z)/s', (string) file_get_contents($path), $matches, PREG_SET_ORDER);

        return collect($matches)->map(fn (array $match) => [
            'title' => $this->title($match[0]),
            'links' => $this->links($match[0]),
        ]);
    }

    private function title(string $section): string
    {
        return trim(substr(explode("\n", $section)[0], 2));
    }

    /**
     * @return array<int, array{title: string, slug: string}>
     */
    private function links(string $section): array
    {
        $links = [];

        foreach (explode("\n", $section) as $line) {
            if (preg_match('/^\s*[-*]\s+\[(.+)\]\((.+)\)/', $line, $match)) {
                $links[] = [
                    'title' => $match[1],
                    'slug' => str_replace('content/', '', $match[2]),
                ];
            }
        }

        return $links;
    }
}
