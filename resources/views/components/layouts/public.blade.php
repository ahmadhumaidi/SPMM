<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $pageTitle = $title ?? 'Kampus Media';
        $pageDescription = $description ?? 'Daftar kuliah online melalui Kampus Media. Pilih kampus, program studi, program perkuliahan, dan dapatkan invoice pendaftaran otomatis.';
        $canonicalUrl = $canonical ?? url()->current();
        $seoImage = url('/images/social/logo%20kampus%20media.png');
        $brandCampus = $campus ?? null;
        $brandLogoUrl = $brandCampus?->logo_path ? \Illuminate\Support\Facades\Storage::url($brandCampus->logo_path) : null;
    @endphp
    <title>{{ $pageTitle }} | Kampus Media</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ ($noindex ?? false) ? 'noindex, nofollow' : 'index, follow' }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Kampus Media">
    <meta property="og:title" content="{{ $pageTitle }} | Kampus Media">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }} | Kampus Media">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <link rel="stylesheet" href="/css/tailwind-build.css?v=1">
    <link rel="stylesheet" href="/css/public.css?v=3">
</head>
<body>
    <div class="utility-bar">
        <span>Konsultasi PMB terpusat</span>
        <span>WhatsApp: 082199976600</span>
        <span>Pendaftaran online 24 jam</span>
    </div>
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            @if ($brandCampus)
                @if ($brandLogoUrl)
                    <img src="{{ $brandLogoUrl }}" alt="Logo {{ $brandCampus->name }}" class="brand-mark h-10 w-10 rounded-lg object-contain bg-white p-1">
                @else
                    <span class="brand-mark">{{ Illuminate\Support\Str::substr($brandCampus->name, 0, 1) }}</span>
                @endif
                <span>
                    <strong>{{ $brandCampus->name }}</strong>
                </span>
            @else
                <img src="/images/social/logo%20kampus%20media.png" alt="Kampus Media" class="h-9 w-auto object-contain">
                <span>
                    <strong>Kampus Media</strong>
                </span>
            @endif
        </a>
        <nav class="nav">
            <a href="{{ route('campuses.index') }}">Kampus</a>
            <a href="{{ route('registration.create') }}">Daftar</a>
            <a href="{{ route('student-portal.login') }}">Login</a>
            <a class="nav-signup" href="{{ route('registration.create') }}">Sign Up</a>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <a class="floating-consult" href="{{ route('registration.create') }}">Daftar PMB</a>
</body>
</html>
