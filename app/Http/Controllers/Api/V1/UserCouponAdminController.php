<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserCouponStoreRequest;
use App\Http\Requests\UserCouponUpdateRequest;
use App\Http\Resources\UserCouponResource;
use App\Repositories\UserCouponRepository;

class UserCouponAdminController extends Controller
{
    protected UserCouponRepository $repo;

    public function __construct(UserCouponRepository $repo) { $this->repo = $repo; }

    public function index()
    {
        return UserCouponResource::collection($this->repo->paginate(['user','coupon']));
    }

    public function store(UserCouponStoreRequest $request)
    {
        $uc = $this->repo->create($request->validated());
        return new UserCouponResource($uc);
    }

    public function update(UserCouponUpdateRequest $request, $id)
    {
        $uc = $this->repo->find($id);
        $this->repo->update($uc, $request->validated());
        return new UserCouponResource($uc);
    }

    public function destroy($id)
    {
        $uc = $this->repo->find($id);
        $this->repo->delete($uc);
        return response()->json(['message' => 'Deleted']);
    }
}
