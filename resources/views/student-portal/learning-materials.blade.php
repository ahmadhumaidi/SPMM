@php
    $lead = $account->lead;
    $studentName = $lead->full_name;
    $avatarInitial = mb_substr($studentName, 0, 1);
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Materi Kuliah | Kampus Nusantara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <nav class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-[2rem] bg-white p-4 shadow-sm">
            <a href="{{ route('student-portal.dashboard') }}" class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-black text-slate-700">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Dashboard
            </a>
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-[#071a3d] to-cyan-500 font-black text-white">{{ $avatarInitial }}</span>
                <span>
                    <strong class="block text-sm">{{ $studentName }}</strong>
                    <span class="block text-xs font-semibold text-slate-500">{{ $lead->studyProgram?->name }}</span>
                </span>
            </div>
        </nav>

        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#071a3d] via-[#12346d] to-cyan-700 p-6 text-white shadow-xl">
            <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-black">LMS Kampus</p>
            <h1 class="mt-5 text-3xl font-black sm:text-5xl">Materi Kuliah</h1>
            <p class="mt-3 max-w-2xl leading-8 text-cyan-50">Materi ini terhubung dengan kelas kuliah di SIAKAD. Jika KRS sudah dibuat, materi dari kelas tersebut akan muncul otomatis.</p>
        </section>

        <section class="mt-6 grid gap-5">
            @forelse ($modules as $module)
                <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-black uppercase tracking-wide text-cyan-700">{{ $module->courseClass?->course?->name }} - Kelas {{ $module->courseClass?->class_name }}</p>
                            <h2 class="mt-1 text-2xl font-black text-[#071a3d]">{{ $module->title }}</h2>
                            @if ($module->description)
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $module->description }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-cyan-50 px-4 py-2 text-xs font-black text-cyan-700">{{ $module->materials->count() }} materi</span>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @forelse ($module->materials as $material)
                            <div class="rounded-3xl bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-cyan-700 shadow-sm">
                                        <i data-lucide="{{ $material->material_type === 'video' ? 'play-circle' : ($material->material_type === 'link' ? 'link' : 'file-text') }}" class="h-5 w-5"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <strong class="block text-[#071a3d]">{{ $material->title }}</strong>
                                        <span class="mt-1 inline-flex rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600">{{ str($material->material_type)->title() }}</span>
                                        @if ($material->content)
                                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $material->content }}</p>
                                        @endif
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if ($material->file_path)
                                                <a href="{{ asset('storage/'.$material->file_path) }}" target="_blank" class="rounded-full bg-[#071a3d] px-4 py-2 text-xs font-black text-white">Buka File</a>
                                            @endif
                                            @if ($material->external_url)
                                                <a href="{{ $material->external_url }}" target="_blank" class="rounded-full bg-cyan-600 px-4 py-2 text-xs font-black text-white">Buka Link</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl bg-slate-50 p-5 text-sm font-bold text-slate-500">Belum ada materi pada modul ini.</div>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] bg-white p-8 text-center shadow-sm">
                    <i data-lucide="book-open" class="mx-auto h-12 w-12 text-cyan-600"></i>
                    <h2 class="mt-4 text-2xl font-black text-[#071a3d]">Materi belum tersedia</h2>
                    <p class="mx-auto mt-2 max-w-xl text-slate-600">Pastikan KRS mahasiswa sudah dibuat di SIAKAD dan admin sudah menambahkan modul pembelajaran untuk kelas tersebut.</p>
                </div>
            @endforelse
        </section>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>
