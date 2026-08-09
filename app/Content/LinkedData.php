<?php

namespace App\Content;


/**
 * The schema.org Article description embedded in a Document's page, which is
 * what search engines and social cards read instead of the prose.
 */
class LinkedData
{
    public function __construct(private readonly OgImage $ogImage) {}

    /**
     * @return array<string, mixed>
     */
    public function for(Document $document): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $document->frontmatter->title,
            'datePublished' => $document->createdAt->toIso8601String(),
            'dateModified' => $document->updatedAt->toIso8601String(),
            'author' => $this->author($document),
            'publisher' => $this->publisher(),
            'image' => $this->image($document),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function author(Document $document): array
    {
        /** @var array<string, array<string, string>> $authors */
        $authors = config('blog.authors');

        return $authors[$document->frontmatter->author] ?? reset($authors);
    }

    /**
     * @return array<string, string>
     */
    private function publisher(): array
    {
        /** @var array<string, string> $publisher */
        $publisher = config('blog.publisher');

        return $publisher;
    }

    /**
     * A Document's own image if it has one, otherwise the site's. Relative
     * paths are made absolute, because a social card cannot follow one.
     */
    private function image(Document $document): string
    {
        return $this->ogImage->urlFor($document) ?? $this->publisher()['image'];
    }
}
