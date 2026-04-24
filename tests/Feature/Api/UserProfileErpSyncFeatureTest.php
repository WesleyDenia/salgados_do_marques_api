<?php

namespace Tests\Feature\Api;

use App\Jobs\SyncCustomerToErpJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileErpSyncFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_marks_erp_pending_and_dispatches_sync_after_commit(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'name' => 'Cliente Antigo',
            'erp_sync_status' => 'synced',
            'erp_sync_error' => 'erro anterior',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/user', [
            'name' => 'Cliente Novo',
            'phone' => '912345678',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Cliente Novo');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'erp_sync_status' => 'pending',
            'erp_sync_error' => null,
        ]);

        Queue::assertPushed(SyncCustomerToErpJob::class, fn (SyncCustomerToErpJob $job) => $job->userId === $user->id);
    }
}
