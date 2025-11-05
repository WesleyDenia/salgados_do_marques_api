<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionStoreRequest;
use App\Http\Requests\PromotionUpdateRequest;
use App\Http\Resources\PromotionResource;
use App\Repositories\PromotionRepository;

class PromotionController extends Controller
{
    protected PromotionRepository $repo;

    public function __construct(PromotionRepository $repo) { $this->repo = $repo; }

    public function index()
    {
        return PromotionResource::collection($this->repo->publicList());
    }

    public function store(PromotionStoreRequest $request)
    {
        $promo = $this->repo->create($request->validated());
        return new PromotionResource($promo);
    }

    public function update(PromotionUpdateRequest $request, $id)
    {
        $promo = $this->repo->find($id);
        $this->repo->update($promo, $request->validated());
        return new PromotionResource($promo);
    }

    public function destroy($id)
    {
        $promo = $this->repo->find($id);
        $this->repo->delete($promo);
        return response()->json(['message' => 'Deleted']);
    }
}
