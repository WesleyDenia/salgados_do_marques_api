<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    public function upload(UploadedFile $file, string $directory = 'uploads'): string
    {
        $path = $file->store($directory, 'public');
        return '/storage/' . ltrim($path, '/');
    }
}
