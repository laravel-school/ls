<?php

namespace App\Providers;

use App\Content\DocumentIndex;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One Index per request: reading the manifest twice on the same page
        // would be pure waste, and every reader wants the same answer.
        $this->app->singleton(DocumentIndex::class);
    }

    public function boot(): void
    {
        // Lets the blog's views address each other as <x-blog::template>.
        Blade::anonymousComponentNamespace('blog.components', 'blog');
    }
}
