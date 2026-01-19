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

  <div class="form-grid">
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

    <div class="form-group" style="grid-column:1/-1;">
      <label for="description">Descrição</label>
      <textarea id="description" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
      @error('description')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
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

    <div class="form-group" style="grid-column:1/-1;">
      <label>Variações</label>
      <p style="margin:6px 0 12px; color:#6b7280; font-size:0.95rem;">
        Configure packs com quantidade, preço e limite de sabores.
      </p>

      <div style="overflow:auto; border:1px solid #e5e7eb; border-radius:12px;">
        <table style="width:100%; border-collapse:collapse; min-width:720px;">
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
                  <input
                    type="number"
                    min="0"
                    name="variants[{{ $index }}][unit_count]"
                    value="{{ $variant['unit_count'] ?? 0 }}"
                    style="max-width:120px;"
                  />
                </td>
                <td style="padding:12px;">
                  <input
                    type="number"
                    min="0"
                    name="variants[{{ $index }}][max_flavors]"
                    value="{{ $variant['max_flavors'] ?? 0 }}"
                    style="max-width:120px;"
                  />
                </td>
                <td style="padding:12px;">
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
                  <input
                    type="number"
                    min="0"
                    name="variants[{{ $index }}][display_order]"
                    value="{{ $variant['display_order'] ?? 0 }}"
                    style="max-width:100px;"
                  />
                </td>
                <td style="padding:12px;">
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
    </div>
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
    if (!body || !addButton) return;

    let index = body.querySelectorAll('tr').length;

    addButton.addEventListener('click', () => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td style="padding:12px;">
          <input type="hidden" name="variants[${index}][id]" value="" />
          <input type="text" name="variants[${index}][name]" placeholder="Pack 25" />
        </td>
        <td style="padding:12px;">
          <input type="number" min="0" name="variants[${index}][unit_count]" value="0" style="max-width:120px;" />
        </td>
        <td style="padding:12px;">
          <input type="number" min="0" name="variants[${index}][max_flavors]" value="0" style="max-width:120px;" />
        </td>
        <td style="padding:12px;">
          <input type="number" step="0.01" min="0" name="variants[${index}][price]" value="" style="max-width:140px;" />
        </td>
        <td style="padding:12px;">
          <input type="number" min="0" name="variants[${index}][display_order]" value="0" style="max-width:100px;" />
        </td>
        <td style="padding:12px;">
          <label class="checkbox-row">
            <input type="checkbox" name="variants[${index}][active]" value="1" checked />
            Ativo
          </label>
        </td>
        <td style="padding:12px;">
          <label class="checkbox-row">
            <input type="checkbox" name="variants[${index}][remove]" value="1" />
            Remover
          </label>
        </td>
      `;
      body.appendChild(row);
      index += 1;
    });
  })();
</script>
