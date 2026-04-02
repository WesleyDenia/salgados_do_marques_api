<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryReorderRequest;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\AdminCategoryService;

class CategoryController extends Controller
{
    public function __construct(protected AdminCategoryService $categories) {}

    public function index()
    {
        return view('admin.categories.index', [
            'categories' => $this->categories->list(),
        ]);
    }

    public function create()
    {
        return view('admin.categories.create', [
            'category' => new Category([
                'active' => true,
            ]),
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $this->categories->create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Categoria criada com sucesso.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $this->categories->update($category, $request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Category $category)
    {
        if (!$this->categories->delete($category)) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Não é possível remover categorias com produtos associados.');
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Categoria removida com sucesso.');
    }

    public function reorder(CategoryReorderRequest $request)
    {
        $this->categories->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
