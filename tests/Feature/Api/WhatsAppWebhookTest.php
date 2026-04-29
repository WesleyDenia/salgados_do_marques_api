<?php

namespace Tests\Feature\Api;

use App\Models\WhatsAppQueueItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_whatsapp_message_is_enqueued_for_later_processing(): void
    {
        config()->set('services.whatsapp.internal_token', 'token-interno');

        $response = $this->postJson('/api/v1/webhooks/whatsapp/messages', [
            'message_id' => 'msg-123',
            'from' => '351911928481@c.us',
            'chat_id' => '351911928481@c.us',
            'body' => 'Olá, preciso de ajuda',
            'contact_name' => 'Cliente Teste',
            'timestamp' => 1_741_000_000,
            'type' => 'chat',
            'is_group' => false,
            'has_media' => false,
        ], [
            'X-Internal-Token' => 'token-interno',
        ]);

        $response->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('queued', true);

        $item = WhatsAppQueueItem::query()->first();

        $this->assertNotNull($item);
        $this->assertSame(WhatsAppQueueItem::TYPE_RECEIVED, $item->type);
        $this->assertSame(WhatsAppQueueItem::DIRECTION_INBOUND, $item->direction);
        $this->assertSame(WhatsAppQueueItem::STATUS_QUEUED, $item->status);
        $this->assertSame('msg-123', $item->external_message_id);
        $this->assertSame('351911928481', $item->phone);
        $this->assertSame('Cliente Teste', $item->recipient_name);
        $this->assertSame('Olá, preciso de ajuda', $item->message);
        $this->assertSame('chat', $item->payload['type']);
        $this->assertSame('351911928481@c.us', $item->payload['chat_id']);
    }

    public function test_inbound_whatsapp_message_requires_token_when_configured(): void
    {
        config()->set('services.whatsapp.internal_token', 'token-interno');

        $response = $this->postJson('/api/v1/webhooks/whatsapp/messages', [
            'message_id' => 'msg-unauthorized',
            'from' => '351911928481@c.us',
            'body' => 'Olá',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('whatsapp_queue_items', 0);
    }
}
