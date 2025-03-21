<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Namespace
    |--------------------------------------------------------------------------
    |
    | The default namespace for all generated classes.
    |
    */
    'namespace' => 'App',

    /*
    |--------------------------------------------------------------------------
    | Path Customization
    |--------------------------------------------------------------------------
    |
    | Customize where generated files should be placed.
    |
    */
    'paths' => [
        'models' => base_path('app/Models'),
        'controllers' => base_path('app/Http/Controllers'),
        'livewire' => base_path('app/Livewire'),
        'migrations' => base_path('database/migrations'),
        'resources' => base_path('app/Http/Resources'),
        'tests' => base_path('tests'),
        'factories' => base_path('database/factories'),
        'seeders' => base_path('database/seeders'),
        'requests' => base_path('app/Http/Requests'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespace Customization
    |--------------------------------------------------------------------------
    |
    | Customize namespaces for specific types of generated files.
    | This is helpful when the namespaces differ from the standard pattern.
    |
    */
    'namespaces' => [
        'model' => 'App\\Models',
        'controller' => 'App\\Http\\Controllers',
        'resource' => 'App\\Http\\Resources',
        'factory' => 'Database\\Factories',
        'seeder' => 'Database\\Seeders',              // Added for SeederGenerator
        'request' => 'App\\Http\\Requests',           // Added for RequestGenerator
    ],

    /*
    |--------------------------------------------------------------------------
    | Stubs Customization
    |--------------------------------------------------------------------------
    |
    | Customize the stubs that are used to generate code.
    |
    */
    'stubs' => [
        'use_custom' => false,
        'custom_path' => resource_path('stubs/vendor/code-generator'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Options
    |--------------------------------------------------------------------------
    |
    | Default options for model generation.
    |
    */
    'models' => [
        'timestamps' => true,
        'soft_deletes' => false,
        'fillable' => true,
        'casts' => true,
        'relationships' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire Options
    |--------------------------------------------------------------------------
    |
    | Default options for Livewire component generation.
    |
    */
    'livewire' => [
        'version' => 3,
        'include_tests' => true,
        'include_views' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Options
    |--------------------------------------------------------------------------
    |
    | Default options for API-related generation.
    |
    */
    'api' => [
        'version' => 'v1',
        'resources' => true,
        'documentation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Options
    |--------------------------------------------------------------------------
    |
    | Configure how routes should be generated.
    |
    */
    'routes' => [
        'enabled' => true,
        'file' => 'routes/web.php',
        'api_file' => 'routes/api.php',
        'prefix' => false,
        'middleware' => ['web'],
        'api_middleware' => ['api'],
        'api_version_prefix' => 'v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Options
    |--------------------------------------------------------------------------
    |
    | Configure how seeders should be generated.
    |
    */
    'seeders' => [
        'update_database_seeder' => true,  // Added for SeederGenerator
    ],
];
