<?php

return [
    'url' => env('VISTIQ_API_URL', 'http://localhost:4205'),
    'gateway_key' => env('VISTIQ_GATEWAY_KEY'),
    'surface' => 'learner',
    'timeout' => (int) env('VISTIQ_API_TIMEOUT', 120),
];
