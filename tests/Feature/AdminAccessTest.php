<?php

namespace Tests\Feature;

use App\Services\DashboardService;
use App\Models\User;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    public function test_non_admin_user_cannot_access_admin_dashboard(): void
    {
        $user = new User([
            'id' => 10,
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $dashboardMock = \Mockery::mock(DashboardService::class);
        $dashboardMock->shouldReceive('metrics')->andReturn([
            'users_count' => 0,
            'coins_generated_total' => 0,
            'coins_used_total' => 0,
            'coins_used_raw' => 0,
            'coins_available_total' => 0,
        ]);
        $this->app->instance(DashboardService::class, $dashboardMock);

        $user = new User([
            'id' => 11,
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }
}
