<?php

namespace App\Jobs;

use App\Models\WhatsappBroadcastRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendWhatsappBroadcastRecipientViaFonnte implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly int $recipientId) {}

    public function handle(): void
    {
        $recipient = WhatsappBroadcastRecipient::with('broadcast')->find($this->recipientId);

        if (! $recipient || $recipient->status !== 'queued') {
            return;
        }

        $recipient->increment('attempts');

        $message = $recipient->broadcast->renderMessageForRecipient($recipient);
        $token = config('spmm.whatsapp.fonnte_token');

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->withHeaders(['Authorization' => $token])
                ->post('https://api.fonnte.com/send', [
                    'target' => $recipient->recipient_number,
                    'message' => $message,
                ]);

            if ($response->successful() && $response->json('status')) {
                $recipient->update([
                    'status' => 'sent',
                    'provider_reference' => (string) $response->json('detail'),
                    'sent_at' => now(),
                    'failed_reason' => null,
                ]);
            } else {
                $this->markFailed($recipient, 'HTTP '.$response->status().': '.$response->body());
            }
        } catch (Throwable $e) {
            Log::error('Fonnte broadcast send failed', ['error' => $e->getMessage(), 'recipient_id' => $recipient->id]);
            $this->markFailed($recipient, $e->getMessage());
        } finally {
            $this->refreshCounters($recipient);
        }
    }

    private function markFailed(WhatsappBroadcastRecipient $recipient, string $reason): void
    {
        if ($this->attempts() < $this->tries) {
            $recipient->update(['failed_reason' => $reason]);

            throw new RuntimeException($reason);
        }

        $recipient->update(['status' => 'failed', 'failed_reason' => $reason]);
    }

    private function refreshCounters(WhatsappBroadcastRecipient $recipient): void
    {
        $broadcast = $recipient->broadcast;

        $counts = $broadcast->recipients()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $finished = (int) $counts->except(['queued'])->sum() >= $broadcast->recipient_count;

        $broadcast->update([
            'status' => $finished ? 'completed' : 'sending',
            'sent_count' => (int) ($counts['sent'] ?? 0),
            'failed_count' => (int) ($counts['failed'] ?? 0) + (int) ($counts['invalid'] ?? 0),
            'completed_at' => $finished ? now() : null,
        ]);
    }
}
