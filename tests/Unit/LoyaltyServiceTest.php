<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Models\User;
use App\Repositories\LoyaltyRepository;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_grant_welcome_bonus_returns_zero_when_user_already_synced(): void
    {
        $user = User::factory()->create([
            'loyalty_synced' => true,
        ]);

        $service = new LoyaltyService(new LoyaltyRepository());

        $result = $service->grantWelcomeBonus($user);

        $this->assertSame(0, $result);
        $this->assertDatabaseMissing('loyalty_accounts', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('loyalty_transactions', ['user_id' => $user->id]);
    }

    public function test_grant_welcome_bonus_uses_setting_value_and_creates_transaction(): void
    {
        Setting::create([
            'key' => 'welcome_bonus_points',
            'value' => '250',
            'type' => 'integer',
        ]);

        $user = User::factory()->create([
            'loyalty_synced' => false,
        ]);

        $service = new LoyaltyService(new LoyaltyRepository());

        $result = $service->grantWelcomeBonus($user);

        $this->assertSame(250, $result);

        $this->assertDatabaseHas('loyalty_accounts', [
            'user_id' => $user->id,
            'points' => 250,
        ]);

        $this->assertDatabaseHas('loyalty_transactions', [
            'user_id' => $user->id,
            'type' => 'earn',
            'points' => 250,
            'reason' => 'Bônus de boas-vindas',
        ]);

        $user->refresh();
        $this->assertTrue($user->loyalty_synced);
        $this->assertNotNull($user->loyalty_synced_at);
    }

    public function test_grant_welcome_bonus_defaults_to_100_when_setting_missing(): void
    {
        $user = User::factory()->create([
            'loyalty_synced' => false,
        ]);

        $service = new LoyaltyService(new LoyaltyRepository());

        $result = $service->grantWelcomeBonus($user);

        $this->assertSame(100, $result);
        $this->assertDatabaseHas('loyalty_accounts', [
            'user_id' => $user->id,
            'points' => 100,
        ]);
    }
}
