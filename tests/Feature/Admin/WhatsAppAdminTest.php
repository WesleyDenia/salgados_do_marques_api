<?php

namespace Tests\Feature\Admin;

use App\Contracts\Notifications\WhatsAppClient;
use App\Services\OrderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class WhatsAppAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_whatsapp_page_shows_session_qr(): void
    {
        config()->set('services.whatsapp.base_url', 'http://salgados-whatsapp:3000');
        config()->set('services.whatsapp.internal_token', 'token-interno');

        $this->app->instance(OrderService::class, Mockery::mock(OrderService::class));

        Http::fake([
            'http://salgados-whatsapp:3000/session' => Http::response([
                'ok' => true,
                'whatsappReady' => false,
                'session' => [
                    'status' => 'qr',
                    'ready' => false,
                    'initialized' => true,
                    'hasQr' => true,
                    'qrDataUrl' => 'data:image/svg+xml;base64,PHN2Zy8+',
                    'qrGeneratedAt' => '2026-04-28T12:00:00.000Z',
                    'lastError' => null,
                ],
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.index'));

        $response->assertOk();
        $response->assertSeeText('WhatsApp');
        $response->assertSeeText('Aguardando scan');
        $response->assertSee('data:image/svg+xml;base64,PHN2Zy8+');
        $response->assertSeeText('Ver fila WhatsApp');
        $response->assertSeeText('Health Check');
    }

    public function test_admin_whatsapp_health_check_sends_conectado_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $orderService = Mockery::mock(OrderService::class);
        $orderService->shouldReceive('whatsappOrderRecipient')
            ->once()
            ->andReturn('+351123456789');

        $whatsAppClient = Mockery::mock(WhatsAppClient::class);
        $whatsAppClient->shouldReceive('sendMessage')
            ->once()
            ->with('+351123456789', 'Conectado!!')
            ->andReturn(true);

        $this->app->instance(OrderService::class, $orderService);
        $this->app->instance(WhatsAppClient::class, $whatsAppClient);

        $response = $this
            ->from(route('admin.whatsapp.index'))
            ->actingAs($admin)
            ->post(route('admin.whatsapp.health-check'));

        $response->assertRedirect(route('admin.whatsapp.index'));
        $response->assertSessionHas('status', 'Health check enviado para +351123456789.');
        $response->assertSessionMissing('error');
    }
}
