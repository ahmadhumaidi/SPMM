<?php

namespace App\Filament\Resources;

use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceStatus;
use App\Filament\Resources\ManualStudentPaymentResource\Pages;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\StudentPayment;
use App\Services\AuditLogger;
use App\Services\ReferralService;
use App\Services\StudentPaymentReceiptMailer;
use App\Support\FilamentResourceScope;
use Illuminate\Support\Str;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ManualStudentPaymentResource extends Resource
{
    protected static ?string $model = StudentPayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Payment';
    protected static ?string $navigationLabel = 'Pembayaran Manual';
    protected static ?string $modelLabel = 'Pembayaran Manual';
    protected static ?string $pluralModelLabel = 'Pembayaran Manual';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessPayments();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('lead_id')
                ->label('Mahasiswa / Lead')
                ->options(fn (): array => FilamentResourceScope::applyManagedLeadCampusScope(Lead::query()->with(['campus', 'studyProgram'])->orderByDesc('id'))
                    ->limit(100)
                    ->get()
                    ->mapWithKeys(fn (Lead $lead): array => [$lead->id => trim("{$lead->full_name} - {$lead->campus?->name} - {$lead->studyProgram?->name}")])
                    ->all())
                ->searchable()
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('rr_student_payment_id', null);
                    $set('payment_label', null);
                    $set('amount', null);
                    $set('due_date', now()->toDateString());
                })
                ->required(),
            Select::make('rr_student_payment_id')
                ->label('Item pembayaran')
                ->options(fn (Get $get, ?StudentPayment $record): array => static::rrPaymentOptions((int) $get('lead_id'), $record?->id))
                ->native(false)
                ->searchable()
                ->live()
                ->disabled(fn (Get $get): bool => blank($get('lead_id')))
                ->afterStateUpdated(fn ($state, Set $set): mixed => static::fillFromRrPayment($state, $set))
                ->helperText('Nominal mengikuti item aktif di Rincian Biaya (RR) mahasiswa.')
                ->required(fn (string $operation): bool => $operation === 'create'),
            Hidden::make('payment_label')->required(),
            TextInput::make('amount')
                ->label('Nominal')
                ->numeric()
                ->prefix('Rp')
                ->disabled()
                ->dehydrated()
                ->required(),
            Select::make('payment_method')->label('Metode pembayaran')->options([
                'transfer_cv' => 'Transfer rekening CV',
                'transfer_rekening_cv' => 'Transfer rekening CV',
                'bank_transfer' => 'Transfer bank',
                'cash' => 'Tunai',
                'other' => 'Lainnya',
            ])->default('transfer_cv')->required(),
            DatePicker::make('due_date')
                ->label('Tanggal rencana pembayaran')
                ->default(now()),
            Hidden::make('status')->default('pending'),
            FileUpload::make('proof_path')
                ->label('Bukti pembayaran')
                ->disk('public')
                ->directory('manual-payment-proofs')
                ->downloadable()
                ->openable()
                ->fetchFileInformation(false)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                ->maxSize(8192),
            DateTimePicker::make('paid_at')->label('Tanggal realita pembayaran'),
            Textarea::make('notes')->label('Catatan admin')->columnSpanFull(),
            Textarea::make('verification_notes')->label('Catatan validasi keuangan')->columnSpanFull(),
            Hidden::make('payment_type')->default('manual'),
        ])->columns(2);
    }

    public static function rrPaymentOptions(int $leadId, ?int $excludeManualPaymentId = null): array
    {
        if ($leadId < 1) {
            return [];
        }

        return static::billableRrPayments($leadId, $excludeManualPaymentId)
            ->mapWithKeys(fn (StudentPayment $payment): array => [
                $payment->id => static::rrPaymentOptionLabel($payment),
            ])
            ->all();
    }

    /**
     * An RR item already covered by a manual payment that's pending review or verified
     * shouldn't be selectable again, to avoid duplicate "Pembayaran Manual" entries for
     * the same underlying bill (e.g. a student's own proof upload plus a staff-created
     * entry for the same item). Rejected manual payments don't block re-submission.
     */
    public static function hasPendingManualPayment(int $rrPaymentId, ?int $excludeManualPaymentId = null): bool
    {
        return StudentPayment::query()
            ->where('payment_type', 'manual')
            ->where('source_row_json->rr_student_payment_id', $rrPaymentId)
            ->whereNotIn('verification_status', ['rejected'])
            ->when($excludeManualPaymentId, fn (Builder $query, int $id): Builder => $query->where('id', '!=', $id))
            ->exists();
    }

    public static function fillFromRrPayment(mixed $paymentId, Set $set): void
    {
        $payment = static::rrPaymentById((int) $paymentId);

        if (! $payment) {
            $set('payment_label', null);
            $set('amount', null);

            return;
        }

        $set('payment_label', static::rrPaymentLabel($payment));
        $set('amount', $payment->amount);
        $set('due_date', $payment->due_date?->toDateString() ?? now()->toDateString());
    }

    public static function rrPaymentById(int $paymentId): ?StudentPayment
    {
        if ($paymentId < 1) {
            return null;
        }

        return StudentPayment::query()
            ->whereKey($paymentId)
            ->where(fn (Builder $query): Builder => $query->whereNull('payment_type')->orWhere('payment_type', '!=', 'manual'))
            ->whereNotIn('status', ['paid', 'waived'])
            ->first();
    }

    public static function rrPaymentLabel(StudentPayment $payment): string
    {
        return $payment->payment_label ?: ((int) $payment->month === 0 ? 'Formulir Pendaftaran' : 'Bulan '.$payment->month);
    }

    public static function rrPaymentOptionLabel(StudentPayment $payment): string
    {
        $dueDate = $payment->due_date?->format('d/m/Y') ?: '-';

        return static::rrPaymentLabel($payment)
            .' - '.static::componentLabel($payment)
            .' - Rp '.number_format((int) $payment->amount, 0, ',', '.')
            .' - Rencana '.$dueDate;
    }

    public static function componentLabel(StudentPayment $payment): string
    {
        $components = collect([
            'Formulir' => $payment->registration_fee,
            'Development' => $payment->development_fee,
            'Tuition' => $payment->tuition_fee,
            'UKT' => $payment->ukt,
        ])->filter(fn ($amount): bool => (int) $amount > 0)->keys();

        $customItems = collect($payment->source_row_json['custom_items'] ?? [])
            ->filter(fn ($item): bool => is_array($item) && filled($item['name'] ?? null) && (int) ($item['amount'] ?? 0) > 0)
            ->map(fn (array $item): string => (string) $item['name']);

        return $components->merge($customItems->map(fn (string $name): string => 'Item: '.$name))->join(', ') ?: 'Tagihan';
    }

    public static function billableRrPayments(int $leadId, ?int $excludeManualPaymentId = null)
    {
        $query = StudentPayment::query()
            ->where('lead_id', $leadId)
            ->where(fn (Builder $query): Builder => $query->whereNull('payment_type')->orWhere('payment_type', '!=', 'manual'))
            ->whereNotIn('status', ['paid', 'waived'])
            ->orderBy('month')
            ->orderBy('due_date');

        $notAlreadyCovered = fn (StudentPayment $payment): bool => ! static::hasPendingManualPayment($payment->id, $excludeManualPaymentId);

        $registrationPayment = (clone $query)->where('month', 0)->first();

        if ($registrationPayment && ! in_array($registrationPayment->status, ['paid', 'waived'], true)) {
            return collect([$registrationPayment])->filter($notAlreadyCovered)->values();
        }

        return $query->where('month', '>', 0)->get()->filter($notAlreadyCovered)->values();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['lead.campus', 'lead.studyProgram', 'lead.studentBiodata', 'verifiedBy'])
            ->where('payment_type', 'manual')
            ->whereHas('lead', fn (Builder $leadQuery) => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery));
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('verification_status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('lead.studentBiodata.selection_number')->label('No. Seleksi')->placeholder('-')->searchable(),
                TextColumn::make('lead.full_name')->label('Nama')->searchable()->sortable()->wrap(),
                TextColumn::make('lead.campus.name')->label('Kampus')->searchable(),
                TextColumn::make('payment_label')->label('Item')->searchable()->wrap(),
                TextColumn::make('amount')->label('Nominal')->money('IDR')->sortable()->alignEnd(),
                TextColumn::make('payment_method')->label('Metode')->formatStateUsing(fn (?string $state): string => match ($state) {
                    'transfer_cv' => 'Transfer CV',
                    'bank_transfer' => 'Transfer bank',
                    'cash' => 'Tunai',
                    default => $state ? str($state)->replace('_', ' ')->title()->toString() : '-',
                }),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (?string $state): string => match ($state) {
                    'unpaid' => 'Belum dibayar',
                    'pending' => 'Menunggu verifikasi',
                    'paid' => 'Lunas',
                    'waived' => 'Dibebaskan',
                    default => $state ?? '-',
                }),
                TextColumn::make('verification_status')->label('Validasi')->badge()->formatStateUsing(fn (?string $state): string => match ($state) {
                    'pending' => 'Menunggu keuangan',
                    'verified' => 'Tervalidasi',
                    'rejected' => 'Ditolak',
                    default => 'Belum divalidasi',
                }),
                TextColumn::make('source_row_json.type')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'student_payment_proof_upload' ? 'info' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'student_payment_proof_upload' => 'Upload Mahasiswa',
                        'manual_payment_from_rr' => 'Input Staff',
                        default => '-',
                    }),
                TextColumn::make('verifiedBy.name')->label('Divalidasi oleh')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('openProof')
                    ->label('Bukti')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (StudentPayment $record): ?string => $record->proof_path ? Storage::disk('public')->url($record->proof_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (StudentPayment $record): bool => filled($record->proof_path)),
                Action::make('verify')
                    ->label('Validasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (StudentPayment $record): bool => FilamentResourceScope::canVerifyPayments() && $record->verification_status !== 'verified')
                    ->action(function (StudentPayment $record): void {
                        $rrPaymentId = (int) data_get($record->source_row_json, 'rr_student_payment_id');
                        $rrPayment = $rrPaymentId > 0 ? StudentPayment::query()->find($rrPaymentId) : null;

                        $record->update([
                            'status' => 'paid',
                            'verification_status' => 'verified',
                            'verified_by_user_id' => auth()->id(),
                            'verified_at' => now(),
                            'paid_at' => $record->paid_at ?? now(),
                        ]);

                        if ($rrPayment) {
                            $rrPayment->update([
                                'status' => 'paid',
                                'payment_method' => $record->payment_method,
                                'proof_path' => $record->proof_path,
                                'submitted_at' => $record->submitted_at ?? now(),
                                'verification_status' => 'verified',
                                'verified_by_user_id' => auth()->id(),
                                'verified_at' => now(),
                                'paid_at' => $record->paid_at ?? now(),
                                'notes' => trim(($rrPayment->notes ? $rrPayment->notes."\n" : '').'Dibayar melalui pembayaran manual #'.$record->id.'.'),
                                'verification_notes' => $record->verification_notes,
                            ]);
                        }

                        $lead = $record->lead()->with(['classTrack', 'studentBiodata', 'studentNumber'])->first();

                        if ($lead && (! $rrPayment || (int) $rrPayment->month === 0)) {
                            $lead->update([
                                'payment_status' => 'paid',
                                'enrollment_status' => $lead->enrollment_status === EnrollmentStatus::CalonMahasiswa
                                    ? EnrollmentStatus::MenungguPemberkasan
                                    : $lead->enrollment_status,
                                'pemberkasan_token' => $lead->pemberkasan_token ?? Str::random(64),
                                'locked_at' => $lead->locked_at ?? now(),
                            ]);
                            app(\App\Services\StudentBiodataProvisioner::class)->createForPaidRegistration($lead->refresh());

                            $invoice = $rrPayment?->invoice_id
                                ? Invoice::query()->find($rrPayment->invoice_id)
                                : null;

                            if ($invoice && $invoice->status !== InvoiceStatus::Paid) {
                                $invoice->update([
                                    'status' => InvoiceStatus::Paid,
                                    'paid_at' => $invoice->paid_at ?? ($record->paid_at ?? now()),
                                ]);

                                app(AuditLogger::class)->record('invoice_paid', $invoice, [
                                    'lead_id' => $lead->id,
                                    'source' => 'manual_payment',
                                    'manual_student_payment_id' => $record->id,
                                ]);
                            }
                        }

                        app(StudentPaymentReceiptMailer::class)->send(($rrPayment ?: $record)->fresh());

                        if ($lead) {
                            app(ReferralService::class)->syncMilestones($lead->refresh(['referralConversion', 'studentPayments']));
                        }
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([Textarea::make('verification_notes')->label('Alasan penolakan')->required()])
                    ->visible(fn (StudentPayment $record): bool => FilamentResourceScope::canVerifyPayments() && $record->verification_status !== 'rejected')
                    ->action(fn (StudentPayment $record, array $data) => $record->update([
                        'status' => 'unpaid',
                        'verification_status' => 'rejected',
                        'verified_by_user_id' => auth()->id(),
                        'verified_at' => now(),
                        'verification_notes' => $data['verification_notes'],
                    ])),
                Tables\Actions\EditAction::make()
                    ->visible(fn (StudentPayment $record): bool => $record->verification_status !== 'verified' || FilamentResourceScope::isSuperAdmin()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (StudentPayment $record): bool => $record->verification_status !== 'verified' || FilamentResourceScope::isSuperAdmin()),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManualStudentPayments::route('/'),
            'create' => Pages\CreateManualStudentPayment::route('/create'),
            'edit' => Pages\EditManualStudentPayment::route('/{record}/edit'),
        ];
    }

    public static function nextManualMonthFor(int $leadId): int
    {
        $latest = StudentPayment::query()
            ->where('lead_id', $leadId)
            ->where('month', '>=', 9000)
            ->max('month');

        return $latest ? ((int) $latest + 1) : 9001;
    }
}
