<?php

namespace App\Services;

use App\Models\ContentHome;
use App\Repositories\ContentHomeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ContentHomeService
{
    public function __construct(
        protected ContentHomeRepository $repository,
        protected AdminImageService $images,
        protected HomeComponentAdminService $components,
    ) {}

    public function listPublic(): Collection
    {
        return $this->repository->publicIndex();
    }

    public function listAdmin(): LengthAwarePaginator
    {
        return ContentHome::query()
            ->orderBy('display_order')
            ->orderByDesc('publish_at')
            ->paginate(12);
    }

    public function componentOptions(?string $selectedKey = null): array
    {
        return $this->components->availableOptions($selectedKey);
    }

    public function create(array $data, ?UploadedFile $image = null): ContentHome
    {
        $payload = $this->preparePayload($data, $image);

        return DB::transaction(function () use ($payload) {
            $desiredOrder = $payload['display_order'];
            $existing = ContentHome::query()->where('display_order', $desiredOrder)->first();

            if ($existing) {
                $maxOrder = ContentHome::query()->max('display_order') ?? 0;
                $existing->update(['display_order' => $maxOrder + 1]);
            }

            return ContentHome::create($payload);
        });
    }

    public function update(ContentHome $contentHome, array $data, ?UploadedFile $image = null): ContentHome
    {
        $payload = $this->preparePayload($data, $image, $contentHome);

        return DB::transaction(function () use ($contentHome, $payload) {
            $currentOrder = $contentHome->display_order;
            $desiredOrder = $payload['display_order'];

            if ($currentOrder !== $desiredOrder) {
                $clashing = ContentHome::query()
                    ->where('display_order', $desiredOrder)
                    ->whereKeyNot($contentHome->id)
                    ->first();

                if ($clashing) {
                    $clashing->update(['display_order' => $currentOrder]);
                }
            }

            $contentHome->update($payload);

            return $contentHome;
        });
    }

    public function delete(ContentHome $contentHome): void
    {
        $this->images->delete($contentHome->image_url);
        $contentHome->delete();
    }

    protected function preparePayload(array $data, ?UploadedFile $image = null, ?ContentHome $contentHome = null): array
    {
        $data = $this->prepareComponentData($data);
        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image']);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['cta_image_only'] = (bool) ($data['cta_image_only'] ?? false);
        $data['show_component_title'] = (bool) ($data['show_component_title'] ?? false);
        $data['image_url'] = $contentHome
            ? $this->images->replace($contentHome->image_url, $image, 'content-home', $removeImage)
            : ($image instanceof UploadedFile ? $this->images->store($image, 'content-home') : null);

        return $data;
    }

    protected function prepareComponentData(array $data): array
    {
        if (($data['type'] ?? null) === 'component') {
            if (empty($data['component_name'])) {
                throw ValidationException::withMessages([
                    'component_name' => 'Selecione um componente válido.',
                ]);
            }

            $props = $data['component_props'] ?? null;
            if ($props === null || $props === '') {
                $data['component_props'] = null;
            } else {
                $decoded = json_decode((string) $props, true);
                $data['component_props'] = is_array($decoded) ? $decoded : null;
            }

            return $data;
        }

        $data['component_name'] = null;
        $data['component_props'] = null;

        return $data;
    }
}
