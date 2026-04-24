<?php

namespace Tests\Feature\Admin;

use App\Jobs\SyncCustomerToErpJob;
use App\Models\ErpSyncTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueMonitorErpTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledPath = sys_get_temp_dir() . '/salgados-api-views';

        if (!is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        config(['view.compiled' => $compiledPath]);
    }

    public function test_queue_monitor_lists_failed_erp_tasks_from_persisted_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        ErpSyncTask::create([
            'operation' => ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            'entity_type' => ErpSyncTask::ENTITY_USER,
            'entity_id' => $user->id,
            'status' => ErpSyncTask::STATUS_FAILED,
            'last_error' => 'Erro sanitizado',
            'finished_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/queue?tab=falhas');

        $response->assertOk()
            ->assertSee('sync_customer')
            ->assertSee('Usuário #' . $user->id)
            ->assertSee('Erro sanitizado');
    }

    public function test_retry_task_uses_persisted_entity_fields(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'erp_sync_status' => 'failed',
            'erp_sync_error' => 'Erro anterior',
        ]);

        $task = ErpSyncTask::create([
            'operation' => ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            'entity_type' => ErpSyncTask::ENTITY_USER,
            'entity_id' => $user->id,
            'status' => ErpSyncTask::STATUS_FAILED,
            'last_error' => 'Erro sanitizado',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.queue.tasks.retry', $task));

        $response->assertRedirect();

        Queue::assertPushed(SyncCustomerToErpJob::class, fn (SyncCustomerToErpJob $job) => $job->userId === $user->id);
        $this->assertDatabaseHas('erp_sync_tasks', [
            'id' => $task->id,
            'status' => ErpSyncTask::STATUS_QUEUED,
            'last_error' => null,
        ]);
    }

    public function test_queue_monitor_does_not_offer_retry_for_manual_review_task(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        ErpSyncTask::create([
            'operation' => ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            'entity_type' => ErpSyncTask::ENTITY_USER,
            'entity_id' => $user->id,
            'status' => ErpSyncTask::STATUS_MANUAL_REVIEW,
            'last_error' => 'Intervenção humana',
            'finished_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/queue?tab=falhas');

        $response->assertOk()
            ->assertSee('Reabertura manual necessária')
            ->assertDontSee('Reenfileirar');
    }
}
