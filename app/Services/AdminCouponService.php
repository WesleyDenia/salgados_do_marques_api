<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class AdminCouponService
{
    public function __construct(
        protected AdminImageService $images,
        protected AdminCategoryService $categories,
    ) {}

    public function list(): LengthAwarePaginator
    {
        return Coupon::query()
            ->with('category')
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function categoryOptions()
    {
        return $this->categories->options();
    }

    public function options()
    {
        return Coupon::query()
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    public function create(array $data, ?UploadedFile $image = null): Coupon
    {
        $payload = $this->normalizePayload($data);

        if ($image instanceof UploadedFile) {
            $payload['image_url'] = $this->images->store($image, 'coupons');
        }

        return Coupon::create($payload);
    }

    public function update(Coupon $coupon, array $data, ?UploadedFile $image = null): Coupon
    {
        $payload = $this->normalizePayload($data);
        $payload['image_url'] = $this->images->replace(
            $coupon->image_url,
            $image,
            'coupons',
            (bool) ($data['remove_image'] ?? false)
        );

        $coupon->update($payload);

        return $coupon;
    }

    public function delete(Coupon $coupon): void
    {
        $this->images->delete($coupon->image_url);
        $coupon->delete();
    }

    protected function normalizePayload(array $data): array
    {
        unset($data['image'], $data['remove_image']);

        $data['amount'] = (float) $data['amount'];
        $data['active'] = (bool) ($data['active'] ?? false);

        return $data;
    }
}
