<?php

namespace Tests\Feature\Api;

use App\Jobs\SendResetLinkJob;
use App\Models\PasswordReset;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthPasswordResetFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_user_token_and_config(): void
    {
        Setting::create([
            'key' => 'ASSET_BASE_URL',
            'value' => 'https://cdn.example.com/',
            'type' => 'string',
        ]);

        $user = User::factory()->create([
            'email' => 'cliente@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'cliente@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('config.assets_base_url', 'https://cdn.example.com')
            ->assertJsonStructure(['user', 'token', 'config']);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_refresh_rotates_current_token_and_keeps_contract(): void
    {
        Setting::create([
            'key' => 'ASSET_BASE_URL',
            'value' => 'https://cdn.example.com',
            'type' => 'string',
        ]);

        $user = User::factory()->create();
        $plainTextToken = $user->createToken('auth_token')->plainTextToken;
        $tokenId = PersonalAccessToken::query()->firstOrFail()->id;

        $response = $this->withToken($plainTextToken)->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('config.assets_base_url', 'https://cdn.example.com')
            ->assertJsonStructure(['user', 'token', 'config']);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_forgot_password_endpoint_preserves_success_contract(): void
    {
        Queue::fake();

        User::factory()->create([
            'email' => 'cliente@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'method' => 'email',
            'identifier' => 'cliente@example.com',
        ]);

        $response->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Link de redefinição enviado por e-mail',
            ]);

        $this->assertDatabaseCount('password_resets', 1);
        Queue::assertPushed(SendResetLinkJob::class, 1);
    }

    public function test_verify_otp_endpoint_resets_password(): void
    {
        $user = User::factory()->create([
            'phone' => '912345678',
            'password' => Hash::make('old-password'),
        ]);

        PasswordReset::create([
            'phone' => '912345678',
            'method' => 'whatsapp',
            'token' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '912345678',
            'token' => '123456',
            'new_password' => 'new-password-123',
        ]);

        $response->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Senha redefinida com sucesso',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_reset_password_endpoint_returns_422_for_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'new_password' => 'new-password-123',
        ]);

        $response->assertStatus(422)
            ->assertExactJson([
                'success' => false,
                'message' => 'Token inválido ou expirado',
            ]);
    }
}
