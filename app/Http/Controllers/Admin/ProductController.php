<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Services\ProductAdminService;

class ProductController extends Controller
{
    public function __construct(protected ProductAdminService $products) {}

    public function index()
    {
        return view('admin.products.index', [
            'products' => $this->products->list(),
        ]);
    }

    public function create()
    {
        return view('admin.products.create', [
            'product' => new Product([
                'active' => true,
            ]),
            'categories' => $this->products->categoryOptions(),
            'flavors' => $this->products->flavorOptions(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $this->products->create($request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Produto criado com sucesso.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', [
            'product' => $this->products->loadForEdit($product),
            'categories' => $this->products->categoryOptions(),
            'flavors' => $this->products->flavorOptions(),
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->products->update($product, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product)
    {
        $this->products->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Produto removido com sucesso.');
    }
}
