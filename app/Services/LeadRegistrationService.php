<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\FeeScheme;
use App\Models\Invoice;
use App\Models\Lead;
use App\Services\Payment\InvoiceService;
use App\Services\Whatsapp\WhatsappManager;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadRegistrationService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly WhatsappManager $whatsapp,
        private readonly AuditLogger $audit,
        private readonly StudentAccountProvisioner $studentAccounts,
        private readonly StudentPaymentScheduleService $studentPayments,
        private readonly ReferralService $referrals,
    ) {
    }

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $feeScheme = FeeScheme::query()
                ->where('campus_id', $data['campus_id'])
                ->where(function ($query) use ($data): void {
                    $query->whereNull('study_program_id')
                        ->orWhere('study_program_id', $data['study_program_id']);
                })
                ->where(function ($query) use ($data): void {
                    $query->whereNull('class_track_id')
                        ->orWhere('class_track_id', $data['class_track_id']);
                })
                ->where('is_active', true)
                ->orderByRaw('study_program_id is null')
                ->orderByRaw('class_track_id is null')
                ->first();

            if (! $feeScheme) {
                throw ValidationException::withMessages([
                    'study_program_id' => 'Biaya pendaftaran untuk pilihan kampus, program studi, dan waktu kuliah ini belum diatur. Silakan pilih kombinasi lain atau hubungi admin PMB.',
                ]);
            }

            $lead = Lead::create([
                'campus_id' => $data['campus_id'],
                'study_program_id' => $data['study_program_id'],
                'class_track_id' => $data['class_track_id'],
                'source_channel' => $data['source_channel'] ?? 'website',
                'source_detail' => $data['source_detail'] ?? 'Website',
                'full_name' => $data['full_name'],
                'whatsapp_number' => PhoneNumber::normalizeWhatsapp($data['whatsapp_number'], config('spmm.whatsapp.default_country_code')),
                'email' => $data['email'],
                'origin_school' => $data['origin_school'] ?? null,
                'graduation_year' => $data['graduation_year'] ?? null,
                'lead_status' => LeadStatus::InPool,
            ]);

            $referralPartner = $this->referrals->partnerForCode($data['referral_code'] ?? null);

            if ($referralPartner) {
                $this->referrals->recordRegistration($lead, $referralPartner);
            }

            /** @var Invoice $invoice */
            $invoice = $this->invoices->createForLead($lead, $feeScheme);
            $this->studentPayments->generateForLead($lead, $feeScheme, $invoice);
            $this->whatsapp->driver()->sendInvoiceMessage($lead, $invoice);
            $this->studentAccounts->createForLead($lead);
            $this->audit->record('lead_created', $lead, ['invoice_id' => $invoice->id]);
            $this->audit->record('invoice_created', $invoice, ['lead_id' => $lead->id]);

            return compact('lead', 'invoice');
        });
    }
}
