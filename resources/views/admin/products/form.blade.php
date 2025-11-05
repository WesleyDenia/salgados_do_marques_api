@php
  $isEdit = $product->exists;
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
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      {{ $isEdit ? 'Atualizar' : 'Criar' }} produto
    </button>
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
