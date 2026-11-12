<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PriceListRequest;
use App\Models\PriceList;
use App\Services\PriceListAdminService;

class PriceListController extends Controller
{
    public function __construct(protected PriceListAdminService $prices) {}

    public function index()
    {
        return view('admin.price-lists.index', ['priceLists' => $this->prices->list()]);
    }

    public function create()
    {
        return view('admin.price-lists.create', $this->formData(new PriceList(['active' => true])));
    }

    public function store(PriceListRequest $request)
    {
        $this->prices->create($request->validated());

        return redirect()->route('admin.price-lists.index')->with('status', 'Lista de preços criada com sucesso.');
    }

    public function edit(PriceList $priceList)
    {
        return view('admin.price-lists.edit', $this->formData($this->prices->loadForEdit($priceList)));
    }

    public function update(PriceListRequest $request, PriceList $priceList)
    {
        $this->prices->update($priceList, $request->validated());

        return redirect()->route('admin.price-lists.index')->with('status', 'Lista de preços atualizada com sucesso.');
    }

    public function destroy(PriceList $priceList)
    {
        if (! $this->prices->delete($priceList)) {
            return redirect()->route('admin.price-lists.index')->with('error', 'A lista Normal não pode ser removida.');
        }

        return redirect()->route('admin.price-lists.index')->with('status', 'Lista de preços removida com sucesso.');
    }

    protected function formData(PriceList $priceList): array
    {
        return [
            'priceList' => $priceList,
            'products' => $this->prices->catalogTargets(),
            'tags' => $this->prices->activeTags(),
        ];
    }
}
