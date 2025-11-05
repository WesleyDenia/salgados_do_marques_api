<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductRepository;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductRepository $repo;
    protected ImageUploadService $upload;

    public function __construct(ProductRepository $repo, ImageUploadService $upload)
    {
        $this->repo = $repo;
        $this->upload = $upload;
    }

    public function index()
    {
        return ProductResource::collection($this->repo->publicList());
    }

    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image_url'] = $this->upload->upload($request->file('image'), 'products');
        }
        $product = $this->repo->create($data);
        return new ProductResource($product);
    }

    public function update(ProductUpdateRequest $request, $id)
    {
        $product = $this->repo->find($id);
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image_url'] = $this->upload->upload($request->file('image'), 'products');
        }
        $this->repo->update($product, $data);
        return new ProductResource($product);
    }

    public function destroy($id)
    {
        $product = $this->repo->find($id);
        $this->repo->delete($product);
        return response()->json(['message' => 'Deleted']);
    }
}
