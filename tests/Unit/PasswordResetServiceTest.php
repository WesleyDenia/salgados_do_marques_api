<?php

namespace Tests\Unit;

use App\Jobs\SendResetLinkJob;
use App\Jobs\SendWhatsAppOtpJob;
use App\Models\PasswordReset;
use App\Models\WhatsAppQueueItem;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordResetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_creates_email_reset_and_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'cliente@example.com',
            'phone' => '912345678',
        ]);

        $service = app(PasswordResetService::class);

        $response = $service->forgot([
            'method' => 'email',
            'identifier' => 'CLIENTE@EXAMPLE.COM',
        ]);

        $this->assertSame([
            'success' => true,
            'message' => 'Link de redefinição enviado por e-mail',
        ], $response);

        $reset = PasswordReset::query()->first();

        $this->assertNotNull($reset);
        $this->assertSame('email', $reset->method);
        $this->assertSame('cliente@example.com', $reset->email);
        $this->assertNotEmpty($reset->token);

        Queue::assertPushed(SendResetLinkJob::class, 1);
    }

    public function test_forgot_creates_whatsapp_reset_and_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'cliente@example.com',
            'phone' => '912345678',
        ]);

        $service = app(PasswordResetService::class);

        $response = $service->forgot([
            'method' => 'whatsapp',
            'identifier' => '912 345 678',
        ]);

        $this->assertSame([
            'success' => true,
            'message' => 'Código enviado via WhatsApp',
        ], $response);

        $reset = PasswordReset::query()->first();

        $this->assertNotNull($reset);
        $this->assertSame('whatsapp', $reset->method);
        $this->assertSame('912345678', $reset->phone);

        Queue::assertPushedOn('notifications', SendWhatsAppOtpJob::class);
        Queue::assertPushed(SendWhatsAppOtpJob::class, 1);

        $this->assertDatabaseHas('whatsapp_queue_items', [
            'type' => WhatsAppQueueItem::TYPE_OTP,
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'phone' => '912345678',
            'status' => WhatsAppQueueItem::STATUS_QUEUED,
        ]);
    }

    public function test_verify_otp_updates_password_and_clears_resets(): void
    {
        $user = User::factory()->create([
            'phone' => '912345678',
            'password' => Hash::make('old-password'),
        ]);

        PasswordReset::create([
            'phone' => '912345678',
            'method' => 'whatsapp',
            'token' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $service = app(PasswordResetService::class);

        $response = $service->verifyOtp([
            'phone' => '912 345 678',
            'token' => '123456',
            'new_password' => 'nova-password',
        ]);

        $this->assertSame([
            'success' => true,
            'message' => 'Senha redefinida com sucesso',
        ], $response);

        $user->refresh();

        $this->assertTrue(Hash::check('nova-password', $user->password));
        $this->assertDatabaseCount('password_resets', 0);
    }

    public function test_reset_updates_password_for_email_token(): void
    {
        $user = User::factory()->create([
            'email' => 'cliente@example.com',
            'password' => Hash::make('old-password'),
        ]);

        PasswordReset::create([
            'email' => 'cliente@example.com',
            'method' => 'email',
            'token' => hash('sha256', 'raw-reset-token'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $service = app(PasswordResetService::class);

        $response = $service->reset([
            'token' => 'raw-reset-token',
            'new_password' => 'nova-password',
        ]);

        $this->assertSame([
            'success' => true,
            'message' => 'Senha redefinida com sucesso',
        ], $response);

        $user->refresh();

        $this->assertTrue(Hash::check('nova-password', $user->password));
        $this->assertDatabaseCount('password_resets', 0);
    }
}
