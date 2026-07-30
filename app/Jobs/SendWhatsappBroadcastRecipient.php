<?php

namespace App\Jobs;

use App\Models\WhatsappBroadcastRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SendWhatsappBroadcastRecipient implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $backoff = 60;

    public function __construct(public readonly int $recipientId) {}

    public function handle(): void
    {
        $recipient = WhatsappBroadcastRecipient::with('broadcast')->find($this->recipientId);

        if (! $recipient || $recipient->status !== 'queued') {
            return;
        }

        $limit = max(1, (int) config('spmm.whatsapp.broadcast_per_minute', 20));

        if (RateLimiter::tooManyAttempts('whatsapp-broadcast', $limit)) {
            $this->release(max(1, RateLimiter::availableIn('whatsapp-broadcast')));

            return;
        }

        RateLimiter::hit('whatsapp-broadcast', 60);

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
                $this->fail($recipient, 'HTTP '.$response->status().': '.$response->body());
            }
        } catch (Throwable $e) {
            Log::error('Broadcast WhatsApp send failed', ['error' => $e->getMessage(), 'recipient_id' => $recipient->id]);
            $this->fail($recipient, $e->getMessage());
        } finally {
            $this->refreshCounters($recipient);
        }
    }

    private function fail(WhatsappBroadcastRecipient $recipient, string $reason): void
    {
        if ($this->attempts() < $this->tries) {
            // Let the queue worker retry via $tries/$backoff.
            $recipient->update(['failed_reason' => $reason]);

            throw new \RuntimeException($reason);
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
            'failed_count' => (int) ($counts['failed'] ?? 0),
            'completed_at' => $finished ? now() : null,
        ]);
    }
}
