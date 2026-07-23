@php
    $seoTitle = 'Lupa Password Affiliate';
    $seoDescription = 'Reset password dashboard Affiliator Umum Kampus Media.';
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }} | Kampus Media</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { navy: '#071a3d', tealx: '#0f766e' },
                    boxShadow: { soft: '0 24px 70px rgba(15, 23, 42, .12)' },
                },
            },
        }
    </script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="grid min-h-screen place-items-center px-4 py-10">
        <section class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-soft sm:p-8">
            <a href="{{ url('/login') }}" class="inline-flex items-center gap-2 text-sm font-black text-tealx">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Kembali ke login
            </a>

            <span class="mt-6 grid h-14 w-14 place-items-center rounded-2xl bg-teal-100 text-tealx">
                <i data-lucide="key-round" class="h-7 w-7"></i>
            </span>
            <h1 class="mt-5 text-3xl font-black text-navy">Lupa password?</h1>
            <p class="mt-3 leading-7 text-slate-600">Masukkan email affiliator umum yang sudah aktif. Kami akan mengirim link verifikasi untuk membuat password baru.</p>

            @if (session('status'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ url('/lupa-password') }}" class="mt-6 grid gap-4">
                @csrf
                <label class="grid gap-2 text-sm font-bold text-slate-700">
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                </label>
                <button class="inline-flex items-center justify-center gap-2 rounded-full bg-tealx px-6 py-4 font-black text-white shadow-lg shadow-teal-700/20">
                    <i data-lucide="mail-check" class="h-5 w-5"></i>
                    Kirim Link Reset
                </button>
            </form>
        </section>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
