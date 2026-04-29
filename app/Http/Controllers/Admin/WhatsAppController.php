<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Notifications\WhatsAppClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TriggerWhatsAppHealthCheckRequest;
use App\Services\OrderService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;

class WhatsAppController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsApp,
        protected OrderService $orders,
    ) {}

    public function index()
    {
        return view('admin.whatsapp.index', [
            'session' => $this->whatsApp->sessionSnapshot(),
        ]);
    }

    public function healthCheck(TriggerWhatsAppHealthCheckRequest $request, WhatsAppClient $whatsAppClient): RedirectResponse
    {
        $recipient = $this->orders->whatsappOrderRecipient();

        if ($recipient === '') {
            return back()->with('error', 'Configure WHATSAPP_ORDER_TO antes de executar o health check.');
        }

        if (!$whatsAppClient->sendMessage($recipient, 'Conectado!!')) {
            return back()->with('error', $whatsAppClient->lastError() ?: 'Falha ao enviar a mensagem de health check.');
        }

        return back()->with('status', "Health check enviado para {$recipient}.");
    }
}
