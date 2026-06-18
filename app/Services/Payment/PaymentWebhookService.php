<?php

namespace App\Services\Payment;

use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\PaymentEvent;
use App\Services\Whatsapp\WhatsappManager;
use App\Services\AuditLogger;
use App\Services\ReferralService;
use App\Services\StudentBiodataProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PaymentWebhookService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly WhatsappManager $whatsapp,
        private readonly AuditLogger $audit,
        private readonly ReferralService $referrals,
        private readonly StudentBiodataProvisioner $studentBiodata,
    ) {
    }

    public function handle(string $provider, Request $request): PaymentEvent
    {
        $gateway = $this->gateways->driver($provider);

        if (! $gateway->validateWebhook($request)) {
            throw new AccessDeniedHttpException('Invalid payment webhook signature.');
        }

        $result = $gateway->parseWebhook($request);

        return DB::transaction(function () use ($result): PaymentEvent {
            $invoice = Invoice::query()
                ->where('gateway_reference', $result->gatewayReference)
                ->lockForUpdate()
                ->first();

            $event = PaymentEvent::create([
                'invoice_id' => $invoice?->id,
                'gateway_reference' => $result->gatewayReference,
                'event_type' => $result->eventType,
                'payload_json' => $result->payload,
            ]);

            if ($invoice === null) {
                return $event;
            }

            if ($result->isPaid && $invoice->status !== InvoiceStatus::Paid) {
                $invoice->update([
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => now(),
                ]);

                $invoice->lead->update([
                    'payment_status' => PaymentStatus::Paid,
                    'enrollment_status' => EnrollmentStatus::MenungguPemberkasan,
                    'pemberkasan_token' => $invoice->lead->pemberkasan_token ?? Str::random(64),
                    'locked_at' => now(),
                ]);

                $lead = $invoice->lead->fresh();
                $this->studentBiodata->createForPaidRegistration($lead);
                $this->referrals->markPaid($lead);
                $this->whatsapp->driver()->sendPaymentSuccess($lead, $invoice);
                $this->whatsapp->driver()->sendPemberkasanLink($lead);
                $this->audit->record('invoice_paid', $invoice, ['lead_id' => $lead->id]);
            }

            $event->update(['processed_at' => now()]);

            return $event->refresh();
        });
    }
}
