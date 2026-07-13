<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FonnteWhatsAppClient implements WhatsAppClient
{
    public function __construct(
        private readonly string $token,
        private readonly string $baseUrl = 'https://api.fonnte.com',
        private readonly int $timeoutSeconds = 20,
        private readonly int $connectTimeoutSeconds = 10
    ) {}

    public function sendText(string $to, string $message): void
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Authorization' => $this->token,
            ])
            ->timeout($this->timeoutSeconds)
            ->connectTimeout($this->connectTimeoutSeconds)
            ->asForm()
            ->post('/send', [
                'target' => $to,
                'message' => $message,
                'countryCode' => '0', // Disable Fonnte's auto-formatting to give us full control
            ])
            ->throw();

        $payload = $response->json();

        // Fonnte may respond HTTP 200 with status=false for business-level failures.
        if (is_array($payload) && array_key_exists('status', $payload) && $payload['status'] === false) {
            $reason = (string) ($payload['reason'] ?? $payload['message'] ?? 'Unknown provider error');

            throw new RuntimeException('Fonnte rejected message: ' . $reason);
        }
    }
}
