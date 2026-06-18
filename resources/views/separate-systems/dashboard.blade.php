<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-[2rem] bg-white p-4 shadow-sm">
            <div>
                <strong class="text-xl font-black text-slate-950">{{ $title }}</strong>
                <p class="text-sm font-semibold text-slate-500">Pintu khusus, terpisah dari menu SPMM.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('home') }}" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-black text-slate-700">SPMM</a>
                <form method="POST" action="{{ route('separate-systems.logout') }}">
                    @csrf
                    <button class="rounded-full bg-slate-950 px-4 py-2 text-sm font-black text-white">Keluar</button>
                </form>
            </div>
        </nav>

        <section class="rounded-[2rem] bg-gradient-to-br from-slate-950 via-[#102a5c] to-cyan-700 p-8 text-white shadow-xl">
            <p class="inline-flex rounded-full bg-white/10 px-4 py-2 text-sm font-black">{{ strtoupper($system) }}</p>
            <h1 class="mt-5 text-4xl font-black sm:text-5xl">{{ $title }}</h1>
            <p class="mt-4 max-w-2xl leading-8 text-cyan-50">{{ $subtitle }}</p>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <a href="{{ $card['url'] }}" class="group rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-cyan-50 text-lg font-black text-cyan-700">{{ $loop->iteration }}</span>
                    <h2 class="mt-5 text-xl font-black text-slate-950">{{ $card['label'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $card['description'] }}</p>
                    <span class="mt-5 inline-flex rounded-full bg-slate-950 px-4 py-2 text-sm font-black text-white">Buka</span>
                </a>
            @endforeach
        </section>
    </main>
</body>
</html>
