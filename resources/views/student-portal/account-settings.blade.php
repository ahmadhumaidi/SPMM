@php
    $lead = $account->lead;
    $studentName = $lead->full_name;
    $nim = $lead->studentNumber?->nim ?? 'NIM sementara';
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Akun | Kampus Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { navy: '#071a3d', cyanx: '#06b6d4' },
                    boxShadow: { soft: '0 24px 80px rgba(15, 23, 42, .10)' },
                },
            },
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 text-slate-900 antialiased transition dark:bg-[#050b18] dark:text-slate-100">
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-24 top-0 h-96 w-96 rounded-full bg-cyan-300/30 blur-3xl dark:bg-cyan-500/10"></div>
        <div class="absolute right-0 top-36 h-[32rem] w-[32rem] rounded-full bg-indigo-400/20 blur-3xl dark:bg-indigo-600/10"></div>
    </div>

    <header class="sticky top-0 z-40 border-b border-white/70 bg-white/80 px-4 py-3 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/70 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <a href="{{ route('student-portal.dashboard') }}" class="flex items-center gap-3">
                @include('student-portal.partials.campus-logo', ['campus' => $lead->campus])
                <span>
                    <span class="block font-black text-navy dark:text-white">Pengaturan Akun</span>
                    <span class="block text-xs font-bold text-slate-500 dark:text-slate-400">{{ $studentName }} - {{ $nim }}</span>
                </span>
            </a>
            <div class="flex items-center gap-2">
                <button type="button" data-theme-toggle class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-slate-700 shadow-sm dark:bg-white/10 dark:text-white">
                    <i data-lucide="moon" class="h-5 w-5 dark:hidden"></i>
                    <i data-lucide="sun" class="hidden h-5 w-5 dark:block"></i>
                </button>
                <a href="{{ route('student-portal.dashboard') }}" class="rounded-2xl bg-navy px-4 py-3 text-sm font-black text-white dark:bg-white dark:text-navy">Dashboard</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-3xl border border-green-200 bg-green-50 p-4 font-bold text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-200">{{ session('status') }}</div>
        @endif

        <section class="grid gap-6 xl:grid-cols-[.85fr_1.15fr]">
            <article class="rounded-[2rem] border border-white/70 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/10">
                <div class="flex items-center gap-3">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200">
                        <i data-lucide="shield-check" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Pengaturan Akun</p>
                        <h1 class="mt-1 text-3xl font-black text-navy dark:text-white">Ubah password login</h1>
                    </div>
                </div>
                <p class="mt-4 leading-7 text-slate-600 dark:text-slate-400">Gunakan password yang kuat agar akses biodata, pembayaran, dan pemberkasan tetap aman.</p>
                <div class="mt-5 grid gap-3">
                    @foreach (['Minimal 8 karakter', 'Gunakan kombinasi huruf dan angka', 'Jangan bagikan password ke orang lain'] as $hint)
                        <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-600 dark:bg-white/5 dark:text-slate-300">
                            <i data-lucide="check-circle-2" class="h-5 w-5 text-cyan-600"></i>
                            <span>{{ $hint }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <form method="POST" action="{{ route('student-portal.password.update') }}" class="rounded-[2rem] border border-white/70 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/10">
                @csrf
                <div class="grid gap-5">
                    <label class="grid gap-2 text-sm font-bold">
                        Password lama
                        <input type="password" name="current_password" required class="h-12 rounded-2xl border border-slate-200 bg-white px-4 outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10">
                        @error('current_password') <span class="text-sm font-bold text-red-600 dark:text-red-300">{{ $message }}</span> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-bold">
                        Password baru
                        <input type="password" name="password" required class="h-12 rounded-2xl border border-slate-200 bg-white px-4 outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10">
                        @error('password') <span class="text-sm font-bold text-red-600 dark:text-red-300">{{ $message }}</span> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-bold">
                        Konfirmasi password baru
                        <input type="password" name="password_confirmation" required class="h-12 rounded-2xl border border-slate-200 bg-white px-4 outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10">
                    </label>

                    <button class="rounded-2xl bg-gradient-to-r from-navy to-cyanx px-6 py-4 font-black text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-1">
                        Simpan Password Baru
                    </button>
                </div>
            </form>
        </section>
    </main>

    <script>
        const root = document.documentElement;
        if (localStorage.getItem('student-theme') === 'dark') root.classList.add('dark');
        document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
            root.classList.toggle('dark');
            localStorage.setItem('student-theme', root.classList.contains('dark') ? 'dark' : 'light');
        });
        lucide.createIcons();
    </script>
</body>
</html>
