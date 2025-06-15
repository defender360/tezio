<?php

return [
    'domain' => env('AUTH0_DOMAIN'),
    'client_id' => env('AUTH0_CLIENT_ID'),
    'client_secret' => env('AUTH0_CLIENT_SECRET'),
    'audience' => env('AUTH0_AUDIENCE', 'https://api.itsm-platform.com'),
    'scope' => env('AUTH0_SCOPE', 'openid profile email'),
    'cookie_secret' => env('AUTH0_COOKIE_SECRET', env('APP_KEY')),
    'redirect_uri' => env('AUTH0_REDIRECT_URI', env('APP_URL') . '/auth/callback'),
    
    // Custom claims for multi-tenancy
    'custom_claims' => [
        'tenant_id' => 'https://itsm-platform.com/tenant_id',
        'roles' => 'https://itsm-platform.com/roles',
    ],
];