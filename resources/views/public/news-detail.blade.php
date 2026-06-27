@php
    use Illuminate\Support\Facades\Storage;

    $imageUrl = $news->image_path
        ? Storage::url($news->image_path)
        : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1400&q=80';
    $canonicalUrl = route('news.show', $news);
    $seoDescription = $news->meta_description ?: ($news->excerpt ?: str(strip_tags($news->content))->limit(155));
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $news->title }} | Kampus Media</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Kampus Media">
    <meta property="og:title" content="{{ $news->title }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $imageUrl }}">
    @if ($news->published_at)
        <meta property="article:published_time" content="{{ $news->published_at->toAtomString() }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $news->title }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $imageUrl }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <nav class="sticky top-0 z-50 border-b border-slate-200 bg-white/85 backdrop-blur-xl">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('campuses.index') }}" class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#071a3d] text-lg font-black text-white">KN</span>
                <span>
                    <span class="block text-sm font-black leading-tight text-[#071a3d] sm:text-base">Kampus Media</span>
                    <span class="block text-xs font-semibold text-slate-500">Berita Pendidikan</span>
                </span>
            </a>
            <a href="{{ route('registration.create') }}" class="rounded-full bg-amber-400 px-5 py-3 text-sm font-black text-[#071a3d]">Daftar PMB</a>
        </div>
    </nav>

    <main>
        <header class="relative overflow-hidden bg-[#071a3d] text-white">
            <img src="{{ $imageUrl }}" alt="{{ $news->title }}" class="absolute inset-0 h-full w-full object-cover opacity-45 blur-[1px]">
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="relative mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/15 px-4 py-2 text-sm font-black text-cyan-100">{{ $news->category }}</span>
                    <span class="rounded-full bg-white/15 px-4 py-2 text-sm font-black text-cyan-100">{{ $news->campuses->pluck('name')->take(3)->join(', ') ?: ($news->campus?->name ?? 'Umum') }}</span>
                </div>
                <h1 class="mt-6 max-w-4xl text-4xl font-black leading-tight sm:text-6xl">{{ $news->title }}</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-100">{{ $news->excerpt }}</p>
                <p class="mt-6 text-sm font-bold text-cyan-100">
                    {{ $news->author_name ?: 'Kampus Media' }} · {{ $news->published_at?->translatedFormat('d M Y H:i') }}
                </p>
            </div>
        </header>

        <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="rounded-[2rem] bg-white p-6 leading-8 text-slate-700 shadow-sm sm:p-10">
                <div class="prose max-w-none">
                    {!! $news->content ?: '<p>Konten berita belum diisi.</p>' !!}
                </div>
            </div>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('campuses.index') }}#berita" class="rounded-full border border-slate-200 bg-white px-5 py-3 font-black text-[#071a3d]">Kembali ke Berita</a>
                <a href="{{ route('registration.create') }}" class="rounded-full bg-[#071a3d] px-5 py-3 font-black text-white">Daftar Sekarang</a>
            </div>
        </article>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>
