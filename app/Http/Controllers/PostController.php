<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MarkdownPostService;

class PostController extends Controller
{
    protected $postService;

    public function __construct(MarkdownPostService $postService)
    {
        $this->postService = $postService;
    }

    public function index()
    {
        $posts = $this->postService->getAllPosts();

        return view('posts.index', compact('posts'));
    }

    public function show($slug)
    {
        $post = $this->postService->getPost($slug);
        if (!$post) {
            abort(404);
        }
        return view('posts.show', compact('post'));
    }
}
