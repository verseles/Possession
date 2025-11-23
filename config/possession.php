<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model class to use for user lookups.
    |
    */
    'user_model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | The authentication guard to use for impersonation.
    |
    */
    'admin_guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Session Keys
    |--------------------------------------------------------------------------
    |
    | The session keys used to store impersonation state.
    |
    */
    'session_keys' => [
        'original_user' => 'possession.original_user_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the package routes. Set 'enabled' to false to disable
    | automatic route registration and define your own routes.
    |
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'possession',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    |
    | Where to redirect after possession actions.
    |
    */
    'redirect_after_possess' => '/',
    'redirect_after_unpossess' => '/',
    'forbidden_redirect' => '/',
];
