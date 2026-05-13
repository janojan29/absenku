<?php

namespace App\Providers;

use App\Services\WhatsApp\FonnteWhatsAppClient;
use App\Services\WhatsApp\NullWhatsAppClient;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
