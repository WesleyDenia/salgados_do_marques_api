<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        $variants = $data['variants'] ?? [];
        unset($data['image'], $data['variants']);

        $data['active'] = $request->boolean('active');
        $data['price'] = (float) $data['price'];

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->uploader->upload($request->file('image'), 'products');
        }

        $product = Product::create($data);
        $this->syncVariants($product, $variants);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Produto criado com sucesso.');
    }

    public function edit(Product $product)
    {
        $product->load('variants');

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->categoriesOptions(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request, $product->id);
        $variants = $data['variants'] ?? [];
        unset($data['image'], $data['variants']);

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
        $this->syncVariants($product, $variants);

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

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.unit_count' => ['nullable', 'integer', 'min:0'],
            'variants.*.max_flavors' => ['nullable', 'integer', 'min:0'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.active' => ['nullable', 'boolean'],
            'variants.*.display_order' => ['nullable', 'integer', 'min:0'],
            'variants.*.remove' => ['nullable', 'boolean'],
        ]);

        $data['variants'] = $this->normalizeVariants($data['variants'] ?? []);

        return $data;
    }

    protected function normalizeVariants(array $variants): array
    {
        $normalized = [];

        foreach ($variants as $index => $variant) {
            $hasData = collect($variant)
                ->except(['id', 'active', 'display_order', 'remove'])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->isNotEmpty();

            if (!$hasData) {
                continue;
            }

            if (empty($variant['name']) || $variant['price'] === null || $variant['price'] === '') {
                throw ValidationException::withMessages([
                    "variants.$index.name" => 'Nome obrigatório',
                    "variants.$index.price" => 'Preço obrigatório',
                ]);
            }

            $normalized[] = [
                'id' => $variant['id'] ?? null,
                'name' => $variant['name'],
                'unit_count' => (int) ($variant['unit_count'] ?? 0),
                'max_flavors' => (int) ($variant['max_flavors'] ?? 0),
                'price' => (float) $variant['price'],
                'active' => (bool) ($variant['active'] ?? false),
                'display_order' => (int) ($variant['display_order'] ?? 0),
                'remove' => (bool) ($variant['remove'] ?? false),
            ];
        }

        return $normalized;
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

    protected function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variant) {
            $variantId = $variant['id'] ?? null;
            $remove = $variant['remove'] ?? false;

            if ($variantId && $remove) {
                $product->variants()->where('id', $variantId)->delete();
                continue;
            }

            if ($remove) {
                continue;
            }

            $payload = [
                'name' => $variant['name'],
                'unit_count' => $variant['unit_count'],
                'max_flavors' => $variant['max_flavors'],
                'price' => $variant['price'],
                'active' => $variant['active'],
                'display_order' => $variant['display_order'],
            ];

            if ($variantId) {
                $product->variants()->where('id', $variantId)->update($payload);
            } else {
                $product->variants()->create($payload);
            }
        }
    }
}

