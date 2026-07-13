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

    public readonly string $to;

    public function __construct(
        string $to,
        public readonly string $message,
        public readonly ?string $relatedType = null,
        public readonly ?int $relatedId = null,
    ) {
        // Hapus semua karakter non-angka
        $cleaned = preg_replace('/[^0-9]/', '', $to);
        
        // Ubah awalan 0 menjadi 62 untuk standar internasional (karena kita mematikan auto-formatting Fonnte)
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        }

        $this->to = $cleaned;
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

            // Log the error but do NOT re-throw when running synchronously,
            // otherwise the caller (e.g. attendance check-in) will get a 500 error
            // even though the attendance was saved successfully.
            \Illuminate\Support\Facades\Log::error('WhatsApp send failed', [
                'to' => $this->to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
