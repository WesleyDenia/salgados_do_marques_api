<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperationalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        Setting::updateOrCreate(['key' => 'ORDER_START_TIME'], ['value' => '12:00', 'type' => 'string', 'editable' => true]);
        Setting::updateOrCreate(['key' => 'ORDER_END_TIME'], ['value' => '20:00', 'type' => 'string', 'editable' => true]);
        Setting::updateOrCreate(['key' => 'ORDER_MINIMUM_MINUTES'], ['value' => '30', 'type' => 'integer', 'editable' => true]);
        Setting::updateOrCreate(['key' => 'ORDER_CANCEL_MINUTES'], ['value' => '60', 'type' => 'integer', 'editable' => true]);
        Setting::updateOrCreate(['key' => 'ORDER_SCHEDULING_WINDOW_DAYS'], ['value' => '14', 'type' => 'integer', 'editable' => true]);
        Setting::updateOrCreate(['key' => 'WHATSAPP_ORDER_TO'], ['value' => '', 'type' => 'string', 'editable' => true]);
        Setting::updateOrCreate(['key' => 'SETTINGS_VERSION'], ['value' => '1', 'type' => 'integer', 'editable' => true]);
    }

    public function test_it_validates_that_start_time_is_before_end_time(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/operational', [
                'version' => 1,
                'ORDER_START_TIME' => '21:00',
                'ORDER_END_TIME' => '20:00',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ORDER_END_TIME']);
    }

    public function test_it_validates_minimum_advance_is_non_negative(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/operational', [
                'version' => 1,
                'ORDER_MINIMUM_MINUTES' => -1,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ORDER_MINIMUM_MINUTES']);
    }

    public function test_it_updates_settings_successfully_and_increments_version(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/operational', [
                'version' => 1,
                'ORDER_START_TIME' => '10:00',
                'ORDER_END_TIME' => '18:00',
                'ORDER_MINIMUM_MINUTES' => 45,
                'ORDER_CANCEL_MINUTES' => 120,
                'ORDER_SCHEDULING_WINDOW_DAYS' => 30,
                'WHATSAPP_ORDER_TO' => '+351912345678',
            ]);

        $response->assertStatus(200);
        
        $this->assertEquals('10:00', Setting::where('key', 'ORDER_START_TIME')->first()->value);
        $this->assertEquals(45, Setting::where('key', 'ORDER_MINIMUM_MINUTES')->first()->value);
        $this->assertEquals(2, Setting::where('key', 'SETTINGS_VERSION')->first()->value);
    }

    public function test_it_prevents_concurrent_updates_with_wrong_version(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/operational', [
                'version' => 2, // Current is 1
                'ORDER_MINIMUM_MINUTES' => 50,
            ]);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'CONCURRENCY_ERROR']);
    }

    public function test_it_rejects_partial_time_updates_that_invert_the_window(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/operational', [
                'version' => 1,
                'ORDER_END_TIME' => '11:00',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ORDER_END_TIME']);
    }

    public function test_it_rejects_cancellation_windows_smaller_than_minimum_lead_time(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/operational', [
                'version' => 1,
                'ORDER_MINIMUM_MINUTES' => 45,
                'ORDER_CANCEL_MINUTES' => 30,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ORDER_CANCEL_MINUTES']);
    }

    public function test_it_rejects_invalid_whatsapp_numbers_on_update(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/admin/settings/operational', [
                'version' => 1,
                'WHATSAPP_ORDER_TO' => '912345678',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['WHATSAPP_ORDER_TO']);
    }

    public function test_it_resets_settings_to_defaults(): void
    {
        // Change something first
        Setting::where('key', 'ORDER_MINIMUM_MINUTES')->update(['value' => 99]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/operational/reset', [
                'version' => 1,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(30, Setting::where('key', 'ORDER_MINIMUM_MINUTES')->first()->value);
    }

    public function test_it_prevents_resets_with_a_stale_version(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/operational/reset', [
                'version' => 2,
            ]);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'CONCURRENCY_ERROR']);
    }

    public function test_it_returns_a_default_settings_version_when_the_setting_is_missing(): void
    {
        Setting::where('key', 'SETTINGS_VERSION')->delete();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/settings/operational');

        $response->assertStatus(200);
        $response->assertJson(['SETTINGS_VERSION' => 1]);
    }

    public function test_it_calls_whatsapp_client_on_test_connection(): void
    {
        $this->mock(\App\Contracts\Notifications\WhatsAppClient::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('+351912345678', 'Teste de conexão de governação operacional Salgados do Marquês.')
                ->andReturn(true);
        });

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/test-whatsapp', [
                'number' => '+351912345678'
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_it_rejects_invalid_whatsapp_numbers_on_test_connection(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/settings/test-whatsapp', [
                'number' => '912345678',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['number']);
    }

    public function test_orders_settings_expose_the_settings_version_for_mobile_cache_invalidation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders/settings');

        $response->assertStatus(200);
        $response->assertJsonPath('data.settings_version', 1);
    }
}
