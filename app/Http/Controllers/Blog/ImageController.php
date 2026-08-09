<?php

namespace App\Http\Controllers\Blog;

use App\Content\ImageLibrary;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageController
{
    public function __construct(private readonly ImageLibrary $images) {}

    public function __invoke(string $path): Response
    {
        $bytes = $this->images->get($path);

        if ($bytes === null) {
            throw new NotFoundHttpException;
        }

        return response($bytes, 200, [
            'Content-Type' => $this->images->contentType($path),
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
