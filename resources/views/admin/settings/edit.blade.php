@extends('admin.layout')

@section('title', 'Editar Configuração')

@section('content')
  <div class="card">
    <h2 style="margin-top:0; font-size:1.4rem;">Editar configuração</h2>
    <p style="color:#6b7280; margin-bottom:24px;">
      Chave: <strong>{{ $setting->key }}</strong> · Tipo: <strong>{{ $setting->type }}</strong>
    </p>

    <form method="POST" action="{{ route('admin.settings.update', $setting) }}">
      @csrf
      @method('PUT')

      <div class="form-grid">
        <div class="form-group" style="grid-column:1/-1;">
          <label for="value">Valor</label>
          @if ($setting->type === 'boolean')
            <select id="value" name="value">
              <option value="1" @selected(old('value', $setting->value ? '1' : '0') === '1')>true</option>
              <option value="0" @selected(old('value', $setting->value ? '1' : '0') === '0')>false</option>
            </select>
          @elseif ($setting->type === 'json')
            <textarea id="value" name="value" rows="8">{{ old('value', json_encode($setting->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
          @else
            <input
              type="text"
              id="value"
              name="value"
              value="{{ old('value', $setting->value) }}"
            />
          @endif
          @error('value')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Salvar alterações</button>
        <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">Cancelar</a>
      </div>
    </form>
  </div>
@endsection
