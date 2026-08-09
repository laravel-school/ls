<?php

namespace App\Content;

use GdImage;

/**
 * Serves the images that live beside the Documents.
 *
 * Images sit outside the public directory so that adding one is the same act as
 * adding a Document — drop the file in, commit, push. A width suffix on the
 * requested name ('diagram-960w.png') asks for that width; the resize happens
 * on demand and is cached by the far-future header the controller sets.
 */
class ImageLibrary
{
    private const SERVABLE = ['png', 'jpg', 'jpeg', 'webp'];

    public function __construct(private readonly DocumentReader $reader) {}

    public function contentType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'image/webp',
        };
    }

    /** Returns encoded image bytes, or null when there is nothing to serve. */
    public function get(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, self::SERVABLE, true)) {
            return null;
        }

        $width = $this->requestedWidth($path);
        $file = $this->reader->root().'/images/'.$this->withoutWidth($path);

        if (! is_file($file) || str_contains($path, '..')) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($file));

        if (! $image instanceof GdImage) {
            return null;
        }

        if ($width !== null) {
            $image = $this->resize($image, $width);
        }

        return $this->encode($image, $extension);
    }

    /** Only widths the site actually offers are honoured, so the route cannot be used to render arbitrary sizes. */
    private function requestedWidth(string $path): ?int
    {
        /** @var array<int, int> $widths */
        $widths = config('blog.image.widths');

        if (preg_match('/-(\d+)w\.(?:png|jpg|jpeg|webp)$/i', $path, $match) && in_array((int) $match[1], $widths, true)) {
            return (int) $match[1];
        }

        return null;
    }

    private function withoutWidth(string $path): string
    {
        return (string) preg_replace('/(.+)-\d+w\.(\w+)$/', '$1.$2', $path);
    }

    private function resize(GdImage $image, int $width): GdImage
    {
        $originalWidth = imagesx($image);
        $height = (int) (imagesy($image) * ($width / $originalWidth));

        $resized = imagecreatetruecolor($width, $height);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $originalWidth, imagesy($image));
        imagedestroy($image);

        return $resized;
    }

    private function encode(GdImage $image, string $extension): ?string
    {
        ob_start();

        match ($extension) {
            'png' => imagepng($image),
            'jpg', 'jpeg' => imagejpeg($image),
            default => imagewebp($image),
        };

        $bytes = ob_get_clean();
        imagedestroy($image);

        return $bytes === false || $bytes === '' ? null : $bytes;
    }
}
