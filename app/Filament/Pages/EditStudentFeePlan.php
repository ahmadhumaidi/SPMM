<?php

namespace App\Filament\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Lead;
use App\Models\StudentPayment;
use App\Services\ReferralService;
use App\Services\StudentBiodataProvisioner;
use App\Services\StudentPaymentScheduleService;
use App\Support\FilamentResourceScope;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class EditStudentFeePlan extends Page
{
    protected static ?string $slug = 'rincian-biaya-mahasiswa';

    protected static string $view = 'filament.pages.edit-student-fee-plan';

    protected static bool $shouldRegisterNavigation = false;

    public Lead $lead;

    public array $paymentRows = [];

    public ?string $planningFirstPaymentDate = null;

    public ?string $planningSecondPaymentDate = null;

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }

    public static function getRoutePath(): string
    {
        return '/'.static::getSlug().'/{lead}/edit';
    }

    public function mount(Lead $lead): void
    {
        abort_unless(FilamentResourceScope::canAccessCampus($lead->campus_id), 403);

        $this->lead = $lead;

        $this->loadLead();

        $schedulePayments = $this->lead->studentPayments
            ->filter(fn (StudentPayment $payment): bool => $payment->payment_type !== 'manual')
            ->values();

        $this->paymentRows = $schedulePayments
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

        $this->planningFirstPaymentDate = $schedulePayments
            ->firstWhere('month', 1)
            ?->due_date
            ?->format('Y-m-d');

        $this->planningSecondPaymentDate = $schedulePayments
            ->firstWhere('month', 2)
            ?->due_date
            ?->format('Y-m-d');
    }

    protected function loadLead(): void
    {
        $this->lead->loadMissing(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'latestInvoice', 'studentPayments' => fn ($query) => $query->orderBy('month')]);
    }

    public function planIsLocked(): bool
    {
        return $this->lead->studentBiodata === null;
    }

    public function runPlanning(): void
    {
        if ($this->planIsLocked()) {
            Notification::make()
                ->title('Belum bisa diedit')
                ->body('Lengkapi biodata mahasiswa baru untuk lead ini terlebih dahulu sebelum mengatur rencana pembayaran.')
                ->danger()
                ->send();

            return;
        }

        if (! filled($this->planningFirstPaymentDate) || ! filled($this->planningSecondPaymentDate)) {
            Notification::make()
                ->title('Tanggal pembayaran ke-1 dan ke-2 wajib diisi')
                ->danger()
                ->send();

            return;
        }

        $firstDate = Carbon::parse($this->planningFirstPaymentDate)->startOfDay();
        $secondDate = Carbon::parse($this->planningSecondPaymentDate)->startOfDay();

        foreach ($this->lead->studentPayments()
            ->where(fn ($query) => $query->whereNull('payment_type')->orWhere('payment_type', '!=', 'manual'))
            ->where('month', '>', 0)
            ->orderBy('month')
            ->get() as $payment) {
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

        $this->lead->refresh();
        $this->loadLead();

        Notification::make()
            ->title('Perencanaan tanggal pembayaran dijalankan')
            ->body('Bulan ke-3 dan seterusnya otomatis mengikuti tanggal pembayaran ke-2.')
            ->success()
            ->send();
    }

    public function savePayments(): void
    {
        if ($this->planIsLocked()) {
            Notification::make()
                ->title('Belum bisa diedit')
                ->body('Lengkapi biodata mahasiswa baru untuk lead ini terlebih dahulu sebelum menyimpan pembayaran.')
                ->danger()
                ->send();

            return;
        }

        $registrationMarkedPaid = false;
        $shouldSyncReferral = false;

        foreach ($this->paymentRows as $paymentId => $row) {
            $payment = $this->lead->studentPayments->firstWhere('id', (int) $paymentId)
                ?? StudentPayment::query()->where('lead_id', $this->lead->id)->find($paymentId);

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

            if ($payment->payment_type !== 'manual' && (int) $payment->month > 0 && in_array($row['status'] ?? null, ['paid', 'waived'], true)) {
                $shouldSyncReferral = true;
            }
        }

        if ($registrationMarkedPaid) {
            $this->markRegistrationAsPaid(app(StudentBiodataProvisioner::class), app(StudentPaymentScheduleService::class));
        }

        if ($shouldSyncReferral) {
            app(ReferralService::class)->syncMilestones($this->lead->fresh(['referralConversion', 'studentPayments']));
        }

        Notification::make()
            ->title('Pembayaran mahasiswa diperbarui')
            ->success()
            ->send();

        $this->redirect($this->redirectUrlForLead());
    }

    protected function markRegistrationAsPaid(StudentBiodataProvisioner $studentBiodata, StudentPaymentScheduleService $studentPayments): void
    {
        if ($this->lead->latestInvoice && $this->lead->latestInvoice->status !== InvoiceStatus::Paid) {
            $this->lead->latestInvoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => $this->lead->latestInvoice->paid_at ?? now(),
            ]);
        }

        $this->lead->update([
            'payment_status' => PaymentStatus::Paid,
            'enrollment_status' => EnrollmentStatus::MenungguPemberkasan,
            'pemberkasan_token' => $this->lead->pemberkasan_token ?? Str::random(64),
            'locked_at' => $this->lead->locked_at ?? now(),
        ]);

        $lead = $this->lead->fresh(['classTrack', 'studentBiodata', 'studentNumber', 'latestInvoice']);

        $studentPayments->generateForLead($lead, invoice: $lead->latestInvoice, includeHerregistration: true);
        $studentBiodata->createForPaidRegistration($lead);
    }

    public function redirectUrlForLead(): string
    {
        $studentType = $this->lead->fresh('studentBiodata')->studentBiodata?->student_type;

        return $studentType === 'old'
            ? OldStudentFeeDetail::getUrl()
            : NewStudentFeeDetail::getUrl();
    }

    public function getTitle(): string
    {
        return 'Rincian Biaya - '.$this->lead->full_name;
    }
}
