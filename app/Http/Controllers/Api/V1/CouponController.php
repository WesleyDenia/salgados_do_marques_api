<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CouponStoreRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Http\Resources\CouponResource;
use App\Repositories\CouponRepository;

class CouponController extends Controller
{
    protected CouponRepository $repo;

    public function __construct(CouponRepository $repo) { $this->repo = $repo; }

    public function index()
    {
        return CouponResource::collection($this->repo->publicPaginate());
    }

    public function store(CouponStoreRequest $request)
    {
        $coupon = $this->repo->create($request->validated());
        return new CouponResource($coupon);
    }

    public function update(CouponUpdateRequest $request, $id)
    {
        $coupon = $this->repo->find($id);
        $this->repo->update($coupon, $request->validated());
        return new CouponResource($coupon);
    }

    public function destroy($id)
    {
        $coupon = $this->repo->find($id);
        $this->repo->delete($coupon);
        return response()->json(['message' => 'Deleted']);
    }
}
