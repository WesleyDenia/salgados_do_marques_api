@extends('admin.layout')

@section('title', 'Nova Configuração')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Nova configuração</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Defina a chave, o tipo e o valor padrão da configuração.
    </p>

    <form method="POST" action="{{ route('admin.settings.store') }}">
      @csrf

      <div class="form-grid">
        <div class="form-group">
          <label for="key">Chave *</label>
          <input
            type="text"
            id="key"
            name="key"
            value="{{ old('key') }}"
            required
          />
          @error('key')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group">
          <label for="type">Tipo *</label>
          <select id="type" name="type" required>
            <option value="string" @selected(old('type') === 'string')>String</option>
            <option value="integer" @selected(old('type') === 'integer')>Integer</option>
            <option value="boolean" @selected(old('type') === 'boolean')>Boolean</option>
            <option value="json" @selected(old('type') === 'json')>JSON</option>
          </select>
          @error('type')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>

        <div class="form-group" style="grid-column:1/-1;">
          <label for="value">Valor</label>
          <input
            type="text"
            id="value"
            name="value"
            value="{{ old('value') }}"
          />
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
              {{ old('editable', '1') ? 'checked' : '' }}
            />
            Editável
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">Cancelar</a>
      </div>
    </form>
  </div>
@endsection
