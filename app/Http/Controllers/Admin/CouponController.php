<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::query()
            ->with('category')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create', [
            'coupon' => new Coupon([
                'active' => true,
                'recurrence' => 'none',
                'type' => 'money',
                'amount' => 0,
            ]),
            'categories' => $this->categoriesOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        unset($data['image']);

        $data['amount'] = (float) $data['amount'];

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->storeImage($request);
        }

        $data['active'] = $request->boolean('active');

        Coupon::create($data);

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Cupom criado com sucesso.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', [
            'coupon' => $coupon,
            'categories' => $this->categoriesOptions(),
        ]);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validateData($request, $coupon->id);
        unset($data['image']);

        $data['amount'] = (float) $data['amount'];

        if ($request->filled('remove_image')) {
            $this->deleteImage($coupon->image_url);
            $data['image_url'] = null;
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($coupon->image_url);
            $data['image_url'] = $this->storeImage($request);
        }

        $data['active'] = $request->boolean('active');

        $coupon->update($data);

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Cupom atualizado com sucesso.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->deleteImage($coupon->image_url);
        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'Cupom removido com sucesso.');
    }

    protected function validateData(Request $request, ?int $id = null): array
    {
        $request->merge([
            'recurrence' => $request->input('recurrence') ?: null,
            'category_id' => $request->input('category_id') ?: null,
            'type' => $request->input('type') ?: 'money',
        ]);

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('coupons', 'code')->ignore($id),
            ],
            'recurrence' => ['nullable', Rule::in(['none', 'daily', 'weekly', 'monthly', 'yearly'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in(['money', 'percent'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    protected function storeImage(Request $request): string
    {
        $path = $request->file('image')->store('coupons', 'public');

        return Storage::url($path);
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

    protected function categoriesOptions()
    {
        return Category::orderBy('display_order')
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
