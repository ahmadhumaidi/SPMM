<?php

return [
    'payment' => [
        'provider' => env('PAYMENT_PROVIDER', 'mock'),
        'invoice_expiry_hours' => (int) env('PAYMENT_INVOICE_EXPIRY_HOURS', 24),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'log'),
        'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '62'),
    ],
];
