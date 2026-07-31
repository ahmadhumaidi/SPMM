@php
    use Illuminate\Support\Facades\Storage;

    $settings = $campus->website_settings ?? [];
    $activePrograms = $campus->studyPrograms;
    $activeTracks = $campus->classTracks;
    $fees = $campus->feeSchemes;
    $firstTrack = $activeTracks->first();
    $lowestRegistration = $fees->where('registration_fee', '>', 0)->min('registration_fee') ?? 100000;
    $secondInstallment = function ($fee): ?int {
        $schedule = $fee->uses_custom_installments
            ? ($fee->custom_installment_schedule_json ?: $fee->installment_schedule_json)
            : $fee->installment_schedule_json;

        $installmentTotal = function (array $row): int {
            $installment = (int) ($row['development_fee'] ?? 0)
                + (int) ($row['tuition_fee'] ?? 0)
                + (int) ($row['ukt'] ?? 0);

            return $installment > 0 ? $installment : (int) ($row['total'] ?? 0);
        };

        $rows = collect($schedule ?: [])->values();
        $secondRow = $rows->first(fn (array $row, int $index): bool => (int) ($row['month'] ?? ($index + 1)) === 2);
        $secondTotal = $secondRow ? $installmentTotal($secondRow) : 0;

        if ($secondTotal > 0) {
            return $secondTotal;
        }

        return $rows
            ->map(function (array $row) use ($installmentTotal): int {
                $installment = (int) ($row['development_fee'] ?? 0)
                    + (int) ($row['tuition_fee'] ?? 0)
                    + (int) ($row['ukt'] ?? 0);

                return $installment > 0 ? $installment : $installmentTotal($row);
            })
            ->filter(fn (int $amount): bool => $amount > 0)
            ->first();
    };
    $lowestMonthly = $fees
        ->where('is_active', true)
        ->map($secondInstallment)
        ->filter()
        ->min() ?? 560000;
    $feeSummaries = $fees
        ->where('is_active', true)
        ->map(function ($fee) use ($secondInstallment) {
            $registration = (int) $fee->registration_fee;
            $development = (int) $fee->building_fee;
            $tuitionPerSemester = (int) $fee->monthly_tuition_fee;
            $ukt = (int) $fee->ukt_total;
            $heregistration = (int) $fee->total_initial_payment;
            $monthlyInstallment = $secondInstallment($fee);

            return [
                'study_program_id' => $fee->study_program_id,
                'class_track_id' => $fee->class_track_id,
                'program' => $fee->studyProgram?->name ?? 'Semua Program Studi',
                'track' => $fee->classTrack?->name ?? 'Semua Program Perkuliahan',
                'model' => $fee->financing_model === 'ukt' ? 'UKT' : 'SPB + SPP',
                'registration' => $registration,
                'development' => $development,
                'tuition_per_semester' => $tuitionPerSemester,
                'ukt' => $ukt,
                'heregistration' => $heregistration,
                'monthly_installment' => $monthlyInstallment,
            ];
        })
        ->values();
    $lowestHeregistrationCost = $feeSummaries->pluck('heregistration')->filter()->min();
    $campusInitial = mb_substr($campus->name, 0, 1);
    $logoUrl = $campus->logo_path ? Storage::url($campus->logo_path) : null;
    $whatsappNumber = '6282199976600';
    $whatsappMessage = rawurlencode('halo min, saya mendapat informasi dari kampus media (KAMI), saya ingin mengetahui informasi lebih lanjut tentang kampus '.$campus->name);
    $whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$whatsappMessage}";
    $heroBadge = $settings['hero_badge'] ?? 'Promo beasiswa PMB terbatas tahun ini';
    $heroHeadline = $settings['hero_headline'] ?? 'Kuliah Karyawan & Reguler Lebih Fleksibel';
    $heroSubheadline = $settings['hero_subheadline'] ?? 'Kuliah bisa sambil kerja, biaya terjangkau, dan bisa dicicil bulanan.';
    $primaryCtaLabel = $settings['primary_cta_label'] ?? 'Daftar Sekarang';
    $secondaryCtaLabel = $settings['secondary_cta_label'] ?? 'Konsultasi Gratis';
    $promoTitle = $settings['promo_title'] ?? 'Daftar lebih awal, peluang beasiswa lebih besar.';
    $promoDescription = $settings['promo_description'] ?? 'Dapatkan informasi biaya, jadwal kuliah, dan rekomendasi program studi yang sesuai dengan target kariermu.';
    $heroImage = filled($settings['hero_image_path'] ?? null)
        ? Storage::url($settings['hero_image_path'])
        : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1100&q=80';
    $trustStats = filled($settings['trust_stats'] ?? null)
        ? $settings['trust_stats']
        : [['value' => '22', 'label' => 'Tahun Pengalaman'], ['value' => '35.000+', 'label' => 'Mahasiswa'], ['value' => '114.000+', 'label' => 'Alumni'], ['value' => '300+', 'label' => 'Program Studi']];
    $features = filled($settings['features'] ?? null)
        ? $settings['features']
        : [['icon' => 'clock-3', 'title' => 'Kuliah Fleksibel', 'description' => 'Pilihan jadwal kuliah yang menyesuaikan aktivitas harian.'], ['icon' => 'monitor-smartphone', 'title' => 'Bisa Online / Hybrid', 'description' => 'Belajar lebih adaptif lewat kelas online dan hybrid.'], ['icon' => 'wallet-cards', 'title' => 'Biaya Bisa Dicicil', 'description' => 'Pembayaran lebih ringan dengan cicilan bulanan.'], ['icon' => 'briefcase-business', 'title' => 'Dosen Praktisi', 'description' => 'Materi kuliah dekat dengan kebutuhan industri.'], ['icon' => 'trending-up', 'title' => 'Career Support', 'description' => 'Dukungan karier untuk pengembangan profesional.'], ['icon' => 'badge-percent', 'title' => 'Beasiswa Pendidikan', 'description' => 'Promo dan beasiswa tersedia untuk periode PMB.']];
    $testimonials = filled($settings['testimonials'] ?? null)
        ? $settings['testimonials']
        : [['name' => 'Rina', 'role' => 'Karyawan Swasta', 'photo_path' => null, 'quote' => 'Jadwal kuliah fleksibel dan konsultannya responsif. Saya bisa atur kuliah tanpa meninggalkan pekerjaan.'], ['name' => 'Fajar', 'role' => 'Staff Operasional', 'photo_path' => null, 'quote' => 'Biaya cicilan bulanan sangat membantu. Proses daftar juga jelas dari awal.'], ['name' => 'Dewi', 'role' => 'Fresh Graduate', 'photo_path' => null, 'quote' => 'Program studinya lengkap dan informasi biaya mudah dipahami.']];
    $gallery = filled($settings['gallery'] ?? null)
        ? $settings['gallery']
        : [
            ['image_path' => null, 'title' => 'Suasana Kampus', 'description' => 'Lingkungan belajar yang nyaman dan mendukung aktivitas mahasiswa.'],
            ['image_path' => null, 'title' => 'Kelas Fleksibel', 'description' => 'Aktivitas pembelajaran untuk mahasiswa reguler dan pekerja.'],
            ['image_path' => null, 'title' => 'Kegiatan Mahasiswa', 'description' => 'Ruang pengembangan diri, komunitas, dan jejaring karier.'],
        ];
    $faqs = filled($settings['faqs'] ?? null)
        ? $settings['faqs']
        : [['question' => 'Apakah biaya bisa dicicil?', 'answer' => 'Bisa. Tersedia skema cicilan bulanan sesuai program dan model pembiayaan kampus.'], ['question' => 'Apakah bisa kuliah sambil kerja?', 'answer' => 'Bisa. Tersedia kelas karyawan, reguler, online, dan hybrid sesuai pilihan kampus.'], ['question' => 'Apakah tersedia kelas online?', 'answer' => 'Tersedia untuk program tertentu. Konsultan PMB akan membantu mengecek pilihan kelas yang aktif.']];
    $classSchedules = filled($settings['class_schedules'] ?? null)
        ? collect($settings['class_schedules'])->filter(fn (array $row): bool => filled($row['day'] ?? null) || filled($row['time'] ?? null) || filled($row['title'] ?? null))->values()
        : collect([
            ['day' => 'Senin - Jumat', 'time' => '18.30 - 21.00', 'title' => 'Kelas Karyawan Malam', 'mode' => 'Hybrid', 'location' => 'Kampus / Online', 'note' => 'Cocok untuk mahasiswa yang bekerja pada jam kantor.'],
            ['day' => 'Sabtu', 'time' => '08.00 - 16.00', 'title' => 'Kelas Weekend', 'mode' => 'Tatap muka', 'location' => 'Kampus', 'note' => 'Jadwal intensif akhir pekan untuk perkuliahan reguler/karyawan.'],
            ['day' => 'Fleksibel', 'time' => 'Sesuai LMS', 'title' => 'Kelas Online', 'mode' => 'Online', 'location' => 'LMS / Zoom', 'note' => 'Materi dan pertemuan online mengikuti ketentuan program studi.'],
        ]);
    $programFallbacks = [
        ['name' => 'Manajemen', 'degree' => 'S1', 'accreditation' => 'Baik Sekali'],
        ['name' => 'Akuntansi', 'degree' => 'S1', 'accreditation' => 'Baik Sekali'],
        ['name' => 'Teknik Informatika', 'degree' => 'S1', 'accreditation' => 'Baik'],
        ['name' => 'Sistem Informasi', 'degree' => 'S1', 'accreditation' => 'Baik'],
        ['name' => 'Bisnis Digital', 'degree' => 'S1', 'accreditation' => 'Baik'],
        ['name' => 'Ilmu Komunikasi', 'degree' => 'S1', 'accreditation' => 'Baik'],
    ];
    $programGroups = $activePrograms
        ->sortBy([
            ['degree_level', 'asc'],
            ['name', 'asc'],
        ])
        ->groupBy(fn ($program) => $program->degree_level ?: 'S1');
    $fallbackProgramGroups = collect($programFallbacks)
        ->sortBy([
            ['degree', 'asc'],
            ['name', 'asc'],
        ])
        ->groupBy(fn ($program) => $program['degree'] ?: 'S1');
    $educationNews = $educationNews ?? collect();
    $canonicalUrl = $campus->publicUrl();
    $seoTitle = 'PMB '.$campus->name.' | Kuliah Fleksibel dan Biaya Transparan';
    $seoDescription = 'Informasi PMB '.$campus->name.': program studi, kelas karyawan, online/hybrid, RPL, biaya kuliah, dan pendaftaran online melalui Kampus Media.';
    $seoImage = $logoUrl ?: $heroImage;
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth bg-navy">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="PMB, kuliah karyawan, kuliah online, {{ $campus->name }}, kampus Indonesia">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Kampus Media">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'EducationalOrganization',
                    'name' => $campus->name,
                    'url' => $canonicalUrl,
                    'logo' => $logoUrl,
                    'address' => array_filter([
                        '@type' => 'PostalAddress',
                        'addressLocality' => $campus->city,
                        'addressRegion' => $campus->province,
                        'streetAddress' => $campus->address,
                    ]),
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => collect($faqs)->map(fn (array $faq): array => [
                        '@type' => 'Question',
                        'name' => $faq['question'] ?? '',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq['answer'] ?? '',
                        ],
                    ])->all(),
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @vite(['resources/css/app.css', 'resources/css/pages/public-site.css', 'resources/css/pages/partner-campus.css', 'resources/js/pages/partner-campus.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="skeleton-loader fixed inset-0 z-[70] bg-white p-6">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="h-12 w-56 animate-pulse rounded-xl bg-slate-200"></div>
            <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
                <div class="h-[420px] animate-pulse rounded-3xl bg-slate-200"></div>
                <div class="h-[420px] animate-pulse rounded-3xl bg-slate-100"></div>
            </div>
        </div>
    </div>

    <nav class="sticky top-0 z-50 border-b border-white/10 bg-black/50 text-white shadow-lg shadow-slate-950/10 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="#home" class="flex items-center gap-3">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo {{ $campus->name }}" width="44" height="44" decoding="async" class="h-11 w-11 rounded-xl bg-white object-contain p-1">
                @else
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-white text-lg font-black text-navy">{{ $campusInitial }}</span>
                @endif
                <span>
                    <span class="block text-sm font-black leading-tight sm:text-base">{{ $campus->name }}</span>
                    <span class="block text-xs font-semibold text-sky-100">Penerimaan Mahasiswa Baru</span>
                </span>
            </a>
            <div class="hidden items-center gap-7 text-sm font-bold text-sky-50 md:flex">
                <a href="#keunggulan" class="hover:text-gold">Keunggulan</a>
                <a href="#program" class="hover:text-gold">Program Studi</a>
                <a href="#jadwal" class="hover:text-gold">Jadwal</a>
                <a href="#biaya" class="hover:text-gold">Biaya</a>
                <a href="#galeri" class="hover:text-gold">Galeri</a>
                <a href="#berita" class="hover:text-gold">Berita</a>
                <a href="#faq" class="hover:text-gold">FAQ</a>
            </div>
            <a href="#daftar" class="inline-flex h-11 items-center rounded-full bg-gold px-5 text-sm font-black text-navy shadow-lg shadow-orange-500/20 transition hover:-translate-y-0.5 hover:bg-yellow-400">
                Daftar Sekarang
            </a>
        </div>
    </nav>

    <header id="home" class="pattern relative -mt-[68px] overflow-hidden text-white">
        <div class="absolute inset-0 grid-pattern opacity-50"></div>
        <div class="sp-orbit sp-orbit-one" aria-hidden="true"></div>
        <div class="sp-orbit sp-orbit-two" aria-hidden="true"></div>
        <div class="sp-hero-glow-fixed" aria-hidden="true"></div>
        <div class="relative mx-auto grid min-h-[calc(100vh-68px)] max-w-7xl items-center gap-10 px-4 pb-10 pt-[calc(68px+2.5rem)] sm:px-6 lg:grid-cols-[1.08fr_.92fr] lg:px-8 lg:pb-16 lg:pt-[calc(68px+4rem)]">
            <section class="reveal">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold text-yellow-100 backdrop-blur">
                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                    {{ $heroBadge }}
                </div>
                <h1 class="max-w-4xl text-4xl font-black leading-[1.02] tracking-normal sm:text-5xl lg:text-7xl">
                    {{ $heroHeadline }}
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-sky-50 sm:text-xl">
                    {{ $heroSubheadline }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#daftar" class="inline-flex h-13 items-center justify-center rounded-full bg-gold px-7 py-4 font-black text-navy shadow-xl shadow-orange-500/20 transition hover:-translate-y-1 hover:bg-yellow-400">
                        {{ $primaryCtaLabel }}
                    </a>
                    <a href="{{ $whatsappUrl }}" class="inline-flex h-13 items-center justify-center gap-2 rounded-full border border-white/25 bg-white/10 px-7 py-4 font-black text-white backdrop-blur transition hover:-translate-y-1 hover:bg-white/20">
                        <i data-lucide="message-circle" class="h-5 w-5"></i>
                        {{ $secondaryCtaLabel }}
                    </a>
                </div>
                <div class="mt-10 grid max-w-2xl grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($trustStats as $stat)
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                            <strong class="block text-2xl font-black text-yellow-200">{{ $stat['value'] ?? '' }}</strong>
                            <span class="mt-1 block text-xs font-bold text-sky-100">{{ $stat['label'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="daftar" class="reveal rounded-[2rem] border border-white/25 bg-white/10 p-5 text-white shadow-2xl shadow-slate-950/25 backdrop-blur-xl sm:p-7">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-sky-100">Form Pendaftaran Cepat</p>
                        <h2 class="mt-1 text-2xl font-black text-white">Mulai konsultasi PMB</h2>
                    </div>
                    <span class="grid h-12 w-12 place-items-center rounded-2xl border border-white/20 bg-white/15 text-sky-100">
                        <i data-lucide="graduation-cap" class="h-6 w-6"></i>
                    </span>
                </div>
                @if ($errors->any())
                    <div class="sp-glass-error mb-4 rounded-2xl p-4 text-sm font-semibold">
                        <p>Mohon periksa lagi data pendaftaran.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('registration.store') }}" class="grid gap-4">
                    @csrf
                    <input type="hidden" name="campus_id" value="{{ $campus->id }}">
                    <input type="hidden" name="source_channel" value="partner_website">
                    <input type="hidden" name="source_detail" value="Landing page {{ $campus->slug }}">

                    <label class="grid gap-2 text-sm font-bold">
                        Nama
                        <input name="full_name" value="{{ old('full_name') }}" required class="sp-glass-field h-12 rounded-2xl px-4 outline-none transition" placeholder="Nama lengkap">
                    </label>
                    <label class="grid gap-2 text-sm font-bold">
                        No WhatsApp
                        <input name="whatsapp_number" value="{{ old('whatsapp_number') }}" required class="sp-glass-field h-12 rounded-2xl px-4 outline-none transition" placeholder="08xxxxxxxxxx">
                    </label>
                    <label class="grid gap-2 text-sm font-bold">
                        Email Mahasiswa
                        <input type="email" name="email" value="{{ old('email') }}" required class="sp-glass-field h-12 rounded-2xl px-4 outline-none transition" placeholder="nama@email.com">
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-bold">
                            Jenjang Pendidikan
                            <select class="sp-glass-field h-12 rounded-2xl px-4 outline-none transition">
                                <option>S1 Sarjana</option>
                                <option>D3 Diploma</option>
                                <option>D4 Sarjana Terapan</option>
                                <option>S2 Magister</option>
                            </select>
                        </label>
                        <label class="grid gap-2 text-sm font-bold">
                            Program Studi
                            <select name="study_program_id" required class="sp-glass-field h-12 rounded-2xl px-4 outline-none transition">
                                <option value="">Pilih prodi</option>
                                @foreach ($campus->studyPrograms as $program)
                                    <option value="{{ $program->id }}" @selected(old('study_program_id') == $program->id)>
                                        {{ $program->degree_level ? $program->degree_level.' - ' : '' }}{{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="grid gap-2 text-sm font-bold">
                        Waktu Kuliah
                        <select name="class_track_id" required class="sp-glass-field h-12 rounded-2xl px-4 outline-none transition">
                            <option value="">Pilih waktu kuliah</option>
                            @foreach ($activeTracks as $track)
                                <option value="{{ $track->id }}" @selected(old('class_track_id') == $track->id)>{{ $track->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <input type="hidden" name="origin_school" value="{{ old('origin_school') }}">
                    <input type="hidden" name="graduation_year" value="{{ old('graduation_year') }}">
                    <button class="mt-2 h-13 rounded-2xl bg-gold px-6 py-4 font-black text-navy shadow-xl shadow-orange-500/20 transition hover:-translate-y-1 hover:bg-yellow-400">
                        Daftar
                    </button>
                    <p class="text-center text-xs font-semibold leading-5 text-sky-100">Tim PMB akan menghubungi via WhatsApp untuk konsultasi dan arahan pembayaran.</p>
                </form>
            </section>
        </div>
    </header>

    <main>
        <section class="-mt-8 px-4 sm:px-6 lg:px-8">
            <div class="reveal mx-auto grid max-w-7xl gap-4 rounded-[2rem] bg-white p-4 shadow-soft sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($trustStats as $item)
                    <div class="flex items-center gap-4 rounded-3xl bg-slate-50 p-5">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-100 text-sky-700"><i data-lucide="badge-check" class="h-6 w-6"></i></span>
                        <strong class="text-base font-black text-navy">{{ ($item['value'] ?? '').' '.($item['label'] ?? '') }}</strong>
                    </div>
                @endforeach
            </div>
        </section>

        <section id="keunggulan" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-wide text-sky-700">Keunggulan Kampus</p>
                    <h2 class="mt-3 text-3xl font-black tracking-normal text-navy sm:text-5xl">Dirancang untuk mahasiswa aktif, pekerja, dan profesional muda.</h2>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($features as $feature)
                        <article class="reveal rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-soft">
                            <span class="grid h-13 w-13 place-items-center rounded-2xl bg-sky-50 text-sky-700"><i data-lucide="{{ $feature['icon'] ?? 'badge-check' }}" class="h-7 w-7"></i></span>
                            <h3 class="mt-5 text-xl font-black text-navy">{{ $feature['title'] ?? '' }}</h3>
                            <p class="mt-3 leading-7 text-slate-600">{{ $feature['description'] ?? '' }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="overflow-hidden bg-white px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-[.9fr_1.1fr]">
                <div class="reveal relative">
                    <img src="{{ $heroImage }}" alt="Mahasiswa kampus Indonesia" width="1100" height="825" loading="lazy" decoding="async" class="aspect-[4/3] w-full rounded-[2rem] object-cover shadow-soft">
                    <div class="absolute -bottom-5 left-5 right-5 rounded-3xl bg-white p-5 shadow-soft sm:left-auto sm:w-72">
                        <p class="text-sm font-black text-sky-700">PMB aktif</p>
                        <strong class="mt-1 block text-2xl font-black text-navy">Konsultasi gratis setiap hari</strong>
                    </div>
                </div>
                <div class="reveal">
                    <p class="text-sm font-black uppercase tracking-wide text-sky-700">Promo Beasiswa</p>
                    <h2 class="mt-3 text-3xl font-black tracking-normal text-navy sm:text-5xl">{{ $promoTitle }}</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">{{ $promoDescription }}</p>
                    <a href="#daftar" class="mt-8 inline-flex rounded-full bg-gold px-7 py-4 font-black text-navy shadow-lg shadow-orange-500/20 transition hover:-translate-y-1 hover:bg-yellow-400">Ambil Promo Beasiswa</a>
                </div>
            </div>
        </section>

        <section id="program" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-8 max-w-3xl">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-sky-700">Program Studi</p>
                        <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Pilih jurusan sesuai rencana karier Anda</h2>
                    </div>
                </div>

                <div class="reveal mb-6 max-w-xl">
                    <label class="sr-only" for="program-search">Cari program studi</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"></i>
                        <input id="program-search" type="search" placeholder="Cari program studi..." class="h-14 w-full rounded-2xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-base font-semibold text-slate-700 outline-none ring-blue-500/10 transition focus:border-blue-500 focus:ring-4">
                    </div>
                </div>

                <div class="reveal overflow-hidden rounded-3xl border border-slate-200 bg-white">
                    <div id="program-list" class="grid md:grid-cols-2">
                    @forelse ($programGroups as $degree => $programs)
                        <div data-program-group-heading="{{ $degree }}" class="col-span-full border-b border-[#E5E7EB] bg-slate-50 px-5 py-3 text-xs font-black uppercase tracking-[0.18em] text-sky-700 md:px-6">
                            {{ $degree }}
                        </div>
                        @foreach ($programs as $program)
                        <button
                            type="button"
                            data-program-list-item
                            data-program-degree="{{ $degree }}"
                            data-program-name="{{ strtolower($degree.' '.$program->name) }}"
                            data-program-modal-target="program-modal-{{ $program->id }}"
                            class="group flex h-16 items-center justify-between gap-4 border-b border-[#E5E7EB] px-5 text-left transition hover:bg-[#F8FAFC] md:px-6 md:odd:border-r"
                        >
                            <span class="text-base font-bold text-[#0F172A] transition group-hover:text-[#2563EB]">{{ $degree }} - {{ $program->name }}</span>
                            <span class="text-xl font-bold text-slate-400 transition group-hover:translate-x-1 group-hover:text-[#2563EB]">→</span>
                        </button>
                        @endforeach
                    @empty
                        @foreach ($fallbackProgramGroups as $degree => $programs)
                            <div data-program-group-heading="{{ $degree }}" class="col-span-full border-b border-[#E5E7EB] bg-slate-50 px-5 py-3 text-xs font-black uppercase tracking-[0.18em] text-sky-700 md:px-6">
                                {{ $degree }}
                            </div>
                            @foreach ($programs as $program)
                            <button
                                type="button"
                                data-program-list-item
                                data-program-degree="{{ $degree }}"
                                data-program-name="{{ strtolower($degree.' '.$program['name']) }}"
                                data-program-modal-target="program-fallback-modal-{{ $loop->parent->iteration }}-{{ $loop->iteration }}"
                                class="group flex h-16 items-center justify-between gap-4 border-b border-[#E5E7EB] px-5 text-left transition hover:bg-[#F8FAFC] md:px-6 md:odd:border-r"
                            >
                                <span class="text-base font-bold text-[#0F172A] transition group-hover:text-[#2563EB]">{{ $degree }} - {{ $program['name'] }}</span>
                                <span class="text-xl font-bold text-slate-400 transition group-hover:translate-x-1 group-hover:text-[#2563EB]">→</span>
                            </button>
                            @endforeach
                        @endforeach
                    @endforelse
                    </div>
                    <div id="program-search-empty" hidden class="px-5 py-6 text-sm font-bold text-slate-500 md:px-6">
                        Program studi tidak ditemukan.
                    </div>
                </div>
            </div>
        </section>

        @foreach ($activePrograms as $program)
            <div id="program-modal-{{ $program->id }}" class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" data-program-modal>
                <div class="max-h-[88vh] w-full max-w-2xl overflow-y-auto rounded-[2rem] bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-black uppercase tracking-wide text-sky-700">Detail Program Studi</p>
                            <h3 class="mt-2 text-3xl font-black text-navy">{{ $program->name }}</h3>
                            <p class="mt-2 font-semibold text-slate-500">
                                {{ $program->degree_level ?: 'S1' }}
                                @if ($program->degree_title)
                                    · Gelar {{ $program->degree_title }}
                                @endif
                                @if ($program->faculty)
                                    · {{ $program->faculty }}
                                @endif
                            </p>
                        </div>
                        <button type="button" data-program-modal-close class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-slate-100 text-navy">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div class="mt-5 rounded-3xl bg-slate-50 p-5">
                        <p class="text-sm font-black text-slate-500">Akreditasi</p>
                        <p class="mt-1 text-xl font-black text-navy">{{ $program->accreditation ?: 'Baik' }}</p>
                    </div>
                    <div class="mt-5 leading-8 text-slate-700">
                        {{ $program->prospectus ?: 'Prospektus program studi belum diisi. Hubungi konsultan PMB untuk informasi kompetensi lulusan, prospek karier, bidang kerja, dan pilihan kelas yang tersedia.' }}
                    </div>
                    <a href="#daftar" data-program-modal-close class="mt-7 inline-flex w-full justify-center rounded-full bg-sky-600 px-6 py-4 font-black text-white shadow-lg shadow-sky-600/20">Daftar Program Ini</a>
                </div>
            </div>
        @endforeach

        @if ($activePrograms->isEmpty())
            @foreach ($fallbackProgramGroups as $degree => $programs)
                @foreach ($programs as $program)
                <div id="program-fallback-modal-{{ $loop->parent->iteration }}-{{ $loop->iteration }}" class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm" data-program-modal>
                    <div class="w-full max-w-2xl rounded-[2rem] bg-white p-6 shadow-2xl">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-black uppercase tracking-wide text-sky-700">Detail Program Studi</p>
                                <h3 class="mt-2 text-3xl font-black text-navy">{{ $program['name'] }}</h3>
                                <p class="mt-2 font-semibold text-slate-500">{{ $program['degree'] }} · Akreditasi {{ $program['accreditation'] }}</p>
                            </div>
                            <button type="button" data-program-modal-close class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-slate-100 text-navy">
                                <i data-lucide="x" class="h-5 w-5"></i>
                            </button>
                        </div>
                        <p class="mt-6 leading-8 text-slate-700">Prospektus program studi belum diisi. Hubungi konsultan PMB untuk informasi kompetensi lulusan dan peluang karier.</p>
                        <a href="#daftar" data-program-modal-close class="mt-7 inline-flex w-full justify-center rounded-full bg-sky-600 px-6 py-4 font-black text-white shadow-lg shadow-sky-600/20">Daftar Program Ini</a>
                    </div>
                </div>
                @endforeach
            @endforeach
        @endif


        <section id="jadwal" class="bg-slate-50 px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                    <div class="max-w-3xl">
                        <p class="text-sm font-black uppercase tracking-wide text-sky-700">Jadwal Kuliah</p>
                        <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Pilihan jadwal yang fleksibel untuk kuliah sambil beraktivitas.</h2>
                        <p class="mt-4 text-lg leading-8 text-slate-600">Jadwal dapat berbeda untuk tiap program studi dan periode akademik. Hubungi konsultan PMB untuk memastikan kelas yang sedang dibuka.</p>
                    </div>
                    <a href="{{ $whatsappUrl }}" class="inline-flex rounded-full bg-gold px-6 py-3 font-black text-navy shadow-lg shadow-orange-500/20">Konsultasi Jadwal</a>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    @foreach ($classSchedules as $schedule)
                        <article class="reveal rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-soft">
                            <div class="flex items-start justify-between gap-4">
                                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-sky-700">
                                    <i data-lucide="calendar-clock" class="h-6 w-6"></i>
                                </span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $schedule['mode'] ?? 'Hybrid' }}</span>
                            </div>
                            <p class="mt-5 text-sm font-black uppercase tracking-wide text-sky-700">{{ $schedule['day'] ?? 'Fleksibel' }}</p>
                            <h3 class="mt-2 text-2xl font-black text-navy">{{ $schedule['title'] ?? 'Jadwal Kuliah' }}</h3>
                            <div class="mt-5 grid gap-3 text-sm font-bold text-slate-600">
                                <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                                    <i data-lucide="clock-3" class="h-5 w-5 text-sky-700"></i>
                                    <span>{{ $schedule['time'] ?? 'Sesuai jadwal kampus' }}</span>
                                </div>
                                <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                                    <i data-lucide="map-pin" class="h-5 w-5 text-sky-700"></i>
                                    <span>{{ $schedule['location'] ?? 'Kampus / Online' }}</span>
                                </div>
                            </div>
                            @if (filled($schedule['note'] ?? null))
                                <p class="mt-5 leading-7 text-slate-600">{{ $schedule['note'] }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        <section id="biaya" class="bg-navy px-4 py-16 text-white sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-wide text-yellow-200">Biaya Kuliah</p>
                    <h2 class="mt-3 text-3xl font-black sm:text-5xl">Biaya transparan dan bisa dicicil bulanan.</h2>
                </div>
                <div class="grid gap-6 lg:grid-cols-3">
                    <article class="reveal rounded-[2rem] border border-white/15 bg-white p-7 text-slate-900 shadow-2xl">
                        <p class="font-black text-sky-700">Uang pendaftaran</p>
                        <strong class="mt-3 block text-4xl font-black text-navy">Rp {{ number_format($lowestRegistration, 0, ',', '.') }}</strong>
                        <p class="mt-3 leading-7 text-slate-600">Registrasi awal untuk memulai proses PMB.</p>
                        <a href="#daftar" class="mt-7 inline-flex w-full justify-center rounded-full bg-navy px-6 py-4 font-black text-white">Daftar Sekarang</a>
                    </article>
                    <article class="reveal relative rounded-[2rem] border-2 border-gold bg-white p-7 text-slate-900 shadow-2xl">
                        <span class="absolute right-6 top-6 rounded-full bg-gold px-3 py-1 text-xs font-black text-navy">Favorit</span>
                        <p class="font-black text-sky-700">Cicilan per bulan</p>
                        <strong class="mt-3 block text-4xl font-black text-navy">Rp {{ number_format($lowestMonthly, 0, ',', '.') }}</strong>
                        <p class="mt-3 leading-7 text-slate-600">Skema cicilan ringan untuk kelas karyawan, reguler, online, dan hybrid.</p>
                        <a href="{{ $whatsappUrl }}" class="mt-7 inline-flex w-full justify-center rounded-full bg-gold px-6 py-4 font-black text-navy">Konsultasi</a>
                    </article>
                    <article class="reveal rounded-[2rem] border border-white/15 bg-white/10 p-7 backdrop-blur">
                        <p class="font-black text-yellow-200">Beasiswa tersedia</p>
                        <strong class="mt-3 block text-4xl font-black">Potongan biaya</strong>
                        <p class="mt-3 leading-7 text-sky-50">Tanyakan promo beasiswa aktif dan rekomendasi biaya terbaik ke konsultan PMB.</p>
                        <a href="#daftar" class="mt-7 inline-flex w-full justify-center rounded-full border border-white/20 px-6 py-4 font-black text-white">Cek Beasiswa</a>
                    </article>
                </div>
                <article class="reveal mt-6 rounded-[2rem] border border-white/15 bg-white p-6 text-slate-900 shadow-2xl lg:p-8">
                    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                        <div>
                            <p class="text-sm font-black uppercase tracking-wide text-sky-700">Estimasi Biaya</p>
                            <h3 class="mt-2 text-3xl font-black text-navy">Estimasi biaya dari sistem pusat.</h3>
                            <p class="mt-3 max-w-3xl leading-7 text-slate-600">Rincian ini mengambil fee scheme aktif kampus, termasuk pendaftaran, SPB/development, SPP per semester, atau UKT.</p>
                        </div>
                        @if ($lowestHeregistrationCost)
                            <div class="rounded-3xl bg-sky-50 p-5 text-right">
                                <p class="text-sm font-black text-sky-700">Heregistrasi mulai dari</p>
                                <strong class="mt-1 block text-3xl font-black text-navy">Rp {{ number_format($lowestHeregistrationCost, 0, ',', '.') }}</strong>
                            </div>
                        @endif
                    </div>

                    @if ($feeSummaries->isEmpty())
                        <div class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6">
                            <p class="font-black text-navy">Belum ada fee scheme aktif.</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Tambahkan rincian biaya dari sistem pusat agar estimasi sampai lulus tampil di halaman kampus.</p>
                        </div>
                    @else
                        <div class="mt-6 grid gap-4 lg:grid-cols-2">
                            <label class="grid gap-2 text-sm font-black text-navy">
                                Pilih Program Studi
                                <select id="fee-study-program" class="h-14 rounded-2xl border border-slate-200 bg-white px-4 text-base font-bold text-slate-700 outline-none ring-sky-500/20 transition focus:border-sky-500 focus:ring-4">
                                    <option value="">Pilih program studi dulu</option>
                                    @foreach ($activePrograms as $program)
                                        <option value="{{ $program->id }}">{{ $program->degree_level }} {{ $program->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2 text-sm font-black text-navy">
                                Pilih Program Perkuliahan
                                <select id="fee-class-track" disabled class="h-14 rounded-2xl border border-slate-200 bg-white px-4 text-base font-bold text-slate-700 outline-none ring-sky-500/20 transition disabled:bg-slate-100 disabled:text-slate-400 focus:border-sky-500 focus:ring-4">
                                    <option value="">Pilih prodi terlebih dahulu</option>
                                    @foreach ($activeTracks as $track)
                                        <option value="{{ $track->id }}">{{ $track->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div id="fee-selection-empty" class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6">
                            <p class="font-black text-navy">Pilih prodi dan program perkuliahan.</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Rincian biaya akan tampil setelah calon mahasiswa memilih kombinasi program yang diinginkan.</p>
                        </div>

                        <div id="fee-selection-no-result" hidden class="mt-6 rounded-3xl border border-dashed border-orange-300 bg-orange-50 p-6">
                            <p class="font-black text-navy">Skema biaya belum tersedia.</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Belum ada fee scheme aktif untuk kombinasi prodi dan program perkuliahan ini. Silakan hubungi konsultan PMB.</p>
                            <a href="{{ $whatsappUrl }}" class="mt-4 inline-flex rounded-full bg-gold px-5 py-3 text-sm font-black text-navy">Konsultasi biaya</a>
                        </div>

                        <div id="fee-selection-results" class="mt-6 grid gap-4 lg:grid-cols-2">
                            @foreach ($feeSummaries as $fee)
                                <div
                                    hidden
                                    data-fee-card
                                    data-study-program-id="{{ $fee['study_program_id'] }}"
                                    data-class-track-id="{{ $fee['class_track_id'] }}"
                                    class="rounded-3xl border border-slate-200 bg-slate-50 p-5"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-black text-sky-700">{{ $fee['model'] }}</p>
                                            <h4 class="mt-1 text-xl font-black text-navy">{{ $fee['program'] }}</h4>
                                            <p class="mt-1 text-sm font-semibold text-slate-500">{{ $fee['track'] }}</p>
                                        </div>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600">Biaya awal</span>
                                    </div>
                                    <div class="mt-5 grid gap-2 text-sm">
                                        @if ($fee['registration'] > 0)
                                            <div class="flex justify-between gap-4 rounded-2xl bg-white px-4 py-3">
                                                <span class="font-bold text-slate-600">Pendaftaran</span>
                                                <strong class="text-navy">Rp {{ number_format($fee['registration'], 0, ',', '.') }}</strong>
                                            </div>
                                        @endif
                                        @if ($fee['development'] > 0)
                                            <div class="flex justify-between gap-4 rounded-2xl bg-white px-4 py-3">
                                                <span class="font-bold text-slate-600">SPB / Development</span>
                                                <strong class="text-navy">Rp {{ number_format($fee['development'], 0, ',', '.') }}</strong>
                                            </div>
                                        @endif
                                        @if ($fee['tuition_per_semester'] > 0)
                                            <div class="flex justify-between gap-4 rounded-2xl bg-white px-4 py-3">
                                                <span class="font-bold text-slate-600">SPP per semester</span>
                                                <strong class="text-navy">Rp {{ number_format($fee['tuition_per_semester'], 0, ',', '.') }}</strong>
                                            </div>
                                        @endif
                                        @if ($fee['ukt'] > 0)
                                            <div class="flex justify-between gap-4 rounded-2xl bg-white px-4 py-3">
                                                <span class="font-bold text-slate-600">UKT</span>
                                                <strong class="text-navy">Rp {{ number_format($fee['ukt'], 0, ',', '.') }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                    @if ($fee['monthly_installment'])
                                        <div class="mt-5 flex items-center justify-between gap-4 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-4 text-navy">
                                            <span>
                                                <span class="block text-xs font-black uppercase tracking-wide text-sky-700">Estimasi per bulan</span>
                                                <span class="mt-1 block text-sm font-bold text-slate-600">Sesuai jadwal angsuran prodi dan program ini</span>
                                            </span>
                                            <strong class="shrink-0 text-xl font-black">Rp {{ number_format($fee['monthly_installment'], 0, ',', '.') }}</strong>
                                        </div>
                                    @endif
                                    <div class="mt-5 flex items-center justify-between gap-4 rounded-2xl bg-navy px-4 py-4 text-white">
                                        <span class="font-black">Total herregistrasi</span>
                                        <strong class="text-xl font-black">Rp {{ number_format($fee['heregistration'], 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>
        </section>

        <section class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-wide text-sky-700">Alur Pendaftaran</p>
                    <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Empat langkah sampai siap kuliah.</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-4">
                    @foreach ([['Isi Form', 'Lengkapi data awal PMB.'], ['Konsultasi', 'Tim PMB menghubungi via WhatsApp.'], ['Registrasi', 'Pilih biaya dan selesaikan pembayaran.'], ['Kuliah', 'Mulai kelas sesuai jadwal.']] as $index => $step)
                        <div class="reveal rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-100 text-lg font-black text-sky-700">{{ $index + 1 }}</span>
                            <h3 class="mt-5 text-xl font-black text-navy">{{ $step[0] }}</h3>
                            <p class="mt-2 leading-7 text-slate-600">{{ $step[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="galeri" class="bg-white px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-sky-700">Galeri Kampus</p>
                        <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Lihat suasana belajar dan aktivitas kampus.</h2>
                    </div>
                    <a href="#daftar" class="inline-flex rounded-full bg-gold px-6 py-3 font-black text-navy shadow-lg shadow-orange-500/20">Daftar PMB</a>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($gallery as $item)
                        @php
                            $galleryImage = filled($item['image_path'] ?? null)
                                ? Storage::url($item['image_path'])
                                : ($loop->iteration === 1
                                    ? 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=900&q=80'
                                    : ($loop->iteration === 2
                                        ? 'https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=900&q=80'
                                        : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80'));
                        @endphp
                        <article class="reveal group overflow-hidden rounded-[1.75rem] bg-slate-100 shadow-sm transition hover:-translate-y-1 hover:shadow-soft">
                            <div class="relative">
                                <img src="{{ $galleryImage }}" alt="{{ $item['title'] ?? 'Galeri kampus' }}" width="600" height="450" loading="lazy" decoding="async" class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/10 to-transparent"></div>
                                <div class="absolute bottom-0 left-0 right-0 p-5 text-white">
                                    <h3 class="text-xl font-black">{{ $item['title'] ?? 'Galeri Kampus' }}</h3>
                                    @if (filled($item['description'] ?? null))
                                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-100">{{ $item['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-white px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-sky-700">Testimoni Mahasiswa</p>
                        <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Cerita mahasiswa aktif.</h2>
                    </div>
                    <div class="hidden gap-2 sm:flex">
                        <button type="button" data-testimonial-prev class="grid h-11 w-11 place-items-center rounded-full border border-slate-200"><i data-lucide="chevron-left" class="h-5 w-5"></i></button>
                        <button type="button" data-testimonial-next class="grid h-11 w-11 place-items-center rounded-full border border-slate-200"><i data-lucide="chevron-right" class="h-5 w-5"></i></button>
                    </div>
                </div>
                <div class="overflow-hidden">
                    <div data-testimonial-track class="flex transition-transform duration-500 ease-out">
                        @foreach ($testimonials as $testimonial)
                            @php
                                $testimonialPhoto = filled($testimonial['photo_path'] ?? null)
                                    ? Storage::url($testimonial['photo_path'])
                                    : null;
                            @endphp
                            <article class="min-w-full px-1 md:min-w-[50%] lg:min-w-[33.333%]">
                                <div class="h-full rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6">
                                    <i data-lucide="quote" class="h-8 w-8 text-gold"></i>
                                    <p class="mt-5 leading-8 text-slate-700">{{ $testimonial['quote'] ?? '' }}</p>
                                    <div class="mt-6 flex items-center gap-3">
                                        @if ($testimonialPhoto)
                                            <img src="{{ $testimonialPhoto }}" alt="Foto {{ $testimonial['name'] ?? 'Mahasiswa' }}" width="56" height="56" loading="lazy" decoding="async" class="h-14 w-14 rounded-full border-2 border-white object-cover shadow-sm">
                                        @else
                                            <span class="grid h-14 w-14 place-items-center rounded-full bg-navy font-black text-white">{{ mb_substr($testimonial['name'] ?? 'M', 0, 1) }}</span>
                                        @endif
                                        <span>
                                            <strong class="block font-black text-navy">{{ $testimonial['name'] ?? '' }}</strong>
                                            <span class="text-sm font-semibold text-slate-500">{{ $testimonial['role'] ?? '' }}</span>
                                        </span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="berita" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-sky-700">Berita Kampus</p>
                        <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Info pendidikan terbaru.</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-news-prev class="grid h-11 w-11 place-items-center rounded-full border border-slate-200 bg-white text-navy shadow-sm"><i data-lucide="chevron-left" class="h-5 w-5"></i></button>
                        <button type="button" data-news-next class="grid h-11 w-11 place-items-center rounded-full border border-slate-200 bg-white text-navy shadow-sm"><i data-lucide="chevron-right" class="h-5 w-5"></i></button>
                        <a href="{{ route('campuses.news.index', ['campus' => $campus->slug]) }}" class="inline-flex rounded-full bg-gold px-6 py-3 font-black text-navy shadow-lg shadow-orange-500/20">Lihat Semua Berita</a>
                    </div>
                </div>

                @if ($educationNews->isEmpty())
                    <div class="reveal rounded-[2rem] border border-dashed border-slate-300 bg-white p-8 shadow-sm">
                        <h3 class="text-2xl font-black text-navy">Belum ada berita untuk kampus ini.</h3>
                        <p class="mt-2 text-slate-600">Berita akan tampil setelah admin menerbitkan post dan memilih kampus ini.</p>
                    </div>
                @else
                    <div class="overflow-hidden">
                        <div data-news-track class="flex transition-transform duration-700 ease-out">
                        @foreach ($educationNews as $news)
                            @php
                                $newsImage = $news->image_path ? Storage::url($news->image_path) : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80';
                            @endphp
                            <article class="min-w-full px-2 md:min-w-[50%] xl:min-w-[33.333%]">
                                <div class="reveal h-full overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-soft">
                                    <img src="{{ $newsImage }}" alt="{{ $news->title }}" width="640" height="400" loading="lazy" decoding="async" class="aspect-[16/10] w-full object-cover">
                                    <div class="p-6">
                                        <div class="flex flex-wrap gap-2">
                                            <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">{{ $news->category }}</span>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $news->published_at?->translatedFormat('d M Y') ?? 'Published' }}</span>
                                        </div>
                                        <h3 class="mt-4 text-xl font-black leading-tight text-navy">{{ $news->title }}</h3>
                                        <p class="mt-3 line-clamp-3 leading-7 text-slate-600">{{ $news->excerpt ?: str(strip_tags($news->content))->limit(150) }}</p>
                                        <a href="{{ route('news.show', $news) }}" class="mt-5 inline-flex rounded-full bg-navy px-4 py-2 text-sm font-black text-white">Baca Berita</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                        </div>
                    </div>
                    <div class="reveal mt-8 text-center">
                        <a href="{{ route('campuses.news.index', ['campus' => $campus->slug]) }}" class="inline-flex rounded-full border border-slate-200 bg-white px-7 py-4 font-black text-navy shadow-sm transition hover:-translate-y-1 hover:shadow-soft">Selengkapnya</a>
                    </div>
                @endif
            </div>
        </section>

        <section id="faq" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[.8fr_1.2fr]">
                <div class="reveal">
                    <p class="text-sm font-black uppercase tracking-wide text-sky-700">FAQ</p>
                    <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Pertanyaan yang sering ditanyakan.</h2>
                    <a href="{{ $whatsappUrl }}" class="mt-8 inline-flex rounded-full bg-gold px-7 py-4 font-black text-navy">Tanya Konsultan</a>
                </div>
                <div class="space-y-4">
                    @foreach ($faqs as $faq)
                        <details class="reveal group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-black text-navy">
                                {{ $faq['question'] ?? '' }}
                                <i data-lucide="plus" class="h-5 w-5 transition group-open:rotate-45"></i>
                            </summary>
                            <p class="mt-4 leading-7 text-slate-600">{{ $faq['answer'] ?? '' }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-navy px-4 py-12 text-white sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-8 md:grid-cols-[1.1fr_.7fr_.7fr]">
            <div>
                <div class="flex items-center gap-3">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo {{ $campus->name }}" width="48" height="48" loading="lazy" decoding="async" class="h-12 w-12 rounded-xl bg-white object-contain p-1">
                    @else
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-white font-black text-navy">{{ $campusInitial }}</span>
                    @endif
                    <strong class="text-xl font-black">{{ $campus->name }}</strong>
                </div>
                <p class="mt-5 max-w-md leading-7 text-sky-100">{{ $campus->address ?: 'Kampus Indonesia dengan layanan PMB online, kelas fleksibel, dan biaya kuliah terjangkau.' }}</p>
            </div>
            <div>
                <h3 class="font-black text-yellow-200">Menu</h3>
                <div class="mt-4 grid gap-3 text-sky-100">
                    <a href="#keunggulan">Keunggulan</a>
                    <a href="#program">Program Studi</a>
                    <a href="#jadwal">Jadwal Kuliah</a>
                    <a href="#biaya">Biaya Kuliah</a>
                    <a href="#galeri">Galeri</a>
                    <a href="#berita">Berita</a>
                    <a href="#faq">FAQ</a>
                </div>
            </div>
            <div>
                <h3 class="font-black text-yellow-200">Kontak</h3>
                <div class="mt-4 grid gap-3 text-sky-100">
                    <a href="{{ $whatsappUrl }}">WhatsApp PMB</a>
                    <span>{{ $campus->city }}{{ $campus->province ? ', '.$campus->province : '' }}</span>
                    <span class="flex gap-3 pt-2">
                        <i data-lucide="instagram" class="h-5 w-5"></i>
                        <i data-lucide="facebook" class="h-5 w-5"></i>
                        <i data-lucide="youtube" class="h-5 w-5"></i>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <a href="{{ $whatsappUrl }}" class="fixed bottom-5 right-5 z-50 inline-flex h-14 w-14 items-center justify-center rounded-full bg-green-500 text-white shadow-2xl shadow-green-600/30 transition hover:-translate-y-1" aria-label="WhatsApp PMB">
        <i data-lucide="message-circle" class="h-7 w-7"></i>
    </a>

    <script>
        const track = document.querySelector('[data-testimonial-track]');
        let slide = 0;
        const maxSlide = 2;

        function updateCarousel() {
            if (!track) return;
            track.style.transform = `translateX(-${slide * 100}%)`;
            if (window.innerWidth >= 1024) track.style.transform = `translateX(-${slide * 33.333}%)`;
            if (window.innerWidth >= 768 && window.innerWidth < 1024) track.style.transform = `translateX(-${slide * 50}%)`;
        }

        document.querySelector('[data-testimonial-next]')?.addEventListener('click', () => {
            slide = Math.min(slide + 1, maxSlide);
            updateCarousel();
        });

        document.querySelector('[data-testimonial-prev]')?.addEventListener('click', () => {
            slide = Math.max(slide - 1, 0);
            updateCarousel();
        });

        window.addEventListener('resize', updateCarousel);

        const newsTrack = document.querySelector('[data-news-track]');
        const newsCards = newsTrack ? Array.from(newsTrack.children) : [];
        let newsSlide = 0;

        function visibleNewsCards() {
            if (window.innerWidth >= 1280) return 3;
            if (window.innerWidth >= 768) return 2;
            return 1;
        }

        function updateNewsCarousel() {
            if (!newsTrack || newsCards.length === 0) return;

            const visible = visibleNewsCards();
            const maxNewsSlide = Math.max(newsCards.length - visible, 0);
            newsSlide = Math.min(newsSlide, maxNewsSlide);
            const cardWidth = newsCards[0]?.getBoundingClientRect().width || 0;
            newsTrack.style.transform = `translateX(-${newsSlide * cardWidth}px)`;
        }

        function nextNewsSlide() {
            if (!newsTrack || newsCards.length === 0) return;

            const visible = visibleNewsCards();
            const maxNewsSlide = Math.max(newsCards.length - visible, 0);
            newsSlide = newsSlide >= maxNewsSlide ? 0 : newsSlide + 1;
            updateNewsCarousel();
        }

        document.querySelector('[data-news-next]')?.addEventListener('click', nextNewsSlide);
        document.querySelector('[data-news-prev]')?.addEventListener('click', () => {
            const visible = visibleNewsCards();
            const maxNewsSlide = Math.max(newsCards.length - visible, 0);
            newsSlide = newsSlide <= 0 ? maxNewsSlide : newsSlide - 1;
            updateNewsCarousel();
        });

        let newsAutoSlide = setInterval(nextNewsSlide, 4200);
        newsTrack?.addEventListener('mouseenter', () => clearInterval(newsAutoSlide));
        newsTrack?.addEventListener('mouseleave', () => {
            newsAutoSlide = setInterval(nextNewsSlide, 4200);
        });
        window.addEventListener('resize', updateNewsCarousel);
        updateNewsCarousel();

        const programSearch = document.getElementById('program-search');
        const programItems = Array.from(document.querySelectorAll('[data-program-list-item]'));
        const programHeadings = Array.from(document.querySelectorAll('[data-program-group-heading]'));
        const programSearchEmpty = document.getElementById('program-search-empty');

        function refreshProgramList() {
            const term = programSearch?.value.toLowerCase().trim() || '';
            let visibleItems = 0;

            programItems.forEach((item) => {
                const matches = ! term || (item.dataset.programName || '').includes(term);
                item.hidden = ! matches;

                if (matches) {
                    visibleItems += 1;
                }
            });

            programHeadings.forEach((heading) => {
                const degree = heading.dataset.programGroupHeading || '';
                heading.hidden = ! programItems.some((item) => item.dataset.programDegree === degree && ! item.hidden);
            });

            if (programSearchEmpty) {
                programSearchEmpty.hidden = visibleItems > 0;
            }
        }

        programSearch?.addEventListener('input', refreshProgramList);
        refreshProgramList();

        const feeStudyProgram = document.getElementById('fee-study-program');
        const feeClassTrack = document.getElementById('fee-class-track');
        const feeCards = Array.from(document.querySelectorAll('[data-fee-card]'));
        const feeEmptyState = document.getElementById('fee-selection-empty');
        const feeNoResultState = document.getElementById('fee-selection-no-result');
        const allTrackOptions = feeClassTrack ? Array.from(feeClassTrack.options).slice(1) : [];

        function feeCardMatchesStudyProgram(card, studyProgramId) {
            const cardStudyProgramId = card.dataset.studyProgramId || '';

            return cardStudyProgramId === '' || cardStudyProgramId === studyProgramId;
        }

        function feeCardMatchesClassTrack(card, classTrackId) {
            const cardClassTrackId = card.dataset.classTrackId || '';

            return cardClassTrackId === '' || cardClassTrackId === classTrackId;
        }

        function refreshFeeTrackOptions() {
            if (!feeStudyProgram || !feeClassTrack) return;

            const selectedStudyProgram = feeStudyProgram.value;
            feeClassTrack.value = '';
            feeClassTrack.disabled = ! selectedStudyProgram;
            feeClassTrack.options[0].textContent = selectedStudyProgram ? 'Pilih program perkuliahan' : 'Pilih prodi terlebih dahulu';

            const matchingCards = feeCards.filter((card) => feeCardMatchesStudyProgram(card, selectedStudyProgram));
            const hasUniversalTrackFee = matchingCards.some((card) => (card.dataset.classTrackId || '') === '');
            const availableTrackIds = new Set(matchingCards.map((card) => card.dataset.classTrackId || '').filter(Boolean));

            allTrackOptions.forEach((option) => {
                option.hidden = selectedStudyProgram ? (! hasUniversalTrackFee && ! availableTrackIds.has(option.value)) : false;
            });
        }

        function refreshFeeCards() {
            if (!feeStudyProgram || !feeClassTrack) return;

            const selectedStudyProgram = feeStudyProgram.value;
            const selectedClassTrack = feeClassTrack.value;
            const isComplete = selectedStudyProgram && selectedClassTrack;
            let visibleCards = 0;

            feeCards.forEach((card) => {
                const isVisible = Boolean(isComplete)
                    && feeCardMatchesStudyProgram(card, selectedStudyProgram)
                    && feeCardMatchesClassTrack(card, selectedClassTrack);

                card.hidden = ! isVisible;

                if (isVisible) {
                    visibleCards += 1;
                }
            });

            if (feeEmptyState) {
                feeEmptyState.hidden = Boolean(isComplete);
            }

            if (feeNoResultState) {
                feeNoResultState.hidden = ! isComplete || visibleCards > 0;
            }
        }

        feeStudyProgram?.addEventListener('change', () => {
            refreshFeeTrackOptions();
            refreshFeeCards();
        });

        feeClassTrack?.addEventListener('change', refreshFeeCards);
        refreshFeeTrackOptions();
        refreshFeeCards();

        document.querySelectorAll('[data-program-modal-target]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.programModalTarget);
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            });
        });

        document.querySelectorAll('[data-program-modal-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('[data-program-modal]');
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            });
        });

        document.querySelectorAll('[data-program-modal]').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target !== modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            });
        });

        window.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('[data-program-modal]').forEach((modal) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
            document.body.classList.remove('overflow-hidden');
        });
    </script>
</body>
</html>
