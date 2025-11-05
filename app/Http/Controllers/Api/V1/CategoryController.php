<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected CategoryRepository $repo;

    public function __construct(CategoryRepository $repo) { $this->repo = $repo; }

    public function index()
    {
        return CategoryResource::collection($this->repo->paginate());
    }
}

