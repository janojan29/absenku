<?php

namespace App\Services\WhatsApp;

interface WhatsAppClient
{
    /**
     * Send a plain text WhatsApp message.
     *
     * @param string $to E.164 formatted number (e.g. +62812xxxxxxx)
     */
    public function sendText(string $to, string $message): void;
}
