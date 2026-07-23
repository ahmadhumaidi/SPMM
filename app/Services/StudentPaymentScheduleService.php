<?php

namespace App\Services;

use App\Models\FeeScheme;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\StudentPayment;
use Illuminate\Database\Eloquent\Collection;

class StudentPaymentScheduleService
{
    public function generateForLead(Lead $lead, ?FeeScheme $feeScheme = null, ?Invoice $invoice = null, bool $includeHerregistration = true): Collection
    {
        $feeScheme ??= $this->resolveFeeScheme($lead);

        if (! $feeScheme) {
            return new Collection();
        }

        $schedule = $this->scheduleRows($feeScheme);

        if ($schedule === []) {
            return new Collection();
        }

        $existingPaid = $lead->studentPayments()
            ->where('status', 'paid')
            ->pluck('id', 'month');

        $records = new Collection();
        $registrationFee = (int) ($feeScheme->registration_fee ?? 0);

        if ($registrationFee > 0) {
            $registrationPayload = [
                'fee_scheme_id' => $feeScheme->id,
                'invoice_id' => $invoice?->id,
                'payment_label' => 'Formulir Pendaftaran',
                'registration_fee' => $registrationFee,
                'development_fee' => 0,
                'tuition_fee' => 0,
                'ukt' => 0,
                'amount' => $registrationFee,
                'due_date' => $lead->created_at?->copy()->startOfDay()->toDateString() ?? now()->toDateString(),
                'source_row_json' => [
                    'type' => 'registration_form',
                    'registration_fee' => $registrationFee,
                    'invoice_id' => $invoice?->id,
                ],
            ];

            if (! $existingPaid->has(0)) {
                $registrationPayload['status'] = $invoice?->status?->value === 'paid' ? 'paid' : 'unpaid';
                $registrationPayload['paid_at'] = $invoice?->paid_at;
            }

            $records->push(StudentPayment::query()->updateOrCreate(
                [
                    'lead_id' => $lead->id,
                    'month' => 0,
                ],
                $registrationPayload
            ));
        }

        if (! $includeHerregistration) {
            return $records;
        }

        foreach ($schedule as $row) {
            $month = (int) ($row['month'] ?? 0);

            $registrationFee = (int) ($row['registration_fee'] ?? 0);
            $developmentFee = (int) ($row['development_fee'] ?? 0);
            $tuitionFee = (int) ($row['tuition_fee'] ?? 0);
            $ukt = (int) ($row['ukt'] ?? 0);
            $herRegistrationAmount = $developmentFee + $tuitionFee + $ukt;

            if ($month < 1 || $herRegistrationAmount < 1) {
                continue;
            }

            $payload = [
                'fee_scheme_id' => $feeScheme->id,
                'invoice_id' => null,
                'payment_label' => 'Bulan '.$month,
                'registration_fee' => 0,
                'development_fee' => $developmentFee,
                'tuition_fee' => $tuitionFee,
                'ukt' => $ukt,
                'amount' => $herRegistrationAmount,
                'due_date' => $lead->created_at?->copy()->startOfDay()->addMonths($month - 1)->toDateString() ?? now()->addMonths($month - 1)->toDateString(),
                'source_row_json' => array_merge($row, [
                    'registration_fee' => $registrationFee,
                    'herregistration_amount' => $herRegistrationAmount,
                ]),
            ];

            if (! $existingPaid->has($month)) {
                $payload['status'] = 'unpaid';
                $payload['paid_at'] = null;
            }

            $records->push(StudentPayment::query()->updateOrCreate(
                [
                    'lead_id' => $lead->id,
                    'month' => $month,
                ],
                $payload
            ));
        }

        return $records;
    }

    public function resolveFeeScheme(Lead $lead): ?FeeScheme
    {
        return FeeScheme::query()
            ->where('campus_id', $lead->campus_id)
            ->where(function ($query) use ($lead): void {
                $query->whereNull('study_program_id')
                    ->orWhere('study_program_id', $lead->study_program_id);
            })
            ->where(function ($query) use ($lead): void {
                $query->whereNull('class_track_id')
                    ->orWhere('class_track_id', $lead->class_track_id);
            })
            ->where('is_active', true)
            ->orderByRaw('study_program_id is null')
            ->orderByRaw('class_track_id is null')
            ->first();
    }

    private function scheduleRows(FeeScheme $feeScheme): array
    {
        $customSchedule = $feeScheme->custom_installment_schedule_json ?: [];

        if ($feeScheme->uses_custom_installments && $customSchedule !== []) {
            return $customSchedule;
        }

        return $feeScheme->installment_schedule_json ?: [];
    }
}
