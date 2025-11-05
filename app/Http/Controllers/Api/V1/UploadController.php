<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Services\UploadService;
use App\Http\Controllers\Controller;
use App\Services\ImageUploadService;

class UploadController extends Controller
{
    protected ImageUploadService $upload;

    public function __construct(ImageUploadService $upload) { $this->upload = $upload; }

    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required','file','image','max:5120'],
        ]);
        $url = $this->upload->upload($request->file('image'), 'uploads');
        return response()->json(['url'=>$url]);
    }

    public function upload(Request $request, UploadService $uploadService)
    {
        $request->validate(['file' => 'required|file|max:2048']);
        $url = $uploadService->upload($request->file('file'), 'documents');
        return response()->json(['url' => $url]);
    }
}
