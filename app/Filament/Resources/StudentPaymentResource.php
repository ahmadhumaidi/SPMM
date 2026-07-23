<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentPaymentResource\Pages;
use App\Models\Campus;
use App\Models\Lead;
use App\Services\StudentPaymentScheduleService;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
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
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Payment';

    protected static ?string $navigationLabel = 'Mahasiswa Sudah Bayar';

    protected static ?string $modelLabel = 'Mahasiswa Sudah Bayar';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessPayments();
    }

    public static function isSuperAdmin(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Data Mahasiswa')
                ->columns(3)
                ->schema([
                    TextInput::make('studentBiodata.selection_number')->label('No. Seleksi')->disabled(),
                    TextInput::make('latestInvoice.va_number')->label('Virtual Account')->disabled(),
                    TextInput::make('payment_status')
                        ->label('Status')
                        ->formatStateUsing(fn ($state): string => $state?->value ?? (string) $state)
                        ->disabled(),
                    TextInput::make('full_name')->label('Nama')->disabled(),
                    TextInput::make('studentBiodata.group')->label('KLP')->disabled(),
                    TextInput::make('studyProgram.name')->label('JRS')->disabled(),
                    TextInput::make('campus.name')->label('Kampus')->disabled(),
                    TextInput::make('classTrack.name')->label('Program perkuliahan')->disabled(),
                    TextInput::make('email')->label('Email')->disabled(),
                ]),
            Section::make('Tagihan Pendaftaran')
                ->columns(3)
                ->schema([
                    TextInput::make('latestInvoice.invoice_number')->label('No. Invoice')->disabled(),
                    TextInput::make('latestInvoice.amount')->label('Nominal pendaftaran')->prefix('Rp')->disabled(),
                    TextInput::make('latestInvoice.status')->label('Status invoice')->disabled(),
                ]),
            Section::make('Tabel Rencana dan Realita Pembayaran')
                ->schema([
                    Repeater::make('studentPayments')
                        ->hiddenLabel()
                        ->relationship(modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('month'))
                        ->itemLabel(fn (array $state): string => ((int) ($state['month'] ?? 0)) === 0 ? 'Formulir Pendaftaran' : 'Bulan '.($state['month'] ?? '-'))
                        ->schema([
                            TextInput::make('month')->label('Bulan')->helperText('0 = pendaftaran, 1-48 = cicilan.')->numeric()->disabled(fn (): bool => ! static::isSuperAdmin())->dehydrated(),
                            TextInput::make('payment_label')->label('Rencana')->disabled(fn (): bool => ! static::isSuperAdmin())->dehydrated(),
                            TextInput::make('registration_fee')
                                ->label('Formulir')
                                ->prefix('Rp')
                                ->numeric()
                                ->disabled(fn (): bool => ! static::isSuperAdmin())
                                ->visible(fn ($get): bool => (int) ($get('month') ?? 0) === 0)
                                ->dehydrated(),
                            TextInput::make('development_fee')
                                ->label('Development')
                                ->prefix('Rp')
                                ->numeric()
                                ->disabled(fn (): bool => ! static::isSuperAdmin())
                                ->visible(fn ($get): bool => (int) ($get('month') ?? 0) > 0)
                                ->dehydrated(),
                            TextInput::make('tuition_fee')
                                ->label('Tuition')
                                ->prefix('Rp')
                                ->numeric()
                                ->disabled(fn (): bool => ! static::isSuperAdmin())
                                ->visible(fn ($get): bool => (int) ($get('month') ?? 0) > 0)
                                ->dehydrated(),
                            TextInput::make('ukt')
                                ->label('UKT')
                                ->prefix('Rp')
                                ->numeric()
                                ->disabled(fn (): bool => ! static::isSuperAdmin())
                                ->visible(fn ($get): bool => (int) ($get('month') ?? 0) > 0)
                                ->dehydrated(),
                            TextInput::make('amount')->label('Total rencana')->prefix('Rp')->numeric()->disabled(fn (): bool => ! static::isSuperAdmin())->dehydrated(),
                            DatePicker::make('due_date')->label('Tgl rencana pembayaran')->disabled(fn (): bool => ! static::isSuperAdmin()),
                            Select::make('status')
                                ->label('Realita')
                                ->options([
                                    'unpaid' => 'Belum dibayar',
                                    'pending' => 'Menunggu verifikasi',
                                    'paid' => 'Lunas',
                                    'waived' => 'Dibebaskan',
                                ])
                                ->required(),
                            DateTimePicker::make('paid_at')->label('Tgl realita/lunas'),
                            Textarea::make('notes')->label('Catatan')->rows(1)->columnSpanFull(),
                        ])
                        ->columns(6)
                        ->defaultItems(0)
                        ->addable(fn (): bool => static::isSuperAdmin())
                        ->deletable(fn (): bool => static::isSuperAdmin())
                        ->reorderable(false),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyManagedLeadCampusScope(
            parent::getEloquentQuery()
                ->with(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'latestInvoice', 'studentPayments'])
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas(
                'studentPayments',
                fn (Builder $paymentQuery): Builder => $paymentQuery
                    ->where('status', 'paid')
                    ->where(fn (Builder $typeQuery): Builder => $typeQuery
                        ->whereNull('payment_type')
                        ->orWhere('payment_type', '!=', 'manual')),
            ))
            ->defaultSort('created_at', 'desc')
            ->groups([
                Group::make('campus.name')->label('Kampus')->collapsible(),
            ])
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('studentBiodata.selection_number')
                    ->label('NO. SELEKSI')
                    ->state(fn (Lead $record): string => $record->studentBiodata?->selection_number ?? static::selectionNumberFor($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('studentBiodata', fn (Builder $biodataQuery) => $biodataQuery->where('selection_number', 'like', "%{$search}%"))),
                TextColumn::make('latestInvoice.va_number')
                    ->label('VIRTUAL ACCOUNT')
                    ->state(fn (Lead $record): string => $record->latestInvoice?->va_number ?: ($record->latestInvoice?->gateway_reference ?: '-')),
                TextColumn::make('full_name')->label('N A M A')->searchable()->sortable(),
                TextColumn::make('payment_status')->label('STATUS')->badge(),
                TextColumn::make('studentBiodata.group')
                    ->label('KLP')
                    ->state(fn (Lead $record): string => $record->studentBiodata?->group ?: ($record->classTrack?->name ?: '-')),
                TextColumn::make('studyProgram.name')->label('JRS')->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campus_id')
                    ->label('Kampus')
                    ->options(fn (): array => FilamentResourceScope::applyCampusScope(Campus::query()->orderBy('name'), 'id')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) ? $query->where('leads.campus_id', $data['value']) : $query),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_schedules')
                    ->label('Generate semua jadwal')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (): bool => static::isSuperAdmin())
                    ->requiresConfirmation()
                    ->action(function (StudentPaymentScheduleService $service): void {
                        $count = 0;

                        static::getEloquentQuery()
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
                    ->url(fn (Lead $record): string => route('admin.student-payments.receipt', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make()->label('Lihat'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function selectionNumberFor(Lead $lead): string
    {
        return 'SEL-'.($lead->created_at?->format('Ymd') ?? now()->format('Ymd')).'-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT);
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
