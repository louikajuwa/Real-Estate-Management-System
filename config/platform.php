<?php

// Platform bootstrap values. Kept in config (not read via env() in seeders) so they keep
// working after `php artisan config:cache`.
return [

    'super_admin' => [
        'email' => env('SUPER_ADMIN_EMAIL', 'super@platform.com'),
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
    ],

];
