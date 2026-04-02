<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentHomeRequest;
use App\Models\ContentHome;
use App\Services\ContentHomeService;

class ContentHomeController extends Controller
{
    public function __construct(protected ContentHomeService $contentHome) {}

    public function index()
    {
        return view('admin.content-home.index', [
            'items' => $this->contentHome->listAdmin(),
        ]);
    }

    public function create()
    {
        $item = new ContentHome([
            'display_order' => 0,
            'type' => 'text',
            'layout' => 'default',
            'is_active' => true,
        ]);

        return view('admin.content-home.create', [
            'item' => $item,
            'components' => $this->contentHome->componentOptions(),
        ]);
    }

    public function store(ContentHomeRequest $request)
    {
        $this->contentHome->create($request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.content-home.index')
            ->with('status', 'Conteúdo criado com sucesso.');
    }

    public function edit(ContentHome $contentHome)
    {
        return view('admin.content-home.edit', [
            'item' => $contentHome,
            'components' => $this->contentHome->componentOptions($contentHome->component_name),
        ]);
    }

    public function update(ContentHomeRequest $request, ContentHome $contentHome)
    {
        $this->contentHome->update($contentHome, $request->validated(), $request->file('image'));

        return redirect()
            ->route('admin.content-home.index')
            ->with('status', 'Conteúdo atualizado com sucesso.');
    }

    public function destroy(ContentHome $contentHome)
    {
        $this->contentHome->delete($contentHome);

        return redirect()
            ->route('admin.content-home.index')
            ->with('status', 'Conteúdo removido com sucesso.');
    }
}
