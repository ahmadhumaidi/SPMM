@props(['title', 'icon' => 'circle'])

<section class="rounded-[2rem] border border-white/70 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/10 lg:p-6">
    <div class="mb-5 flex items-center gap-3">
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200">
            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
        </span>
        <h2 class="text-xl font-black text-navy dark:text-white">{{ $title }}</h2>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        {{ $slot }}
    </div>
</section>
