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

    'ai_news' => [
        'openai_api_key' => env('OPENAI_API_KEY'),
        'openai_model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'sources' => [
            'Pendidikan Tinggi Indonesia' => 'https://news.google.com/rss/search?q=pendidikan+tinggi+Indonesia&hl=id&gl=ID&ceid=ID:id',
            'Kampus dan Kuliah Karyawan' => 'https://news.google.com/rss/search?q=kampus+kuliah+karyawan+Indonesia&hl=id&gl=ID&ceid=ID:id',
            'PDDIKTI dan Perguruan Tinggi' => 'https://news.google.com/rss/search?q=PDDIKTI+perguruan+tinggi&hl=id&gl=ID&ceid=ID:id',
        ],
        'trend_sources' => [
            'Google Trends Indonesia' => 'https://trends.google.com/trending/rss?geo=ID',
        ],
        'trend_keywords' => [
            'kuliah online',
            'kuliah karyawan',
            'kelas hybrid',
            'RPL',
            'beasiswa',
            'karier',
            'sertifikasi',
            'kampus terakreditasi',
            'PDDIKTI',
        ],
    ],
];
