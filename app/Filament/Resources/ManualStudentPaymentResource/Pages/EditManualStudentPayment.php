<?php

namespace App\Filament\Resources\ManualStudentPaymentResource\Pages;

use App\Filament\Resources\ManualStudentPaymentResource;
use Filament\Resources\Pages\EditRecord;

class EditManualStudentPayment extends EditRecord
{
    protected static string $resource = ManualStudentPaymentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['rr_student_payment_id'] ?? null)) {
            $rrPayment = ManualStudentPaymentResource::rrPaymentById((int) $data['rr_student_payment_id']);

            if (! $rrPayment || (int) $rrPayment->lead_id !== (int) ($data['lead_id'] ?? $this->record->lead_id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'rr_student_payment_id' => 'Item pembayaran harus dipilih dari Rincian Biaya (RR) mahasiswa yang aktif.',
                ]);
            }

            $data['payment_label'] = ManualStudentPaymentResource::rrPaymentLabel($rrPayment);
            $data['amount'] = (int) $rrPayment->amount;
            $data['due_date'] = $rrPayment->due_date?->toDateString() ?? ($data['due_date'] ?? now()->toDateString());
            $data['registration_fee'] = (int) $rrPayment->registration_fee;
            $data['development_fee'] = (int) $rrPayment->development_fee;
            $data['tuition_fee'] = (int) $rrPayment->tuition_fee;
            $data['ukt'] = (int) $rrPayment->ukt;
            $data['source_row_json'] = array_merge($rrPayment->source_row_json ?: [], [
                'type' => 'manual_payment_from_rr',
                'rr_student_payment_id' => $rrPayment->id,
                'rr_month' => $rrPayment->month,
                'rr_payment_label' => ManualStudentPaymentResource::rrPaymentLabel($rrPayment),
            ]);
        }

        unset($data['rr_student_payment_id']);
        unset($data['status']);

        if (filled($data['proof_path'] ?? null) && ($data['verification_status'] ?? null) === 'unverified') {
            $data['verification_status'] = 'pending';
            $data['submitted_at'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
