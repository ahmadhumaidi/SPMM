<?php

namespace App\Filament\Pages;

use App\Jobs\SendBulkWhatsappJob;
use App\Models\Lead;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SendWhatsappBulk extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Kirim WA Bulk';

    protected static string $view = 'filament.pages.send-whatsapp-bulk';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('lead_ids')
                    ->label('Pilih dari Lead/Mahasiswa')
                    ->multiple()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Lead::query()
                        ->where(fn ($query) => $query
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('whatsapp_number', 'like', "%{$search}%"))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Lead $lead): array => [$lead->id => "{$lead->full_name} ({$lead->whatsapp_number})"])
                        ->all())
                    ->getOptionLabelsUsing(fn (array $values): array => Lead::query()
                        ->whereIn('id', $values)
                        ->get()
                        ->mapWithKeys(fn (Lead $lead): array => [$lead->id => "{$lead->full_name} ({$lead->whatsapp_number})"])
                        ->all()),
                Textarea::make('manual_numbers')
                    ->label('Atau tempel nomor manual')
                    ->helperText('Satu nomor per baris, contoh: 08123456789')
                    ->rows(4),
                Textarea::make('message')
                    ->label('Pesan')
                    ->required()
                    ->rows(6)
                    ->helperText('Pakai {{nama}} untuk sisipkan nama penerima (hanya berlaku untuk penerima yang dipilih dari Lead).'),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $recipients = [];

        if (! empty($data['lead_ids'])) {
            foreach (Lead::query()->whereIn('id', $data['lead_ids'])->get() as $lead) {
                if (filled($lead->whatsapp_number)) {
                    $recipients[] = [
                        'number' => $lead->whatsapp_number,
                        'name' => $lead->full_name,
                        'lead_id' => $lead->id,
                    ];
                }
            }
        }

        if (! empty($data['manual_numbers'])) {
            foreach (preg_split('/\r\n|\r|\n/', (string) $data['manual_numbers']) as $line) {
                $number = trim($line);
                if ($number !== '') {
                    $recipients[] = [
                        'number' => $number,
                        'name' => null,
                        'lead_id' => null,
                    ];
                }
            }
        }

        if (empty($recipients)) {
            Notification::make()
                ->title('Tidak ada penerima yang dipilih')
                ->danger()
                ->send();

            return;
        }

        SendBulkWhatsappJob::dispatch($recipients, $data['message']);

        Notification::make()
            ->title(count($recipients).' pesan sedang dikirim ke antrian')
            ->success()
            ->send();

        $this->form->fill();
    }
}
