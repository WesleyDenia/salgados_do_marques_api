<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_returns_available_dates_for_the_selected_store(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        $user = User::factory()->create();
        $store = $this->makeStore('Loja Centro');
        $this->setSchedulingSettings(30, 5);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/orders/availability/dates?store_id={$store->id}");

        $response->assertOk();
        $response->assertJsonPath('data.store_id', $store->id);
        $response->assertJsonPath('data.timezone', 'Europe/Lisbon');
        $response->assertJsonPath('data.dates', [
            '2026-03-17',
            '2026-03-19',
            '2026-03-20',
        ]);
    }

    public function test_it_filters_same_day_hours_with_minimum_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-17 15:40', 'Europe/Lisbon'));
        $user = User::factory()->create();
        $store = $this->makeStore('Loja Horas', [
            'pickup_weekly_schedule' => [
                'monday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'tuesday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'wednesday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'thursday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'friday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'saturday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'sunday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
            ],
            'pickup_date_exceptions' => [],
        ]);
        $this->setSchedulingSettings(30, 3);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/orders/availability/hours?store_id={$store->id}&date=2026-03-17");

        $response->assertOk();
        $response->assertJsonPath('data.hours', ['16:00', '17:00', '18:00', '19:00', '20:00']);
    }

    public function test_it_returns_minute_options_for_an_hour_using_exception_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 10:00', 'Europe/Lisbon'));
        $user = User::factory()->create();
        $store = $this->makeStore('Loja Excecao');
        $this->setSchedulingSettings(30, 5);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/orders/availability/minutes?store_id={$store->id}&date=2026-03-19&hour=15:00");

        $response->assertOk();
        $response->assertJsonPath('data.minute_options', [
            '15:00',
            '15:05',
            '15:10',
            '15:15',
            '15:20',
            '15:25',
            '15:30',
            '15:35',
            '15:40',
            '15:45',
            '15:50',
            '15:55',
        ]);
    }

    public function test_it_validates_query_parameters_with_form_requests(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/orders/availability/hours?store_id=abc&date=2026-03-17')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['store_id']);

        $store = $this->makeStore('Loja Validacao');

        $this->getJson("/api/v1/orders/availability/minutes?store_id={$store->id}&date=17-03-2026&hour=3pm")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date', 'hour']);
    }

    protected function setSchedulingSettings(int $minimumMinutes, int $windowDays): void
    {
        Cache::flush();

        Setting::create([
            'key' => 'order_minimum_minutes',
            'value' => $minimumMinutes,
            'type' => 'integer',
            'editable' => true,
        ]);

        Setting::create([
            'key' => 'ORDER_SCHEDULING_WINDOW_DAYS',
            'value' => $windowDays,
            'type' => 'integer',
            'editable' => true,
        ]);

        Setting::create([
            'key' => 'order_timezone',
            'value' => 'Europe/Lisbon',
            'type' => 'string',
            'editable' => true,
        ]);
    }

    protected function makeStore(string $name, array $overrides = []): Store
    {
        return Store::create(array_replace_recursive([
            'name' => $name,
            'address' => 'Rua 1',
            'city' => 'Lisboa',
            'latitude' => 38.7169,
            'longitude' => -9.1399,
            'phone' => '123456789',
            'type' => 'principal',
            'is_active' => true,
            'accepts_orders' => true,
            'default_store' => true,
            'pickup_weekly_schedule' => [
                'monday' => ['is_open' => false, 'start_time' => null, 'end_time' => null],
                'tuesday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'wednesday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'thursday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'friday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'saturday' => ['is_open' => true, 'start_time' => '12:00', 'end_time' => '20:00'],
                'sunday' => ['is_open' => true, 'start_time' => '14:00', 'end_time' => '20:00'],
            ],
            'pickup_date_exceptions' => [
                ['date' => '2026-03-18', 'is_open' => false, 'start_time' => null, 'end_time' => null],
                ['date' => '2026-03-19', 'is_open' => true, 'start_time' => '15:00', 'end_time' => '17:00'],
            ],
        ], $overrides));
    }
}
