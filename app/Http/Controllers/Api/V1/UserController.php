<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Jobs\SyncCustomerToErpJob;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $erpFields = ['name', 'email', 'nif', 'phone', 'street', 'city', 'postal_code'];

        DB::transaction(function () use ($user, $validated, $erpFields) {
            $user->fill($validated);
            $shouldSyncErp = $user->isDirty($erpFields);

            if ($shouldSyncErp) {
                $user->forceFill([
                    'erp_sync_status' => 'pending',
                    'erp_sync_error' => null,
                ]);
            }

            $user->save();

            if ($shouldSyncErp) {
                SyncCustomerToErpJob::dispatch($user->id)->afterCommit();
            }
        });

        return new UserResource($user->refresh());
    }
}
