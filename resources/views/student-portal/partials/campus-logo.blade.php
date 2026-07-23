@props([
    'campus' => null,
    'class' => 'h-11 w-11',
])

@php
    $campusName = $campus?->name ?: 'Kampus';
    $logoUrl = $campus?->logo_path ? \Illuminate\Support\Facades\Storage::url($campus->logo_path) : null;
    $initials = collect(explode(' ', $campusName))
        ->filter()
        ->take(2)
        ->map(fn (string $word): string => mb_substr($word, 0, 1))
        ->join('');
@endphp

<span {{ $attributes->merge(['class' => 'grid '.$class.' shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-1 shadow-sm dark:border-white/10 dark:bg-white']) }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo {{ $campusName }}" class="h-full w-full object-contain">
    @else
        <span class="grid h-full w-full place-items-center rounded-xl bg-gradient-to-br from-navy to-cyanx text-xs font-black text-white">
            {{ $initials ?: 'KM' }}
        </span>
    @endif
</span>
