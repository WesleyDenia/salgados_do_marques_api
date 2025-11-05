<?php

namespace App\Contracts\Notifications;

interface WhatsAppClient
{
    /**
     * Envia uma mensagem de WhatsApp para o número informado.
     */
    public function sendMessage(string $phone, string $message): bool;
}
