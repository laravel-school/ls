@extends('app')

@section('content')
    <div class="space-y-16">
        <header>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white">Blog Posts</h1>
        </header>

        <div class="space-y-16">
            @forelse($posts as $post)
                <article class="space-y-4">
                    <header>
                        <h2 class="text-2xl font-bold">
                            <a href="{{ route('posts.show', $post['slug']) }}"
                               class="text-gray-900 dark:text-white hover:text-gray-600 dark:hover:text-gray-300">
                                {{ $post['title'] }}
                            </a>
                        </h2>
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

                    @if($post['description'])
                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $post['description'] }}
                        </p>
                    @endif

                    @if(!empty($post['tags']))
                        <div class="flex flex-wrap gap-2">
                            @foreach($post['tags'] as $tag)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <p class="text-gray-600 dark:text-gray-400">No posts found.</p>
            @endforelse
        </div>
    </div>
@endsection
