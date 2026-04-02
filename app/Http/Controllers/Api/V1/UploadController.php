<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFileRequest;
use App\Http\Requests\UploadImageRequest;
use App\Services\ImageUploadService;
use App\Services\UploadService;

class UploadController extends Controller
{
    protected ImageUploadService $upload;

    public function __construct(ImageUploadService $upload) { $this->upload = $upload; }

    public function store(UploadImageRequest $request)
    {
        $url = $this->upload->upload($request->file('image'), 'uploads');
        return response()->json(['url'=>$url]);
    }

    public function upload(UploadFileRequest $request, UploadService $uploadService)
    {
        $url = $uploadService->upload($request->file('file'), 'documents');
        return response()->json(['url' => $url]);
    }
}
