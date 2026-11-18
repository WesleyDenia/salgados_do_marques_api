<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_search_customers_by_name_or_normalized_phone(): void
    {
        $staff = User::factory()->create(['role' => 'atendimento']);
        $customer = User::factory()->create([
            'name' => 'Maria Silva',
            'phone' => '+351 912 345 678',
            'phone_normalized' => '912345678',
            'role' => 'cliente',
        ]);

        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/orders/customers/search?query=912%20345')
            ->assertOk()
            ->assertJsonPath('data.0.id', $customer->id)
            ->assertJsonPath('data.0.name', 'Maria Silva')
            ->assertJsonPath('data.0.phone', '912345678');
    }
}
