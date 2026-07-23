@php
    $seoTitle = 'Program Affiliate';
    $seoDescription = 'Program Affiliate Kampus Media untuk calon Affiliator umum dan mahasiswa yang ingin mereferensikan calon mahasiswa melalui link referral resmi.';
    $affiliateHomeUrl = request()->getHost() === 'affiliate.kampus.media' ? rtrim(url('/'), '/').'/' : route('affiliate.public');
    $canonicalUrl = request()->getHost() === 'affiliate.kampus.media' ? url('/') : url('/affiliate');
    $affiliateRegisterUrl = request()->getHost() === 'affiliate.kampus.media' ? url('/daftar') : route('affiliate.register');
    $affiliateLoginUrl = request()->getHost() === 'affiliate.kampus.media' ? url('/login') : 'https://affiliate.kampus.media/login';
    $studentAffiliateUrl = route('student-portal.affiliate');
    $pmbRegistrationUrl = rtrim(config('spmm.public_url', 'https://kampus.media'), '/').'/daftar';

    if (! empty($affiliateReferralCode)) {
        $pmbRegistrationUrl .= '?ref='.urlencode($affiliateReferralCode);
    }

    $benefits = [
        'Komisi Rp500.000 setelah herregistrasi tervalidasi',
        'Bonus Rp500.000 setelah SPP+SPB atau UKT semester 1 lunas',
        'Gratis bergabung dan mendapatkan link referral pribadi',
        'Dashboard untuk memantau lead, registrasi, herregistrasi, dan komisi',
    ];

    $advantages = [
        ['coins', 'Komisi Transparan', 'Nominal dan status komisi ditampilkan jelas berdasarkan tahapan pembayaran mahasiswa.'],
        ['link', 'Link Referral Pribadi', 'Affiliator mendapatkan kode unik seperti aff001 untuk melacak pendaftar.'],
        ['chart-no-axes-combined', 'Dashboard Monitoring', 'Pantau calon mahasiswa, status herregistrasi, kelayakan fee, dan bukti pembayaran.'],
        ['shield-check', 'Pembayaran Tervalidasi', 'Komisi dibayarkan setelah pembayaran mahasiswa dan bukti transfer diverifikasi tim.'],
    ];

    $steps = [
        ['Lead', 'Bagikan link referral ke media sosial, relasi, komunitas, atau calon mahasiswa.'],
        ['Registrasi', 'Calon mahasiswa mengisi form PMB dan membayar formulir pendaftaran.'],
        ['Herregistrasi', 'Mahasiswa membayar tagihan awal kuliah sesuai rincian biaya aktif.'],
        ['Komisi Cair', 'Affiliator menerima komisi setelah status pembayaran valid dan diproses keuangan.'],
    ];

    $faqs = [
        ['Apa bedanya affiliate dan affiliator?', 'Affiliate adalah nama programnya. Affiliator adalah orang yang mendaftar dan aktif membagikan link referral.'],
        ['Siapa yang bisa bergabung?', 'Umum non mahasiswa dapat daftar melalui form affiliator umum. Mahasiswa terdaftar tetap memakai akses affiliate mahasiswa di portal mahasiswa.'],
        ['Kapan komisi bisa cair?', 'Komisi pertama layak cair setelah herregistrasi tervalidasi. Komisi tambahan layak cair setelah semester 1 lunas sesuai aturan sistem.'],
    ];
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }} | Kampus Media</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Kampus Media">
    <meta property="og:title" content="{{ $seoTitle }} | Kampus Media">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ url('/images/social/logo%20kampus%20media.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#071a3d',
                        ink: '#0b1634',
                        tealx: '#0797a5',
                        mint: '#ebfbfd',
                    },
                    boxShadow: {
                        soft: '0 24px 70px rgba(15, 23, 42, .12)',
                        card: '0 14px 38px rgba(15, 23, 42, .10)',
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-white text-ink antialiased">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur-xl sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-5">
            <a href="{{ $affiliateHomeUrl }}" class="flex items-center gap-3">
                <img src="/images/social/logo%20kampus%20media.png" alt="Kampus Media" class="h-8 w-auto sm:h-10">
                <span>
                    <strong class="block text-base font-black leading-tight text-navy sm:text-lg">Kampus Media</strong>
                    <small class="hidden text-sm font-bold text-tealx sm:block">Affiliate Program</small>
                </span>
            </a>

            <nav class="hidden items-center gap-6 text-sm font-bold text-navy lg:flex">
                <a href="#tentang" class="hover:text-tealx">Tentang</a>
                <a href="#syarat" class="hover:text-tealx">Syarat & Cara Daftar</a>
                <a href="#komisi" class="hover:text-tealx">Cara Raih Komisi</a>
                <a href="#promosi" class="hover:text-tealx">Cara Promosi</a>
                <a href="#faq" class="hover:text-tealx">FAQ</a>
                <a href="#kontak" class="hover:text-tealx">Hubungi Kami</a>
            </nav>

            <a href="{{ $affiliateLoginUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-navy px-5 py-3 text-sm font-black text-white shadow-lg shadow-slate-900/15">
                <i data-lucide="user" class="h-4 w-4"></i>
                Login
            </a>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden px-4 pb-10 pt-8 sm:px-6 lg:px-8 lg:pb-12 lg:pt-10">
            <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-[.94fr_1.06fr]">
                <div>
                    <h1 class="max-w-3xl text-4xl font-black leading-tight tracking-normal text-navy sm:text-5xl lg:text-6xl">
                        Raih Komisi dengan Berbagi Informasi Kuliah
                    </h1>
                    <p class="mt-4 max-w-2xl text-base font-semibold leading-7 text-slate-600 lg:text-lg">
                        Gabung menjadi affiliator Kampus Media dan bantu calon mahasiswa menemukan kampus pilihan melalui link referral resmi.
                    </p>

                    <ul class="mt-5 grid gap-2 text-sm font-bold text-navy lg:text-base">
                        @foreach ($benefits as $benefit)
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-tealx text-white">
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                </span>
                                <span>{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6 flex flex-wrap gap-4">
                        <a href="{{ $affiliateRegisterUrl }}" class="inline-flex items-center gap-3 rounded-lg bg-navy px-5 py-3.5 font-black text-white shadow-xl shadow-slate-900/20 transition hover:-translate-y-1">
                            <i data-lucide="users" class="h-5 w-5"></i>
                            Affiliator Umum
                            <i data-lucide="arrow-right" class="h-5 w-5"></i>
                        </a>
                        <a href="{{ $studentAffiliateUrl }}" class="inline-flex items-center gap-3 rounded-lg border-2 border-tealx bg-white px-5 py-3.5 font-black text-tealx shadow-sm transition hover:-translate-y-1">
                            <i data-lucide="graduation-cap" class="h-5 w-5"></i>
                            Affiliator Mahasiswa
                            <i data-lucide="arrow-right" class="h-5 w-5"></i>
                        </a>
                    </div>
                </div>

                <div class="relative min-h-[360px] overflow-hidden rounded-bl-[4rem] bg-mint lg:min-h-[455px]">
                    <img
                        src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1400&q=85"
                        alt="Mahasiswa berdiskusi menggunakan laptop untuk program affiliate Kampus Media"
                        class="absolute inset-0 h-full w-full object-cover"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-white via-white/15 to-transparent"></div>
                    <div class="absolute left-6 top-8 rounded-lg bg-tealx p-4 text-white shadow-card">
                        <span class="block text-xs font-bold">Komisi Layak Cair</span>
                        <strong class="mt-1 block text-2xl font-black">Rp 500.000</strong>
                    </div>
                    <div class="absolute bottom-6 left-6 right-6 rounded-lg border border-white/70 bg-white/90 p-5 shadow-card backdrop-blur">
                        <p class="text-sm font-black text-tealx">Form PMB Referral</p>
                        <p class="mt-1 text-lg font-black text-navy">Lead masuk otomatis tercatat dengan kode affiliator.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="tentang" class="px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="text-center">
                    <h2 class="text-3xl font-black text-navy sm:text-4xl">Kenapa Bergabung?</h2>
                </div>
                <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    @foreach ($advantages as $item)
                        <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-card">
                            <span class="grid h-16 w-16 place-items-center rounded-full bg-tealx text-white">
                                <i data-lucide="{{ $item[0] }}" class="h-8 w-8"></i>
                            </span>
                            <h3 class="mt-5 text-lg font-black text-navy">{{ $item[1] }}</h3>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $item[2] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="komisi" class="bg-mint px-4 py-14 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <h2 class="text-center text-3xl font-black text-navy sm:text-4xl">Alur Program</h2>
                <div class="mt-9 grid gap-5 lg:grid-cols-4">
                    @foreach ($steps as $index => $step)
                        <article class="relative rounded-lg bg-white p-6 shadow-sm">
                            <span class="absolute -top-3 left-5 grid h-8 w-8 place-items-center rounded-full bg-tealx text-sm font-black text-white">{{ $index + 1 }}</span>
                            <div class="grid h-16 w-16 place-items-center rounded-full border border-slate-200 bg-white text-navy">
                                <i data-lucide="{{ ['megaphone', 'clipboard-check', 'landmark', 'wallet'][$index] }}" class="h-8 w-8"></i>
                            </div>
                            <h3 class="mt-5 text-lg font-black text-navy">{{ $step[0] }}</h3>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $step[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="syarat" class="px-4 py-16 sm:px-6 lg:px-8">
            <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[.9fr_1.1fr] lg:items-center">
                <div>
                    <p class="text-sm font-black uppercase tracking-wide text-tealx">Syarat & Cara Daftar</p>
                    <h2 class="mt-3 text-3xl font-black leading-tight text-navy sm:text-5xl">Semua bisa jadi affiliator Kampus Media.</h2>
                    <p class="mt-5 text-lg font-semibold leading-8 text-slate-600">
                        Affiliate adalah program. Affiliator adalah orang yang mendaftar dan membagikan link referral resmi. Pendaftaran umum wajib memakai data KTP dan rekening atas nama sendiri.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-4">
                        <a href="{{ $affiliateRegisterUrl }}" class="inline-flex rounded-lg bg-tealx px-6 py-4 font-black text-white shadow-lg shadow-teal-700/20">Daftar Sekarang</a>
                        <a href="{{ $affiliateLoginUrl }}" class="inline-flex rounded-lg border border-slate-200 px-6 py-4 font-black text-navy">Login Dashboard</a>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([['Data jelas', 'Nama, email, WhatsApp, alamat KTP, dan rekening pribadi wajib lengkap.'], ['Verifikasi email', 'Akun aktif setelah affiliator melakukan verifikasi email.'], ['Upload KTP', 'KTP dipakai untuk validasi identitas sebelum komisi diproses.'], ['Dashboard aktif', 'Affiliator dapat melihat referral, status mahasiswa, dan bukti transfer.']] as $card)
                        <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-xl font-black text-navy">{{ $card[0] }}</h3>
                            <p class="mt-3 font-semibold leading-7 text-slate-600">{{ $card[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="promosi" class="border-y border-slate-200 bg-slate-50 px-4 py-16 sm:px-6 lg:px-8">
            <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-3">
                <div class="lg:col-span-1">
                    <p class="text-sm font-black uppercase tracking-wide text-tealx">Cara Promosi</p>
                    <h2 class="mt-3 text-3xl font-black text-navy">Bagikan link, kawal prosesnya.</h2>
                </div>
                <div class="grid gap-5 sm:grid-cols-3 lg:col-span-2">
                    @foreach ([['Share', 'Sebarkan link referral ke calon mahasiswa yang membutuhkan informasi kuliah.'], ['Kawal', 'Bantu calon mahasiswa memahami alur PMB, registrasi, dan herregistrasi.'], ['Pantau', 'Lihat perkembangan melalui dashboard affiliator secara mandiri.']] as $item)
                        <article class="rounded-lg bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-black text-navy">{{ $item[0] }}</h3>
                            <p class="mt-3 text-sm font-semibold leading-6 text-slate-600">{{ $item[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="faq" class="px-4 py-16 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl">
                <h2 class="text-center text-3xl font-black text-navy sm:text-4xl">FAQ</h2>
                <div class="mt-8 divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white shadow-sm">
                    @foreach ($faqs as $faq)
                        <article class="p-6">
                            <h3 class="text-lg font-black text-navy">{{ $faq[0] }}</h3>
                            <p class="mt-2 font-semibold leading-7 text-slate-600">{{ $faq[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="kontak" class="bg-navy px-4 py-14 text-white sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-7xl flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-wide text-cyan-100">Mulai Sekarang</p>
                    <h2 class="mt-2 text-3xl font-black">Siap membagikan informasi kuliah?</h2>
                    <p class="mt-3 max-w-2xl font-semibold leading-7 text-sky-100">Daftar sebagai affiliator umum atau masuk sebagai mahasiswa untuk mendapatkan link referral resmi.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ $affiliateRegisterUrl }}" class="inline-flex rounded-lg bg-white px-6 py-4 font-black text-navy">Daftar Affiliator Umum</a>
                    <a href="{{ $pmbRegistrationUrl }}" class="inline-flex rounded-lg border border-white/30 px-6 py-4 font-black text-white">Form PMB</a>
                </div>
            </div>
        </section>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
