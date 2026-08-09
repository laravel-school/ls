<?php

namespace App\Content;

use GdImage;

/**
 * Draws the social card for a Document that has no image of its own.
 *
 * Most Documents have none — for years they pointed at a default image that did
 * not exist, so every share of them showed no picture at all. A card drawn from
 * the title is plainer than a designed image and infinitely better than a blank
 * one.
 *
 * Cards are drawn at build time, never per request, so no browser and no image
 * runtime is needed in production — only GD, which is already there.
 */
class OgImage
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    /** The name a Document's card is filed under; slugs may contain slashes, filenames may not. */
    public static function filename(Document $document): string
    {
        return str_replace('/', '_', $document->slug).'.png';
    }

    /**
     * The social image for a Document: its own if it declared one, otherwise
     * the card drawn for it, otherwise nothing — never a placeholder that does
     * not exist, and never a URL that points at a page instead of an image.
     */
    public function urlFor(Document $document): ?string
    {
        if (filled($document->frontmatter->image)) {
            return url($document->frontmatter->image);
        }

        $file = self::filename($document);

        return is_file(public_path("og/{$file}")) ? url("og/{$file}") : null;
    }

    public function fontPath(): string
    {
        return (string) config('blog.og.font');
    }

    public function available(): bool
    {
        return is_file($this->fontPath());
    }

    /** PNG bytes for this Document's card, or null when no font is installed to draw it with. */
    public function render(Document $document): ?string
    {
        if (! $this->available()) {
            return null;
        }

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $ink = $this->palette($canvas);

        imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $ink['background']);
        imagefilledrectangle($canvas, 0, self::HEIGHT - 12, self::WIDTH, self::HEIGHT, $ink['accent']);

        $this->text($canvas, strtoupper($document->frontmatter->category), 22, 80, 110, $ink['accent']);

        $lines = $this->wrap($document->frontmatter->title, 54, self::WIDTH - 160);
        $y = 220;

        foreach (array_slice($lines, 0, 4) as $line) {
            $this->text($canvas, $line, 54, 80, $y, $ink['title']);
            $y += 76;
        }

        $this->text($canvas, (string) config('blog.publisher.name'), 24, 80, self::HEIGHT - 70, $ink['muted']);

        ob_start();
        imagepng($canvas);
        $bytes = ob_get_clean();
        imagedestroy($canvas);

        return $bytes === false || $bytes === '' ? null : $bytes;
    }

    /**
     * @return array<string, int>
     */
    private function palette(GdImage $canvas): array
    {
        return [
            'background' => (int) imagecolorallocate($canvas, 0xFA, 0xF8, 0xF5),
            'title' => (int) imagecolorallocate($canvas, 0x1C, 0x19, 0x17),
            'muted' => (int) imagecolorallocate($canvas, 0x78, 0x71, 0x6C),
            'accent' => (int) imagecolorallocate($canvas, 0xC2, 0x41, 0x0C),
        ];
    }

    private function text(GdImage $canvas, string $text, int $size, int $x, int $y, int $colour): void
    {
        imagettftext($canvas, $size, 0, $x, $y, $colour, $this->fontPath(), $text);
    }

    /**
     * Break a title into lines that fit, measuring the actual glyphs rather
     * than guessing from character counts.
     *
     * @return array<int, string>
     */
    private function wrap(string $title, int $size, int $maxWidth): array
    {
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/', trim($title)) ?: [] as $word) {
            $candidate = $line === '' ? $word : "{$line} {$word}";
            $box = imagettfbbox($size, 0, $this->fontPath(), $candidate);

            if ($box && ($box[2] - $box[0]) > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;

                continue;
            }

            $line = $candidate;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }
}
