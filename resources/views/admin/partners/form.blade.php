@php
  $isEdit = $partner->exists;
@endphp

<form
  method="POST"
  action="{{ $isEdit ? route('admin.partners.update', $partner) : route('admin.partners.store') }}"
  enctype="multipart/form-data"
>
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <div class="form-grid">
    <div class="form-group">
      <label for="name">Nome *</label>
      <input type="text" id="name" name="name" value="{{ old('name', $partner->name) }}" required />
      @error('name')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="slug">Slug *</label>
      <input type="text" id="slug" name="slug" value="{{ old('slug', $partner->slug) }}" required />
      @error('slug')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="description">Descrição *</label>
      <textarea id="description" name="description" required>{{ old('description', $partner->description) }}</textarea>
      @error('description')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="image">Imagem</label>
      @if ($partner->image_url)
        <div style="margin-bottom:12px;">
          <img src="{{ $partner->image_url }}" alt="Imagem atual" style="max-width:100%; border-radius:12px; border:1px solid #e5e7eb;" />
        </div>
      @endif
      <input type="file" id="image" name="image" accept="image/*" />
      @error('image')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
      @if ($partner->image_url)
        <label class="checkbox-row" style="margin-top:12px;">
          <input type="checkbox" name="remove_image" value="1" />
          Remover imagem atual
        </label>
      @endif
    </div>

    <div class="form-group">
      <label class="checkbox-row">
        <input type="checkbox" name="active" value="1" {{ old('active', $partner->active) ? 'checked' : '' }} />
        Parceiro ativo
      </label>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Atualizar' : 'Criar' }} parceiro</button>
    <a href="{{ route('admin.partners.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
