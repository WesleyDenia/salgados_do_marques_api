<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\User\UserSyncService;


class UserController extends Controller
{
    public function __construct(protected UserSyncService $syncService) {}
    
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();
        $this->syncService->sync($user);
        return new UserResource($user);
    }
}
