<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\AdminSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_casts_json_and_boolean_values_on_create(): void
    {
        /** @var AdminSettingService $service */
        $service = $this->app->make(AdminSettingService::class);

        $json = $service->create([
            'key' => 'APP_FLAGS',
            'type' => 'json',
            'value' => '{"promo":true}',
            'editable' => true,
        ]);
        $boolean = $service->create([
            'key' => 'APP_ENABLED',
            'type' => 'boolean',
            'value' => '1',
            'editable' => true,
        ]);

        $this->assertSame(['promo' => true], $json->fresh()->value);
        $this->assertTrue($boolean->fresh()->value);
    }

    public function test_it_rejects_invalid_json_values(): void
    {
        $this->expectException(ValidationException::class);

        $service = $this->app->make(AdminSettingService::class);
        $service->create([
            'key' => 'APP_FLAGS',
            'type' => 'json',
            'value' => '{"promo":}',
            'editable' => true,
        ]);
    }

    public function test_it_refuses_to_update_non_editable_settings(): void
    {
        $setting = Setting::create([
            'key' => 'LOCKED_KEY',
            'type' => 'string',
            'value' => 'abc',
            'editable' => false,
        ]);

        $service = $this->app->make(AdminSettingService::class);
        $updated = $service->update($setting, [
            'key' => 'LOCKED_KEY',
            'type' => 'string',
            'value' => 'changed',
            'editable' => false,
        ]);

        $this->assertFalse($updated);
        $this->assertSame('abc', $setting->fresh()->value);
    }
}
