@php
    $campusNames = $record->campuses->pluck('name')->filter()->values();
    $items = [
        'Kategori' => $record->category ?: '-',
        'Status' => $record->status ?: '-',
        'AI' => $record->ai_generated ? 'AI' : 'Manual',
        'Keyword' => $record->keyword_utama ?: '-',
        'Sumber' => $record->source_name ?: '-',
        'Dibuat' => $record->created_at?->format('d M Y H:i') ?: '-',
        'Dibuat oleh' => $record->createdBy?->name ?: 'Sistem',
        'Publish' => $record->published_at?->format('d M Y H:i') ?: '-',
        'Diverifikasi oleh' => $record->publishedBy?->name ?: '-',
        'Kampus' => $campusNames->isNotEmpty() ? $campusNames->implode(', ') : 'Umum',
    ];
@endphp

<div class="space-y-5">
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Judul</p>
        <p class="mt-1 text-base font-semibold leading-7 text-gray-950 dark:text-white">{{ $record->title }}</p>
    </div>

    <dl style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
        @foreach ($items as $label => $value)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900/70" style="min-width: 0;">
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                <dd class="mt-1 break-words text-sm font-semibold text-gray-950 dark:text-white">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    @if (filled($record->meta_description))
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900/70" style="min-width: 0;">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Meta Description</p>
            <p class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-200">{{ $record->meta_description }}</p>
        </div>
    @endif
</div>
