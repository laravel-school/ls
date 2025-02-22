<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\MarkdownConverter;
use Phiki\CommonMark\PhikiExtension;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class MarkdownPostService
{
    protected $converter;

    public function __construct()
    {
        // Configure CommonMark environment
        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new AttributesExtension());

        // Add Phiki extension with GitHub Dark theme
        $environment->addExtension(new PhikiExtension(
            'github-dark',
            withWrapper: true
        ));

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Get all Markdown posts from storage.
     */
    public function getAllPosts()
    {
        $directory = storage_path('app/posts');

        if (!File::exists($directory)) {
            return collect([]);
        }

        $files = File::files($directory);
        $posts = collect($files)
            ->filter(fn($file) => $file->getExtension() === 'md')
            ->map(fn($file) => $this->getPost($file->getFilenameWithoutExtension()))
            ->filter()
            ->sortByDesc('date');

        return $posts;
    }

    /**
     * Get a single Markdown post by slug.
     */
    public function getPost($slug)
    {
        $filePath = storage_path("app/posts/{$slug}.md");

        if (!File::exists($filePath)) {
            return null;
        }

        $content = File::get($filePath);

        // Parse front matter
        $document = YamlFrontMatter::parse($content);
        $matter = $document->matter();

        // Convert markdown to HTML
        $html = $this->converter->convert($document->body())->getContent();

        if (empty($matter)) {
            return null;
        }

        return [
            'title' => $matter['title'] ?? null,
            'date' => isset($matter['date']) ? date('F j, Y', strtotime($matter['date'])) : null,
            'author' => $matter['author'] ?? null,
            'description' => $matter['description'] ?? null,
            'tags' => $matter['tags'] ?? [],
            'slug' => $slug,
            'content' => $html,
            'reading_time' => $this->calculateReadingTime($html),
        ];
    }

    private function calculateReadingTime($content)
    {
        $words = str_word_count(strip_tags($content));
        $minutes = ceil($words / 200); // Average reading speed
        return $minutes;
    }
}
