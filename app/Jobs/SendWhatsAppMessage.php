<?php

namespace App\Jobs;

use App\Models\WhatsAppLog;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $to,
        public readonly string $message,
        public readonly ?string $relatedType = null,
        public readonly ?int $relatedId = null,
    ) {
    }

    public function handle(WhatsAppClient $client): void
    {
        $log = WhatsAppLog::query()->create([
            'provider' => (string) config('whatsapp.driver', 'unknown'),
            'to' => $this->to,
            'message' => $this->message,
            'status' => 'queued',
            'related_type' => $this->relatedType,
            'related_id' => $this->relatedId,
        ]);

        try {
            $client->sendText($this->to, $this->message);
            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
