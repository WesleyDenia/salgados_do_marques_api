@php
  $isEdit = $store->exists;
@endphp

<form
  method="POST"
  action="{{ $isEdit ? route('admin.stores.update', $store) : route('admin.stores.store') }}"
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
        value="{{ old('name', $store->name) }}"
        required
      />
      @error('name')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="type">Tipo *</label>
      <select id="type" name="type" required>
        <option value="principal" @selected(old('type', $store->type) === 'principal')>Principal</option>
        <option value="revenda" @selected(old('type', $store->type) === 'revenda')>Revenda</option>
      </select>
      @error('type')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="phone">Telefone</label>
      <input
        type="text"
        id="phone"
        name="phone"
        value="{{ old('phone', $store->phone) }}"
        placeholder="(11) 99999-9999"
      />
      @error('phone')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="city">Cidade *</label>
      <input
        type="text"
        id="city"
        name="city"
        value="{{ old('city', $store->city) }}"
        required
      />
      @error('city')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="address">Endereço completo *</label>
      <input
        type="text"
        id="address"
        name="address"
        value="{{ old('address', $store->address) }}"
        required
      />
      @error('address')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="latitude">Latitude *</label>
      <input
        type="number"
        step="any"
        id="latitude"
        name="latitude"
        value="{{ old('latitude', $store->latitude) }}"
        required
      />
      @error('latitude')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="longitude">Longitude *</label>
      <input
        type="number"
        step="any"
        id="longitude"
        name="longitude"
        value="{{ old('longitude', $store->longitude) }}"
        required
      />
      @error('longitude')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label class="checkbox-row">
        <input
          type="checkbox"
          name="is_active"
          value="1"
          {{ old('is_active', $store->is_active) ? 'checked' : '' }}
        />
        Loja ativa
      </label>
      @error('is_active')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label class="checkbox-row">
        <input
          type="checkbox"
          name="accepts_orders"
          value="1"
          {{ old('accepts_orders', $store->accepts_orders) ? 'checked' : '' }}
        />
        Aceita pedidos para retirada
      </label>
      @error('accepts_orders')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label class="checkbox-row">
        <input
          type="checkbox"
          name="default_store"
          value="1"
          {{ old('default_store', $store->default_store) ? 'checked' : '' }}
        />
        Loja padrão para retirada
      </label>
      @error('default_store')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      {{ $isEdit ? 'Atualizar' : 'Salvar' }} loja
    </button>
    <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
