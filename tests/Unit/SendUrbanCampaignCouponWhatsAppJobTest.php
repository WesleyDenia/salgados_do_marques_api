<?php

namespace Tests\Unit;

use App\Contracts\Notifications\WhatsAppClient;
use App\Jobs\SendUrbanCampaignCouponWhatsAppJob;
use App\Models\WhatsAppQueueItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendUrbanCampaignCouponWhatsAppJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_the_stored_coupon_message_and_marks_the_queue_item_as_sent(): void
    {
        $item = WhatsAppQueueItem::create([
            'type' => WhatsAppQueueItem::TYPE_URBAN_CAMPAIGN_COUPON,
            'direction' => WhatsAppQueueItem::DIRECTION_OUTBOUND,
            'entity_type' => 'urban_campaign_coupon_claim',
            'entity_id' => 99,
            'phone' => '351912345678',
            'message' => 'Codigo: VD-URBANA-10',
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
            'queued_at' => now(),
        ]);

        $whatsAppClient = Mockery::mock(WhatsAppClient::class);
        $whatsAppClient->shouldReceive('sendMessage')
            ->once()
            ->with('351912345678', $item->message)
            ->andReturn(true);
        $whatsAppClient->shouldReceive('lastError')->andReturnNull();

        $job = new SendUrbanCampaignCouponWhatsAppJob($item->id);
        $this->app->instance(WhatsAppClient::class, $whatsAppClient);

        $this->app->call([$job, 'handle']);

        $item->refresh();

        $this->assertSame(WhatsAppQueueItem::STATUS_SENT, $item->status);
        $this->assertNotNull($item->sent_at);
    }

    public function test_it_marks_the_queue_item_as_failed_when_sending_fails(): void
    {
        $item = WhatsAppQueueItem::create([
            'type' => WhatsAppQueueItem::TYPE_URBAN_CAMPAIGN_COUPON,
            'direction' => WhatsAppQueueItem::DIRECTION_OUTBOUND,
            'entity_type' => 'urban_campaign_coupon_claim',
            'entity_id' => 100,
            'phone' => '351912345678',
            'message' => 'Codigo: VD-URBANA-10',
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
            'queued_at' => now(),
        ]);

        $whatsAppClient = Mockery::mock(WhatsAppClient::class);
        $whatsAppClient->shouldReceive('sendMessage')
            ->once()
            ->andReturn(false);
        $whatsAppClient->shouldReceive('lastError')
            ->andReturn('HTTP 500: WhatsApp not ready');

        $job = new SendUrbanCampaignCouponWhatsAppJob($item->id);
        $this->app->instance(WhatsAppClient::class, $whatsAppClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500: WhatsApp not ready');

        try {
            $this->app->call([$job, 'handle']);
        } finally {
            $item->refresh();
            $this->assertSame(WhatsAppQueueItem::STATUS_FAILED, $item->status);
            $this->assertSame('HTTP 500: WhatsApp not ready', $item->last_error);
        }
    }
}
