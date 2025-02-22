@extends('app')

@section('content')
    <article class="prose prose-lg dark:prose-invert max-w-none">
        <header class="not-prose mb-16">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ $post['title'] }}
            </h1>
            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                <time datetime="{{ $post['date'] }}">{{ $post['date'] }}</time>
                <span>·</span>
                <span>{{ $post['reading_time'] }} min read</span>
                @if($post['author'])
                    <span>·</span>
                    <span>{{ $post['author'] }}</span>
                @endif
            </div>
        </header>

        <div class="markdown-content">
            {!! $post['content'] !!}
        </div>

        <footer class="not-prose mt-16 pt-8 border-t border-gray-200 dark:border-gray-800">
            <a href="{{ route('posts.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                ← Back to all posts
            </a>
        </footer>
    </article>
@endsection
