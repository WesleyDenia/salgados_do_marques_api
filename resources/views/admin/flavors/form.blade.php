@php
  $isEdit = $flavor->exists;
@endphp

<form
  method="POST"
  action="{{ $isEdit ? route('admin.flavors.update', $flavor) : route('admin.flavors.store') }}"
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
        value="{{ old('name', $flavor->name) }}"
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
        value="{{ old('display_order', $flavor->display_order) }}"
      />
      @error('display_order')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label class="checkbox-row">
        <input
          type="checkbox"
          name="active"
          value="1"
          {{ old('active', $flavor->active ?? true) ? 'checked' : '' }}
        />
        Sabor ativo
      </label>
      @error('active')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      {{ $isEdit ? 'Atualizar' : 'Criar' }} sabor
    </button>
    <a href="{{ route('admin.flavors.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
