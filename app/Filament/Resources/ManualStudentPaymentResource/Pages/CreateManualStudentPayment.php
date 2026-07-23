<?php

namespace App\Filament\Resources\ManualStudentPaymentResource\Pages;

use App\Filament\Resources\ManualStudentPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManualStudentPayment extends CreateRecord
{
    protected static string $resource = ManualStudentPaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rrPayment = ManualStudentPaymentResource::rrPaymentById((int) ($data['rr_student_payment_id'] ?? 0));

        if (! $rrPayment || (int) $rrPayment->lead_id !== (int) ($data['lead_id'] ?? 0)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'rr_student_payment_id' => 'Item pembayaran harus dipilih dari Rincian Biaya (RR) mahasiswa yang aktif.',
            ]);
        }

        $data['payment_label'] = ManualStudentPaymentResource::rrPaymentLabel($rrPayment);
        $data['amount'] = (int) $rrPayment->amount;
        $data['due_date'] = $rrPayment->due_date?->toDateString() ?? ($data['due_date'] ?? now()->toDateString());
        $data['status'] = 'pending';

        $data['payment_type'] = 'manual';
        $data['month'] = ManualStudentPaymentResource::nextManualMonthFor((int) $data['lead_id']);
        $data['registration_fee'] = (int) $rrPayment->registration_fee;
        $data['development_fee'] = (int) $rrPayment->development_fee;
        $data['tuition_fee'] = (int) $rrPayment->tuition_fee;
        $data['ukt'] = (int) $rrPayment->ukt;
        $data['verification_status'] = filled($data['proof_path'] ?? null) ? 'pending' : 'unverified';
        $data['submitted_at'] = filled($data['proof_path'] ?? null) ? now() : null;
        $data['source_row_json'] = array_merge($rrPayment->source_row_json ?: [], [
            'type' => 'manual_payment_from_rr',
            'rr_student_payment_id' => $rrPayment->id,
            'rr_month' => $rrPayment->month,
            'rr_payment_label' => ManualStudentPaymentResource::rrPaymentLabel($rrPayment),
        ]);

        unset($data['rr_student_payment_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
