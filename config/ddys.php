<?php

return [
    'api_base_url' => function_exists('env') ? env('DDYS_API_BASE_URL', 'https://ddys.io/api/v1') : (getenv('DDYS_API_BASE_URL') ?: 'https://ddys.io/api/v1'),
    'site_base_url' => function_exists('env') ? env('DDYS_SITE_BASE_URL', 'https://ddys.io') : (getenv('DDYS_SITE_BASE_URL') ?: 'https://ddys.io'),
    'api_key' => function_exists('env') ? env('DDYS_API_KEY', '') : (getenv('DDYS_API_KEY') ?: ''),
    'timeout' => 12,
    'user_agent' => 'ddys-thinkphp-package/0.1.0',

    'route_prefix' => 'ddys',
    'route_middleware' => [],
    'enable_routes' => true,
    'enable_proxy' => true,
    'enable_request_form' => false,
    'enable_assets_route' => true,

    'cache' => [
        'enabled' => true,
        'store' => null,
        'prefix' => 'ddys:thinkphp:',
        'default_ttl' => 300,
        'dictionary_ttl' => 86400,
        'fresh_ttl' => 300,
        'list_ttl' => 600,
        'detail_ttl' => 1800,
        'community_ttl' => 120,
    ],

    'display' => [
        'theme' => 'auto',
        'layout' => 'grid',
        'columns' => 4,
        'target' => '_blank',
        'show_source_link' => true,
        'show_nav' => true,
        'load_assets' => true,
        'view_path' => '',
        'asset_url' => '',
    ],

    'request_form' => [
        'nonce_secret' => function_exists('env') ? env('DDYS_NONCE_SECRET', '') : (getenv('DDYS_NONCE_SECRET') ?: ''),
        'rate_limit_seconds' => 60,
        'honeypot_field' => 'ddys_website',
    ],

    'security' => [
        'proxy_allow_routes' => [
            'movies', 'latest', 'hot', 'search', 'suggest', 'calendar',
            'movie', 'sources', 'related', 'comments',
            'collections', 'collection', 'shares', 'share',
            'requests', 'activities', 'user', 'types', 'genres', 'regions',
        ],
        'allowed_targets' => ['_blank', '_self'],
        'max_per_page' => 50,
        'max_limit' => 50,
    ],
];
