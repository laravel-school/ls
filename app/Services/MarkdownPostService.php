<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Parsedown;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class MarkdownPostService
{
    protected $parser;

    public function __construct()
    {
        $this->parser = new Parsedown();
        $this->parser->setSafeMode(true);
    }

    /**
     * Get all Markdown posts from storage.
     */
    public function getAllPosts()
    {
        $directory = storage_path('app/posts');

        if (!File::exists($directory)) {
            return []; // Return empty array if directory doesn't exist
        }

        $files = File::files($directory);
        $posts = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'md') {
                $post = $this->getPost($file->getFilenameWithoutExtension());
                if ($post) {
                    $posts[] = $post;
                }
            }
        }

        return collect($posts)->sortByDesc('date');
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
        $document = YamlFrontMatter::parse($content);

        return [
            'title' => $document->matter('title'),
            'date' => date('F j, Y', strtotime($document->matter('date'))),
            'author' => $document->matter('author'),
            'slug' => $slug,
            'content' => $this->parser->text($document->body()) // Convert Markdown to HTML
        ];
    }
}
