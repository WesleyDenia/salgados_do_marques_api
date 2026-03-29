@php
  $isEdit = $component->exists;
@endphp

<form
  method="POST"
  action="{{ $isEdit ? route('admin.home-components.update', $component) : route('admin.home-components.store') }}"
>
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <div class="form-grid">
    <div class="form-group">
      <label for="key">Nome técnico *</label>
      <input
        type="text"
        id="key"
        name="key"
        value="{{ old('key', $component->key) }}"
        maxlength="100"
        required
      />
      @error('key')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
      <p style="font-size:0.85rem; color:#6b7280; margin-top:6px;">
        Use o identificador exato usado no app, por exemplo <strong>CouponsCarousel</strong>.
      </p>
      <p style="font-size:0.85rem; color:#6b7280; margin-top:6px;">
        Registar aqui apenas disponibiliza o componente no painel. O app mobile ainda precisa conhecer este nome para o renderizar.
      </p>
    </div>

    <div class="form-group">
      <label for="label">Rótulo no painel *</label>
      <input
        type="text"
        id="label"
        name="label"
        value="{{ old('label', $component->label) }}"
        maxlength="255"
        required
      />
      @error('label')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="description">Descrição</label>
      <textarea id="description" name="description" rows="4">{{ old('description', $component->description) }}</textarea>
      @error('description')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
      <p style="font-size:0.85rem; color:#6b7280; margin-top:6px;">
        Documente quando usar este componente e que propriedades JSON ele espera, se aplicável.
      </p>
    </div>

    <div class="form-group">
      <label class="checkbox-row">
        <input
          type="checkbox"
          name="is_active"
          value="1"
          {{ old('is_active', $component->is_active ?? true) ? 'checked' : '' }}
        />
        Componente ativo para seleção
      </label>
      @error('is_active')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      {{ $isEdit ? 'Atualizar' : 'Criar' }} componente
    </button>
    <a href="{{ route('admin.home-components.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
