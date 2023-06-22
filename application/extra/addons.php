<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'crontab',
            'epay',
            'log',
        ],
        'config_init' => [
            'summernote',
        ],
    ],
    'route' => [
        '/example$' => 'example/index/index',
        '/example/d/[:name]' => 'example/demo/index',
        '/example/d1/[:name]' => 'example/demo/demo1',
        '/example/d2/[:name]' => 'example/demo/demo2',
    ],
    'priority' => [],
    'domain' => '',
];
