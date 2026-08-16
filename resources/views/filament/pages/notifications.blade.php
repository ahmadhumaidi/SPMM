<x-filament-panels::page>
    <div class="fi-section divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm dark:divide-white/10 dark:bg-gray-900">
        @forelse ($this->getNotifications() as $notification)
            <div class="relative p-4">
                {{ $this->renderNotification($notification)->inline() }}
            </div>
        @empty
            <div class="p-8 text-center text-sm text-gray-500">Belum ada notifikasi.</div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $this->getNotifications()->links() }}
    </div>
</x-filament-panels::page>