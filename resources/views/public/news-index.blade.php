@php
    use Illuminate\Support\Facades\Storage;
    $canonicalUrl = $campus
        ? route('campuses.news.index', ['campus' => $campus->slug])
        : route('news.index');
    $seoImage = url('/images/social/logo%20kampus%20media.png');
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo-meta
        :title="$title.' | Kampus Media'"
        :description="$subtitle"
        :canonical="$canonicalUrl"
        :image="$seoImage"
    />
    @vite(['resources/css/app.css', 'resources/js/pages/news.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <nav class="sticky top-0 z-50 border-b border-slate-200 bg-white/85 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ $campus ? route('campuses.show', ['campus' => $campus->slug]) : route('campuses.index') }}" class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#071a3d] text-lg font-black text-white">KN</span>
                <span>
                    <span class="block text-sm font-black leading-tight text-[#071a3d] sm:text-base">Kampus Media</span>
                    <span class="block text-xs font-semibold text-slate-500">{{ $campus?->name ?? 'Berita Pendidikan' }}</span>
                </span>
            </a>
            <a href="{{ route('registration.create') }}" class="rounded-full bg-amber-400 px-5 py-3 text-sm font-black text-[#071a3d]">Daftar PMB</a>
        </div>
    </nav>

    <header class="relative overflow-hidden bg-[#071a3d] text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(245,158,11,.24),transparent_26%),radial-gradient(circle_at_80%_12%,rgba(56,189,248,.22),transparent_30%)]"></div>
        <div class="absolute inset-0 bg-black/35 backdrop-blur-[1px]"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <p class="inline-flex rounded-full bg-white/15 px-4 py-2 text-sm font-black text-cyan-100">Berita</p>
            <h1 class="mt-6 max-w-4xl text-4xl font-black leading-tight sm:text-6xl">{{ $title }}</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-100">{{ $subtitle }}</p>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if ($educationNews->isEmpty())
            <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                <i data-lucide="newspaper" class="mx-auto h-12 w-12 text-sky-700"></i>
                <h2 class="mt-4 text-2xl font-black text-[#071a3d]">Belum ada berita.</h2>
                <p class="mt-2 text-slate-600">Berita akan tampil setelah admin menerbitkan post.</p>
            </div>
        @else
            <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($educationNews as $news)
                    @php
                        $newsImage = $news->image_path ? Storage::url($news->image_path) : 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80';
                        $campusLabel = $news->campuses->pluck('name')->take(2)->join(', ') ?: ($news->campus?->name ?? 'Umum');
                    @endphp
                    <article class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        <img src="{{ $newsImage }}" alt="{{ $news->title }}" class="aspect-[16/10] w-full object-cover">
                        <div class="p-6">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-700">{{ $news->category }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $campusLabel }}</span>
                            </div>
                            <h2 class="mt-4 text-xl font-black leading-tight text-[#071a3d]">{{ $news->title }}</h2>
                            <p class="mt-3 leading-7 text-slate-600">{{ $news->excerpt ?: str(strip_tags($news->content))->limit(150) }}</p>
                            <div class="mt-5 flex items-center justify-between gap-3">
                                <span class="text-xs font-bold text-slate-500">{{ $news->published_at?->translatedFormat('d M Y') ?? 'Published' }}</span>
                                <a href="{{ route('news.show', $news) }}" class="rounded-full bg-[#071a3d] px-4 py-2 text-sm font-black text-white">Baca</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <div class="mt-10">
                {{ $educationNews->links() }}
            </div>
        @endif
    </main>

</body>
</html>
