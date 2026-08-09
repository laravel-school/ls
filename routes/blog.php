<?php

use App\Http\Controllers\Blog\ImageController;
use App\Http\Controllers\Blog\IndexController;
use App\Http\Controllers\Blog\SearchController;
use App\Http\Controllers\Blog\ShowController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
 * The public site holds no per-visitor state, so it runs without sessions,
 * without CSRF, and without cookies. Nothing here writes anything.
 *
 * These URLs are promises. '/posts/{slug}' covers Posts, Snippets and Pages
 * alike, even though a Snippet now has its own feed — moving 41 Snippets to
 * '/snippets/{slug}' would break every link ever published to them.
 */
Route::withoutMiddleware([
    ShareErrorsFromSession::class,
    StartSession::class,
    VerifyCsrfToken::class,
])->group(function () {
    Route::get('posts/search', SearchController::class)->name('blog.search');

    Route::get('posts/img/{path}', ImageController::class)
        ->name('blog.image')
        ->where('path', '.*');

    Route::get('/', IndexController::class)->name('blog.index');
    Route::get('posts', IndexController::class)->name('blog.posts');

    Route::get('snippets', IndexController::class)
        ->defaults('type', 'snippet')
        ->name('blog.snippets');

    Route::get('posts/{slug}', ShowController::class)
        ->name('blog.show')
        ->where('slug', '.*');
});
