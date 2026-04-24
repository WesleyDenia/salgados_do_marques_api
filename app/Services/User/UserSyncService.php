<?php

namespace App\Services\User;

use App\Jobs\SyncCustomerToErpJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserSyncService
{
    public function sync(User $user): ?string
    {
        Log::warning('[UserSync] Sync now queues ERP work instead of calling Vendus synchronously.', [
            'user_id' => $user->id,
        ]);

        $user->forceFill([
            'erp_sync_status' => 'pending',
            'erp_sync_error' => null,
        ])->save();

        SyncCustomerToErpJob::dispatch($user->id)->afterCommit();

        return $user->external_id;
    }
}
