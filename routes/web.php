<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');

Route::get('/test-torchlight', function () {
    return view('test', ['content' => '<pre><x-torchlight-code language="php">echo "test";</x-torchlight-code></pre>']);
});
