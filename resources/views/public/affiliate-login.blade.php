@php
    $seoTitle = 'Login Affiliate';
    $seoDescription = 'Login Affiliate Kampus Media untuk Affiliator Umum dan Affiliate Mahasiswa.';
    $canonicalUrl = url('/login');
    $affiliateHomeUrl = request()->getHost() === 'affiliate.kampus.media' ? rtrim(url('/'), '/').'/' : route('affiliate.public');
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }} | Kampus Media</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { navy: '#071a3d', gold: '#f59e0b', tealx: '#0f766e' },
                    boxShadow: { soft: '0 24px 70px rgba(15, 23, 42, .12)' },
                },
            },
        }
    </script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white px-4 py-3 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
            <a href="{{ $affiliateHomeUrl }}" class="flex items-center gap-3">
                <img src="/images/social/logo%20kampus%20media.png" alt="Kampus Media" class="h-11 w-auto">
                <span>
                    <strong class="block text-base font-black text-navy">Kampus Media</strong>
                    <small class="block text-xs font-bold text-slate-500">Login Affiliate</small>
                </span>
            </a>
            <a href="{{ url('/daftar') }}" class="rounded-full bg-tealx px-4 py-3 text-sm font-black text-white">Daftar Umum</a>
        </div>
    </header>

    <main class="px-4 py-10 sm:px-6 lg:px-8">
        <section class="mx-auto max-w-6xl">
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-wide text-tealx">Pilih akses affiliate</p>
                <h1 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Masuk ke dashboard sesuai jenis affiliator.</h1>
                <p class="mt-4 leading-8 text-slate-600">Affiliator Umum login memakai email dan password yang dibuat saat pendaftaran. Affiliate Mahasiswa tetap masuk melalui akun mahasiswa.</p>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1.05fr_.95fr]">
                <form method="POST" action="{{ url('/login') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-soft sm:p-8">
                    @csrf
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-teal-100 text-tealx">
                        <i data-lucide="users" class="h-7 w-7"></i>
                    </span>
                    <h2 class="mt-5 text-2xl font-black text-navy">Login Affiliator Umum</h2>
                    <p class="mt-2 leading-7 text-slate-600">Untuk non mahasiswa yang sudah mendaftar, membuat password, dan verifikasi email affiliate.</p>

                    @if (session('status'))
                        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-800">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="mt-6 grid gap-4">
                        <label class="grid gap-2 text-sm font-bold text-slate-700">
                            Email
                            <input type="email" name="email" value="{{ old('email') }}" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                        </label>
                        <label class="grid gap-2 text-sm font-bold text-slate-700">
                            Password
                            <input type="password" name="password" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                        </label>
                    </div>

                    <button class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full bg-tealx px-6 py-4 font-black text-white shadow-lg shadow-teal-700/20">
                        <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                        Masuk Dashboard Umum
                    </button>
                    <a href="{{ url('/lupa-password') }}" class="mt-4 inline-flex w-full items-center justify-center text-sm font-black text-tealx">
                        Lupa password?
                    </a>
                </form>

                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-amber-100 text-amber-700">
                        <i data-lucide="graduation-cap" class="h-7 w-7"></i>
                    </span>
                    <h2 class="mt-5 text-2xl font-black text-navy">Affiliate Mahasiswa</h2>
                    <p class="mt-2 leading-7 text-slate-600">Untuk mahasiswa terdaftar yang ingin melihat affiliate dari dashboard mahasiswa.</p>
                    <a href="{{ $studentLoginUrl }}" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full bg-navy px-6 py-4 font-black text-white shadow-lg shadow-slate-700/20">
                        <i data-lucide="log-in" class="h-5 w-5"></i>
                        Login Mahasiswa
                    </a>

                    <div class="mt-6 rounded-3xl bg-slate-50 p-5">
                        <strong class="block font-black text-navy">Belum punya akun umum?</strong>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Daftar sebagai Affiliator Umum untuk mendapatkan kode referral resmi Kampus Media.</p>
                        <a href="{{ url('/daftar') }}" class="mt-4 inline-flex rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black text-navy">Daftar Affiliator Umum</a>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
