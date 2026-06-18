@props([
    'name',
    'label',
    'value' => '',
    'type' => 'text',
    'required' => false,
    'prefix' => null,
    'readonly' => false,
])

<label class="grid gap-2 text-sm font-bold">
    {{ $label }}
    <span class="relative">
        @if ($prefix)
            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-black text-slate-400">{{ $prefix }}</span>
        @endif
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            value="{{ $value }}"
            @required($required)
            @readonly($readonly)
            class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10 {{ $prefix ? 'pl-12' : '' }}"
        >
    </span>
</label>
