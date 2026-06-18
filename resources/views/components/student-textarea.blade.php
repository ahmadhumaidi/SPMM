@props([
    'name',
    'label',
    'value' => '',
])

<label class="grid gap-2 text-sm font-bold md:col-span-2">
    {{ $label }}
    <textarea name="{{ $name }}" rows="3" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10">{{ $value }}</textarea>
</label>
