<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_whatsapp_page_shows_session_qr(): void
    {
        config()->set('services.whatsapp.base_url', 'http://salgados-whatsapp:3000');
        config()->set('services.whatsapp.internal_token', 'token-interno');

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
    }
}
