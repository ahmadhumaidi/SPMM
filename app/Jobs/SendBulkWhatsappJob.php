<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBulkWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, array{number: string, name: ?string, lead_id: ?int, vars?: array<string, string>}>  $recipients
     */
    public function __construct(
        private readonly array $recipients,
        private readonly string $messageTemplate,
    ) {}

    public function handle(): void
    {
        $token = config('spmm.whatsapp.fonnte_token');

        foreach ($this->recipients as $recipient) {
            $vars = $recipient['vars'] ?? [];

            $message = str_replace(
                ['{{nama}}', '{{1}}', '{{2}}', '{{3}}'],
                [$recipient['name'] ?? '', $vars['1'] ?? '', $vars['2'] ?? '', $vars['3'] ?? ''],
                $this->messageTemplate,
            );

            $status = 'failed';
            $reference = null;
            $failedReason = null;

            try {
                $response = Http::asForm()
                    ->timeout(15)
                    ->withHeaders(['Authorization' => $token])
                    ->post('https://api.fonnte.com/send', [
                        'target' => $recipient['number'],
                        'message' => $message,
                    ]);

                if ($response->successful() && $response->json('status')) {
                    $status = 'sent';
                    $reference = (string) $response->json('detail');
                } else {
                    $failedReason = 'HTTP '.$response->status().': '.$response->body();
                }
            } catch (Throwable $e) {
                $failedReason = $e->getMessage();
                Log::error('Bulk WhatsApp send failed', ['error' => $e->getMessage(), 'target' => $recipient['number']]);
            }

            WhatsappMessage::create([
                'lead_id' => $recipient['lead_id'] ?? null,
                'recipient_number' => $recipient['number'],
                'template_key' => 'bulk_manual',
                'message' => $message,
                'provider_reference' => $reference,
                'status' => $status,
                'sent_at' => $status === 'sent' ? now() : null,
                'failed_reason' => $failedReason,
            ]);
        }
    }
}
