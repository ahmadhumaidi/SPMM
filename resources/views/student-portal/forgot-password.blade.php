<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password Mahasiswa | Kampus Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="grid min-h-screen place-items-center px-4 py-10">
        <section class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl shadow-slate-200">
            <p class="text-sm font-black uppercase tracking-wide text-sky-700">Portal Mahasiswa</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Lupa Password</h1>
            <p class="mt-3 leading-7 text-slate-600">Masukkan email akun mahasiswa. Sistem akan mengirim link verifikasi untuk membuat password baru.</p>

            @if (session('status'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold leading-6 text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('student-portal.password.email') }}" class="mt-7 grid gap-4">
                @csrf
                <label class="grid gap-2 text-sm font-bold">
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-100">
                    @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
                <button class="h-12 rounded-2xl bg-slate-950 font-black text-white">Kirim Link Reset</button>
                <a href="{{ route('student-portal.login') }}" class="text-center text-sm font-black text-sky-700">Kembali ke login</a>
            </form>
        </section>
    </main>
</body>
</html>
