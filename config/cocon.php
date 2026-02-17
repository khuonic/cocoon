<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed Users
    |--------------------------------------------------------------------------
    |
    | Only users with these email addresses can access the application.
    | Used by the RestrictToHousehold middleware and setup screen.
    |
    */

    'allowed_users' => [
        ['name' => 'Kevin', 'email' => 'kevininc155@gmail.com'],
        ['name' => 'Lola', 'email' => 'lolavivant@hotmail.fr'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync API URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the remote server API used for data synchronization.
    | Set this in .env via SYNC_API_URL.
    |
    */

    'sync_api_url' => env('SYNC_API_URL', ''),

];
