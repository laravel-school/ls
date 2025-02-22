@extends('app')

@section('content')
    <div class="max-w-4xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-8">Blog Posts</h1>
        @forelse($posts as $post)
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-2">
                    <a href="{{ route('posts.show', $post['slug']) }}"
                       class="hover:text-gray-600 dark:hover:text-gray-300">
                        {{ $post['title'] ?? 'Untitled Post' }}
                    </a>
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if($post['author'] || $post['date'])
                        By {{ $post['author'] ?? 'Anonymous' }}
                        @if($post['date'])
                            on {{ $post['date'] }}
                        @endif
                    @endif
                </p>
            </div>
            @if(!$loop->last)
                <hr class="my-6 border-gray-200 dark:border-gray-700">
            @endif
        @empty
            <p class="text-gray-600 dark:text-gray-400">No blog posts found.</p>
        @endforelse
    </div>
@endsection
