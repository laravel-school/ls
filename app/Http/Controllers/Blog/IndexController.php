<?php

namespace App\Http\Controllers\Blog;

use App\Content\DocumentIndex;
use App\Content\DocumentType;
use App\Content\Navigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndexController
{
    public function __construct(
        private readonly DocumentIndex $index,
        private readonly Navigation $navigation,
    ) {}

    public function __invoke(Request $request, ?string $type = null): View
    {
        $documents = $this->index->feed(
            type: $type ? DocumentType::from($type) : null,
            category: $request->string('category')->toString() ?: null,
            tag: $request->string('tag')->toString() ?: null,
        );

        $paginator = $this->index->paginate(
            $documents,
            perPage: (int) config('blog.per_page'),
            page: max(1, (int) $request->integer('page', 1)),
        )->withQueryString();

        return view('blog.index', [
            'nav' => $this->navigation->sections(),
            'documents' => collect($paginator->items()),
            'paginator' => $paginator,
            'currentCategory' => $request->query('category'),
            'currentTag' => $request->query('tag'),
        ]);
    }
}
