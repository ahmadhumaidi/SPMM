<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SPMM - Sistem Pusat Mahera Media' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/public.css?v=1">
</head>
<body>
    <div class="utility-bar">
        <span>Konsultasi PMB terpusat</span>
        <span>WhatsApp: 6280000000000</span>
        <span>Pendaftaran online 24 jam</span>
    </div>
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand-mark">S</span>
            <span>
                <strong>SPMM</strong>
                <small>Sistem Pusat Mahera Media</small>
            </span>
        </a>
        <nav class="nav">
            <a href="{{ route('campuses.index') }}">Kampus</a>
            <a href="{{ route('registration.create') }}">Daftar</a>
            <a href="{{ route('student-portal.login') }}">Login</a>
            <a class="nav-signup" href="{{ route('registration.create') }}">Sign Up</a>
            <a href="{{ url('/admin') }}">Admin</a>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <a class="floating-consult" href="{{ route('registration.create') }}">Daftar PMB</a>
</body>
</html>
