<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreWhatsAppInboundMessageRequest;
use App\Models\WhatsAppQueueItem;
use App\Services\WhatsAppQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function __construct(protected WhatsAppQueueService $queue)
    {
    }

    public function store(StoreWhatsAppInboundMessageRequest $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return response()->json([
                'ok' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validated();
        $payload = $request->all();

        $senderPhone = $this->normalizePhone((string) ($data['author'] ?? $data['from'] ?? ''));
        if ($senderPhone === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Sender phone is required.',
            ], 422);
        }

        $message = trim((string) ($data['body'] ?? ''));
        if ($message === '') {
            $message = !empty($data['has_media'])
                ? '[Mensagem com mídia]'
                : '[Mensagem sem texto]';
        }

        $item = $this->queue->enqueueReceived([
            'type' => WhatsAppQueueItem::TYPE_RECEIVED,
            'external_message_id' => $data['message_id'] ?? null,
            'recipient_name' => $data['contact_name'] ?? $data['push_name'] ?? null,
            'phone' => $senderPhone,
            'message' => $message,
            'payload' => $payload,
        ]);

        return response()->json([
            'ok' => true,
            'queued' => true,
            'item_id' => $item->id,
            'direction' => $item->direction,
            'status' => $item->status,
        ], 201);
    }

    protected function isAuthorized(Request $request): bool
    {
        $token = (string) config('services.whatsapp.internal_token', '');

        if ($token === '') {
            return true;
        }

        $headerToken = $request->header('X-Internal-Token');
        if (is_string($headerToken) && $headerToken === $token) {
            return true;
        }

        $authorization = $request->header('Authorization');
        if (is_string($authorization) && str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7) === $token;
        }

        return false;
    }

    protected function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);

        return is_string($digits) ? $digits : '';
    }
}
