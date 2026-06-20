@php
    use Illuminate\Support\Facades\Storage;

    $firstMonthFee = function ($feeScheme): ?int {
        $schedule = $feeScheme->uses_custom_installments
            ? ($feeScheme->custom_installment_schedule_json ?: $feeScheme->installment_schedule_json)
            : $feeScheme->installment_schedule_json;

        $firstMonth = collect($schedule ?: [])->first(fn ($row) => (int) ($row['month'] ?? 0) === 1);
        $total = (int) ($firstMonth['total'] ?? 0);

        if ($total > 0) {
            return $total;
        }

        $fallback = (int) ($feeScheme->total_initial_payment ?? 0);

        return $fallback > 0 ? $fallback : null;
    };

    $totalPrograms = $campuses->sum(fn ($campus) => $campus->studyPrograms->count());
    $lowestFee = $campuses->flatMap->feeSchemes
        ->map($firstMonthFee)
        ->filter()
        ->min() ?? 500000;
    $popularPrograms = $campuses->flatMap->studyPrograms
        ->pluck('name')
        ->filter()
        ->unique()
        ->take(8)
        ->values();
    $educationNews = $educationNews ?? collect();
    $canonicalUrl = rtrim(config('spmm.public_url', 'https://kampusmedia.cloud'), '/').'/';
    $seoTitle = 'Kampus Media | Portal Kuliah Karyawan, Online, Hybrid & RPL';
    $seoDescription = 'Temukan kampus terpercaya, program studi, biaya kuliah transparan, kelas karyawan, full online, hybrid learning, dan RPL di Kampus Media.';
    $seoImage = url('/images/social/logo%20kampus%20media.png');
    $whatsappNumber = '6282199976600';
    $whatsappMessage = rawurlencode('halo min, saya mendapat informasi dari kampus media (KAMI), saya ingin mengetahui informasi lebih lanjut');
    $whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$whatsappMessage}";
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="kampus media, kuliah karyawan, kuliah online, PMB online, daftar kuliah">
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#071a3d',
                        ink: '#102033',
                        gold: '#f59e0b',
                    },
                    boxShadow: {
                        soft: '0 24px 70px rgba(15, 23, 42, .12)',
                    },
                },
            },
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .hero-bg {
            background-image:
                radial-gradient(circle at 18% 16%, rgba(245, 158, 11, .20), transparent 26%),
                radial-gradient(circle at 82% 20%, rgba(56, 189, 248, .20), transparent 30%),
                linear-gradient(135deg, #071a3d 0%, #0b2d63 55%, #0b5e8e 100%);
        }
        .grid-pattern {
            background-image: linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 44px 44px;
        }
        .reveal { opacity: 0; transform: translateY(18px); transition: opacity .6s ease, transform .6s ease; }
        .reveal.is-visible { opacity: 1; transform: translateY(0); }
        .skeleton-loader.is-hidden { display: none; }
        .program-filter.is-active {
            border-color: rgb(125 211 252);
            background: rgb(224 242 254);
            color: rgb(12 74 110);
            box-shadow: 0 14px 30px rgba(2, 132, 199, .12);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="skeleton-loader fixed inset-0 z-[70] bg-white p-6">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="h-12 w-64 animate-pulse rounded-xl bg-slate-200"></div>
            <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
                <div class="h-[430px] animate-pulse rounded-3xl bg-slate-200"></div>
                <div class="h-[430px] animate-pulse rounded-3xl bg-slate-100"></div>
            </div>
        </div>
    </div>

    <nav class="sticky top-0 z-50 border-b border-white/10 bg-black/50 text-white shadow-lg shadow-slate-950/10 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="/images/social/logo%20kampus%20media.png" alt="Kampus Media" decoding="async" class="h-12 w-auto object-contain [filter:drop-shadow(0_0_1px_white)_drop-shadow(0_0_4px_rgba(255,255,255,.75))]">
            </a>
            <div class="hidden items-center gap-7 text-sm font-bold text-sky-50 md:flex">
                <a href="#kampus" class="hover:text-gold">Kampus</a>
                <a href="#layanan" class="hover:text-gold">Layanan</a>
                <a href="#berita" class="hover:text-gold">Berita</a>
                <a href="#alur" class="hover:text-gold">Alur</a>
                <a href="#faq" class="hover:text-gold">FAQ</a>
                <a href="{{ route('student-portal.login') }}" class="hover:text-gold">Login</a>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('student-portal.login') }}" class="hidden h-11 items-center rounded-full border border-white/20 px-5 text-sm font-black text-white transition hover:-translate-y-0.5 hover:bg-white/10 sm:inline-flex">
                    Login
                </a>
                <a href="{{ route('registration.create') }}" class="inline-flex h-11 items-center rounded-full bg-gold px-5 text-sm font-black text-navy shadow-lg shadow-orange-500/20 transition hover:-translate-y-0.5 hover:bg-yellow-400">
                    Sign Up
                </a>
            </div>
        </div>
    </nav>

    <header class="hero-bg relative overflow-hidden text-white">
        <div class="absolute inset-0 grid-pattern opacity-50"></div>
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"></div>
        <div class="relative mx-auto grid min-h-[calc(100vh-68px)] max-w-7xl items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1.08fr_.92fr] lg:px-8 lg:py-16">
            <section class="reveal">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold text-yellow-100 backdrop-blur">
                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                    Portal PMB kampus mitra se-Indonesia
                </div>
                <h1 class="max-w-4xl text-4xl font-black leading-[1.02] tracking-normal sm:text-5xl lg:text-7xl">
                    Masa Depan yang Lebih Baik<br>Dimulai dari Keputusan Hari Ini.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-sky-50 sm:text-xl">
                    Temukan kampus dan program kuliah yang membantumu meraih karier, penghasilan, dan masa depan yang lebih cerah.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#kampus" class="inline-flex items-center justify-center rounded-full bg-gold px-7 py-4 font-black text-navy shadow-xl shadow-orange-500/20 transition hover:-translate-y-1 hover:bg-yellow-400">
                        Jelajahi Kampus
                    </a>
                    <a href="{{ route('registration.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/25 bg-white/10 px-7 py-4 font-black text-white backdrop-blur transition hover:-translate-y-1 hover:bg-white/20">
                        <i data-lucide="send" class="h-5 w-5"></i>
                        Daftar Tanpa Pilih Kampus
                    </a>
                </div>
                <div class="mt-10 grid max-w-2xl grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ([[$campuses->count(), 'Kampus Mitra'], [$totalPrograms ?: 300, 'Program Studi'], ['24 Jam', 'Daftar Online'], ['Rp '.number_format($lowestFee, 0, ',', '.'), 'Biaya Mulai']] as $stat)
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                            <strong class="block text-2xl font-black text-yellow-200">{{ $stat[0] }}</strong>
                            <span class="mt-1 block text-xs font-bold text-sky-100">{{ $stat[1] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="reveal rounded-[2rem] border border-white/20 bg-white p-5 text-slate-900 shadow-2xl shadow-slate-950/25 sm:p-7">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-sky-700">Cari kampus</p>
                        <h2 class="mt-1 text-2xl font-black text-navy">Kampus mitra aktif</h2>
                    </div>
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-sm font-black text-sky-700">{{ $campuses->count() }} kampus</span>
                </div>
                <form id="campus-search-form" class="grid gap-2 text-sm font-bold">
                    Nama kampus, kota, atau provinsi
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"></i>
                        <input id="campus-search" class="h-13 w-full rounded-2xl border border-slate-200 bg-white py-4 pl-12 pr-14 outline-none ring-sky-500/20 transition focus:border-sky-500 focus:ring-4" placeholder="Contoh: Jakarta, STIE, Surabaya">
                        <button id="campus-search-button" type="submit" class="absolute right-2 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-xl bg-sky-600 text-white shadow-lg shadow-sky-600/20 transition hover:bg-sky-700" aria-label="Cari kampus">
                            <i data-lucide="arrow-right" class="h-5 w-5"></i>
                        </button>
                    </div>
                </form>
                <div class="mt-5 grid grid-cols-2 gap-3 text-sm font-bold text-slate-600">
                    @foreach ([['briefcase', 'Kuliah Karyawan', 'kuliah karyawan'], ['monitor-smartphone', 'Full Online', 'full online'], ['shuffle', 'Hybrid Learning', 'hybrid learning'], ['badge-check', 'RPL', 'rpl']] as $tag)
                        <button type="button" data-program-filter="{{ $tag[2] }}" class="program-filter flex items-center gap-3 rounded-2xl border border-transparent bg-slate-50 p-4 text-left transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-50">
                            <i data-lucide="{{ $tag[0] }}" class="h-5 w-5 text-sky-700"></i>
                            <span>{{ $tag[1] }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-5 rounded-2xl bg-gradient-to-r from-yellow-50 to-orange-50 p-4">
                    <p class="font-black text-navy">Promo beasiswa PMB</p>
                    <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">Konsultasi gratis untuk cek biaya, jadwal kuliah, dan kampus yang paling cocok.</p>
                </div>
            </section>
        </div>
    </header>

    <main>
        <section class="-mt-8 px-4 sm:px-6 lg:px-8">
            <div class="reveal mx-auto grid max-w-7xl gap-4 rounded-[2rem] bg-white p-4 shadow-soft sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([['map-pin', 'Kampus terpercaya'], ['wallet-cards', 'Terdaftar PDDIKTI'], ['message-circle', 'Terakreditasi BAN-PT/LAM'], ['file-check-2', 'Ijazah Terverifikasi']] as $item)
                    <div class="flex items-center gap-4 rounded-3xl bg-slate-50 p-5">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-100 text-sky-700"><i data-lucide="{{ $item[0] }}" class="h-6 w-6"></i></span>
                        <strong class="text-base font-black text-navy">{{ $item[1] }}</strong>
                    </div>
                @endforeach
            </div>
        </section>

        <section id="layanan" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-wide text-sky-700">Layanan Utama</p>
                    <h2 class="mt-3 text-3xl font-black tracking-normal text-navy sm:text-5xl">Satu platform untuk menemukan kuliah yang tepat dan mempercepat karier Anda !</h2>
                </div>
                <div class="grid gap-5 md:grid-cols-3">
                    @foreach ([['graduation-cap', 'Cari Kampus & Program Studi', 'Temukan kampus terbaik, program studi sesuai minat, biaya kuliah, jadwal kelas, serta pilihan kelas karyawan, online, dan RPL dalam satu platform.'], ['book-open', 'Kuliah Fleksibel Sesuai Kesibukan', 'Pilih jalur pendidikan yang sesuai dengan kondisi Anda, mulai dari kelas karyawan, kuliah online, hybrid learning, hingga Rekognisi Pembelajaran Lampau (RPL).'], ['rocket', 'Pengembangan Karier & Masa Depan', 'Dapatkan informasi peluang karier, peningkatan kompetensi, sertifikasi, dan pendidikan lanjutan untuk membantu mencapai tujuan profesional Anda.']] as $service)
                        <article class="reveal rounded-[1.75rem] border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-soft">
                            <span class="grid h-13 w-13 place-items-center rounded-2xl bg-sky-50 text-sky-700"><i data-lucide="{{ $service[0] }}" class="h-7 w-7"></i></span>
                            <h3 class="mt-5 text-xl font-black text-navy">{{ $service[1] }}</h3>
                            <p class="mt-3 leading-7 text-slate-600">{{ $service[2] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-white px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-[.95fr_1.05fr]">
                <div class="reveal relative">
                    <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1200&q=80" alt="Mahasiswa mencari kampus" class="aspect-[4/3] w-full rounded-[2rem] object-cover shadow-soft">
                    <div class="absolute -bottom-5 left-5 right-5 rounded-3xl bg-white p-5 shadow-soft sm:left-auto sm:w-80">
                        <p class="text-sm font-black text-sky-700">PMB online</p>
                        <strong class="mt-1 block text-2xl font-black text-navy">Cari kampus tanpa ribet</strong>
                    </div>
                </div>
                <div class="reveal">
                    <p class="text-sm font-black uppercase tracking-wide text-sky-700">Program Populer</p>
                    <h2 class="mt-3 text-3xl font-black tracking-normal text-navy sm:text-5xl">Banyak pilihan jurusan untuk karier masa depan.</h2>
                    <div class="mt-6 flex flex-wrap gap-3">
                        @forelse ($popularPrograms as $program)
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-black text-slate-700">{{ $program }}</span>
                        @empty
                            @foreach (['Manajemen', 'Akuntansi', 'Teknik Informatika', 'Sistem Informasi', 'Bisnis Digital', 'Ilmu Hukum', 'Psikologi', 'Ilmu Komunikasi'] as $program)
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-black text-slate-700">{{ $program }}</span>
                            @endforeach
                        @endforelse
                    </div>
                    <a href="#kampus" class="mt-8 inline-flex rounded-full bg-gold px-7 py-4 font-black text-navy shadow-lg shadow-orange-500/20 transition hover:-translate-y-1 hover:bg-yellow-400">Lihat Kampus</a>
                </div>
            </div>
        </section>

        <section id="kampus" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-sky-700">Kampus Mitra</p>
                        <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Pilih kampus tujuan.</h2>
                    </div>
                    <a href="{{ route('registration.create') }}" class="inline-flex rounded-full border border-slate-200 bg-white px-6 py-3 font-black text-navy shadow-sm transition hover:-translate-y-1">Daftar Tanpa Pilih Kampus</a>
                </div>

                @if ($campuses->isEmpty())
                    <div class="reveal rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 shadow-sm">
                        <h3 class="text-2xl font-black text-navy">Belum ada kampus aktif.</h3>
                        <p class="mt-2 text-slate-600">Tambahkan kampus, prodi, program perkuliahan, dan fee scheme dari dashboard admin.</p>
                    </div>
                @else
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($campuses as $campus)
                            @php
                                $logoUrl = $campus->logo_path ? Storage::url($campus->logo_path) : null;
                                $minFee = $campus->feeSchemes->map($firstMonthFee)->filter()->min();
                                $programTrackText = strtolower($campus->classTracks->pluck('name')->join(' '));
                            @endphp
                            <article class="reveal group rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-soft" data-campus-card data-programs="{{ $programTrackText }}" data-search="{{ strtolower($campus->name.' '.$campus->city.' '.$campus->province.' '.$campus->studyPrograms->pluck('name')->join(' ').' '.$programTrackText) }}">
                                <div class="flex items-start gap-4">
                                    @if ($logoUrl)
                                        <img class="h-16 w-16 rounded-2xl border border-slate-200 bg-white object-contain p-1" src="{{ $logoUrl }}" alt="Logo {{ $campus->name }}">
                                    @else
                                        <div class="grid h-16 w-16 place-items-center rounded-2xl bg-navy text-2xl font-black text-white">{{ mb_substr($campus->name, 0, 1) }}</div>
                                    @endif
                                    <div>
                                        <h3 class="text-xl font-black text-navy">{{ $campus->name }}</h3>
                                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ $campus->city ?: 'Kota belum diisi' }}{{ $campus->province ? ', '.$campus->province : '' }}</p>
                                    </div>
                                </div>
                                <div class="mt-6 grid grid-cols-3 gap-2 text-center">
                                    <div class="rounded-2xl bg-slate-50 p-3">
                                        <p class="text-xs font-bold text-slate-500">Prodi</p>
                                        <p class="text-lg font-black text-navy">{{ $campus->studyPrograms->count() }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 p-3">
                                        <p class="text-xs font-bold text-slate-500">Program</p>
                                        <p class="text-lg font-black text-navy">{{ $campus->classTracks->count() }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 p-3">
                                        <p class="text-xs font-bold text-slate-500">Mulai</p>
                                        <p class="text-sm font-black text-navy">{{ $minFee ? 'Rp '.number_format($minFee, 0, ',', '.') : '-' }}</p>
                                    </div>
                                </div>
                                <div class="mt-6 flex gap-3">
                                    <a class="flex-1 rounded-full border border-slate-200 px-4 py-3 text-center text-sm font-black text-navy transition group-hover:border-sky-300" href="{{ route('campuses.show', ['campus' => $campus->name]) }}">Kunjungi</a>
                                    <a class="flex-1 rounded-full bg-sky-600 px-4 py-3 text-center text-sm font-black text-white transition hover:bg-sky-700" href="{{ route('registration.create', ['kampus' => $campus->slug]) }}">Daftar</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section id="alur" class="bg-navy px-4 py-16 text-white sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-wide text-yellow-200">Alur PMB</p>
                    <h2 class="mt-3 text-3xl font-black sm:text-5xl">Daftar kuliah lebih jelas dari awal.</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-4">
                    @foreach ([['Pilih Kampus', 'Cari kampus dan program studi.'], ['Cek Biaya', 'Bandingkan biaya awal dan cicilan.'], ['Daftar Online', 'Isi form dan dapatkan invoice.'], ['Pemberkasan', 'Lengkapi data setelah pembayaran.']] as $index => $step)
                        <div class="reveal rounded-[1.75rem] border border-white/15 bg-white/10 p-6 backdrop-blur">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gold text-lg font-black text-navy">{{ $index + 1 }}</span>
                            <h3 class="mt-5 text-xl font-black">{{ $step[0] }}</h3>
                            <p class="mt-2 leading-7 text-sky-50">{{ $step[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="berita" class="bg-white px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-sky-700">Berita Pendidikan</p>
                        <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Update dunia kampus dan karier.</h2>
                    </div>
                    <a href="{{ route('news.index') }}" class="inline-flex rounded-full bg-gold px-6 py-3 font-black text-navy shadow-lg shadow-orange-500/20">Lihat Semua Berita</a>
                </div>

                @if ($educationNews->isEmpty())
                    <div class="reveal rounded-[2rem] border border-dashed border-slate-300 bg-slate-50 p-8">
                        <h3 class="text-2xl font-black text-navy">Belum ada berita pendidikan.</h3>
                        <p class="mt-2 text-slate-600">Tambahkan berita dari admin pada menu Portal Publik &gt; Berita Pendidikan.</p>
                    </div>
                @else
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($educationNews as $news)
                            @php
                                $newsImage = $news->image_path ? Storage::url($news->image_path) : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80';
                            @endphp
                            <article class="reveal overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-soft">
                                <img src="{{ $newsImage }}" alt="{{ $news->title }}" class="aspect-[16/10] w-full object-cover">
                                <div class="p-6">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">{{ $news->category }}</span>
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $news->campuses->pluck('name')->take(2)->join(', ') ?: ($news->campus?->name ?? 'Umum') }}</span>
                                    </div>
                                    <h3 class="mt-4 text-xl font-black leading-tight text-navy">{{ $news->title }}</h3>
                                    <p class="mt-3 line-clamp-3 leading-7 text-slate-600">{{ $news->excerpt ?: str(strip_tags($news->content))->limit(150) }}</p>
                                    <div class="mt-5 flex items-center justify-between gap-3">
                                        <span class="text-xs font-bold text-slate-500">{{ $news->published_at?->translatedFormat('d M Y') ?? 'Belum dijadwalkan' }}</span>
                                        <a href="{{ route('news.show', $news) }}" class="rounded-full bg-navy px-4 py-2 text-sm font-black text-white">Baca</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="reveal mt-8 text-center">
                        <a href="{{ route('news.index') }}" class="inline-flex rounded-full border border-slate-200 bg-white px-7 py-4 font-black text-navy shadow-sm transition hover:-translate-y-1 hover:shadow-soft">Selengkapnya</a>
                    </div>
                @endif
            </div>
        </section>

        <section id="faq" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[.8fr_1.2fr]">
                <div class="reveal">
                    <p class="text-sm font-black uppercase tracking-wide text-sky-700">FAQ</p>
                    <h2 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Pertanyaan calon mahasiswa.</h2>
                    <a href="{{ route('registration.create') }}" class="mt-8 inline-flex rounded-full bg-gold px-7 py-4 font-black text-navy">Mulai Daftar</a>
                </div>
                <div class="space-y-4">
                    @foreach ([['Apakah bisa memilih kampus dulu sebelum daftar?', 'Bisa. Kamu bisa membuka kartu kampus, melihat detail prodi dan biaya, lalu daftar dari kampus tersebut.'], ['Apakah biaya bisa dicicil?', 'Bisa, tergantung fee scheme dan program perkuliahan yang tersedia di kampus.'], ['Apakah ada kelas online atau hybrid?', 'Ada untuk kampus dan program tertentu. Cek pilihan program perkuliahan di halaman kampus.']] as $faq)
                        <details class="reveal group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-black text-navy">
                                {{ $faq[0] }}
                                <i data-lucide="plus" class="h-5 w-5 transition group-open:rotate-45"></i>
                            </summary>
                            <p class="mt-4 leading-7 text-slate-600">{{ $faq[1] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/10 bg-navy/90 px-4 py-12 text-white shadow-2xl shadow-slate-950/20 backdrop-blur-xl sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col justify-between gap-6 md:flex-row md:items-center">
            <div>
                <img src="/images/social/logo%20kampus%20media.png" alt="Kampus Media" loading="lazy" decoding="async" class="h-14 w-auto object-contain [filter:drop-shadow(0_0_1px_white)_drop-shadow(0_0_4px_rgba(255,255,255,.75))]">
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @foreach ([['facebook', 'Facebook'], ['instagram', 'Instagram'], ['tiktok', 'TikTok'], ['youtube', 'YouTube']] as $social)
                    <a href="#" aria-label="{{ $social[1] }} Kampus Media" class="grid h-11 w-11 place-items-center rounded-2xl border border-white/40 bg-white shadow-lg shadow-slate-950/10 transition hover:-translate-y-1 hover:bg-sky-50">
                        <img src="/images/social/{{ $social[0] }}.png" alt="{{ $social[1] }}" loading="lazy" decoding="async" class="h-6 w-6 object-contain">
                    </a>
                @endforeach
            </div>
        </div>
    </footer>

    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="fixed bottom-5 right-5 z-50 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white p-1 shadow-2xl shadow-green-600/30 transition hover:-translate-y-1" aria-label="WhatsApp Kampus Media">
        <img src="/images/social/whatsapp.png" alt="WhatsApp" loading="lazy" decoding="async" class="h-full w-full object-contain">
    </a>

    <script>
        window.addEventListener('load', () => {
            document.querySelector('.skeleton-loader')?.classList.add('is-hidden');
        });

        lucide.createIcons();

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) entry.target.classList.add('is-visible');
            });
        }, { threshold: 0.12 });

        document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));

        const searchForm = document.getElementById('campus-search-form');
        const search = document.getElementById('campus-search');
        const campusSection = document.getElementById('kampus');
        const filterButtons = Array.from(document.querySelectorAll('[data-program-filter]'));
        const cards = Array.from(document.querySelectorAll('[data-campus-card]'));
        const selectedPrograms = new Set();
        const programAliases = {
            'kuliah karyawan': ['kuliah karyawan', 'kelas karyawan', 'professional', 'profesional', 'program kuliah professional'],
            'full online': ['full online', 'online'],
            'hybrid learning': ['hybrid learning', 'hybird learning', 'hybrid', 'hybird'],
            'rpl': ['rpl', 'rekognisi pembelajaran lampau'],
        };

        const applyCampusFilters = () => {
            const term = search?.value.toLowerCase().trim() || '';
            const activePrograms = Array.from(selectedPrograms);

            cards.forEach((card) => {
                const searchableText = card.dataset.search || '';
                const programText = card.dataset.programs || '';
                const matchesSearch = ! term || searchableText.includes(term);
                const matchesProgram = activePrograms.length === 0 || activePrograms.some((program) => {
                    return (programAliases[program] || [program]).some((alias) => programText.includes(alias));
                });

                card.hidden = ! (matchesSearch && matchesProgram);
            });
        };

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.programFilter;

                if (selectedPrograms.has(filter)) {
                    selectedPrograms.delete(filter);
                    button.classList.remove('is-active');
                } else {
                    selectedPrograms.add(filter);
                    button.classList.add('is-active');
                }

                applyCampusFilters();
            });
        });

        search?.addEventListener('input', applyCampusFilters);
        searchForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            applyCampusFilters();
            campusSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>
</body>
</html>
