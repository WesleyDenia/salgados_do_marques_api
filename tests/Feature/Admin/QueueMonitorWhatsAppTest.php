<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendOrderPlacedWhatsAppJob;
use App\Jobs\SendWhatsAppOtpJob;
use App\Models\User;
use App\Models\WhatsAppQueueItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueMonitorWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledPath = sys_get_temp_dir() . '/salgados-api-views';

        if (!is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        config(['view.compiled' => $compiledPath]);
    }

    public function test_queue_monitor_lists_failed_whatsapp_messages_from_persisted_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        WhatsAppQueueItem::create([
            'type' => WhatsAppQueueItem::TYPE_OTP,
            'entity_type' => 'user',
            'entity_id' => 10,
            'recipient_name' => 'Cliente Teste',
            'phone' => '351911928481',
            'message' => 'Mensagem de teste',
            'status' => WhatsAppQueueItem::STATUS_FAILED,
            'last_error' => 'HTTP 401: Unauthorized',
            'finished_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/queue?tab=whatsapp');

        $response->assertOk()
            ->assertSee('Fila WhatsApp')
            ->assertSee('OTP')
            ->assertSee('HTTP 401: Unauthorized');
    }

    public function test_retry_whatsapp_message_requeues_otp_items(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $item = WhatsAppQueueItem::create([
            'type' => WhatsAppQueueItem::TYPE_OTP,
            'entity_type' => 'user',
            'entity_id' => 10,
            'recipient_name' => 'Cliente Teste',
            'phone' => '351911928481',
            'message' => 'Mensagem de teste',
            'status' => WhatsAppQueueItem::STATUS_FAILED,
            'last_error' => 'HTTP 401: Unauthorized',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.queue.whatsapp.retry', $item));

        $response->assertRedirect();

        Queue::assertPushed(SendWhatsAppOtpJob::class, fn (SendWhatsAppOtpJob $job) => $job->queueItemId === $item->id);
        $this->assertDatabaseHas('whatsapp_queue_items', [
            'id' => $item->id,
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
            'last_error' => null,
        ]);
    }

    public function test_retry_whatsapp_message_requeues_order_items(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $item = WhatsAppQueueItem::create([
            'type' => WhatsAppQueueItem::TYPE_ORDER_PLACED,
            'entity_type' => 'order',
            'entity_id' => 99,
            'recipient_name' => 'Cliente Teste',
            'phone' => '351911928481',
            'message' => 'Mensagem de pedido',
            'status' => WhatsAppQueueItem::STATUS_FAILED,
            'last_error' => 'HTTP 500: Internal Server Error',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.queue.whatsapp.retry', $item));

        $response->assertRedirect();

        Queue::assertPushed(SendOrderPlacedWhatsAppJob::class, fn (SendOrderPlacedWhatsAppJob $job) => $job->queueItemId === $item->id);
        $this->assertDatabaseHas('whatsapp_queue_items', [
            'id' => $item->id,
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
            'last_error' => null,
        ]);
    }

    public function test_close_whatsapp_message_marks_item_as_manual_closed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $item = WhatsAppQueueItem::create([
            'type' => WhatsAppQueueItem::TYPE_ORDER_PLACED,
            'entity_type' => 'order',
            'entity_id' => 99,
            'recipient_name' => 'Cliente Teste',
            'phone' => '351911928481',
            'message' => 'Mensagem de pedido',
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
            'queued_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.queue.whatsapp.close', $item), [
            'manual_note' => 'Baixa manual pelo painel administrativo.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('whatsapp_queue_items', [
            'id' => $item->id,
            'status' => WhatsAppQueueItem::STATUS_MANUALLY_CLOSED,
            'manual_note' => 'Baixa manual pelo painel administrativo.',
            'resolved_by' => $admin->id,
        ]);
    }
}
