<?php

namespace App\Services;

use App\Models\Partner;
use App\Repositories\PartnerRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class PartnerService
{
    public function __construct(
        protected PartnerRepository $partners,
        protected AdminImageService $images,
    ) {}

    public function listActive(): Collection
    {
        return $this->partners->activeForApp();
    }

    public function showActive(int $id): Partner
    {
        return $this->partners->activeById($id);
    }

    public function listAdmin(): LengthAwarePaginator
    {
        return $this->partners->query()
            ->orderBy('name')
            ->paginate(15);
    }

    public function options()
    {
        return $this->partners->query()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function createAdmin(array $data, ?UploadedFile $image = null): Partner
    {
        $payload = $this->normalizeAdminPayload($data);

        if ($image instanceof UploadedFile) {
            $payload['image_url'] = $this->images->store($image, 'partners');
        }

        return $this->partners->create($payload);
    }

    public function updateAdmin(Partner $partner, array $data, ?UploadedFile $image = null): Partner
    {
        $payload = $this->normalizeAdminPayload($data);
        $payload['image_url'] = $this->images->replace(
            $partner->image_url,
            $image,
            'partners',
            (bool) ($data['remove_image'] ?? false)
        );

        return $this->partners->update($partner, $payload);
    }

    public function deleteAdmin(Partner $partner): void
    {
        $this->images->delete($partner->image_url);
        $this->partners->delete($partner);
    }

    protected function normalizeAdminPayload(array $data): array
    {
        unset($data['image'], $data['remove_image']);
        $data['active'] = (bool) ($data['active'] ?? false);

        return $data;
    }
}
