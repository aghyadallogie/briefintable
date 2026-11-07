<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Markdown Parser Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may define the configuration arrays for each markdown parser.
    | You may use the base CommonMark options as well as any extensions'
    | options here. The available options are in the CommonMark docs.
    |
    | https://statamic.dev/extending/markdown#configuration
    |
    */

    'configs' => [

        'default' => [
            // User-written content is rendered on public pages, so never pass raw HTML
            // or javascript:/data: links through. See tests/Feature/BriefingsXssTest.php.
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            // 'heading_permalink' => [
            //     'symbol' => '#',
            // ],
        ],

    ],

];
