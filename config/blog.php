<?php

use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;

return [

    /*
    |--------------------------------------------------------------------------
    | Content
    |--------------------------------------------------------------------------
    |
    | Where the Documents live, and which directory means which kind of
    | Document. The directory is the only thing that decides a Document's type —
    | its Category names a topic and nothing else.
    |
    */

    'path' => base_path('prezet'),

    'types' => [
        'content/posts' => 'post',
        'content/snippets' => 'snippet',
        'content/about' => 'page',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feeds
    |--------------------------------------------------------------------------
    */

    'per_page' => 4,

    /*
    |--------------------------------------------------------------------------
    | CommonMark
    |--------------------------------------------------------------------------
    |
    | Heading permalinks use the 'content' prefix, which every published
    | heading anchor on the site already depends on. Changing it would silently
    | break every deep link anyone has ever shared.
    |
    | NOTE: 'internal_hosts' is deliberately left at the inherited value for
    | now. It is wrong — it makes the site treat its OWN links as external —
    | but correcting it changes rendered HTML, so it is a separate change from
    | proving this renderer reproduces the old one. See docs/adr.
    |
    */

    'commonmark' => [

        'extensions' => [
            CommonMarkCoreExtension::class,
            HeadingPermalinkExtension::class,
            ExternalLinkExtension::class,
            FrontMatterExtension::class,
        ],

        'config' => [
            'heading_permalink' => [
                'html_class' => 'prezet-heading',
                'id_prefix' => 'content',
                'apply_id_to_heading' => false,
                'heading_class' => '',
                'fragment_prefix' => 'content',
                'insert' => 'before',
                'min_heading_level' => 2,
                'max_heading_level' => 3,
                'title' => 'Permalink',
                'symbol' => '#',
                'aria_hidden' => false,
            ],
            'external_link' => [
                'internal_hosts' => 'www.example.com',
                'open_in_new_window' => true,
                'html_class' => 'external-link',
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    |
    | Widths offered in the srcset of every local image, the sizes attribute
    | that goes with them, and whether images are click-to-zoom.
    |
    */

    'image' => [
        'widths' => [480, 640, 768, 960, 1536],
        'sizes' => '92vw, (max-width: 1024px) 92vw, 768px',
        'zoomable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Social cards
    |--------------------------------------------------------------------------
    |
    | Cards are drawn with GD at build time for Documents that carry no image of
    | their own. GD needs a real font file on disk — a webfont URL is no use to
    | it. Without one, card generation is skipped and those Documents simply
    | have no og:image, exactly as before.
    |
    */

    'og' => [
        'font' => base_path('resources/fonts/Inter-SemiBold.ttf'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Structured data
    |--------------------------------------------------------------------------
    */

    'authors' => [
        'default' => [
            '@type' => 'Person',
            'name' => 'Thouhedul Islam Suchi',
            'url' => 'https://tisuchi.com',
            'image' => 'https://unavatar.io/github/tisuchi',
        ],
    ],

    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Laravel School',
        'url' => 'https://laravel-school.com',
        'logo' => 'https://laravel-school.com/favicon.svg',
        'image' => 'https://laravel-school.com/ogimage.png',
    ],
];
