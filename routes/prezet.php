<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BenBjurstrom\Prezet\Http\Controllers\ShowController;
use BenBjurstrom\Prezet\Http\Controllers\ImageController;
use BenBjurstrom\Prezet\Http\Controllers\IndexController;
use BenBjurstrom\Prezet\Http\Controllers\SearchController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use BenBjurstrom\Prezet\Http\Controllers\OgimageController;

Route::withoutMiddleware([
    ShareErrorsFromSession::class,
    StartSession::class,
    VerifyCsrfToken::class,
])
    ->group(function () {
        Route::get('posts/search', SearchController::class)->name('prezet.search');

        Route::get('posts/img/{path}', ImageController::class)
            ->name('prezet.image')
            ->where('path', '.*');

        Route::get('/posts/ogimage/{slug}', OgimageController::class)
            ->name('prezet.ogimage')
            ->where('slug', '.*');

        Route::get('/', IndexController::class)
            ->name('prezet.index');

        Route::get('/posts', IndexController::class)
            ->name('prezet.index');

        Route::get('posts/{slug}', ShowController::class)
            ->name('prezet.show')
            ->where('slug', '.*'); // https://laravel.com/docs/11.x/routing#parameters-encoded-forward-slashes
    });
