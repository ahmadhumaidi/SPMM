<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Services\InvoicePaymentReminderMailer;
use App\Services\InvoiceRegenerationService;
use App\Support\FilamentResourceScope;
use App\Support\FinancialStatusLabels;
use DomainException;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Payment';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessPayments();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('invoice_number')->disabled(),
            TextInput::make('lead.full_name')->label('Lead')->disabled(),
            TextInput::make('payment_gateway')->disabled(),
            TextInput::make('gateway_reference')->disabled(),
            TextInput::make('amount')
                ->label('Tagihan aktif')
                ->prefix('Rp')
                ->formatStateUsing(fn (?Invoice $record): int => static::activeBillingAmount($record))
                ->disabled(),
            TextInput::make('payment_method')->disabled(),
            TextInput::make('payment_url')->disabled()->columnSpanFull(),
            TextInput::make('status')
                ->label('Status Keuangan')
                ->formatStateUsing(fn ($state, ?Invoice $record): string => static::financialStatusLabel($record))
                ->disabled(),
            DateTimePicker::make('expires_at')->disabled(),
            DateTimePicker::make('paid_at')->disabled(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['lead.studentPayments']);
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas('lead', fn (Builder $leadQuery) => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('invoice_number')->searchable()->sortable(),
                TextColumn::make('lead.full_name')->searchable(),
                TextColumn::make('amount')
                    ->label('Tagihan Aktif')
                    ->state(fn (Invoice $record): int => static::activeBillingAmount($record))
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label('Status Keuangan')
                    ->state(fn (Invoice $record): string => static::financialStatusLabel($record))
                    ->formatStateUsing(fn (string $state) => FinancialStatusLabels::statusDotHtml($state))
                    ->html(),
                TextColumn::make('payment_gateway'),
                TextColumn::make('expires_at')->dateTime()->sortable(),
                TextColumn::make('paid_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status invoice teknis')
                    ->options([
                    InvoiceStatus::Pending->value => 'Pending',
                    InvoiceStatus::Paid->value => 'Paid',
                    InvoiceStatus::Expired->value => 'Expired',
                    InvoiceStatus::Cancelled->value => 'Cancelled',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('send_payment_reminder')
                    ->label('Kirim Invoice')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Invoice $record): string => 'Kirim email tagihan pendaftaran ini ke '.($record->lead?->email ?: 'email calon mahasiswa').'?')
                    ->visible(fn (Invoice $record): bool => $record->isPayable() && filled($record->lead?->email))
                    ->action(function (Invoice $record): void {
                        app(InvoicePaymentReminderMailer::class)->send($record);

                        Notification::make()
                            ->title('Invoice dikirim ke '.$record->lead?->email)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('regenerate_invoice')
                    ->label('Buat Ulang Tagihan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record): bool => in_array($record->status, [InvoiceStatus::Expired, InvoiceStatus::Cancelled], true)
                        && $record->lead !== null
                        && $record->id === $record->lead->latestInvoice?->id)
                    ->action(function (Invoice $record, InvoiceRegenerationService $regeneration): void {
                        try {
                            $regeneration->regenerate($record->lead);

                            Notification::make()->title('Invoice baru dibuat')->success()->send();
                        } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    private static function financialStatusLabel(?Invoice $invoice): string
    {
        if (! $invoice?->lead) {
            return '-';
        }

        return FinancialStatusLabels::leadStatus($invoice->lead->loadMissing('studentPayments'));
    }

    private static function financialStatusColor(string $state): string
    {
        return match (true) {
            str_contains($state, 'Herregistrasi') && (str_contains($state, 'Lunas') || str_contains($state, 'Dibebaskan')) => 'success',
            str_contains($state, 'Registrasi') && (str_contains($state, 'Lunas') || str_contains($state, 'Dibebaskan')) => 'info',
            str_contains($state, 'Menunggu') => 'warning',
            str_contains($state, 'Kedaluwarsa') || str_contains($state, 'Ditolak') || str_contains($state, 'Batal') => 'danger',
            default => 'gray',
        };
    }
    private static function activeBillingAmount(?Invoice $invoice): int
    {
        if (! $invoice?->lead) {
            return 0;
        }

        $payments = $invoice->lead->relationLoaded('studentPayments')
            ? $invoice->lead->studentPayments
            : $invoice->lead->studentPayments()->get();

        $scheduledPayments = $payments
            ->filter(fn ($payment): bool => $payment->payment_type !== 'manual')
            ->values();

        $registrationPayment = $scheduledPayments->firstWhere('month', 0);
        $registrationPaid = $registrationPayment && in_array($registrationPayment->status, ['paid', 'waived'], true);

        if (! $registrationPaid) {
            return (int) $scheduledPayments
                ->filter(fn ($payment): bool => (int) $payment->month === 0)
                ->filter(fn ($payment): bool => ! in_array($payment->status, ['paid', 'waived'], true))
                ->sum('amount');
        }

        return (int) $scheduledPayments
            ->filter(fn ($payment): bool => $payment->due_date && $payment->due_date->lte(now()->endOfDay()))
            ->filter(fn ($payment): bool => ! in_array($payment->status, ['paid', 'waived'], true))
            ->sum('amount');
    }
}


