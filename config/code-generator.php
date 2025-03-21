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
        'models' => app_path('Models'),
        'controllers' => app_path('Http/Controllers'),
        'livewire' => app_path('Livewire'),
        'migrations' => database_path('migrations'),
        'resources' => app_path('Http/Resources'),
        'tests' => base_path('tests'),
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
]; 