<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $pageTitle = ($title ?? 'Kampus Media').' | Kampus Media';
        $pageDescription = $description ?? 'Daftar kuliah online melalui Kampus Media. Pilih kampus, program studi, program perkuliahan, dan dapatkan invoice pendaftaran otomatis.';
        $canonicalUrl = $canonical ?? url()->current();
        $seoImage = url('/images/social/logo%20kampus%20media.png');
        $brandCampus = $campus ?? null;
        $brandLogoUrl = $brandCampus?->logo_path ? \Illuminate\Support\Facades\Storage::url($brandCampus->logo_path) : null;
    @endphp
    <x-seo-meta
        :title="$pageTitle"
        :description="$pageDescription"
        :canonical="$canonicalUrl"
        :image="$seoImage"
        :noindex="$noindex ?? false"
    />
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@graph' => array_filter([
                [
                    '@type' => 'Organization',
                    'name' => 'Kampus Media',
                    'url' => url('/'),
                    'logo' => $seoImage,
                ],
                [
                    '@type' => 'WebSite',
                    'name' => 'Kampus Media',
                    'url' => $canonicalUrl,
                ],
                $brandCampus ? [
                    '@type' => 'EducationalOrganization',
                    'name' => $brandCampus->name,
                    'url' => $canonicalUrl,
                ] : null,
            ]),
        ], JSON_UNESCAPED_SLASHES) !!}
    </script>
    @vite(['resources/css/app.css', 'resources/css/pages/public-site.css'])
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
