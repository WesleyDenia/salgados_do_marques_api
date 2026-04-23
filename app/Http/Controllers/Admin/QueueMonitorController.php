<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncCustomerToErpJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class QueueMonitorController extends Controller
{
    public function index()
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
                    ->orWhere('payload', 'like', '%SyncPendingCustomersJob%');
            })
            ->orderByDesc('created_at');

        $failedJobsQuery = DB::table('failed_jobs')
            ->where(function ($query) {
                $query->where('payload', 'like', '%SyncCustomerToErpJob%')
                    ->orWhere('payload', 'like', '%SyncPendingCustomersJob%');
            })
            ->orderByDesc('failed_at');

        return view('admin.queue.index', [
            'stats' => [
                'missing_users' => (clone $missingUsersQuery)->count(),
                'sync_errors' => User::where('erp_sync_status', 'failed')->count(),
                'queued_jobs' => (clone $queuedJobsQuery)->count(),
                'failed_jobs' => (clone $failedJobsQuery)->count(),
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

        if (!$userId) {
            return back()->with('status', 'Não foi possível identificar o usuário deste job falhado.');
        }

        SyncCustomerToErpJob::dispatch($userId);
        User::whereKey($userId)->update([
            'erp_sync_status' => 'pending',
            'erp_sync_error' => null,
        ]);
        DB::table('failed_jobs')->where('id', $failedJob)->delete();

        return back()->with('status', "Sincronização do usuário #{$userId} reenfileirada.");
    }

    public function destroyFailed(int $failedJob): RedirectResponse
    {
        DB::table('failed_jobs')->where('id', $failedJob)->delete();

        return back()->with('status', 'Falha removida da fila.');
    }

    protected function formatQueuedJob(object $job): object
    {
        $job->display_name = $this->extractDisplayName($job->payload);
        $job->user_id = $this->extractUserId($job->payload);
        $job->created_at_human = $this->timestampToDate($job->created_at);
        $job->available_at_human = $this->timestampToDate($job->available_at);
        $job->reserved_at_human = $job->reserved_at ? $this->timestampToDate($job->reserved_at) : null;

        return $job;
    }

    protected function formatFailedJob(object $job): object
    {
        $job->display_name = $this->extractDisplayName($job->payload);
        $job->user_id = $this->extractUserId($job->payload);
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
