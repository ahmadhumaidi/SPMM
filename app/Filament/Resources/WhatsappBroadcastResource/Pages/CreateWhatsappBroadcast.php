<?php

namespace App\Filament\Resources\WhatsappBroadcastResource\Pages;

use App\Filament\Resources\WhatsappBroadcastResource;
use App\Models\WhatsappBroadcast;
use App\Services\Whatsapp\WhatsappBroadcastService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsappBroadcast extends CreateRecord
{
    protected static string $resource = WhatsappBroadcastResource::class;

    /**
     * @var array<string, mixed>
     */
    private array $rawData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->rawData = $data;

        if (empty($data['lead_ids']) && empty($data['manual_numbers']) && empty($data['recipients_file'])) {
            Notification::make()
                ->title('Pilih minimal satu sumber penerima: Lead, nomor manual, atau file.')
                ->danger()
                ->send();

            $this->halt();
        }

        $data['created_by_user_id'] = auth()->id();
        $data['status'] = 'draft';

        unset($data['lead_ids'], $data['manual_numbers'], $data['recipients_file']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var WhatsappBroadcast $broadcast */
        $broadcast = $this->record;

        try {
            app(WhatsappBroadcastService::class)->buildRecipients($broadcast, $this->rawData);
        } catch (\DomainException $e) {
            $broadcast->delete();

            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        Notification::make()
            ->title("Broadcast \"{$broadcast->name}\" dibuat dengan {$broadcast->fresh()->recipient_count} penerima")
            ->body('Klik "Kirim Sekarang" di daftar broadcast untuk mulai mengirim.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
