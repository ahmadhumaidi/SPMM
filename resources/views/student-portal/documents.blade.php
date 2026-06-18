@php
    use Illuminate\Support\Facades\Storage;

    $lead = $account->lead;
    $completed = collect($documentTypes)->filter(fn ($document, $key) => $documents->has($key))->count();
    $total = count($documentTypes);
    $progress = (int) round(($completed / max($total, 1)) * 100);
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemberkasan | Kampus Nusantara</title>
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
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-navy to-cyanx font-black text-white">KN</span>
                <span>
                    <span class="block font-black text-navy dark:text-white">Pemberkasan</span>
                    <span class="block text-xs font-bold text-slate-500 dark:text-slate-400">Upload dokumen mahasiswa</span>
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
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-navy via-[#0b2d63] to-cyan-700 p-6 text-white shadow-2xl shadow-cyan-900/15 lg:p-8">
            <div class="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-center">
                <div>
                    <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-black text-cyan-100">Dokumen PMB</p>
                    <h1 class="mt-5 text-3xl font-black sm:text-5xl">Lengkapi pemberkasan digital.</h1>
                    <p class="mt-4 max-w-2xl leading-8 text-sky-50">Upload KTP, KK, ijazah, transkrip/SKHU, pass foto formal, dan dokumen pendukung lainnya.</p>
                </div>
                <div class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                    <p class="text-sm font-bold text-cyan-100">Progress dokumen</p>
                    <strong class="mt-2 block text-4xl font-black">{{ $progress }}%</strong>
                    <div class="mt-5 h-3 rounded-full bg-white/15">
                        <div class="h-3 rounded-full bg-gradient-to-r from-yellow-300 to-cyan-300" style="width: {{ $progress }}%"></div>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-sky-100">{{ $completed }} dari {{ $total }} dokumen sudah diupload.</p>
                </div>
            </div>
        </section>

        @if (session('status'))
            <div class="mt-6 rounded-3xl border border-green-200 bg-green-50 p-4 font-bold text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-200">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-4 font-bold text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-200">
                <p>Mohon periksa dokumen berikut:</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('student-portal.documents.upload') }}" enctype="multipart/form-data" class="mt-6">
            @csrf
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($documentTypes as $key => $document)
                    @php($uploaded = $documents->get($key))
                    <article class="rounded-[2rem] border border-white/70 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-soft dark:border-white/10 dark:bg-white/10">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200">
                                    <i data-lucide="{{ $document['icon'] }}" class="h-6 w-6"></i>
                                </span>
                                <div>
                                    <h2 class="font-black text-navy dark:text-white">{{ $document['label'] }}</h2>
                                    <p class="mt-1 text-xs font-bold {{ $document['required'] ? 'text-red-500' : 'text-slate-400' }}">
                                        {{ $document['required'] ? 'Wajib' : 'Opsional' }}
                                    </p>
                                </div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $uploaded ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-200' : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300' }}">
                                {{ $uploaded ? 'Uploaded' : 'Belum' }}
                            </span>
                        </div>

                        @if ($uploaded)
                            <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                                <p class="truncate text-sm font-black">{{ $uploaded->original_name }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format(($uploaded->file_size ?? 0) / 1024, 1) }} KB</p>
                                <a href="{{ Storage::url($uploaded->file_path) }}" target="_blank" class="mt-3 inline-flex rounded-full bg-white px-4 py-2 text-xs font-black text-cyan-700 shadow-sm dark:bg-white/10 dark:text-cyan-200">Lihat dokumen</a>
                            </div>
                        @else
                            <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm font-semibold text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                                Belum ada file. Format: JPG, PNG, PDF. Maksimal 4 MB.
                            </div>
                        @endif

                        <label class="mt-5 grid gap-2 text-sm font-bold">
                            {{ $uploaded ? 'Ganti file' : 'Upload file' }}
                            <input type="file" name="{{ $key }}" accept=".jpg,.jpeg,.png,.pdf" class="rounded-2xl border border-slate-200 bg-white p-3 file:mr-4 file:rounded-xl file:border-0 file:bg-navy file:px-4 file:py-2 file:font-black file:text-white dark:border-white/10 dark:bg-white/10">
                        </label>
                    </article>
                @endforeach
            </div>

            <div class="sticky bottom-4 z-20 mt-6 rounded-[2rem] border border-white/70 bg-white/90 p-4 shadow-2xl shadow-slate-300/40 backdrop-blur dark:border-white/10 dark:bg-slate-950/80">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Dokumen yang sudah diupload akan tersimpan di sistem pusat.</p>
                    <button class="rounded-2xl bg-gradient-to-r from-navy to-cyanx px-6 py-4 font-black text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-1">
                        Simpan Pemberkasan
                    </button>
                </div>
            </div>
        </form>
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
