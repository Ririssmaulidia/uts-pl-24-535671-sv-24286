<?php

/*
 | CORS hanya diperlukan saat Vue dijalankan terpisah lewat "npm run dev"
 | (origin berbeda dari Laravel). Di Docker Compose, Nginx pada container
 | frontend meneruskan /api ke backend sehingga origin-nya sama.
 | Origin tambahan dapat diatur lewat CORS_ALLOWED_ORIGINS (pisah dengan koma).
 */

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:8080'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
