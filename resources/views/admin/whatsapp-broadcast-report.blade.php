<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report Broadcast WhatsApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">
    <main class="mx-auto min-h-screen max-w-6xl px-4 py-8">
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-red-700">Report Broadcast WhatsApp</p>
                    <h1 class="mt-2 text-2xl font-black">{{ $broadcast->name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">Dibuat {{ $broadcast->created_at?->format('d M Y H:i') }}</p>
                </div>
                <a href="{{ route('filament.admin.resources.whatsapp-broadcasts.index') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-black text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-50">
                    Kembali
                </a>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm font-bold text-slate-500">Total penerima</p>
                    <p class="mt-2 text-3xl font-black">{{ $recipients->count() }}</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-5">
                    <p class="text-sm font-bold text-emerald-700">Terkirim</p>
                    <p class="mt-2 text-3xl font-black text-emerald-800">{{ $sentCount }}</p>
                </div>
                <div class="rounded-2xl bg-red-50 p-5">
                    <p class="text-sm font-bold text-red-700">Tidak terkirim</p>
                    <p class="mt-2 text-3xl font-black text-red-800">{{ $queuedCount }}</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-5">
                    <p class="text-sm font-bold text-amber-700">Nomor invalid</p>
                    <p class="mt-2 text-3xl font-black text-amber-800">{{ $invalidCount }}</p>
                </div>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-4 text-sm font-black text-slate-700">Detail penerima</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Nama</th>
                            <th class="px-6 py-3">Nomor</th>
                            <th class="px-6 py-3">Jurusan</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Waktu terkirim</th>
                            <th class="px-6 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recipients as $recipient)
                            @php
                                $statusLabel = match ($recipient->status) {
                                    'sent' => 'Terkirim',
                                    'invalid' => 'Nomor invalid',
                                    default => 'Tidak terkirim',
                                };
                                $statusClass = match ($recipient->status) {
                                    'sent' => 'bg-emerald-50 text-emerald-700',
                                    'invalid' => 'bg-amber-50 text-amber-700',
                                    default => 'bg-red-50 text-red-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-6 py-4 font-bold">{{ $recipient->lead?->full_name ?? $recipient->recipient_name ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $recipient->recipient_number }}</td>
                                <td class="px-6 py-4">{{ $recipient->lead?->studyProgram?->name ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-6 py-4">{{ $recipient->sent_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $recipient->failed_reason ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center font-semibold text-slate-500">Belum ada penerima.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
