<?php

return [
    'shopware' => [
        'url' => env('CUSTOMER_SYSTEMS_SHOPWARE_URL', ''),
        'backend_url' => env('CUSTOMER_SYSTEMS_SHOPWARE_BACKEND_URL', ''),
        'username' => env('CUSTOMER_SYSTEMS_SHOPWARE_USERNAME', ''),
        'api_key' => env('CUSTOMER_SYSTEMS_SHOPWARE_API_KEY', ''),
        'auth' => env('CUSTOMER_SYSTEMS_SHOPWARE_AUTH', 'digest'),
    ],
];
