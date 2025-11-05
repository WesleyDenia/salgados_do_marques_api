<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{
    public function upload(UploadedFile $file, string $directory = 'uploads'): string
    {
        // Nome único com timestamp + random string
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        // Armazena no disco "public"
        $path = $file->storeAs($directory, $filename, 'public');

        return Storage::url($path); // retorna URL acessível
    }
}
