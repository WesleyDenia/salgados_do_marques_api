<?php

namespace Tests\Feature\Api\V1;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AdminPlanningSlotCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_default_slot_capacities_without_existing_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/planning/slot-capacities');

        $response->assertOk();
        $response->assertJsonPath('data.scope', 'global');
        $response->assertJsonPath('data.setting_key', 'ORDER_SLOT_BASE_CAPACITY');
        $response->assertJsonPath('data.slot_capacities.0.slot', 'manha');
        $response->assertJsonPath('data.slot_capacities.0.value', 12);
        $response->assertJsonPath('data.slot_capacities.1.slot', 'tarde');
        $response->assertJsonPath('data.slot_capacities.1.value', 10);
        $response->assertJsonPath('data.slot_capacities.2.slot', 'noite');
        $response->assertJsonPath('data.slot_capacities.2.value', 8);
    }

    public function test_admin_can_persist_slot_capacities_with_minimal_audit_log(): void
    {
        Log::spy();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/planning/slot-capacities', [
                'manha' => 4,
                'tarde' => 6,
                'noite' => 2,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.slot_capacities.0.value', 4);
        $response->assertJsonPath('data.slot_capacities.1.value', 6);
        $response->assertJsonPath('data.slot_capacities.2.value', 2);

        $setting = Setting::query()->where('key', 'ORDER_SLOT_BASE_CAPACITY')->first();

        $this->assertNotNull($setting);
        $this->assertSame('json', $setting->type);
        $this->assertSame([
            'manha' => 4,
            'tarde' => 6,
            'noite' => 2,
        ], $setting->value);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($admin): bool {
                return $message === '[PlanningSlotCapacityService] Slot base capacity updated'
                    && $context['actor_id'] === $admin->id
                    && $context['before'] === [
                        'manha' => 12,
                        'tarde' => 10,
                        'noite' => 8,
                    ]
                    && $context['after'] === [
                        'manha' => 4,
                        'tarde' => 6,
                        'noite' => 2,
                    ];
            });
    }

    public function test_admin_update_rejects_invalid_payload_and_sem_slot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/planning/slot-capacities', [
                'manha' => -1,
                'tarde' => 6,
                'noite' => 2,
                'sem_slot' => 99,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['manha', 'slot_capacities']);
    }

    public function test_non_admin_cannot_read_or_write_slot_capacities(): void
    {
        $operational = User::factory()->create(['role' => 'operacional']);

        $this->actingAs($operational, 'sanctum')
            ->getJson('/api/v1/admin/planning/slot-capacities')
            ->assertForbidden();

        $this->actingAs($operational, 'sanctum')
            ->putJson('/api/v1/admin/planning/slot-capacities', [
                'manha' => 1,
                'tarde' => 1,
                'noite' => 1,
            ])
            ->assertForbidden();
    }
}
