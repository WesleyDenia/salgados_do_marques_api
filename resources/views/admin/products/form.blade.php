@php
  $isEdit = $product->exists;
  $variants = old('variants');
  if ($variants === null) {
    $variants = $product->variants?->map(fn ($variant) => [
      'id' => $variant->id,
      'name' => $variant->name,
      'unit_count' => $variant->unit_count,
      'max_flavors' => $variant->max_flavors,
      'price' => $variant->price,
      'display_order' => $variant->display_order,
      'active' => $variant->active,
      'remove' => false,
    ])->toArray() ?? [];
  }
  $selectedFlavorIds = collect(old('flavor_ids', $product->flavors?->pluck('id')->all() ?? []))
    ->map(fn ($id) => (int) $id)
    ->unique()
    ->values()
    ->all();
@endphp

<form
  method="POST"
  action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
  enctype="multipart/form-data"
>
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <div class="form-grid form-grid-product">
    <section class="form-section form-span-full">
      <h3 class="form-section-title">Informações básicas</h3>
      <p class="form-section-description">Os campos essenciais ficam juntos para o registo ser direto e rápido.</p>

      <div class="form-grid form-grid-product">
        <div class="form-group">
          <label for="name">Nome *</label>
          <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $product->name) }}"
            required
          />
          @error('name')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="price">Preço *</label>
          <input
            type="number"
            step="0.01"
            min="0"
            id="price"
            name="price"
            value="{{ old('price', $product->price) }}"
            required
          />
          @error('price')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="category_id">Categoria</label>
          <select id="category_id" name="category_id">
            <option value="">Sem categoria</option>
            @foreach ($categories as $id => $name)
              <option value="{{ $id }}" @selected((string) old('category_id', $product->category_id) === (string) $id)>
                {{ $name }}
              </option>
            @endforeach
          </select>
          @error('category_id')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label class="checkbox-row">
            <input
              type="checkbox"
              name="active"
              value="1"
              {{ old('active', $product->active ?? true) ? 'checked' : '' }}
            />
            Produto ativo
          </label>
          @error('active')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group form-span-full">
          <label for="description">Descrição</label>
          <textarea id="description" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
          @error('description')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>
      </div>
    </section>

    <section class="form-section form-span-full">
      <h3 class="form-section-title">Imagem e estado</h3>
      <p class="form-section-description">A imagem atual e o respetivo controlo ficam concentrados num único bloco.</p>

      <div class="form-group">
        <label for="image">Imagem</label>
        @if ($product->image_url)
          <div style="margin-bottom:12px;">
            <img
              src="{{ $product->image_url }}"
              alt="Imagem atual"
              style="max-width:100%; border-radius:12px; border:1px solid #e5e7eb;"
            />
          </div>
        @endif
        <input type="file" id="image" name="image" accept="image/*" />
        @error('image')
          <span class="alert alert-error">{{ $message }}</span>
        @enderror
        @if ($product->image_url)
          <label class="checkbox-row" style="margin-top:12px;">
            <input type="checkbox" name="remove_image" value="1" />
            Remover imagem atual
          </label>
        @endif
      </div>
    </section>

    <section class="form-section form-span-full">
      <h3 class="form-section-title">Variações</h3>
      <p class="form-section-description">Configure packs com quantidade, preço e limite de sabores sem perder legibilidade em áreas menores.</p>

      <div class="variant-table-wrap">
        <table class="variant-table">
          <thead>
            <tr>
              <th style="text-align:left; padding:12px;">Nome</th>
              <th style="text-align:left; padding:12px;">Unidades</th>
              <th style="text-align:left; padding:12px;">Sabores</th>
              <th style="text-align:left; padding:12px;">Preço</th>
              <th style="text-align:left; padding:12px;">Ordem</th>
              <th style="text-align:left; padding:12px;">Ativo</th>
              <th style="text-align:left; padding:12px;">Remover</th>
            </tr>
          </thead>
          <tbody id="variants-body">
            @foreach ($variants as $index => $variant)
              <tr>
                <td style="padding:12px;">
                  <span class="stack-table-label">Nome</span>
                  <input
                    type="hidden"
                    name="variants[{{ $index }}][id]"
                    value="{{ $variant['id'] ?? '' }}"
                  />
                  <input
                    type="text"
                    name="variants[{{ $index }}][name]"
                    value="{{ $variant['name'] ?? '' }}"
                    placeholder="Pack 25"
                  />
                </td>
                <td style="padding:12px;">
                  <span class="stack-table-label">Unidades</span>
                  <input
                    type="number"
                    min="0"
                    name="variants[{{ $index }}][unit_count]"
                    value="{{ $variant['unit_count'] ?? 0 }}"
                    style="max-width:120px;"
                  />
                </td>
                <td style="padding:12px;">
                  <span class="stack-table-label">Sabores</span>
                  <input
                    type="number"
                    min="0"
                    name="variants[{{ $index }}][max_flavors]"
                    value="{{ $variant['max_flavors'] ?? 0 }}"
                    style="max-width:120px;"
                  />
                </td>
                <td style="padding:12px;">
                  <span class="stack-table-label">Preço</span>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="variants[{{ $index }}][price]"
                    value="{{ $variant['price'] ?? '' }}"
                    style="max-width:140px;"
                  />
                </td>
                <td style="padding:12px;">
                  <span class="stack-table-label">Ordem</span>
                  <input
                    type="number"
                    min="0"
                    name="variants[{{ $index }}][display_order]"
                    value="{{ $variant['display_order'] ?? 0 }}"
                    style="max-width:100px;"
                  />
                </td>
                <td style="padding:12px;">
                  <span class="stack-table-label">Ativo</span>
                  <label class="checkbox-row">
                    <input
                      type="checkbox"
                      name="variants[{{ $index }}][active]"
                      value="1"
                      {{ ($variant['active'] ?? false) ? 'checked' : '' }}
                    />
                    Ativo
                  </label>
                </td>
                <td style="padding:12px;">
                  <span class="stack-table-label">Remover</span>
                  <label class="checkbox-row">
                    <input
                      type="checkbox"
                      name="variants[{{ $index }}][remove]"
                      value="1"
                      {{ ($variant['remove'] ?? false) ? 'checked' : '' }}
                    />
                    Remover
                  </label>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <button type="button" class="btn btn-secondary" style="margin-top:12px;" id="add-variant">
        Adicionar variação
      </button>

      @error('variants')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </section>

    <section class="form-section form-span-full" style="display:none;" id="allowed-flavors-section">
      <h3 class="form-section-title">Sabores permitidos</h3>
      <p class="form-section-description">
        Estes sabores são usados nos packs deste artigo e serão mostrados no app apenas quando houver variações ativas.
      </p>

      <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:240px; flex:1;">
          <label for="flavor-picker" style="display:block; margin-bottom:6px;">Adicionar sabor</label>
          <select id="flavor-picker">
            <option value="">Selecionar sabor</option>
            @foreach ($flavors as $flavor)
              <option value="{{ $flavor->id }}">{{ $flavor->name }}</option>
            @endforeach
          </select>
        </div>
        <button type="button" class="btn btn-secondary" id="add-flavor">Adicionar sabor</button>
      </div>

      <div id="selected-flavors" style="margin-top:12px; display:grid; gap:8px;"></div>

      @error('flavor_ids')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
      @error('flavor_ids.*')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </section>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      {{ $isEdit ? 'Atualizar' : 'Criar' }} produto
    </button>
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>

