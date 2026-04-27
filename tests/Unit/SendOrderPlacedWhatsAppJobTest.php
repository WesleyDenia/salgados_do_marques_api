<?php

namespace Tests\Unit;

use App\Contracts\Notifications\WhatsAppClient;
use App\Jobs\SendOrderPlacedWhatsAppJob;
use App\Models\WhatsAppQueueItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendOrderPlacedWhatsAppJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_the_stored_message_and_marks_the_queue_item_as_sent(): void
    {
        $item = WhatsAppQueueItem::create([
            'type' => WhatsAppQueueItem::TYPE_ORDER_PLACED,
            'entity_type' => 'order',
            'entity_id' => 99,
            'recipient_name' => 'Joao Silva',
            'phone' => '351911928481',
            'message' => "Nome: Joao Silva\nTel: 351911928481\nData/Hora: 15/01/2026 12:30\nPedido:\n3x Coxinha",
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
            'queued_at' => now(),
        ]);

        $whatsAppClient = Mockery::mock(WhatsAppClient::class);
        $whatsAppClient->shouldReceive('sendMessage')
            ->once()
            ->with('351911928481', $item->message)
            ->andReturn(true);
        $whatsAppClient->shouldReceive('lastError')->andReturnNull();

        $job = new SendOrderPlacedWhatsAppJob($item->id);

        $this->app->instance(WhatsAppClient::class, $whatsAppClient);

        $this->app->call([$job, 'handle']);

        $item->refresh();

        $this->assertSame(WhatsAppQueueItem::STATUS_SENT, $item->status);
        $this->assertNotNull($item->sent_at);
    }

    public function test_it_marks_the_queue_item_as_failed_when_sending_fails(): void
    {
        $item = WhatsAppQueueItem::create([
            'type' => WhatsAppQueueItem::TYPE_ORDER_PLACED,
            'entity_type' => 'order',
            'entity_id' => 100,
            'recipient_name' => 'Joao Silva',
            'phone' => '351911928481',
            'message' => 'Mensagem de teste',
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
            'queued_at' => now(),
        ]);

        $whatsAppClient = Mockery::mock(WhatsAppClient::class);
        $whatsAppClient->shouldReceive('sendMessage')
            ->once()
            ->andReturn(false);
        $whatsAppClient->shouldReceive('lastError')
            ->andReturn('HTTP 401: Unauthorized');

        $job = new SendOrderPlacedWhatsAppJob($item->id);
        $this->app->instance(WhatsAppClient::class, $whatsAppClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP 401: Unauthorized');

        try {
            $this->app->call([$job, 'handle']);
        } finally {
            $item->refresh();
            $this->assertSame(WhatsAppQueueItem::STATUS_FAILED, $item->status);
            $this->assertSame('HTTP 401: Unauthorized', $item->last_error);
        }
    }
}
