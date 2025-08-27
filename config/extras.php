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
    'api_url' => env('EXTRAS_API_URL', ''),
    
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
        'path' => env('EXTRAS_CACHE_PATH', defined('EVO_CORE_PATH') ? EVO_CORE_PATH . 'cache/extras/' : 'cache/extras/'),
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
        'project_path' => env('EXTRAS_PROJECT_PATH', defined('EVO_CORE_PATH') ? EVO_CORE_PATH . '../' : '../'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Repositories Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки репозиториев дополнений
    |
    */
    'repositories' => [
        [
            'type' => 'github',
            'organization' => 'evolution-cms-extras',
            'name' => 'EvolutionCMS Extras'
        ],
        // Пример добавления дополнительных GitHub репозиториев
        // [
        //     'type' => 'github',
        //     'organization' => 'your-org',
        //     'name' => 'Your Repository'
        // ],
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
