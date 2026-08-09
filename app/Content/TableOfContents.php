<?php

namespace App\Content;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * The "on this page" outline: every h2, with the h3s that follow it.
 *
 * Anchor ids are rebuilt here rather than read out of the rendered markup, and
 * they must keep matching the ids CommonMark writes — every heading link anyone
 * has ever shared points at 'content-' plus the slugified heading text.
 */
class TableOfContents
{
    /**
     * @return array<int, array{id: string, title: string, children: array<int, array{id: string, title: string}>}>
     */
    public function from(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $headings = (new DOMXPath($dom))->query('//h2');

        if (! $headings) {
            return [];
        }

        $outline = [];

        foreach ($headings as $heading) {
            $outline[] = [
                'id' => $this->anchor($heading->textContent),
                'title' => trim($heading->textContent, '#'),
                'children' => $this->subheadings($heading),
            ];
        }

        return $outline;
    }

    /**
     * @return array<int, array{id: string, title: string}>
     */
    private function subheadings(DOMNode $heading): array
    {
        $children = [];
        $sibling = $heading->nextSibling;

        while ($sibling) {
            if ($sibling instanceof DOMElement) {
                $tag = strtolower($sibling->tagName);

                if ($tag === 'h2') {
                    break;
                }

                if ($tag === 'h3') {
                    $children[] = [
                        'id' => $this->anchor($sibling->textContent),
                        'title' => trim($sibling->textContent, '#'),
                    ];
                }
            }

            $sibling = $sibling->nextSibling;
        }

        return $children;
    }

    private function anchor(string $text): string
    {
        return 'content-'.Str::slug($text, language: null);
    }
}
