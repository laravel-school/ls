@php
    /* @var array $nav */
    /* @var array|null|string $currentTag */
    /* @var array|null|string $currentCategory */
    /* @var \Illuminate\Support\Collection<int,\App\Content\Document> $documents */
@endphp

<x-blog::template
    title="Laravel School | Thouhedul Islam Suchi | Tisuchi | Learn Laravel, PHP, Vue.js"
    description="Thouhedul Islam Suchi is the main author of Laravel School | Get started with Laravel, Vue.js, and modern web technologies. Laravel School offers developer tutorials that cover all the essential topics."
    {{-- Both '/' and '/posts' serve this feed, so one of them has to be the
         canonical one. It has always been '/posts' — not by choice, but because
         the old routes shared a name and the later registration won. Preserved
         deliberately: changing which URL search engines consolidate on is a
         decision to take on its own, not a side effect of replacing a renderer. --}}
    :url="route('blog.posts')"
>
    <x-slot name="left">
        <x-blog::sidebar :nav="$nav" />
    </x-slot>
    <section>
        <div class="divide-y divide-gray-200">
            <div class="space-y-2 pb-8 md:space-y-5">
                <h1
                    class="font-display text-4xl font-bold tracking-tight text-gray-900"
                >
                    Laravel School
                </h1>

                <div class="justify-between sm:flex">
                    <p class="text-lg leading-7 text-gray-500">
                        A programmer's journal who loves to write about Laravel, PHP, and other web languages.
                    </p>
                    <div class="mt-4 block sm:mt-0">
                        @if ($currentTag)
                            <span
                                class="inline-flex items-center gap-x-0.5 rounded-md bg-gray-50 px-2.5 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10"
                            >
                                {{ \Illuminate\Support\Str::title($currentTag) }}
                                <a
                                    href="{{ route('blog.index', array_filter(request()->except('tag'))) }}"
                                    class="group relative -mr-1 h-3.5 w-3.5 rounded-xs hover:bg-gray-500/20"
                                >
                                    <span class="sr-only">Remove</span>
                                    <svg
                                        viewBox="0 0 14 14"
                                        class="h-3.5 w-3.5 stroke-gray-600/50 group-hover:stroke-gray-600/75"
                                    >
                                        <path d="M4 4l6 6m0-6l-6 6" />
                                    </svg>
                                    <span class="absolute -inset-1"></span>
                                </a>
                            </span>
                        @endif

                        @if ($currentCategory)
                            <span
                                class="inline-flex items-center gap-x-0.5 rounded-md bg-gray-50 px-2.5 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10"
                            >
                                {{ $currentCategory }}
                                <a
                                    href="{{ route('blog.index', array_filter(request()->except('category'))) }}"
                                    class="group relative -mr-1 h-3.5 w-3.5 rounded-xs hover:bg-gray-500/20"
                                >
                                    <span class="sr-only">Remove</span>
                                    <svg
                                        viewBox="0 0 14 14"
                                        class="h-3.5 w-3.5 stroke-gray-600/50 group-hover:stroke-gray-600/75"
                                    >
                                        <path d="M4 4l6 6m0-6l-6 6" />
                                    </svg>
                                    <span class="absolute -inset-1"></span>
                                </a>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <ul class="divide-y divide-gray-200">
                @foreach ($documents as $document)
                    <li class="py-12">
                        <x-blog::article :document="$document" />
                    </li>
                @endforeach
            </ul>
            <div class="pt-12">
                {{ $paginator->links() }}
            </div>
        </div>
    </section>
</x-blog::template>
