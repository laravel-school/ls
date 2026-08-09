<?php

namespace App\Content;

use Carbon\Carbon;

/**
 * Everything the author wrote in a Document's YAML block.
 *
 * Nothing in here is computed. If a value is on this object, a human typed it —
 * that is the whole distinction between Frontmatter and the Document that
 * carries it.
 *
 * The one rule this class cannot enforce alone is that a Post or Snippet must
 * declare its own slug (a derived slug would change whenever a title was
 * edited, breaking a published URL). Pages have no authored slug at all, so
 * that rule lives in DocumentType, which knows which kind of Document this is.
 */
readonly class Frontmatter
{
    /**
     * @param  array<int, string>  $tags
     */
    public function __construct(
        public string $title,
        public Carbon $date,
        public string $excerpt,
        public string $category,
        public ?string $slug = null,
        public array $tags = [],
        public ?string $image = null,
        public ?string $author = null,
        public bool $draft = false,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     * @return array{0: ?self, 1: array<int, string>} The Frontmatter, or null and the reasons it could not be built.
     */
    public static function fromArray(array $values, DocumentType $type): array
    {
        $errors = self::errorsIn($values, $type);

        if ($errors !== []) {
            return [null, $errors];
        }

        return [new self(
            title: trim((string) $values['title']),
            date: self::date($values['date']),
            excerpt: trim((string) $values['excerpt']),
            category: trim((string) $values['category']),
            slug: filled($values['slug'] ?? null) ? trim((string) $values['slug']) : null,
            tags: array_values(array_map(strval(...), $values['tags'] ?? [])),
            image: filled($values['image'] ?? null) ? trim((string) $values['image']) : null,
            author: filled($values['author'] ?? null) ? trim((string) $values['author']) : null,
            draft: (bool) ($values['draft'] ?? false),
        ), []];
    }

    /**
     * A YAML date arrives as a Unix timestamp, because that is what the parser
     * makes of an unquoted ISO date. A quoted one arrives as a string.
     */
    private static function date(mixed $value): Carbon
    {
        return is_int($value) || ctype_digit((string) $value)
            ? Carbon::createFromTimestamp((int) $value)
            : Carbon::parse((string) $value);
    }

    private static function readableDate(mixed $value): bool
    {
        if (is_int($value) || ctype_digit((string) $value)) {
            return true;
        }

        return is_string($value) && strtotime($value) !== false;
    }

    /**
     * Every way this block of YAML fails to describe a Document, reported at
     * once — a build that stops at the first error makes you fix 155 files one
     * deploy at a time.
     *
     * @param  array<string, mixed>  $values
     * @return array<int, string>
     */
    private static function errorsIn(array $values, DocumentType $type): array
    {
        $errors = [];

        foreach ($type->requiredFields() as $required) {
            if (blank($values[$required] ?? null)) {
                $errors[] = "missing required field '{$required}'";
            }
        }

        if (isset($values['tags']) && ! is_array($values['tags'])) {
            $errors[] = "'tags' must be a list";
        }

        if (filled($values['slug'] ?? null) && preg_match('#[^a-z0-9_/-]#', (string) $values['slug'])) {
            $errors[] = "'slug' may only contain lowercase letters, digits, hyphens, underscores and slashes";
        }

        if (filled($values['date'] ?? null) && ! self::readableDate($values['date'])) {
            $errors[] = "'date' is not a date";
        }

        $known = ['title', 'slug', 'date', 'excerpt', 'category', 'tags', 'image', 'author', 'draft'];

        foreach (array_diff(array_keys($values), $known) as $unknown) {
            $errors[] = "unknown field '{$unknown}'";
        }

        return $errors;
    }
}
