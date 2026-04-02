<?php

namespace Tests\Feature\Api;

use App\Models\ContentHome;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LgpdAndContentHomeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_lgpd_terms_endpoint_reads_service_payload(): void
    {
        Setting::create([
            'key' => 'LGPD_TERMS',
            'value' => "Linha 1  Linha 2\r\n\r\n\r\nLinha 3",
            'type' => 'string',
        ]);

        $response = $this->getJson('/api/v1/lgpd/terms');

        $response->assertOk()
            ->assertJsonPath('key', 'LGPD_TERMS')
            ->assertJsonPath('content', "Linha 1\nLinha 2\n\nLinha 3")
            ->assertJsonStructure(['hash', 'version', 'updated_at']);
    }

    public function test_content_home_endpoint_returns_only_active_published_items(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $visible = ContentHome::create([
            'title' => 'Banner ativo',
            'display_order' => 1,
            'is_active' => true,
            'publish_at' => now()->subHour(),
        ]);

        ContentHome::create([
            'title' => 'Banner inativo',
            'display_order' => 2,
            'is_active' => false,
        ]);

        ContentHome::create([
            'title' => 'Banner futuro',
            'display_order' => 3,
            'is_active' => true,
            'publish_at' => now()->addHour(),
        ]);

        $response = $this->getJson('/api/v1/content-home');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $visible->id);
    }
}
