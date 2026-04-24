<?php

namespace Tests\Unit;

use App\Models\ErpSyncTask;
use App\Models\User;
use App\Services\ErpSyncTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpSyncTaskServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_or_reuse_active_task_is_idempotent_for_active_work(): void
    {
        $user = User::factory()->create();
        $service = app(ErpSyncTaskService::class);

        $first = $service->createOrReuseActive(
            ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            ErpSyncTask::ENTITY_USER,
            $user->id
        );

        $second = $service->createOrReuseActive(
            ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            ErpSyncTask::ENTITY_USER,
            $user->id,
            ['external_id' => '123']
        );

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('erp_sync_tasks', 1);
        $this->assertSame('123', $second->external_id);
    }

    public function test_task_lifecycle_tracks_processing_success_and_failure(): void
    {
        $user = User::factory()->create();
        $service = app(ErpSyncTaskService::class);

        $task = $service->createOrReuseActive(
            ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            ErpSyncTask::ENTITY_USER,
            $user->id
        );

        $processing = $service->markProcessing($task);
        $this->assertSame(ErpSyncTask::STATUS_PROCESSING, $processing->status);
        $this->assertSame(1, $processing->attempts);

        $synced = $service->markSynced($processing, ['external_id' => 'V-1']);
        $this->assertSame(ErpSyncTask::STATUS_SYNCED, $synced->status);
        $this->assertSame('V-1', $synced->external_id);

        $failed = $service->createOrReuseActive(
            ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            ErpSyncTask::ENTITY_USER,
            $user->id
        );

        $service->markFailed($failed, 'Erro Vendus');
        $this->assertDatabaseHas('erp_sync_tasks', [
            'id' => $failed->id,
            'status' => ErpSyncTask::STATUS_FAILED,
            'active_key' => null,
            'last_error' => 'Erro Vendus',
        ]);
    }

    public function test_sync_historical_reuses_same_terminal_record(): void
    {
        $user = User::factory()->create();
        $service = app(ErpSyncTaskService::class);

        $first = $service->syncHistorical(
            ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            ErpSyncTask::ENTITY_USER,
            $user->id,
            ErpSyncTask::STATUS_FAILED,
            ['last_error' => 'Falha 1']
        );

        $second = $service->syncHistorical(
            ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            ErpSyncTask::ENTITY_USER,
            $user->id,
            ErpSyncTask::STATUS_FAILED,
            ['last_error' => 'Falha 2']
        );

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('erp_sync_tasks', 1);
        $this->assertSame('Falha 2', $second->last_error);
    }
}
