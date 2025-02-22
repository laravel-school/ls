<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>
        <meta name="description" content="{{ $description ?? 'Personal blog' }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Phiki styles -->
        <style>
            /* GitHub Dark theme background colors */
            .phiki {
                background-color: #0d1117;
                border-radius: 0.5rem;
            }
            .phiki pre {
                margin: 0;
                padding: 1rem;
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-white dark:bg-gray-900">
        <div class="min-h-screen">
            <!-- Navigation -->
            <nav class="border-b border-gray-200 dark:border-gray-800">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <a href="{{ route('posts.index') }}" class="flex items-center text-gray-900 dark:text-white">
                                Blog
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="py-12">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            <footer class="border-t border-gray-200 dark:border-gray-800">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <p class="text-center text-gray-500 dark:text-gray-400">
                        © {{ date('Y') }} All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    </body>
</html>
