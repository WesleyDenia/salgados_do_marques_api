@php
  $isEdit = $reward->exists;
@endphp

<form
  method="POST"
  action="{{ $isEdit ? route('admin.loyalty-rewards.update', $reward) : route('admin.loyalty-rewards.store') }}"
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
        value="{{ old('name', $reward->name) }}"
        required
      />
      @error('name')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="threshold">Pontos necessários *</label>
      <input
        type="number"
        min="0"
        id="threshold"
        name="threshold"
        value="{{ old('threshold', $reward->threshold) }}"
        required
      />
      @error('threshold')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="value">Valor do prêmio (€) *</label>
      <input
        type="number"
        min="0"
        step="0.01"
        id="value"
        name="value"
        value="{{ old('value', $reward->value) }}"
        required
      />
      @error('value')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="description">Descrição</label>
      <textarea id="description" name="description">{{ old('description', $reward->description) }}</textarea>
      @error('description')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="image">Imagem</label>
      @if ($reward->image_url)
        <div style="margin-bottom:12px;">
          <img
            src="{{ $reward->image_url }}"
            alt="Imagem atual"
            style="max-width:100%; border-radius:12px; border:1px solid #e5e7eb;"
          />
        </div>
      @endif
      <input type="file" id="image" name="image" accept="image/*" />
      @error('image')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
      @if ($reward->image_url)
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
          {{ old('active', $reward->active) ? 'checked' : '' }}
        />
        Recompensa ativa
      </label>
      @error('active')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      {{ $isEdit ? 'Atualizar' : 'Criar' }} recompensa
    </button>
    <a href="{{ route('admin.loyalty-rewards.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
