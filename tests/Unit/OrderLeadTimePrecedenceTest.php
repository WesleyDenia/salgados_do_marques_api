<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PlanningSlotCapacityService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLeadTimePrecedenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_global_minimum_if_more_restrictive_than_slot_lead_time(): void
    {
        Setting::updateOrCreate(['key' => 'ORDER_MINIMUM_MINUTES'], ['value' => 120, 'type' => 'integer']);
        
        // Slot rules has 60 mins for 'tarde'
        Setting::updateOrCreate(['key' => 'ORDER_SLOT_OPERATIONAL_RULES'], [
            'value' => json_encode([
                'lead_times' => [
                    'manha' => 120,
                    'tarde' => 60,
                    'noite' => 60,
                ],
                'blocked_dates' => [],
            ]),
            'type' => 'json'
        ]);

        /** @var PlanningSlotCapacityService $service */
        $service = app(PlanningSlotCapacityService::class);
        
        // Window for tarde: start at 14:00 (840 mins)
        $window = ['start' => 840, 'end' => 1140];
        $date = now()->addDays(1); // Future date to avoid "now" interference in simple check
        
        // Let's force "now" to be close to the slot start
        // Slot starts at 14:00. 120 mins before is 12:00.
        // If we are at 12:30, it should be blocked because 12:30 + 120 > 14:00.
        
        \Carbon\Carbon::setTestNow($date->copy()->startOfDay()->addHours(12)->addMinutes(30));
        
        $reason = $service->getSlotBlockReason('tarde', ['14:00'], $window, 0, $date);
        
        $this->assertEquals('SLOT_LEAD_TIME_VIOLATION', $reason);
        
        // If we are at 11:30, it should be available (11:30 + 120 = 13:30 < 14:00)
        \Carbon\Carbon::setTestNow($date->copy()->startOfDay()->addHours(11)->addMinutes(30));
        $reason = $service->getSlotBlockReason('tarde', ['14:00'], $window, 0, $date);
        $this->assertNull($reason);

        \Carbon\Carbon::setTestNow(); // Reset
    }

    public function test_it_uses_slot_lead_time_if_more_restrictive_than_global(): void
    {
        Setting::updateOrCreate(['key' => 'ORDER_MINIMUM_MINUTES'], ['value' => 30, 'type' => 'integer']);
        
        // Slot rules has 120 mins for 'manha'
        Setting::updateOrCreate(['key' => 'ORDER_SLOT_OPERATIONAL_RULES'], [
            'value' => json_encode([
                'lead_times' => [
                    'manha' => 120,
                    'tarde' => 60,
                    'noite' => 60,
                ],
                'blocked_dates' => [],
            ]),
            'type' => 'json'
        ]);

        /** @var PlanningSlotCapacityService $service */
        $service = app(PlanningSlotCapacityService::class);
        
        // Window for manha: start at 09:00 (540 mins)
        $window = ['start' => 540, 'end' => 810];
        $date = now()->addDays(1);
        
        // Now is 07:30. 07:30 + 120 = 09:30 > 09:00. Should block.
        // Even though global is only 30 mins (07:30 + 30 = 08:00 < 09:00).
        
        \Carbon\Carbon::setTestNow($date->copy()->startOfDay()->addHours(7)->addMinutes(30));
        
        $reason = $service->getSlotBlockReason('manha', ['09:00'], $window, 0, $date);
        
        $this->assertEquals('SLOT_LEAD_TIME_VIOLATION', $reason);

        \Carbon\Carbon::setTestNow(); // Reset
    }
}
