<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CloseCouponImportRequest;
use App\Http\Requests\Admin\UpdateErpSyncTaskStatusRequest;
use App\Jobs\CreateVendusDiscountCardJob;
use App\Jobs\ProcessVendusDiscountCardImportJob;
use App\Jobs\SyncCustomerToErpJob;
use App\Models\ErpSyncTask;
use App\Models\UserCoupon;
use App\Models\User;
use App\Models\VendusDiscountCardImport;
use App\Repositories\ErpSyncTaskRepository;
use App\Repositories\UserCouponRepository;
use App\Services\ErpSyncTaskService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class QueueMonitorController extends Controller
{
    public function __construct(
        protected ErpSyncTaskRepository $tasks,
        protected ErpSyncTaskService $taskService,
        protected UserCouponRepository $userCoupons,
    ) {
    }

    public function index(Request $request)
    {
        $missingUsersQuery = User::query()
            ->where(function ($query) {
                $query->whereNull('external_id')
                    ->orWhere('erp_sync_status', 'failed');
            })
            ->orderByDesc('created_at');

        $queuedTaskStatuses = [ErpSyncTask::STATUS_PENDING, ErpSyncTask::STATUS_QUEUED, ErpSyncTask::STATUS_PROCESSING];
        $failedTaskStatuses = [ErpSyncTask::STATUS_FAILED, ErpSyncTask::STATUS_MANUAL_REVIEW];

        $couponFilters = [
            'code' => trim((string) $request->query('coupon_code', '')),
            'status' => (string) $request->query('coupon_status', ''),
        ];

        $couponStatusOptions = [
            VendusDiscountCardImport::STATUS_DOWNLOADED => 'Baixado',
            VendusDiscountCardImport::STATUS_QUEUED => 'Enfileirado',
            VendusDiscountCardImport::STATUS_PROCESSING => 'Processando',
            VendusDiscountCardImport::STATUS_PROCESSED => 'Processado',
            VendusDiscountCardImport::STATUS_FAILED => 'Erro',
            VendusDiscountCardImport::STATUS_MANUALLY_CLOSED => 'Baixa manual',
        ];

        if (!array_key_exists($couponFilters['status'], $couponStatusOptions)) {
            $couponFilters['status'] = '';
        }

        $couponImportsQuery = VendusDiscountCardImport::query()
            ->with(['userCoupon.user', 'matchedUserCoupon.user'])
            ->when($couponFilters['code'] !== '', function ($query) use ($couponFilters) {
                $query->where('external_code', 'like', "%{$couponFilters['code']}%");
            })
            ->when(
                $couponFilters['status'] !== '',
                fn ($query) => $query->where('sync_status', $couponFilters['status']),
                fn ($query) => $query->where('sync_status', '!=', VendusDiscountCardImport::STATUS_MANUALLY_CLOSED)
            )
            ->orderByDesc('downloaded_at')
            ->orderByDesc('created_at');

        return view('admin.queue.index', [
            'stats' => [
                'missing_users' => (clone $missingUsersQuery)->count(),
                'sync_errors' => User::where('erp_sync_status', 'failed')->count(),
                'queued_tasks' => $this->tasks->queryForAdmin($queuedTaskStatuses)->count(),
                'failed_tasks' => $this->tasks->queryForAdmin($failedTaskStatuses)->count(),
                'coupon_imports_failed' => VendusDiscountCardImport::where('sync_status', VendusDiscountCardImport::STATUS_FAILED)->count(),
                'coupon_imports_pending' => VendusDiscountCardImport::whereIn('sync_status', [
                    VendusDiscountCardImport::STATUS_DOWNLOADED,
                    VendusDiscountCardImport::STATUS_QUEUED,
                    VendusDiscountCardImport::STATUS_PROCESSING,
                ])->count(),
            ],
            'missingUsers' => $missingUsersQuery
                ->paginate(15, ['*'], 'users_page')
                ->withQueryString(),
            'queuedTasks' => $this->tasks
                ->paginateForAdmin($queuedTaskStatuses, 10, 'tasks_page')
                ->withQueryString(),
            'failedTasks' => $this->tasks
                ->paginateForAdmin($failedTaskStatuses, 10, 'failed_page')
                ->withQueryString(),
            'couponImports' => $couponImportsQuery
                ->paginate(15, ['*'], 'coupons_page')
                ->withQueryString(),
            'couponFilters' => $couponFilters,
            'couponStatusOptions' => $couponStatusOptions,
        ]);
    }

    public function enqueueUser(User $user): RedirectResponse
    {
        $user->forceFill([
            'erp_sync_status' => 'pending',
            'erp_sync_error' => null,
        ])->save();

        $this->taskService->createOrReuseActive(
            ErpSyncTask::OPERATION_SYNC_CUSTOMER,
            ErpSyncTask::ENTITY_USER,
            $user->id,
            [
                'status' => ErpSyncTask::STATUS_QUEUED,
                'external_id' => $user->external_id,
                'queued_at' => now(),
            ]
        );

        SyncCustomerToErpJob::dispatch($user->id);

        return back()->with('status', "Sincronização do usuário #{$user->id} reenfileirada.");
    }

    public function retryTask(ErpSyncTask $task): RedirectResponse
    {
        if ($task->operation === ErpSyncTask::OPERATION_SYNC_CUSTOMER && $task->entity_type === ErpSyncTask::ENTITY_USER) {
            SyncCustomerToErpJob::dispatch($task->entity_id);
            User::whereKey($task->entity_id)->update([
                'erp_sync_status' => 'pending',
                'erp_sync_error' => null,
            ]);
        } elseif ($task->operation === ErpSyncTask::OPERATION_CREATE_DISCOUNT_CARD && $task->entity_type === ErpSyncTask::ENTITY_USER_COUPON) {
            $userCoupon = UserCoupon::query()->find($task->entity_id);

            if (!$userCoupon) {
                return back()->with('status', 'Cupom local não encontrado para reprocessamento.');
            }

            $this->userCoupons->markPendingErp($userCoupon);
            CreateVendusDiscountCardJob::dispatch($userCoupon->id);
        } elseif ($task->operation === ErpSyncTask::OPERATION_IMPORT_DISCOUNT_CARD && $task->entity_type === ErpSyncTask::ENTITY_VENDUS_DISCOUNT_CARD_IMPORT) {
            VendusDiscountCardImport::whereKey($task->entity_id)->update([
                'sync_status' => VendusDiscountCardImport::STATUS_QUEUED,
                'sync_error' => null,
                'queued_at' => now(),
            ]);
            ProcessVendusDiscountCardImportJob::dispatch($task->entity_id);
        } else {
            return back()->with('status', 'Esta tarefa ERP ainda não tem reprocessamento automático.');
        }

        $this->taskService->markQueued($task);

        return back()->with('status', "Tarefa ERP #{$task->id} reenfileirada.");
    }

    public function updateTaskStatus(UpdateErpSyncTaskStatusRequest $request, ErpSyncTask $task): RedirectResponse
    {
        if ($task->operation !== ErpSyncTask::OPERATION_CREATE_DISCOUNT_CARD || $task->entity_type !== ErpSyncTask::ENTITY_USER_COUPON) {
            return back()->with('status', 'Esta tarefa ERP não suporta atualização manual de status.');
        }

        $userCoupon = UserCoupon::query()->find($task->entity_id);

        if (!$userCoupon) {
            return back()->with('status', 'Cupom local não encontrado para atualização manual.');
        }

        $payload = $request->validated();
        $note = $payload['manual_note'] ?? null;
        $actorId = $request->user()?->id;

        if ($payload['target_status'] === ErpSyncTask::STATUS_MANUAL_REVIEW) {
            $this->userCoupons->markManualReview($userCoupon, $note);
            $this->taskService->markManualReview($task, $note, $actorId);
            return back()->with('status', "Tarefa ERP #{$task->id} marcada para revisão manual.");
        }

        $this->userCoupons->markCancelled($userCoupon, $note);
        $this->taskService->markCancelled($task, $note, $actorId);

        return back()->with('status', "Tarefa ERP #{$task->id} cancelada.");
    }

    public function retryCouponImport(VendusDiscountCardImport $import): RedirectResponse
    {
        if ($import->sync_status === VendusDiscountCardImport::STATUS_MANUALLY_CLOSED) {
            return back()->with('status', 'Este cupom já recebeu baixa manual.');
        }

        $import->update([
            'sync_status' => VendusDiscountCardImport::STATUS_QUEUED,
            'sync_error' => null,
            'queued_at' => now(),
        ]);

        $this->taskService->createOrReuseActive(
            ErpSyncTask::OPERATION_IMPORT_DISCOUNT_CARD,
            ErpSyncTask::ENTITY_VENDUS_DISCOUNT_CARD_IMPORT,
            $import->id,
            [
                'status' => ErpSyncTask::STATUS_QUEUED,
                'external_id' => $import->external_id,
                'external_code' => $import->external_code,
                'queued_at' => now(),
            ]
        );

        ProcessVendusDiscountCardImportJob::dispatch($import->id);

        return back()->with('status', "Cupom Vendus #{$import->id} reenfileirado.");
    }

    public function closeCouponImport(CloseCouponImportRequest $request, VendusDiscountCardImport $import): RedirectResponse
    {
        $data = $request->validated();

        $import->update([
            'sync_status' => VendusDiscountCardImport::STATUS_MANUALLY_CLOSED,
            'sync_error' => null,
            'manually_closed_at' => now(),
            'manually_closed_by' => $request->user()?->id,
            'manual_note' => $data['manual_note'] ?? null,
        ]);

        $task = $this->taskService->createOrReuseActive(
            ErpSyncTask::OPERATION_IMPORT_DISCOUNT_CARD,
            ErpSyncTask::ENTITY_VENDUS_DISCOUNT_CARD_IMPORT,
            $import->id,
            [
                'external_id' => $import->external_id,
                'external_code' => $import->external_code,
            ]
        );

        $task->forceFill([
            'status' => ErpSyncTask::STATUS_MANUAL_REVIEW,
            'last_error' => $data['manual_note'] ?? 'Baixa manual aplicada no painel.',
            'finished_at' => now(),
            'resolved_by' => $request->user()?->id,
        ])->save();

        return back()->with('status', "Baixa manual aplicada ao cupom Vendus #{$import->id}.");
    }
}
