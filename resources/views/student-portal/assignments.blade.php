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
    <title>Tugas Kuliah | Kampus Nusantara</title>
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

        @if (session('status'))
            <div class="mb-5 rounded-3xl border border-green-200 bg-green-50 p-4 font-bold text-green-700">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#071a3d] via-[#12346d] to-cyan-700 p-6 text-white shadow-xl">
            <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-black">LMS Kampus</p>
            <h1 class="mt-5 text-3xl font-black sm:text-5xl">Tugas Kuliah</h1>
            <p class="mt-3 max-w-2xl leading-8 text-cyan-50">Tugas otomatis mengikuti kelas kuliah di KRS. Mahasiswa bisa mengirim jawaban teks atau upload file.</p>
        </section>

        <section class="mt-6 grid gap-5">
            @forelse ($assignments as $assignment)
                @php
                    $submission = $assignment->submissions->first();
                    $isLate = $assignment->due_at && now()->greaterThan($assignment->due_at);
                @endphp
                <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="grid gap-5 lg:grid-cols-[1fr_24rem]">
                        <div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-black text-cyan-700">{{ $assignment->module?->courseClass?->course?->name }}</span>
                                <span class="rounded-full {{ $submission ? 'bg-green-50 text-green-700' : ($isLate ? 'bg-red-50 text-red-700' : 'bg-orange-50 text-orange-700') }} px-3 py-1 text-xs font-black">
                                    {{ $submission ? str($submission->status)->replace('_', ' ')->title() : ($isLate ? 'Terlambat' : 'Belum Submit') }}
                                </span>
                            </div>
                            <h2 class="mt-3 text-2xl font-black text-[#071a3d]">{{ $assignment->title }}</h2>
                            <p class="mt-2 text-sm font-semibold text-slate-500">
                                Modul: {{ $assignment->module?->title }}.
                                Deadline: {{ $assignment->due_at?->translatedFormat('d M Y H:i') ?? 'Tidak dibatasi' }}.
                                Nilai maksimal: {{ $assignment->max_score }}.
                            </p>
                            @if ($assignment->instructions)
                                <p class="mt-4 rounded-3xl bg-slate-50 p-4 text-sm leading-7 text-slate-600">{{ $assignment->instructions }}</p>
                            @endif
                            @if ($submission)
                                <div class="mt-4 rounded-3xl border border-green-100 bg-green-50 p-4 text-sm text-green-800">
                                    <strong class="block">Sudah dikumpulkan pada {{ $submission->submitted_at?->translatedFormat('d M Y H:i') }}</strong>
                                    @if ($submission->score)
                                        <span class="mt-1 block">Nilai: {{ $submission->score }}</span>
                                    @endif
                                    @if ($submission->feedback)
                                        <span class="mt-1 block">Feedback: {{ $submission->feedback }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('student-portal.assignments.submit', $assignment) }}" enctype="multipart/form-data" class="rounded-[1.75rem] bg-slate-50 p-4">
                            @csrf
                            <label class="block text-sm font-black text-[#071a3d]">Jawaban Teks</label>
                            <textarea name="answer_text" rows="4" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white p-3 text-sm outline-none focus:ring-4 focus:ring-cyan-100">{{ old('answer_text', $submission?->answer_text) }}</textarea>
                            <label class="mt-4 block text-sm font-black text-[#071a3d]">Upload File</label>
                            <input type="file" name="file_path" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white p-3 text-sm">
                            <button class="mt-4 w-full rounded-2xl bg-gradient-to-r from-[#071a3d] to-cyan-600 px-5 py-3 font-black text-white shadow-lg">
                                {{ $submission ? 'Update Tugas' : 'Kumpulkan Tugas' }}
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] bg-white p-8 text-center shadow-sm">
                    <i data-lucide="clipboard-check" class="mx-auto h-12 w-12 text-cyan-600"></i>
                    <h2 class="mt-4 text-2xl font-black text-[#071a3d]">Belum ada tugas</h2>
                    <p class="mx-auto mt-2 max-w-xl text-slate-600">Tugas akan muncul setelah KRS dibuat di SIAKAD dan admin menambahkan tugas di modul LMS.</p>
                </div>
            @endforelse
        </section>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>
