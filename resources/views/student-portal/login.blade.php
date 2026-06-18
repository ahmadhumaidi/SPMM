<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Mahasiswa | Kampus Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="grid min-h-screen place-items-center px-4 py-10">
        <section class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl shadow-slate-200">
            <p class="text-sm font-black uppercase tracking-wide text-sky-700">Portal Mahasiswa</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Login Kampus Media</h1>
            <p class="mt-3 leading-7 text-slate-600">Masuk menggunakan email dan password sementara yang dikirim setelah pendaftaran.</p>

            <form method="POST" action="{{ route('student-portal.authenticate') }}" class="mt-7 grid gap-4">
                @csrf
                <label class="grid gap-2 text-sm font-bold">
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                    @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
                <label class="grid gap-2 text-sm font-bold">
                    Password
                    <input type="password" name="password" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                    @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
                <button class="h-12 rounded-2xl bg-slate-950 font-black text-white">Masuk</button>
            </form>
        </section>
    </main>
</body>
</html>
