<?php

use Illuminate\Support\Facades\Route;

/*
 * Slug repairs. Both of these Slugs were published with characters that do not
 * belong in a URL — a literal space, and an en-dash instead of a hyphen. The
 * Slug is a promise (see CONTEXT.md), so the old forms redirect permanently
 * rather than 404.
 */
Route::permanentRedirect(
    'posts/handling-rounding-millisecond-issue-with-diffinseconds-in time',
    '/posts/handling-rounding-millisecond-issue-with-diffinseconds-in-time',
);

Route::permanentRedirect(
    'posts/get-5-laravel-books-for-free–download-now',
    '/posts/get-5-laravel-books-for-free-download-now',
);

/*
 * The sitemap used to be a committed file named after the package that wrote
 * it. Search engines were told about that URL, so it keeps working.
 */
Route::permanentRedirect('prezet_sitemap.xml', '/sitemap.xml');

require __DIR__.'/blog.php';
