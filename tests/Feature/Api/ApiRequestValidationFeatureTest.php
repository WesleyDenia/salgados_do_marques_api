<?php

namespace Tests\Feature\Api;

use App\Models\LoyaltyReward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiRequestValidationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_index_uses_form_request_validation(): void
    {
        $response = $this->getJson('/api/v1/stores?lat=999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lat']);
    }

    public function test_loyalty_reward_redeem_uses_form_request_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $reward = LoyaltyReward::create([
            'name' => 'Vale teste',
            'threshold' => 100,
            'value' => 5,
            'active' => true,
        ]);

        $response = $this->postJson("/api/v1/loyalty/rewards/{$reward->id}/redeem", [
            'quantity' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_admin_upload_image_uses_form_request_validation(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->postJson('/api/v1/upload', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_admin_upload_image_keeps_response_contract(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->postJson('/api/v1/upload', [
            'image' => UploadedFile::fake()->create('banner.png', 64, 'image/png'),
        ]);

        $response->assertOk()
            ->assertJsonStructure(['url']);
    }
}
