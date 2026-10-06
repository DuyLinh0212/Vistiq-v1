<?php

return [
    'admin_url' => env('VISTIQ_ADMIN_URL', 'http://vistiq-admin.test'),
    'learner_url' => env('VISTIQ_LEARNER_URL', 'http://vistiq.test'),
    'shared_key' => env('VISTIQ_SHARED_KEY', env('APP_KEY')),
];
