<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdminImageService
{
    public function __construct(protected ImageUploadService $uploader) {}

    public function store(UploadedFile $file, string $directory): string
    {
        return $this->uploader->upload($file, $directory);
    }

    public function replace(?string $currentUrl, ?UploadedFile $file, string $directory, bool $removeCurrent = false): ?string
    {
        if ($file instanceof UploadedFile) {
            $this->delete($currentUrl);

            return $this->store($file, $directory);
        }

        if ($removeCurrent) {
            $this->delete($currentUrl);

            return null;
        }

        return $currentUrl;
    }

    public function delete(?string $url): void
    {
        if (!$url) {
            return;
        }

        $path = ltrim(str_replace('/storage/', '', $url), '/');

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
