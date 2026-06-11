<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\PlanningSlotCapacityService;
use App\Services\SettingService;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PlanningSlotCapacityServiceRulesTest extends TestCase
{
    use RefreshDatabase;

    protected PlanningSlotCapacityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PlanningSlotCapacityService::class);
    }

    public function test_resolve_slot_state_respects_lead_time(): void
    {
        // Now is 10:00. Lead time for manha is 120min (2h).
        // Slot 'manha' starts at 00:00 (from OrderService::SLOT_WINDOWS).
        // Wait, if manha starts at 00:00, then at 10:00 it's always blocked if lead time > 0 and date is today.
        
        Carbon::setTestNow('2026-06-02 10:00:00');
        
        $window = ['start' => 0, 'end' => 719]; // manha: 00:00 - 11:59
        $date = Carbon::parse('2026-06-02', 'Europe/Lisbon');

        // Setting lead time to 30 mins
        $this->service->updateOperationalRulesPayload([
            'lead_times' => ['manha' => 30, 'tarde' => 30, 'noite' => 30],
            'blocked_dates' => [],
        ]);

        // At 10:00, with 30min lead time, slot starting at 00:00 is definitely blocked.
        $state = $this->service->resolveSlotState('manha', ['08:00', '09:00', '10:00', '11:00'], $window, 0, $date);
        $this->assertEquals('bloqueado', $state);
        $this->assertEquals('SLOT_LEAD_TIME_VIOLATION', $this->service->getSlotBlockReason('manha', ['08:00', '09:00', '10:00', '11:00'], $window, 0, $date));

        // For tomorrow, it should be available
        $tomorrow = $date->copy()->addDay();
        $minutes = ['08:00', '08:05', '08:10', '08:15', '08:20', '08:25', '08:30', '08:35'];
        $state = $this->service->resolveSlotState('manha', $minutes, $window, 0, $tomorrow);
        $this->assertEquals('disponível', $state);

        Carbon::setTestNow();
    }

    public function test_resolve_slot_state_respects_blocked_dates(): void
    {
        $date = Carbon::parse('2026-12-25', 'Europe/Lisbon');
        $window = ['start' => 0, 'end' => 719];

        $this->service->updateOperationalRulesPayload([
            'lead_times' => ['manha' => 0, 'tarde' => 0, 'noite' => 0],
            'blocked_dates' => [
                ['date' => '2026-12-25', 'slots' => ['manha', 'noite']]
            ],
        ]);

        // Manha is blocked
        $this->assertEquals('bloqueado', $this->service->resolveSlotState('manha', ['08:00'], $window, 0, $date));
        $this->assertEquals('SLOT_DATE_BLOCKED', $this->service->getSlotBlockReason('manha', ['08:00'], $window, 0, $date));

        // Tarde is NOT blocked
        $tardeWindow = ['start' => 720, 'end' => 1079];
        $minutes = ['14:00', '14:05', '14:10', '14:15', '14:20', '14:25', '14:30', '14:35'];
        $this->assertEquals('disponível', $this->service->resolveSlotState('tarde', $minutes, $tardeWindow, 0, $date));
    }
}
