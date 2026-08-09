<?php

namespace App\Content;

/**
 * What kind of Document this is.
 *
 * A Document's directory decides its type, and nothing else does. Category once
 * carried this meaning as well — a Snippet was anything whose Category was the
 * literal string 'Snippets' — which meant one idea had two identities that had
 * to agree, with nothing enforcing it.
 */
enum DocumentType: string
{
    case Post = 'post';
    case Snippet = 'snippet';
    case Page = 'page';

    /**
     * Which directory holds which type, longest path first so a nested
     * directory is never shadowed by its parent.
     */
    public static function forPath(string $relativePath): ?self
    {
        /** @var array<string, string> $map */
        $map = config('blog.types');

        $directories = array_keys($map);
        usort($directories, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($directories as $directory) {
            if (str_starts_with($relativePath, rtrim($directory, '/').'/')) {
                return self::from($map[$directory]);
            }
        }

        return null;
    }

    /**
     * Pages carry no authored slug — theirs comes from where the file sits, and
     * they are reached only from navigation, so no published URL depends on a
     * title staying still.
     *
     * @return array<int, string>
     */
    public function requiredFields(): array
    {
        $fields = ['title', 'date', 'excerpt', 'category'];

        return $this === self::Page ? $fields : [...$fields, 'slug'];
    }

    /** Whether Documents of this type appear in the main chronological feed. */
    public function isFeedable(): bool
    {
        return $this !== self::Page;
    }
}
