<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessVendusDiscountCardImportJob;
use App\Jobs\SyncCustomerToErpJob;
use App\Models\User;
use App\Models\VendusDiscountCardImport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class QueueMonitorController extends Controller
{
    public function index(Request $request)
    {
        $missingUsersQuery = User::query()
            ->where(function ($query) {
                $query->whereNull('external_id')
                    ->orWhere('erp_sync_status', 'failed');
            })
            ->orderByDesc('created_at');

        $queuedJobsQuery = DB::table('jobs')
            ->where(function ($query) {
                $query->where('payload', 'like', '%SyncCustomerToErpJob%')
                    ->orWhere('payload', 'like', '%SyncPendingCustomersJob%')
                    ->orWhere('payload', 'like', '%ProcessVendusDiscountCardImportJob%')
                    ->orWhere('payload', 'like', '%SyncVendusCouponsJob%');
            })
            ->orderByDesc('created_at');

        $failedJobsQuery = DB::table('failed_jobs')
            ->where(function ($query) {
                $query->where('payload', 'like', '%SyncCustomerToErpJob%')
                    ->orWhere('payload', 'like', '%SyncPendingCustomersJob%')
                    ->orWhere('payload', 'like', '%ProcessVendusDiscountCardImportJob%')
                    ->orWhere('payload', 'like', '%SyncVendusCouponsJob%');
            })
            ->orderByDesc('failed_at');

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
                'queued_jobs' => (clone $queuedJobsQuery)->count(),
                'failed_jobs' => (clone $failedJobsQuery)->count(),
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
            'queuedJobs' => $queuedJobsQuery
                ->paginate(10, ['*'], 'jobs_page')
                ->through(fn ($job) => $this->formatQueuedJob($job))
                ->withQueryString(),
            'failedJobs' => $failedJobsQuery
                ->paginate(10, ['*'], 'failed_page')
                ->through(fn ($job) => $this->formatFailedJob($job))
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

        SyncCustomerToErpJob::dispatch($user->id);

        return back()->with('status', "Sincronização do usuário #{$user->id} reenfileirada.");
    }

    public function retryFailed(int $failedJob): RedirectResponse
    {
        $job = DB::table('failed_jobs')->where('id', $failedJob)->first();

        if (!$job) {
            return back()->with('status', 'Falha não encontrada.');
        }

        $userId = $this->extractUserId($job->payload);
        $importId = $this->extractImportId($job->payload);

        if ($userId) {
            SyncCustomerToErpJob::dispatch($userId);
            User::whereKey($userId)->update([
                'erp_sync_status' => 'pending',
                'erp_sync_error' => null,
            ]);
        } elseif ($importId) {
            VendusDiscountCardImport::whereKey($importId)->update([
                'sync_status' => VendusDiscountCardImport::STATUS_QUEUED,
                'sync_error' => null,
                'queued_at' => now(),
            ]);
            ProcessVendusDiscountCardImportJob::dispatch($importId);
        } else {
            return back()->with('status', 'Não foi possível identificar o registro deste job falhado.');
        }

        DB::table('failed_jobs')->where('id', $failedJob)->delete();

        return back()->with('status', 'Job falhado reenfileirado.');
    }

    public function destroyFailed(int $failedJob): RedirectResponse
    {
        DB::table('failed_jobs')->where('id', $failedJob)->delete();

        return back()->with('status', 'Falha removida da fila.');
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

        ProcessVendusDiscountCardImportJob::dispatch($import->id);

        return back()->with('status', "Cupom Vendus #{$import->id} reenfileirado.");
    }

    public function closeCouponImport(Request $request, VendusDiscountCardImport $import): RedirectResponse
    {
        $data = $request->validate([
            'manual_note' => ['nullable', 'string', 'max:500'],
        ]);

        $import->update([
            'sync_status' => VendusDiscountCardImport::STATUS_MANUALLY_CLOSED,
            'sync_error' => null,
            'manually_closed_at' => now(),
            'manually_closed_by' => $request->user()?->id,
            'manual_note' => $data['manual_note'] ?? null,
        ]);

        return back()->with('status', "Baixa manual aplicada ao cupom Vendus #{$import->id}.");
    }

    protected function formatQueuedJob(object $job): object
    {
        $job->display_name = $this->extractDisplayName($job->payload);
        $job->user_id = $this->extractUserId($job->payload);
        $job->import_id = $this->extractImportId($job->payload);
        $job->created_at_human = $this->timestampToDate($job->created_at);
        $job->available_at_human = $this->timestampToDate($job->available_at);
        $job->reserved_at_human = $job->reserved_at ? $this->timestampToDate($job->reserved_at) : null;

        return $job;
    }

    protected function formatFailedJob(object $job): object
    {
        $job->display_name = $this->extractDisplayName($job->payload);
        $job->user_id = $this->extractUserId($job->payload);
        $job->import_id = $this->extractImportId($job->payload);
        $job->exception_summary = $this->exceptionSummary($job->exception);

        return $job;
    }

    protected function extractDisplayName(string $payload): string
    {
        $decoded = json_decode($payload, true);

        return (string) ($decoded['displayName'] ?? 'Job desconhecido');
    }

    protected function extractUserId(string $payload): ?int
    {
        $decoded = json_decode($payload, true);
        $command = (string) ($decoded['data']['command'] ?? '');

        if (preg_match('/"userId";i:(\d+)/', $command, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/userId[^0-9]+(\d+)/', $payload, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function extractImportId(string $payload): ?int
    {
        $decoded = json_decode($payload, true);
        $command = (string) ($decoded['data']['command'] ?? '');

        if (preg_match('/"importId";i:(\d+)/', $command, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/importId[^0-9]+(\d+)/', $payload, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function exceptionSummary(string $exception): string
    {
        $lines = preg_split('/\R/', trim($exception));

        return (string) ($lines[0] ?? 'Erro sem detalhe.');
    }

    protected function timestampToDate(int $timestamp): string
    {
        return Carbon::createFromTimestamp($timestamp)->format('d/m/Y H:i');
    }
}
