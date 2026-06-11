<?php

namespace Tests\Feature\Api\V1;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Carbon\Carbon;

class AdminPlanningSlotOperationalRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_default_operational_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/planning/operational-rules');

        $response->assertOk();
        $response->assertJsonPath('data.rules.lead_times.manha', 120);
        $response->assertJsonPath('data.rules.blocked_dates', []);
    }

    public function test_admin_can_update_operational_rules(): void
    {
        Log::spy();
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = [
            'lead_times' => [
                'manha' => 180,
                'tarde' => 90,
                'noite' => 45,
            ],
            'blocked_dates' => [
                ['date' => '2026-12-25', 'slots' => ['manha', 'tarde', 'noite']],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/planning/operational-rules', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.rules.lead_times.manha', 180);
        $response->assertJsonPath('data.rules.blocked_dates.0.date', '2026-12-25');

        $setting = Setting::query()->where('key', 'ORDER_SLOT_OPERATIONAL_RULES')->first();
        $this->assertNotNull($setting);
        $this->assertEquals($payload, $setting->value);
    }

    public function test_admin_update_cleans_up_past_blocked_dates(): void
    {
        Carbon::setTestNow('2026-06-02 10:00:00');
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = [
            'lead_times' => [
                'manha' => 120,
                'tarde' => 60,
                'noite' => 60,
            ],
            'blocked_dates' => [
                ['date' => '2026-06-01', 'slots' => ['manha']], // Past
                ['date' => '2026-06-02', 'slots' => ['tarde']], // Today
                ['date' => '2026-06-03', 'slots' => ['noite']], // Future
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/planning/operational-rules', $payload);

        $response->assertOk();
        $this->assertCount(2, $response->json('data.rules.blocked_dates'));
        $response->assertJsonPath('data.rules.blocked_dates.0.date', '2026-06-02');
        $response->assertJsonPath('data.rules.blocked_dates.1.date', '2026-06-03');

        Carbon::setTestNow();
    }
}
