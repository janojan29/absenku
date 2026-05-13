<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

class NullWhatsAppClient implements WhatsAppClient
{
    public function sendText(string $to, string $message): void
    {
        Log::info('WhatsApp (null) sendText', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}
