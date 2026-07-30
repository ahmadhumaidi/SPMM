<x-filament-panels::page>
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form wire:submit="send">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit">
                    Kirim WhatsApp
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
