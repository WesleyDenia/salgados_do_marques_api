@extends('admin.layout')

@section('title', 'Editar Configuração')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Editar configuração</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Atualize a chave, o tipo e o valor mantendo a configuração consistente com o uso no app e no backend.
    </p>

    <form method="POST" action="{{ route('admin.settings.update', $setting) }}">
      @csrf
      @method('PUT')

      <div class="form-grid">
        <div class="form-group">
          <label for="key">Chave *</label>
          <input
            type="text"
            id="key"
            name="key"
            value="{{ old('key', $setting->key) }}"
            required
          />
          @error('key')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="type">Tipo *</label>
          <select id="type" name="type" required>
            <option value="string" @selected(old('type', $setting->type) === 'string')>String</option>
            <option value="integer" @selected(old('type', $setting->type) === 'integer')>Integer</option>
            <option value="boolean" @selected(old('type', $setting->type) === 'boolean')>Boolean</option>
            <option value="json" @selected(old('type', $setting->type) === 'json')>JSON</option>
          </select>
          @error('type')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group" style="grid-column:1/-1;">
          <label for="value">Valor</label>
          @php
            $selectedType = old('type', $setting->type);
            $currentValue = old('value');
            if ($currentValue === null) {
              if ($selectedType === 'json') {
                $currentValue = json_encode($setting->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
              } elseif ($selectedType === 'boolean') {
                $currentValue = $setting->value ? '1' : '0';
              } else {
                $currentValue = $setting->value;
              }
            }
          @endphp

          @if ($selectedType === 'boolean')
            <select id="value" name="value">
              <option value="1" @selected((string) $currentValue === '1')>true</option>
              <option value="0" @selected((string) $currentValue === '0')>false</option>
            </select>
          @elseif ($selectedType === 'json')
            <textarea id="value" name="value" rows="8">{{ $currentValue }}</textarea>
          @else
            <input
              type="text"
              id="value"
              name="value"
              value="{{ $currentValue }}"
            />
          @endif
          @error('value')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label class="checkbox-row">
            <input
              type="checkbox"
              name="editable"
              value="1"
              {{ old('editable', $setting->editable ? '1' : '0') ? 'checked' : '' }}
            />
            Editável
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
        <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">Cancelar</a>
      </div>
    </form>
  </div>
@endsection
