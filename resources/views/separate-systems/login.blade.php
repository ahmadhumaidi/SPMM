<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login {{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <main class="grid min-h-screen lg:grid-cols-[1.1fr_.9fr]">
        <section class="relative overflow-hidden px-6 py-10 sm:px-10 lg:px-16">
            <div class="absolute -left-24 top-10 h-96 w-96 rounded-full bg-cyan-400/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-[30rem] w-[30rem] rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="relative flex min-h-full flex-col justify-between">
                <a href="{{ route('home') }}" class="w-fit rounded-full border border-white/15 px-4 py-2 text-sm font-black text-cyan-100">Kembali ke SPMM</a>
                <div class="max-w-2xl py-16">
                    <p class="inline-flex rounded-full bg-white/10 px-4 py-2 text-sm font-black uppercase tracking-wide text-cyan-100">{{ $system === 'siakad' ? 'Academic System' : 'Learning System' }}</p>
                    <h1 class="mt-6 text-4xl font-black tracking-tight sm:text-6xl">{{ $title }}</h1>
                    <p class="mt-5 text-lg leading-8 text-slate-300">{{ $subtitle }}</p>
                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        @foreach ($system === 'siakad' ? ['KRS', 'Jadwal', 'Nilai'] : ['Materi', 'Tugas', 'Submission'] as $item)
                            <div class="rounded-3xl border border-white/10 bg-white/10 p-4">
                                <strong>{{ $item }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
                <p class="text-sm font-semibold text-slate-400">Prototype terpisah dari menu SPMM. Produksi nanti bisa memakai domain sendiri.</p>
            </div>
        </section>

        <section class="grid place-items-center bg-white px-6 py-10 text-slate-900">
            <form method="POST" action="{{ route($system.'.authenticate') }}" class="w-full max-w-md rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-950/10">
                @csrf
                <h2 class="text-3xl font-black text-slate-950">Masuk</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Gunakan akun admin/operator yang sudah aktif.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
                @endif

                <label class="mt-6 block text-sm font-black">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 outline-none focus:ring-4 focus:ring-cyan-100">

                <label class="mt-4 block text-sm font-black">Password</label>
                <input name="password" type="password" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 px-4 outline-none focus:ring-4 focus:ring-cyan-100">

                <label class="mt-4 flex items-center gap-2 text-sm font-bold text-slate-600">
                    <input name="remember" type="checkbox" class="rounded border-slate-300">
                    Ingat saya
                </label>

                <button class="mt-6 h-12 w-full rounded-2xl bg-gradient-to-r from-slate-950 to-cyan-600 font-black text-white shadow-lg shadow-cyan-900/20">Masuk {{ $title }}</button>
            </form>
        </section>
    </main>
</body>
</html>
