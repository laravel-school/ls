<?php

return [
    // The Torchlight client caches highlighted code blocks. Here
    // you can define which cache driver you'd like to use. If
    // leave this blank your default app cache will be used.
    'cache' => env('TORCHLIGHT_CACHE_DRIVER'),

    // Cache blocks for 30 days. Set null to store permanently
    'cache_seconds' => env('TORCHLIGHT_CACHE_TTL', 60 * 60 * 24 * 30),

    // Which theme you want to use. You can find all of the themes at
    // https://torchlight.dev/docs/themes.
    'theme' => env('TORCHLIGHT_THEME', 'github-dark'),

    // If you want to use two separate themes for dark and light modes,
    // you can use an array to define both themes. Torchlight renders
    // both on the page, and you will be responsible for hiding one
    // or the other depending on the dark / light mode via CSS.
    // 'theme' => [
    //     'dark' => 'github-dark',
    //     'light' => 'github-light',
    // ],

    // Your API token from torchlight.dev.
    'token' => env('TORCHLIGHT_TOKEN', 'torch_TCKOrchjdBmstRTB1URFgCYZZK4jPXGZIskmMrfx'),

    // If you want to register the blade directives, set this to true.
    'blade_components' => true,

    // The Host of the API.
    'host' => env('TORCHLIGHT_HOST', 'https://api.torchlight.dev'),

    // We replace tabs in your code blocks with spaces in HTML. Set
    // the number of spaces you'd like to use per tab. Set to
    // `false` to leave literal tabs in the HTML.
    'tab_width' => 4,

    // If you pass a filename to the code component or in a markdown
    // block, Torchlight will look for code snippets in the
    // following directories.
    'snippet_directories' => [
        resource_path()
    ],

    // Global options to control blocks-level settings.
    // https://torchlight.dev/docs/options
    'options' => [
        'lineNumbers' => true,
        'diffIndicators' => true,
        'diffIndicatorsInPlaceOfLineNumbers' => true,
    ]
];
