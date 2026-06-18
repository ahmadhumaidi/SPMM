@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
])

<label class="grid gap-2 text-sm font-bold">
    {{ $label }}
    <select name="{{ $name }}" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10">
        <option value="">Pilih {{ strtolower($label) }}</option>
        @foreach ($options as $option)
            <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
        @endforeach
    </select>
</label>
