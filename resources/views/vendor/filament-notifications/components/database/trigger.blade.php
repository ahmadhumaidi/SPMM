<div
    x-data="{}"
    x-on:click="$dispatch('open-modal', { id: 'database-notifications' })"
    wire:click="markAllNotificationsAsRead"
    {{ $attributes->class(['inline-block']) }}
>
    {{ $slot }}
</div>
