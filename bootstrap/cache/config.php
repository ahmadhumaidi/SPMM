<?php return array (
  'view' => 
  array (
    'paths' => 
    array (
      0 => '/var/www/spmm/resources/views',
    ),
    'compiled' => '/var/www/spmm/storage/framework/views',
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => 12,
      'verify' => true,
      'limit' => NULL,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'token' => NULL,
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'ses' => 
    array (
      'key' => NULL,
      'secret' => NULL,
      'region' => 'us-east-1',
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
  ),
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'broadcasting' => 
  array (
    'default' => 'null',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'cluster' => NULL,
          'host' => 'api-mt1.pusher.com',
          'port' => 443,
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => 'null',
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => '/var/www/spmm/storage/logs/laravel.log',
        'level' => 'warning',
        'replace_placeholders' => true,
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => '/var/www/spmm/storage/logs/laravel.log',
        'level' => 'warning',
        'days' => 14,
        'replace_placeholders' => true,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'warning',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'warning',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'warning',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'handler_with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'formatter' => NULL,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'warning',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'warning',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => '/var/www/spmm/storage/logs/laravel.log',
      ),
    ),
  ),
  'app' => 
  array (
    'name' => 'Sistem Pusat Mahera Media',
    'env' => 'production',
    'debug' => false,
    'url' => 'https://kampus.media',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'Asia/Jakarta',
    'locale' => 'id',
    'fallback_locale' => 'id',
    'faker_locale' => 'id_ID',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:8wjVuEK+I9m4fEaam+/dQWV/mXunLg77hl5nc8fQR8Y=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
      6 => 'Illuminate\\Cookie\\CookieServiceProvider',
      7 => 'Illuminate\\Database\\DatabaseServiceProvider',
      8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      11 => 'Illuminate\\Hashing\\HashServiceProvider',
      12 => 'Illuminate\\Mail\\MailServiceProvider',
      13 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      14 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      15 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      16 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      17 => 'Illuminate\\Queue\\QueueServiceProvider',
      18 => 'Illuminate\\Redis\\RedisServiceProvider',
      19 => 'Illuminate\\Session\\SessionServiceProvider',
      20 => 'Illuminate\\Translation\\TranslationServiceProvider',
      21 => 'Illuminate\\Validation\\ValidationServiceProvider',
      22 => 'Illuminate\\View\\ViewServiceProvider',
      23 => 'App\\Providers\\AppServiceProvider',
      24 => 'App\\Providers\\Filament\\AdminPanelProvider',
      25 => 'App\\Providers\\Filament\\SiakadPanelProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Benchmark' => 'Illuminate\\Support\\Benchmark',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Context' => 'Illuminate\\Support\\Facades\\Context',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Number' => 'Illuminate\\Support\\Number',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'Uri' => 'Illuminate\\Support\\Uri',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'Vite' => 'Illuminate\\Support\\Facades\\Vite',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => NULL,
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'cache' => 
  array (
    'default' => 'database',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'session' => 
      array (
        'driver' => 'session',
        'key' => '_cache',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'cache',
        'connection' => NULL,
        'lock_connection' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => '/var/www/spmm/storage/framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => NULL,
        'secret' => NULL,
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'stores' => 
        array (
          0 => 'database',
          1 => 'array',
        ),
      ),
    ),
    'prefix' => 'spmm_cache_',
  ),
  'database' => 
  array (
    'default' => 'pgsql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'spmm',
        'prefix' => '',
        'foreign_key_constraints' => true,
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5432',
        'database' => 'spmm',
        'username' => 'spmm_user',
        'password' => '@K4g4klup4170845!',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => NULL,
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5432',
        'database' => 'spmm',
        'username' => 'spmm_user',
        'password' => '@K4g4klup4170845!',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5432',
        'database' => 'spmm',
        'username' => 'spmm_user',
        'password' => '@K4g4klup4170845!',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '5432',
        'database' => 'spmm',
        'username' => 'spmm_user',
        'password' => '@K4g4klup4170845!',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'laravel_database_',
        'persistent' => false,
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'public',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => '/var/www/spmm/storage/app/private',
        'serve' => true,
        'throw' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => '/var/www/spmm/storage/app/public',
        'url' => '/storage',
        'visibility' => 'public',
        'throw' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => NULL,
        'secret' => NULL,
        'region' => NULL,
        'bucket' => NULL,
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
      ),
    ),
    'links' => 
    array (
      '/var/www/spmm/public/storage' => '/var/www/spmm/storage/app/public',
    ),
  ),
  'mail' => 
  array (
    'default' => 'smtp',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'host' => 'smtp.hostinger.com',
        'port' => '465',
        'encryption' => 'ssl',
        'username' => 'info@kampus.media',
        'password' => '@Kagaklupa170845!',
        'timeout' => NULL,
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
        'retry_after' => 60,
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
        'retry_after' => 60,
      ),
    ),
    'from' => 
    array (
      'address' => 'info@kampus.media',
      'name' => 'Kampus Media',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => '/var/www/spmm/resources/views/vendor/mail',
      ),
      'extensions' => 
      array (
      ),
    ),
  ),
  'queue' => 
  array (
    'default' => 'database',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => NULL,
        'secret' => NULL,
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
      'deferred' => 
      array (
        'driver' => 'deferred',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'connections' => 
        array (
          0 => 'database',
          1 => 'deferred',
        ),
      ),
    ),
    'batching' => 
    array (
      'database' => 'pgsql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'pgsql',
      'table' => 'failed_jobs',
    ),
  ),
  'session' => 
  array (
    'driver' => 'database',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => '/var/www/spmm/storage/framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'spmm_session',
    'path' => '/',
    'domain' => NULL,
    'secure' => NULL,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
  ),
  'spmm' => 
  array (
    'public_url' => 'https://kampus.media',
    'admin_url' => 'https://spmm.maheramedia.com/admin',
    'payment' => 
    array (
      'provider' => 'midtrans',
      'invoice_expiry_hours' => 24,
      'midtrans' => 
      array (
        'server_key' => 'Mid-server-joRrx6Sr4ppvILL7PtmXu8ho',
        'client_key' => 'Mid-client-FT4RYQKpn4cJSEc5',
        'is_production' => false,
      ),
    ),
    'whatsapp' => 
    array (
      'provider' => 'n8n',
      'default_country_code' => '62',
      'n8n_webhook_base' => 'https://n8n.maheramedia.com/webhook',
      'fonnte_token' => 'KAwN765SViRyub1PJmFM',
      'fonnte_devices' => 
      array (
        'primary' => 
        array (
          'label' => '08999928709',
          'token' => 'KAwN765SViRyub1PJmFM',
        ),
        'secondary' => 
        array (
          'label' => '081190027830',
          'token' => 'L5qm1gEFzsygmP7hLkmF',
        ),
      ),
    ),
    'ai_news' => 
    array (
      'openai_api_key' => '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',
      'openai_model' => 'gpt-4o-mini',
      'generate_cover_image' => true,
      'openai_image_model' => 'gpt-image-1',
      'openai_image_size' => '1536x1024',
      'sources' => 
      array (
        'Pendidikan Tinggi Indonesia' => 'https://news.google.com/rss/search?q=pendidikan+tinggi+Indonesia&hl=id&gl=ID&ceid=ID:id',
        'Kampus dan Kuliah Karyawan' => 'https://news.google.com/rss/search?q=kampus+kuliah+karyawan+Indonesia&hl=id&gl=ID&ceid=ID:id',
        'Prospek Jurusan dan Karier' => 'https://news.google.com/rss/search?q=prospek+kerja+jurusan+kuliah+Indonesia&hl=id&gl=ID&ceid=ID:id',
        'Kelas Karyawan dan RPL' => 'https://news.google.com/rss/search?q=kelas+karyawan+RPL+perguruan+tinggi&hl=id&gl=ID&ceid=ID:id',
        'PDDIKTI dan Perguruan Tinggi' => 'https://news.google.com/rss/search?q=PDDIKTI+perguruan+tinggi&hl=id&gl=ID&ceid=ID:id',
      ),
      'trend_sources' => 
      array (
        'Google Trends Indonesia' => 'https://trends.google.com/trending/rss?geo=ID',
      ),
      'trend_keywords' => 
      array (
        0 => 'kuliah online',
        1 => 'kuliah karyawan',
        2 => 'kelas hybrid',
        3 => 'RPL',
        4 => 'beasiswa',
        5 => 'karier',
        6 => 'sertifikasi',
        7 => 'kampus terakreditasi',
        8 => 'PDDIKTI',
        9 => 'jurusan kuliah',
        10 => 'prospek kerja',
        11 => 'kelas karyawan',
        12 => 'kuliah sambil kerja',
        13 => 'kampus swasta',
        14 => 'biaya kuliah',
        15 => 'program RPL',
      ),
      'editorial_knowledge' => 
      array (
        'brand' => 
        array (
          'name' => 'Kampus Media',
          'positioning' => 'portal informasi PMB yang membantu calon mahasiswa menemukan kampus, program studi, biaya kuliah, kelas fleksibel, dan jalur RPL secara lebih mudah.',
          'audience' => 
          array (
            0 => 'lulusan SMA/SMK/MA yang ingin kuliah',
            1 => 'karyawan yang ingin kuliah sambil kerja',
            2 => 'lulusan D3 yang ingin lanjut S1',
            3 => 'calon mahasiswa pindahan',
            4 => 'profesional yang ingin pengakuan pengalaman kerja melalui RPL',
            5 => 'orang tua yang sedang membandingkan biaya dan kualitas kampus',
          ),
        ),
        'content_pillars' => 
        array (
          'kampus' => 'cara memilih kampus terpercaya, terdaftar PDDIKTI, terakreditasi BAN-PT/LAM, dan memiliki layanan PMB yang jelas.',
          'jurusan' => 'panduan memilih jurusan berdasarkan minat, kemampuan, prospek kerja, kebutuhan industri, dan peluang karier.',
          'prospek' => 'hubungan jurusan dengan peluang kerja, peningkatan karier, sertifikasi, portofolio, dan penghasilan masa depan.',
          'kuliah_karyawan' => 'kuliah untuk pekerja dengan jadwal malam, akhir pekan, online, atau hybrid agar tetap bisa bekerja.',
          'kelas_karyawan' => 'kelas fleksibel untuk karyawan, staf, entrepreneur, dan profesional yang membutuhkan jadwal kuliah adaptif.',
          'rpl' => 'Rekognisi Pembelajaran Lampau sebagai jalur pengakuan pengalaman kerja, pelatihan, sertifikasi, dan pembelajaran nonformal sesuai ketentuan kampus.',
          'biaya' => 'transparansi biaya pendaftaran, herregistrasi, SPB, SPP, UKT, cicilan, dan simulasi pembayaran.',
        ),
        'study_program_angles' => 
        array (
          'Manajemen' => 'cocok untuk karier bisnis, operasional, HR, marketing, supervisor, entrepreneur, dan manajemen organisasi.',
          'Akuntansi' => 'cocok untuk karier finance, pajak, audit, administrasi keuangan, dan analis laporan keuangan.',
          'Teknik Informatika' => 'cocok untuk karier software developer, data, AI, jaringan, keamanan siber, dan produk digital.',
          'Sistem Informasi' => 'cocok untuk karier analis sistem, product owner, IT business analyst, dan transformasi digital.',
          'Ilmu Komunikasi' => 'cocok untuk karier media, public relations, content, brand communication, dan digital marketing.',
          'Hukum' => 'cocok untuk karier legal officer, compliance, advokasi, HR legal, dan administrasi publik.',
          'Psikologi' => 'cocok untuk karier HR, konseling, asesmen, pendidikan, dan pengembangan SDM.',
          'Pendidikan' => 'cocok untuk karier guru, tutor, trainer, pengembang kurikulum, dan pendidikan anak.',
          'Kesehatan' => 'cocok untuk karier layanan kesehatan, administrasi kesehatan, farmasi, keperawatan, dan gizi.',
        ),
        'seo_rules' => 
        array (
          0 => 'Gunakan bahasa Indonesia yang natural, tidak kaku, dan mudah dipahami calon mahasiswa.',
          1 => 'Judul harus mengandung intent pencarian seperti kuliah karyawan, kelas karyawan, RPL, prospek jurusan, biaya kuliah, atau kampus terpercaya bila relevan.',
          2 => 'Artikel wajib memiliki pembuka yang menjawab kebutuhan pembaca, beberapa subjudul H2, dan ajakan konsultasi/pendaftaran yang halus.',
          3 => 'Jangan melakukan keyword stuffing. Keyword boleh diulang wajar maksimal 2-4 kali untuk artikel pendek.',
          4 => 'Sisipkan konteks Kampus Media sebagai portal pembanding kampus dan PMB, bukan sebagai klaim berlebihan.',
          5 => 'Arahkan pembaca untuk mengecek akreditasi, PDDIKTI, biaya, jadwal kelas, dan prospek kerja sebelum memilih kampus.',
        ),
        'compliance_rules' => 
        array (
          0 => 'Jangan mengklaim kampus pasti diterima, pasti lulus cepat, atau pasti mendapat pekerjaan.',
          1 => 'Jangan mengarang data ranking, akreditasi, biaya, kuota beasiswa, atau kerja sama kampus jika tidak ada di sumber.',
          2 => 'Jika membahas RPL, jelaskan bahwa hasil konversi dan pengakuan SKS mengikuti asesmen dan kebijakan kampus.',
          3 => 'Jika memakai konteks Google Trends, sebut sebagai indikasi minat pencarian, bukan angka resmi.',
        ),
        'preferred_cta' => 'Konsultasikan pilihan kampus, jurusan, kelas karyawan, kuliah online, hybrid, RPL, dan simulasi biaya melalui Kampus Media.',
      ),
    ),
    'meta_leads' => 
    array (
      'verify_token' => 'spmm_meta_leads_2026',
      'page_access_token' => 'EAGKXZACv5OrEBR4LCULsbuyrNXv574mzBZCE704n9yFcEF7zwenwJDcbgZCISoxiuf3mk0bz0lmG6O1r3ou7tvZCCmUUXUpRHi0ZBhagU4Xkt84SLUAQ2WvxSFfsT22D9KCpzXdIWXgk2jEwk0sw02iJVXoOJpZApThZB0VxZBK1nXZBsKlzrmHZCdNvENUXaVM9M3Mmhh',
      'graph_version' => 'v25.0',
      'page_id' => '1208749898980613',
      'form_id' => '1421182943367763',
      'auto_import_limit' => '100',
      'default_campus_id' => NULL,
      'default_study_program_id' => NULL,
      'default_class_track_id' => NULL,
    ),
    'meta_conversions' => 
    array (
      'pixel_id' => NULL,
      'access_token' => NULL,
      'graph_version' => 'v25.0',
      'test_event_code' => NULL,
      'action_source' => 'system_generated',
    ),
    'siakad_integration' => 
    array (
      'base_url' => NULL,
      'api_token' => NULL,
    ),
  ),
  'blade-heroicons' => 
  array (
    'prefix' => 'heroicon',
    'fallback' => '',
    'class' => '',
    'attributes' => 
    array (
    ),
  ),
  'blade-icons' => 
  array (
    'sets' => 
    array (
    ),
    'class' => '',
    'attributes' => 
    array (
    ),
    'fallback' => '',
    'components' => 
    array (
      'disabled' => false,
      'default' => 'icon',
    ),
  ),
  'filament' => 
  array (
    'broadcasting' => 
    array (
    ),
    'default_filesystem_disk' => 'public',
    'assets_path' => NULL,
    'cache_path' => '/var/www/spmm/bootstrap/cache/filament',
    'livewire_loading_delay' => 'default',
    'system_route_prefix' => 'filament',
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'kampus.media',
      1 => 'www.kampus.media',
      2 => 'kampusmedia.cloud',
      3 => 'www.kampusmedia.cloud',
      4 => 'spmm.maheramedia.com',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => NULL,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
      'validate_csrf_token' => 'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
    ),
  ),
  'livewire' => 
  array (
    'class_namespace' => 'App\\Livewire',
    'view_path' => '/var/www/spmm/resources/views/livewire',
    'layout' => 'components.layouts.app',
    'lazy_placeholder' => NULL,
    'temporary_file_upload' => 
    array (
      'disk' => NULL,
      'rules' => NULL,
      'directory' => NULL,
      'middleware' => NULL,
      'preview_mimes' => 
      array (
        0 => 'png',
        1 => 'gif',
        2 => 'bmp',
        3 => 'svg',
        4 => 'wav',
        5 => 'mp4',
        6 => 'mov',
        7 => 'avi',
        8 => 'wmv',
        9 => 'mp3',
        10 => 'm4a',
        11 => 'jpg',
        12 => 'jpeg',
        13 => 'mpga',
        14 => 'webp',
        15 => 'wma',
      ),
      'max_upload_time' => 5,
      'cleanup' => true,
    ),
    'render_on_redirect' => false,
    'legacy_model_binding' => false,
    'inject_assets' => true,
    'navigate' => 
    array (
      'show_progress_bar' => true,
      'progress_bar_color' => '#2299dd',
    ),
    'inject_morph_markers' => true,
    'smart_wire_keys' => false,
    'pagination_theme' => 'tailwind',
    'release_token' => 'a',
  ),
  'excel' => 
  array (
    'exports' => 
    array (
      'chunk_size' => 1000,
      'pre_calculate_formulas' => false,
      'strict_null_comparison' => false,
      'csv' => 
      array (
        'delimiter' => ',',
        'enclosure' => '"',
        'line_ending' => '
',
        'use_bom' => false,
        'include_separator_line' => false,
        'excel_compatibility' => false,
        'output_encoding' => '',
        'test_auto_detect' => true,
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
    ),
    'imports' => 
    array (
      'read_only' => true,
      'ignore_empty' => false,
      'heading_row' => 
      array (
        'formatter' => 'slug',
      ),
      'csv' => 
      array (
        'delimiter' => NULL,
        'enclosure' => '"',
        'escape_character' => '\\',
        'contiguous' => false,
        'input_encoding' => 'guess',
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
      'cells' => 
      array (
        'middleware' => 
        array (
        ),
      ),
    ),
    'extension_detector' => 
    array (
      'xlsx' => 'Xlsx',
      'xlsm' => 'Xlsx',
      'xltx' => 'Xlsx',
      'xltm' => 'Xlsx',
      'xls' => 'Xls',
      'xlt' => 'Xls',
      'ods' => 'Ods',
      'ots' => 'Ods',
      'slk' => 'Slk',
      'xml' => 'Xml',
      'gnumeric' => 'Gnumeric',
      'htm' => 'Html',
      'html' => 'Html',
      'csv' => 'Csv',
      'tsv' => 'Csv',
      'pdf' => 'Dompdf',
    ),
    'value_binder' => 
    array (
      'default' => 'Maatwebsite\\Excel\\DefaultValueBinder',
    ),
    'cache' => 
    array (
      'driver' => 'memory',
      'batch' => 
      array (
        'memory_limit' => 60000,
      ),
      'illuminate' => 
      array (
        'store' => NULL,
      ),
      'default_ttl' => 10800,
    ),
    'transactions' => 
    array (
      'handler' => 'db',
      'db' => 
      array (
        'connection' => NULL,
      ),
    ),
    'temporary_files' => 
    array (
      'local_path' => '/var/www/spmm/storage/framework/cache/laravel-excel',
      'local_permissions' => 
      array (
      ),
      'remote_disk' => NULL,
      'remote_prefix' => NULL,
      'force_resync_remote' => NULL,
    ),
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'Spatie\\Permission\\Models\\Permission',
      'role' => 'Spatie\\Permission\\Models\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'roles',
      'permissions' => 'permissions',
      'model_has_permissions' => 'model_has_permissions',
      'model_has_roles' => 'model_has_roles',
      'role_has_permissions' => 'role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => NULL,
      'permission_pivot_key' => NULL,
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'team_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => false,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
);
