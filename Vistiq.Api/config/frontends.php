<?php

return [
    'default_surface' => env('VISTIQ_SURFACE', 'learner'),
    'gateway_key' => env('VISTIQ_GATEWAY_KEY'),
    'learner' => [
        'path' => env('VISTIQ_LEARNER_PATH', base_path('../Vistiq-user')),
        'url' => env('VISTIQ_LEARNER_URL', 'http://localhost:4203'),
    ],
    'admin' => [
        'path' => env('VISTIQ_ADMIN_PATH', base_path('../Vistiq-admin')),
        'url' => env('VISTIQ_ADMIN_URL', 'http://localhost:4204'),
    ],
];
