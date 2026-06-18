<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\PaymentEvent;
use App\Services\ReferralService;
use App\Services\StudentBiodataProvisioner;
use App\Services\Whatsapp\WhatsappManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MockPaymentController extends Controller
{
    public function pay(string $reference, WhatsappManager $whatsapp, ReferralService $referrals, StudentBiodataProvisioner $studentBiodata): JsonResponse
    {
        abort_unless(app()->environment('local'), 404);

        $invoice = Invoice::query()
            ->where('gateway_reference', $reference)
            ->firstOrFail();

        DB::transaction(function () use ($invoice, $reference, $whatsapp, $referrals, $studentBiodata): void {
            PaymentEvent::create([
                'invoice_id' => $invoice->id,
                'gateway_reference' => $reference,
                'event_type' => 'mock.payment.paid',
                'payload_json' => ['gateway_reference' => $reference, 'paid' => true],
                'processed_at' => now(),
            ]);

            if ($invoice->status !== InvoiceStatus::Paid) {
                $invoice->update([
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => now(),
                ]);

                $lead = $invoice->lead;
                $lead->update([
                    'payment_status' => PaymentStatus::Paid,
                    'enrollment_status' => EnrollmentStatus::MenungguPemberkasan,
                    'pemberkasan_token' => $lead->pemberkasan_token ?? Str::random(64),
                    'locked_at' => now(),
                ]);

                $lead = $lead->fresh();
                $studentBiodata->createForPaidRegistration($lead);
                $referrals->markPaid($lead);
                $whatsapp->driver()->sendPaymentSuccess($lead, $invoice);
                $whatsapp->driver()->sendPemberkasanLink($lead);
            }
        });

        return response()->json([
            'message' => 'Mock payment marked as paid.',
            'invoice_number' => $invoice->invoice_number,
            'pemberkasan_url' => url('/pemberkasan/'.$invoice->lead->fresh()->pemberkasan_token),
        ]);
    }
}
