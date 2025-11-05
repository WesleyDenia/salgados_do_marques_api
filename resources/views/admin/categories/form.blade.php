@php
  $isEdit = $category->exists;
@endphp

<form
  method="POST"
  action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
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
        value="{{ old('name', $category->name) }}"
        required
      />
      @error('name')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="display_order">Ordem de exibição</label>
      <input
        type="number"
        min="0"
        id="display_order"
        name="display_order"
        value="{{ old('display_order', $category->display_order) }}"
      />
      @error('display_order')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="external_id">ID externo</label>
      <input
        type="text"
        id="external_id"
        name="external_id"
        value="{{ old('external_id', $category->external_id) }}"
      />
      @error('external_id')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="description">Descrição</label>
      <textarea
        id="description"
        name="description"
        rows="4"
      >{{ old('description', $category->description) }}</textarea>
      @error('description')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label class="checkbox-row">
        <input
          type="checkbox"
          name="active"
          value="1"
          {{ old('active', $category->active ?? true) ? 'checked' : '' }}
        />
        Categoria ativa
      </label>
      @error('active')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      {{ $isEdit ? 'Atualizar' : 'Criar' }} categoria
    </button>
    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
