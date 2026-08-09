<?php

namespace App\Http\Controllers\Blog;

use App\Content\DocumentIndex;
use App\Content\LinkedData;
use App\Content\MarkdownRenderer;
use App\Content\Navigation;
use App\Content\TableOfContents;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowController
{
    public function __construct(
        private readonly DocumentIndex $index,
        private readonly MarkdownRenderer $renderer,
        private readonly TableOfContents $toc,
        private readonly Navigation $navigation,
        private readonly LinkedData $linkedData,
    ) {}

    public function __invoke(string $slug): View
    {
        $document = $this->index->find($slug);

        if (! $document || $document->frontmatter->draft) {
            throw new NotFoundHttpException;
        }

        $body = $this->renderer->render($document);

        return view('blog.show', [
            'document' => $document,
            'body' => $body,
            'headings' => $this->toc->from($body),
            'nav' => $this->navigation->sections(),
            'linkedData' => json_encode($this->linkedData->for($document), JSON_UNESCAPED_SLASHES),
        ]);
    }
}
