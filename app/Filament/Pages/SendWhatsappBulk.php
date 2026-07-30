<?php

namespace App\Filament\Pages;

use App\Jobs\SendBulkWhatsappJob;
use App\Models\Lead;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
                FileUpload::make('recipients_file')
                    ->label('Atau upload file CSV/XLS/XLSX')
                    ->helperText('Kolom pertama = nama, kolom kedua = nomor WA. Baris pertama boleh header (nama, nomor) atau langsung data.')
                    ->disk('local')
                    ->directory('wa-bulk-imports')
                    ->visibility('private')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->storeFiles()
                    ->maxSize(5120),
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

        if (! empty($data['recipients_file'])) {
            foreach ($this->parseRecipientsFile((string) $data['recipients_file']) as $row) {
                $recipients[] = $row;
            }

            Storage::disk('local')->delete((string) $data['recipients_file']);
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

    /**
     * @return array<int, array{number: string, name: ?string, lead_id: null}>
     */
    private function parseRecipientsFile(string $relativePath): array
    {
        $fullPath = Storage::disk('local')->path($relativePath);

        if (! is_file($fullPath)) {
            return [];
        }

        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($fullPath)->getActiveSheet();

        $rows = [];

        foreach ($sheet->toArray(null, true, true, false) as $index => $row) {
            $name = isset($row[0]) ? trim((string) $row[0]) : '';
            $number = isset($row[1]) ? trim((string) $row[1]) : '';

            // Only one column filled in: treat it as a bare number, not a name.
            if ($number === '' && $name !== '') {
                $number = $name;
                $name = '';
            }

            if ($number === '') {
                continue;
            }

            // Skip an obvious header row (e.g. "nama"/"nomor"/"whatsapp"/"phone").
            if ($index === 0 && preg_match('/^(nama|name|nomor|no\.?\s*wa|whatsapp|phone|number)$/i', $number)) {
                continue;
            }

            $rows[] = [
                'number' => $number,
                'name' => $name !== '' ? $name : null,
                'lead_id' => null,
            ];
        }

        return $rows;
    }
}
