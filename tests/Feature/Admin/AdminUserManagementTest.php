<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_listing_only_shows_staff_users(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrador',
            'role' => User::ROLE_ADMIN,
        ]);
        $operational = User::factory()->create([
            'name' => 'Operador',
            'role' => User::ROLE_OPERACIONAL,
        ]);
        $support = User::factory()->create([
            'name' => 'Atendimento',
            'role' => User::ROLE_ATENDIMENTO,
        ]);
        $client = User::factory()->create([
            'name' => 'Cliente Oculto',
            'role' => User::ROLE_CLIENTE,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Administrador');
        $response->assertSee('Operador');
        $response->assertSee('Atendimento');
        $response->assertDontSee('Cliente Oculto');
        $response->assertSee((string) User::query()->whereIn('role', User::STAFF_ROLES)->count());
        $this->assertDatabaseHas('users', ['id' => $client->id, 'role' => User::ROLE_CLIENTE]);
    }

    public function test_admin_can_create_update_and_delete_staff_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $storeResponse = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Novo Operacional',
            'email' => 'operacional@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role' => User::ROLE_OPERACIONAL,
            'phone' => '912345678',
            'active' => '1',
        ]);

        $user = User::query()->where('email', 'operacional@example.com')->firstOrFail();

        $storeResponse->assertRedirect(route('admin.users.edit', $user));
        $this->assertTrue(Hash::check('secret1234', $user->password));

        $updateResponse = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Operacional Atualizado',
            'email' => 'operacional@example.com',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
            'role' => User::ROLE_ATENDIMENTO,
            'phone' => '919999999',
            'active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.users.show', $user));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Operacional Atualizado',
            'role' => User::ROLE_ATENDIMENTO,
            'phone' => '919999999',
            'active' => true,
        ]);
        $this->assertTrue(Hash::check('new-secret-123', $user->fresh()->password));

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $deleteResponse->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_change_own_password(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('admin.users.password.edit'));
        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
    }

    public function test_admin_cannot_delete_own_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertSessionHas('error', 'Você não pode excluir o próprio usuário logado.');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
