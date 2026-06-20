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
        'generate_cover_image' => (bool) env('AI_NEWS_GENERATE_COVER_IMAGE', true),
        'openai_image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-1'),
        'openai_image_size' => env('OPENAI_IMAGE_SIZE', '1536x1024'),
        'sources' => [
            'Pendidikan Tinggi Indonesia' => 'https://news.google.com/rss/search?q=pendidikan+tinggi+Indonesia&hl=id&gl=ID&ceid=ID:id',
            'Kampus dan Kuliah Karyawan' => 'https://news.google.com/rss/search?q=kampus+kuliah+karyawan+Indonesia&hl=id&gl=ID&ceid=ID:id',
            'Prospek Jurusan dan Karier' => 'https://news.google.com/rss/search?q=prospek+kerja+jurusan+kuliah+Indonesia&hl=id&gl=ID&ceid=ID:id',
            'Kelas Karyawan dan RPL' => 'https://news.google.com/rss/search?q=kelas+karyawan+RPL+perguruan+tinggi&hl=id&gl=ID&ceid=ID:id',
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
            'jurusan kuliah',
            'prospek kerja',
            'kelas karyawan',
            'kuliah sambil kerja',
            'kampus swasta',
            'biaya kuliah',
            'program RPL',
        ],
        'editorial_knowledge' => [
            'brand' => [
                'name' => 'Kampus Media',
                'positioning' => 'portal informasi PMB yang membantu calon mahasiswa menemukan kampus, program studi, biaya kuliah, kelas fleksibel, dan jalur RPL secara lebih mudah.',
                'audience' => [
                    'lulusan SMA/SMK/MA yang ingin kuliah',
                    'karyawan yang ingin kuliah sambil kerja',
                    'lulusan D3 yang ingin lanjut S1',
                    'calon mahasiswa pindahan',
                    'profesional yang ingin pengakuan pengalaman kerja melalui RPL',
                    'orang tua yang sedang membandingkan biaya dan kualitas kampus',
                ],
            ],
            'content_pillars' => [
                'kampus' => 'cara memilih kampus terpercaya, terdaftar PDDIKTI, terakreditasi BAN-PT/LAM, dan memiliki layanan PMB yang jelas.',
                'jurusan' => 'panduan memilih jurusan berdasarkan minat, kemampuan, prospek kerja, kebutuhan industri, dan peluang karier.',
                'prospek' => 'hubungan jurusan dengan peluang kerja, peningkatan karier, sertifikasi, portofolio, dan penghasilan masa depan.',
                'kuliah_karyawan' => 'kuliah untuk pekerja dengan jadwal malam, akhir pekan, online, atau hybrid agar tetap bisa bekerja.',
                'kelas_karyawan' => 'kelas fleksibel untuk karyawan, staf, entrepreneur, dan profesional yang membutuhkan jadwal kuliah adaptif.',
                'rpl' => 'Rekognisi Pembelajaran Lampau sebagai jalur pengakuan pengalaman kerja, pelatihan, sertifikasi, dan pembelajaran nonformal sesuai ketentuan kampus.',
                'biaya' => 'transparansi biaya pendaftaran, herregistrasi, SPB, SPP, UKT, cicilan, dan simulasi pembayaran.',
            ],
            'study_program_angles' => [
                'Manajemen' => 'cocok untuk karier bisnis, operasional, HR, marketing, supervisor, entrepreneur, dan manajemen organisasi.',
                'Akuntansi' => 'cocok untuk karier finance, pajak, audit, administrasi keuangan, dan analis laporan keuangan.',
                'Teknik Informatika' => 'cocok untuk karier software developer, data, AI, jaringan, keamanan siber, dan produk digital.',
                'Sistem Informasi' => 'cocok untuk karier analis sistem, product owner, IT business analyst, dan transformasi digital.',
                'Ilmu Komunikasi' => 'cocok untuk karier media, public relations, content, brand communication, dan digital marketing.',
                'Hukum' => 'cocok untuk karier legal officer, compliance, advokasi, HR legal, dan administrasi publik.',
                'Psikologi' => 'cocok untuk karier HR, konseling, asesmen, pendidikan, dan pengembangan SDM.',
                'Pendidikan' => 'cocok untuk karier guru, tutor, trainer, pengembang kurikulum, dan pendidikan anak.',
                'Kesehatan' => 'cocok untuk karier layanan kesehatan, administrasi kesehatan, farmasi, keperawatan, dan gizi.',
            ],
            'seo_rules' => [
                'Gunakan bahasa Indonesia yang natural, tidak kaku, dan mudah dipahami calon mahasiswa.',
                'Judul harus mengandung intent pencarian seperti kuliah karyawan, kelas karyawan, RPL, prospek jurusan, biaya kuliah, atau kampus terpercaya bila relevan.',
                'Artikel wajib memiliki pembuka yang menjawab kebutuhan pembaca, beberapa subjudul H2, dan ajakan konsultasi/pendaftaran yang halus.',
                'Jangan melakukan keyword stuffing. Keyword boleh diulang wajar maksimal 2-4 kali untuk artikel pendek.',
                'Sisipkan konteks Kampus Media sebagai portal pembanding kampus dan PMB, bukan sebagai klaim berlebihan.',
                'Arahkan pembaca untuk mengecek akreditasi, PDDIKTI, biaya, jadwal kelas, dan prospek kerja sebelum memilih kampus.',
            ],
            'compliance_rules' => [
                'Jangan mengklaim kampus pasti diterima, pasti lulus cepat, atau pasti mendapat pekerjaan.',
                'Jangan mengarang data ranking, akreditasi, biaya, kuota beasiswa, atau kerja sama kampus jika tidak ada di sumber.',
                'Jika membahas RPL, jelaskan bahwa hasil konversi dan pengakuan SKS mengikuti asesmen dan kebijakan kampus.',
                'Jika memakai konteks Google Trends, sebut sebagai indikasi minat pencarian, bukan angka resmi.',
            ],
            'preferred_cta' => 'Konsultasikan pilihan kampus, jurusan, kelas karyawan, kuliah online, hybrid, RPL, dan simulasi biaya melalui Kampus Media.',
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
