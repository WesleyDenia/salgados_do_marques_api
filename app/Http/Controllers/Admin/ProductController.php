<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(protected ImageUploadService $uploader) {}

    public function index()
    {
        $products = Product::query()
            ->with('category')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create', [
            'product' => new Product([
                'active' => true,
            ]),
            'categories' => $this->categoriesOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        unset($data['image']);

        $data['active'] = $request->boolean('active');
        $data['price'] = (float) $data['price'];

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->uploader->upload($request->file('image'), 'products');
        }

        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Produto criado com sucesso.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->categoriesOptions(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request, $product->id);
        unset($data['image']);

        $data['active'] = $request->boolean('active');
        $data['price'] = (float) $data['price'];

        if ($request->filled('remove_image')) {
            $this->deleteImage($product->image_url);
            $data['image_url'] = null;
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($product->image_url);
            $data['image_url'] = $this->uploader->upload($request->file('image'), 'products');
        }

        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product)
    {
        $this->deleteImage($product->image_url);
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Produto removido com sucesso.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $request->merge([
            'category_id' => $request->input('category_id') ?: null,
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    protected function categoriesOptions()
    {
        return Category::orderBy('display_order')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function deleteImage(?string $url): void
    {
        if (!$url) {
            return;
        }

        $disk = Storage::disk('public');
        $path = str_replace('/storage/', '', $url);

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
