<?php

namespace App\Content;

use Carbon\Carbon;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;

/**
 * One markdown file, described.
 *
 * Everything the author wrote is under ->frontmatter. Everything the system
 * worked out — where the file lives, what it is, when it changed, its public
 * address — is on the Document itself.
 *
 * A Document knows nothing about HTML. Turning its markdown into a page is the
 * renderer's job, so that indexing 157 Documents does not mean rendering 157
 * Documents.
 */
readonly class Document
{
    public function __construct(
        public string $slug,
        public ?string $key,
        public DocumentType $type,
        public string $filepath,
        public string $hash,
        public Carbon $createdAt,
        public Carbon $updatedAt,
        public Frontmatter $frontmatter,
    ) {}

    /**
     * @return array{0: ?self, 1: array<int, string>} The Document, or null and the reasons it could not be read.
     */
    public static function read(string $absolutePath, string $relativePath): array
    {
        $type = DocumentType::forPath($relativePath);

        if (! $type instanceof DocumentType) {
            return [null, ["lives outside every configured content directory"]];
        }

        $contents = (string) file_get_contents($absolutePath);
        $parsed = (new FrontMatterExtension)->getFrontMatterParser()->parse($contents)->getFrontMatter();

        if (! is_array($parsed)) {
            return [null, ['has no frontmatter']];
        }

        [$frontmatter, $errors] = Frontmatter::fromArray($parsed, $type);

        if (! $frontmatter instanceof Frontmatter) {
            return [null, $errors];
        }

        return [new self(
            slug: $frontmatter->slug ?? self::slugFromPath($relativePath),
            key: self::keyFromPath($relativePath),
            type: $type,
            filepath: $relativePath,
            hash: md5($contents),
            createdAt: $frontmatter->date,
            updatedAt: Carbon::createFromTimestamp(filemtime($absolutePath) ?: 0),
            frontmatter: $frontmatter,
        ), []];
    }

    /** The public address of this Document. */
    public function url(): string
    {
        return route('blog.show', $this->slug);
    }

    /** @see RevisionDates for why the build replaces the modification time. */
    public function withUpdatedAt(Carbon $updatedAt): self
    {
        return new self(
            $this->slug, $this->key, $this->type, $this->filepath,
            $this->hash, $this->createdAt, $updatedAt, $this->frontmatter,
        );
    }

    /**
     * A Page's address comes from where its file sits: content/about/uses.md is
     * served at about/uses.
     */
    private static function slugFromPath(string $relativePath): string
    {
        $withoutContent = trim(str_replace('content', '', $relativePath), '/');

        return trim(
            pathinfo($withoutContent, PATHINFO_DIRNAME).'/'.pathinfo($withoutContent, PATHINFO_FILENAME),
            './'
        );
    }

    /**
     * Documents are filed by number — 99.md is key '99'. The number outlives
     * every retitling, which is why published slugs end in it.
     */
    private static function keyFromPath(string $relativePath): ?string
    {
        $name = pathinfo($relativePath, PATHINFO_FILENAME);

        return ctype_digit($name) ? $name : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'key' => $this->key,
            'type' => $this->type->value,
            'filepath' => $this->filepath,
            'hash' => $this->hash,
            'createdAt' => $this->createdAt->toIso8601String(),
            'updatedAt' => $this->updatedAt->toIso8601String(),
            'frontmatter' => [
                'title' => $this->frontmatter->title,
                'slug' => $this->frontmatter->slug,
                'date' => $this->frontmatter->date->toIso8601String(),
                'excerpt' => $this->frontmatter->excerpt,
                'category' => $this->frontmatter->category,
                'tags' => $this->frontmatter->tags,
                'image' => $this->frontmatter->image,
                'author' => $this->frontmatter->author,
                'draft' => $this->frontmatter->draft,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values): self
    {
        /** @var array<string, mixed> $fm */
        $fm = $values['frontmatter'];

        return new self(
            slug: $values['slug'],
            key: $values['key'],
            type: DocumentType::from($values['type']),
            filepath: $values['filepath'],
            hash: $values['hash'],
            createdAt: Carbon::parse($values['createdAt']),
            updatedAt: Carbon::parse($values['updatedAt']),
            frontmatter: new Frontmatter(
                title: $fm['title'],
                date: Carbon::parse($fm['date']),
                excerpt: $fm['excerpt'],
                category: $fm['category'],
                slug: $fm['slug'],
                tags: $fm['tags'],
                image: $fm['image'],
                author: $fm['author'],
                draft: $fm['draft'],
            ),
        );
    }
}
