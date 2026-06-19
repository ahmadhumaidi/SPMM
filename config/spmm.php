<?php

return [
    'public_url' => env('SPMM_PUBLIC_URL', 'https://kampusmedia.cloud'),

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

    'meta_leads' => [
        'verify_token' => env('META_LEADS_VERIFY_TOKEN'),
        'page_access_token' => env('META_LEADS_PAGE_ACCESS_TOKEN'),
        'graph_version' => env('META_GRAPH_VERSION', 'v20.0'),
        'default_campus_id' => env('META_LEADS_DEFAULT_CAMPUS_ID'),
        'default_study_program_id' => env('META_LEADS_DEFAULT_STUDY_PROGRAM_ID'),
        'default_class_track_id' => env('META_LEADS_DEFAULT_CLASS_TRACK_ID'),
    ],
];
