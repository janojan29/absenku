<?php

namespace App\Providers;

use App\Services\WhatsApp\FonnteWhatsAppClient;
use App\Services\WhatsApp\NullWhatsAppClient;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppClient::class, function () {
            $driver = config('whatsapp.driver', 'null');

            if ($driver === 'fonnte') {
                $token = (string) config('whatsapp.fonnte.token');
                if (! Str::of($token)->trim()->isEmpty()) {
                    return new FonnteWhatsAppClient(
                        token: $token,
                        baseUrl: (string) config('whatsapp.fonnte.base_url', 'https://api.fonnte.com'),
                        timeoutSeconds: (int) config('whatsapp.fonnte.timeout', 20),
                        connectTimeoutSeconds: (int) config('whatsapp.fonnte.connect_timeout', 10)
                    );
                }
            }

            return new NullWhatsAppClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verifikasi Alamat Email Anda')
                ->line('Silakan klik tombol di bawah ini untuk memverifikasi alamat email Anda.')
                ->action('Verifikasi Alamat Email', $url)
                ->line('Jika Anda tidak merasa membuat akun ini, abaikan saja email ini.');
        });

        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $token, $isValid) {
            if (! $isValid) {
                return false;
            }

            $lastActivity = $token->last_used_at ?? $token->created_at;
            if ($lastActivity && $lastActivity->diffInMinutes(now()) > 60) {
                return false;
            }

            return true;
        });
    }
}
