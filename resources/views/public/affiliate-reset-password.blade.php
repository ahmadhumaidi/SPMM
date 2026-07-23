@php
    $seoTitle = 'Buat Password Baru';
    $seoDescription = 'Buat password baru dashboard Affiliator Umum Kampus Media.';
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
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-teal-100 text-tealx">
                <i data-lucide="lock-keyhole" class="h-7 w-7"></i>
            </span>
            <h1 class="mt-5 text-3xl font-black text-navy">Buat password baru</h1>
            <p class="mt-3 leading-7 text-slate-600">Password baru akan digunakan untuk login dashboard affiliator umum dengan email <strong>{{ $email }}</strong>.</p>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ url('/reset-password/'.$token) }}" class="mt-6 grid gap-4">
                @csrf
                <label class="grid gap-2 text-sm font-bold text-slate-700">
                    Password baru
                    <input type="password" name="password" required minlength="8" class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                    <span class="text-xs font-semibold text-slate-500">Minimal 8 karakter.</span>
                </label>
                <label class="grid gap-2 text-sm font-bold text-slate-700">
                    Konfirmasi password baru
                    <input type="password" name="password_confirmation" required minlength="8" class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                </label>
                <button class="inline-flex items-center justify-center gap-2 rounded-full bg-tealx px-6 py-4 font-black text-white shadow-lg shadow-teal-700/20">
                    <i data-lucide="check-circle-2" class="h-5 w-5"></i>
                    Simpan Password Baru
                </button>
            </form>
        </section>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
