<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки API для получения данных о дополнениях
    |
    */
    'api_url' => env('EXTRAS_API_URL', 'https://extras.evolutioncms.com/api'),
    
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки кэширования данных дополнений
    |
    */
    'cache' => [
        'enabled' => env('EXTRAS_CACHE_ENABLED', true),
        'ttl' => env('EXTRAS_CACHE_TTL', 3600),
        'path' => env('EXTRAS_CACHE_PATH', EVO_CORE_PATH . 'cache/extras/'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Composer Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки для работы с Composer
    |
    */
    'composer' => [
        'timeout' => env('EXTRAS_COMPOSER_TIMEOUT', 300),
        'project_path' => env('EXTRAS_PROJECT_PATH', EVO_CORE_PATH . '../'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Commands Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки для консольных команд
    |
    */
    'commands' => [
        'list' => [
            'default_format' => env('EXTRAS_LIST_FORMAT', 'table'),
            'items_per_page' => env('EXTRAS_ITEMS_PER_PAGE', 20),
        ],
        'install' => [
            'confirm_by_default' => env('EXTRAS_INSTALL_CONFIRM', false),
        ],
        'remove' => [
            'confirm_by_default' => env('EXTRAS_REMOVE_CONFIRM', true),
        ],
    ],
];
