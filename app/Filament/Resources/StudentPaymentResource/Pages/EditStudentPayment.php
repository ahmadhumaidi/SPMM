<?php

namespace App\Filament\Resources\StudentPaymentResource\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\StudentPaymentResource;
use App\Models\StudentPayment;
use App\Services\StudentBiodataProvisioner;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditStudentPayment extends EditRecord
{
    protected static string $resource = StudentPaymentResource::class;

    protected static string $view = 'filament.resources.student-payment-resource.pages.edit-student-payment';

    public array $paymentRows = [];

    public ?string $planningFirstPaymentDate = null;

    public ?string $planningSecondPaymentDate = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'latestInvoice', 'studentPayments' => fn ($query) => $query->orderBy('month')]);

        $this->paymentRows = $this->record->studentPayments
            ->mapWithKeys(fn (StudentPayment $payment): array => [
                $payment->id => [
                    'month' => $payment->month,
                    'payment_label' => $payment->payment_label,
                    'registration_fee' => $payment->registration_fee,
                    'development_fee' => $payment->development_fee,
                    'tuition_fee' => $payment->tuition_fee,
                    'ukt' => $payment->ukt,
                    'amount' => $payment->amount,
                    'due_date' => $payment->due_date?->format('Y-m-d'),
                    'status' => $payment->status,
                    'paid_at' => $payment->paid_at?->format('Y-m-d\TH:i'),
                    'notes' => $payment->notes,
                ],
            ])
            ->all();

        $this->planningFirstPaymentDate = $this->record->studentPayments
            ->firstWhere('month', 1)
            ?->due_date
            ?->format('Y-m-d');

        $this->planningSecondPaymentDate = $this->record->studentPayments
            ->firstWhere('month', 2)
            ?->due_date
            ?->format('Y-m-d');
    }

    public function runPlanning(): void
    {
        if (! filled($this->planningFirstPaymentDate) || ! filled($this->planningSecondPaymentDate)) {
            Notification::make()
                ->title('Tanggal pembayaran ke-1 dan ke-2 wajib diisi')
                ->danger()
                ->send();

            return;
        }

        $firstDate = Carbon::parse($this->planningFirstPaymentDate)->startOfDay();
        $secondDate = Carbon::parse($this->planningSecondPaymentDate)->startOfDay();

        foreach ($this->record->studentPayments()->where('month', '>', 0)->orderBy('month')->get() as $payment) {
            $month = (int) $payment->month;

            if ($month === 1) {
                $date = $firstDate->copy();
            } elseif ($month === 2) {
                $date = $secondDate->copy();
            } else {
                $date = $secondDate->copy()->addMonthsNoOverflow($month - 2);
            }

            $dateValue = $date->toDateString();

            $payment->update([
                'due_date' => $dateValue,
            ]);

            if (isset($this->paymentRows[$payment->id])) {
                $this->paymentRows[$payment->id]['due_date'] = $dateValue;
            }
        }

        $this->record->refresh()->load(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'latestInvoice', 'studentPayments' => fn ($query) => $query->orderBy('month')]);

        Notification::make()
            ->title('Perencanaan tanggal pembayaran dijalankan')
            ->body('Bulan ke-3 dan seterusnya otomatis mengikuti tanggal pembayaran ke-2.')
            ->success()
            ->send();
    }

    public function savePayments(): void
    {
        $registrationMarkedPaid = false;

        foreach ($this->paymentRows as $paymentId => $row) {
            $payment = $this->record->studentPayments->firstWhere('id', (int) $paymentId)
                ?? StudentPayment::query()->where('lead_id', $this->record->id)->find($paymentId);

            if (! $payment) {
                continue;
            }

            $payment->update([
                'payment_label' => $row['payment_label'] ?? $payment->payment_label,
                'registration_fee' => (int) ($row['registration_fee'] ?? 0),
                'development_fee' => (int) ($row['development_fee'] ?? 0),
                'tuition_fee' => (int) ($row['tuition_fee'] ?? 0),
                'ukt' => (int) ($row['ukt'] ?? 0),
                'amount' => (int) ($row['amount'] ?? 0),
                'due_date' => filled($row['due_date'] ?? null) ? $row['due_date'] : null,
                'status' => $row['status'] ?? 'unpaid',
                'paid_at' => filled($row['paid_at'] ?? null) ? $row['paid_at'] : null,
                'notes' => $row['notes'] ?? null,
            ]);

            if ((int) $payment->month === 0 && ($row['status'] ?? null) === 'paid') {
                $registrationMarkedPaid = true;
            }
        }

        if ($registrationMarkedPaid) {
            $this->markRegistrationAsPaid(app(StudentBiodataProvisioner::class));
        }

        $this->record->refresh()->load(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'latestInvoice', 'studentPayments' => fn ($query) => $query->orderBy('month')]);

        Notification::make()
            ->title('Pembayaran mahasiswa diperbarui')
            ->success()
            ->send();
    }

    protected function markRegistrationAsPaid(StudentBiodataProvisioner $studentBiodata): void
    {
        if ($this->record->latestInvoice && $this->record->latestInvoice->status !== InvoiceStatus::Paid) {
            $this->record->latestInvoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => $this->record->latestInvoice->paid_at ?? now(),
            ]);
        }

        $this->record->update([
            'payment_status' => PaymentStatus::Paid,
            'enrollment_status' => EnrollmentStatus::MenungguPemberkasan,
            'pemberkasan_token' => $this->record->pemberkasan_token ?? Str::random(64),
            'locked_at' => $this->record->locked_at ?? now(),
        ]);

        $studentBiodata->createForPaidRegistration($this->record->fresh(['classTrack', 'studentBiodata', 'studentNumber']));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