<script>
  (function () {
    const body = document.getElementById('variants-body');
    const addButton = document.getElementById('add-variant');
    const flavorSection = document.getElementById('allowed-flavors-section');
    const flavorPicker = document.getElementById('flavor-picker');
    const addFlavorButton = document.getElementById('add-flavor');
    const selectedFlavors = document.getElementById('selected-flavors');
    if (!body || !addButton || !flavorSection || !flavorPicker || !addFlavorButton || !selectedFlavors) return;

    let index = body.querySelectorAll('tr').length;
    const flavorOptions = @json($flavors->map(fn ($flavor) => ['id' => $flavor->id, 'name' => $flavor->name])->values());
    const state = {
      selectedFlavorIds: @json($selectedFlavorIds),
    };

    const renderSelectedFlavors = () => {
      selectedFlavors.innerHTML = '';

      if (!state.selectedFlavorIds.length) {
        const empty = document.createElement('p');
        empty.style.margin = '0';
        empty.style.color = '#6b7280';
        empty.textContent = 'Nenhum sabor associado.';
        selectedFlavors.appendChild(empty);
        return;
      }

      state.selectedFlavorIds.forEach((flavorId) => {
        const flavor = flavorOptions.find((item) => Number(item.id) === Number(flavorId));
        if (!flavor) return;

        const row = document.createElement('div');
        row.style.display = 'flex';
        row.style.alignItems = 'center';
        row.style.justifyContent = 'space-between';
        row.style.gap = '12px';
        row.style.padding = '10px 12px';
        row.style.border = '1px solid #e5e7eb';
        row.style.borderRadius = '12px';

        row.innerHTML = `
          <span>${flavor.name}</span>
          <div style="display:flex; align-items:center; gap:8px;">
            <input type="hidden" name="flavor_ids[]" value="${flavor.id}" />
            <button type="button" class="btn btn-secondary" data-remove-flavor="${flavor.id}">Remover</button>
          </div>
        `;

        selectedFlavors.appendChild(row);
      });
    };

    const hasVisibleActiveVariant = () =>
      Array.from(body.querySelectorAll('tr')).some((row) => {
        const activeInput = row.querySelector('input[name$="[active]"]');
        const removeInput = row.querySelector('input[name$="[remove]"]');
        return activeInput?.checked && !removeInput?.checked;
      });

    const syncFlavorSectionVisibility = () => {
      flavorSection.style.display = hasVisibleActiveVariant() ? 'block' : 'none';
    };

    addButton.addEventListener('click', () => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td style="padding:12px;">
          <span class="stack-table-label">Nome</span>
          <input type="hidden" name="variants[${index}][id]" value="" />
          <input type="text" name="variants[${index}][name]" placeholder="Pack 25" />
        </td>
        <td style="padding:12px;">
          <span class="stack-table-label">Unidades</span>
          <input type="number" min="0" name="variants[${index}][unit_count]" value="0" style="max-width:120px;" />
        </td>
        <td style="padding:12px;">
          <span class="stack-table-label">Sabores</span>
          <input type="number" min="0" name="variants[${index}][max_flavors]" value="0" style="max-width:120px;" />
        </td>
        <td style="padding:12px;">
          <span class="stack-table-label">Preço</span>
          <input type="number" step="0.01" min="0" name="variants[${index}][price]" value="" style="max-width:140px;" />
        </td>
        <td style="padding:12px;">
          <span class="stack-table-label">Ordem</span>
          <input type="number" min="0" name="variants[${index}][display_order]" value="0" style="max-width:100px;" />
        </td>
        <td style="padding:12px;">
          <span class="stack-table-label">Ativo</span>
          <label class="checkbox-row">
            <input type="checkbox" name="variants[${index}][active]" value="1" checked />
            Ativo
          </label>
        </td>
        <td style="padding:12px;">
          <span class="stack-table-label">Remover</span>
          <label class="checkbox-row">
            <input type="checkbox" name="variants[${index}][remove]" value="1" />
            Remover
          </label>
        </td>
      `;
      body.appendChild(row);
      index += 1;
      syncFlavorSectionVisibility();
    });

    addFlavorButton.addEventListener('click', () => {
      const selectedId = Number(flavorPicker.value);
      if (!selectedId || state.selectedFlavorIds.includes(selectedId)) {
        return;
      }

      state.selectedFlavorIds.push(selectedId);
      renderSelectedFlavors();
      flavorPicker.value = '';
    });

    selectedFlavors.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const removeFlavorId = Number(target.getAttribute('data-remove-flavor'));
      if (!removeFlavorId) {
        return;
      }

      state.selectedFlavorIds = state.selectedFlavorIds.filter((id) => id !== removeFlavorId);
      renderSelectedFlavors();
    });

    body.addEventListener('change', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLInputElement)) {
        return;
      }

      if (target.name.endsWith('[active]') || target.name.endsWith('[remove]')) {
        syncFlavorSectionVisibility();
      }
    });

    renderSelectedFlavors();
    syncFlavorSectionVisibility();
  })();
</script>
