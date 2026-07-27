<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentPaymentResource\Pages;
use App\Models\Campus;
use App\Models\Lead;
use App\Models\StudentPayment;
use App\Services\StudentPaymentReceiptArchiver;
use App\Services\StudentPaymentReceiptMailer;
use App\Services\StudentPaymentScheduleService;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentPaymentResource extends Resource
{
    protected static ?string $model = StudentPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Payment';

    protected static ?string $navigationLabel = 'Pembayaran Realtime';

    protected static ?string $modelLabel = 'Pembayaran Realtime';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessPayments();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Transaksi')
                ->columns(2)
                ->schema([
                    TextInput::make('lead.full_name')->label('Mahasiswa')->disabled()->dehydrated(false),
                    TextInput::make('lead.campus.name')->label('Kampus')->disabled()->dehydrated(false),
                    TextInput::make('lead.email')->label('Email')->disabled()->dehydrated(false),
                    TextInput::make('payment_label')->label('Keterangan Transaksi')->maxLength(255),
                    TextInput::make('amount')->label('Nominal')->prefix('Rp')->numeric()->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'unpaid' => 'Belum dibayar',
                            'pending' => 'Menunggu verifikasi',
                            'paid' => 'Lunas',
                            'waived' => 'Dibebaskan',
                        ])
                        ->required(),
                    DatePicker::make('due_date')->label('Tanggal jatuh tempo'),
                    DateTimePicker::make('paid_at')->label('Tanggal lunas'),
                    Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyRelatedCampusScope(
            parent::getEloquentQuery()
                ->with(['lead.campus', 'lead.studyProgram', 'lead.classTrack'])
                ->where('status', 'paid')
                ->where(fn (Builder $query): Builder => $query->whereNull('payment_type')->orWhere('payment_type', '!=', 'manual')),
            'lead'
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->groups([
                Group::make('lead.campus.name')->label('Kampus')->collapsible(),
            ])
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('lead.full_name')->label('MAHASISWA')->searchable()->sortable(),
                TextColumn::make('lead.campus.name')->label('KAMPUS')->searchable(),
                TextColumn::make('payment_label')
                    ->label('TRANSAKSI')
                    ->formatStateUsing(fn (?string $state, StudentPayment $record): string => $state ?: ((int) $record->month === 0 ? 'Formulir Pendaftaran' : 'Bulan '.$record->month)),
                TextColumn::make('amount')->label('NOMINAL')->money('IDR')->sortable()->alignEnd(),
                TextColumn::make('status')->label('STATUS')->badge(),
                TextColumn::make('paid_at')->label('TGL LUNAS')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('lead.email')->label('EMAIL')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('receipt_archived_at')
                    ->label('ARSIP')
                    ->boolean()
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (StudentPayment $record): string => $record->receipt_archived_at
                        ? 'Diarsipkan '.$record->receipt_archived_at->format('d M Y H:i')
                        : 'Kwitansi belum diarsipkan'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campus')
                    ->label('Kampus')
                    ->options(fn (): array => FilamentResourceScope::applyCampusScope(Campus::query()->orderBy('name'), 'id')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('lead', fn (Builder $leadQuery) => $leadQuery->where('campus_id', $data['value']))
                        : $query),
                Tables\Filters\Filter::make('unarchived_receipt')
                    ->label('Kwitansi belum diarsipkan')
                    ->query(fn (Builder $query): Builder => $query->whereNull('receipt_pdf_path')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('archive_receipts')
                    ->label('Arsipkan kwitansi tertunda')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(fn (): string => 'Membuat dan menyimpan file PDF untuk semua kwitansi lunas yang belum diarsipkan.')
                    ->action(function (StudentPaymentReceiptArchiver $archiver): void {
                        $pending = StudentPayment::query()
                            ->whereIn('status', ['paid', 'waived'])
                            ->whereNull('receipt_pdf_path')
                            ->get();

                        foreach ($pending as $payment) {
                            $archiver->contentsFor($payment);
                        }

                        Notification::make()
                            ->title($pending->count().' kwitansi berhasil diarsipkan')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('generate_schedules')
                    ->label('Generate semua jadwal')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->requiresConfirmation()
                    ->action(function (StudentPaymentScheduleService $service): void {
                        $count = 0;

                        Lead::query()
                            ->with(['latestInvoice'])
                            ->whereDoesntHave('studentPayments')
                            ->chunkById(100, function ($leads) use ($service, &$count): void {
                                foreach ($leads as $lead) {
                                    $count += $service->generateForLead($lead, invoice: $lead->latestInvoice)->count();
                                }
                            });

                        Notification::make()
                            ->title('Jadwal pembayaran dibuat')
                            ->body($count.' baris pembayaran berhasil dibuat dari fee scheme.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('print_receipt')
                    ->label('Kwitansi')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (StudentPayment $record): string => route('admin.student-payments.transaction-receipt', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('download_receipt')
                    ->label('Unduh')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (StudentPayment $record): bool => in_array($record->status, ['paid', 'waived'], true))
                    ->url(fn (StudentPayment $record): string => route('admin.student-payments.transaction-receipt', ['payment' => $record, 'download' => 1])),
                Tables\Actions\Action::make('send_receipt_email')
                    ->label('Kirim Email')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(fn (StudentPayment $record): string => 'Kirim ulang kwitansi transaksi ini ke '.($record->lead?->email ?: 'email mahasiswa').'?')
                    ->visible(fn (StudentPayment $record): bool => filled($record->lead?->email))
                    ->action(function (StudentPayment $record): void {
                        app(StudentPaymentReceiptMailer::class)->send($record);

                        Notification::make()
                            ->title('Kwitansi dikirim ke '.$record->lead?->email)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make()->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => FilamentResourceScope::isSuperAdmin()),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentPayments::route('/'),
            'view' => Pages\ViewStudentPayment::route('/{record}'),
            'edit' => Pages\EditStudentPayment::route('/{record}/edit'),
        ];
    }
}
