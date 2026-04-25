@php
  $isEdit = $store->exists;
  $days = [
    'monday' => 'Segunda-feira',
    'tuesday' => 'Terça-feira',
    'wednesday' => 'Quarta-feira',
    'thursday' => 'Quinta-feira',
    'friday' => 'Sexta-feira',
    'saturday' => 'Sábado',
    'sunday' => 'Domingo',
  ];
  $weeklySchedule = old('pickup_weekly_schedule', $scheduleDays ?? []);
  $dateExceptions = old('pickup_date_exceptions', $dateExceptions ?? []);
  if (!is_array($dateExceptions)) {
    $dateExceptions = [];
  }
  if (count($dateExceptions) < 3) {
    $dateExceptions = array_pad($dateExceptions, 3, ['date' => '', 'is_open' => true, 'start_time' => '', 'end_time' => '']);
  }
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

    <div class="form-group" style="grid-column:1/-1;">
      <label style="display:block; margin-bottom:10px;">Agenda semanal de retirada *</label>
      <div style="display:grid; gap:12px;">
        @foreach ($days as $dayKey => $dayLabel)
          @php
            $dayConfig = $weeklySchedule[$dayKey] ?? ['is_open' => false, 'start_time' => null, 'end_time' => null];
          @endphp
          <div class="store-schedule-row" style="display:grid; grid-template-columns: minmax(180px, 1.2fr) repeat(3, minmax(120px, 1fr)); gap:12px; align-items:end; padding:12px; border:1px solid #e5e7eb; border-radius:12px;">
            <div>
              <label class="checkbox-row">
                <input type="hidden" name="pickup_weekly_schedule[{{ $dayKey }}][is_open]" value="0" />
                <input
                  type="checkbox"
                  name="pickup_weekly_schedule[{{ $dayKey }}][is_open]"
                  value="1"
                  {{ !empty($dayConfig['is_open']) ? 'checked' : '' }}
                />
                {{ $dayLabel }}
              </label>
            </div>
            <div class="form-group" style="margin:0;">
              <label for="pickup_weekly_schedule_{{ $dayKey }}_start_time">Abre às</label>
              <input
                type="time"
                id="pickup_weekly_schedule_{{ $dayKey }}_start_time"
                name="pickup_weekly_schedule[{{ $dayKey }}][start_time]"
                value="{{ $dayConfig['start_time'] ?? '' }}"
              />
              @error("pickup_weekly_schedule.{$dayKey}.start_time")
                <span class="alert alert-error">{{ $message }}</span>
              @enderror
            </div>
            <div class="form-group" style="margin:0;">
              <label for="pickup_weekly_schedule_{{ $dayKey }}_end_time">Fecha às</label>
              <input
                type="time"
                id="pickup_weekly_schedule_{{ $dayKey }}_end_time"
                name="pickup_weekly_schedule[{{ $dayKey }}][end_time]"
                value="{{ $dayConfig['end_time'] ?? '' }}"
              />
              @error("pickup_weekly_schedule.{$dayKey}.end_time")
                <span class="alert alert-error">{{ $message }}</span>
              @enderror
            </div>
            <div style="font-size:0.9rem; color:#6b7280;">
              {{ !empty($dayConfig['is_open']) ? 'Disponível para retirada' : 'Encerrado' }}
            </div>
          </div>
        @endforeach
      </div>
      @error('pickup_weekly_schedule')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label style="display:block; margin-bottom:10px;">Exceções por data</label>
      <p style="margin:0 0 12px; color:#6b7280; font-size:0.9rem;">
        Preencha apenas as linhas necessárias. Datas fechadas devem ficar com horários em branco.
      </p>
      <div style="display:grid; gap:12px;">
        @foreach ($dateExceptions as $index => $exception)
          <div class="store-exception-row" style="display:grid; grid-template-columns: repeat(4, minmax(120px, 1fr)); gap:12px; align-items:end; padding:12px; border:1px solid #e5e7eb; border-radius:12px;">
            <div class="form-group" style="margin:0;">
              <label for="pickup_date_exceptions_{{ $index }}_date">Data</label>
              <input
                type="date"
                id="pickup_date_exceptions_{{ $index }}_date"
                name="pickup_date_exceptions[{{ $index }}][date]"
                value="{{ $exception['date'] ?? '' }}"
              />
              @error("pickup_date_exceptions.{$index}.date")
                <span class="alert alert-error">{{ $message }}</span>
              @enderror
            </div>
            <div>
              <label class="checkbox-row">
                <input type="hidden" name="pickup_date_exceptions[{{ $index }}][is_open]" value="0" />
                <input
                  type="checkbox"
                  name="pickup_date_exceptions[{{ $index }}][is_open]"
                  value="1"
                  {{ array_key_exists('is_open', $exception) ? (!empty($exception['is_open']) ? 'checked' : '') : 'checked' }}
                />
                Data aberta
              </label>
            </div>
            <div class="form-group" style="margin:0;">
              <label for="pickup_date_exceptions_{{ $index }}_start_time">Abre às</label>
              <input
                type="time"
                id="pickup_date_exceptions_{{ $index }}_start_time"
                name="pickup_date_exceptions[{{ $index }}][start_time]"
                value="{{ $exception['start_time'] ?? '' }}"
              />
              @error("pickup_date_exceptions.{$index}.start_time")
                <span class="alert alert-error">{{ $message }}</span>
              @enderror
            </div>
            <div class="form-group" style="margin:0;">
              <label for="pickup_date_exceptions_{{ $index }}_end_time">Fecha às</label>
              <input
                type="time"
                id="pickup_date_exceptions_{{ $index }}_end_time"
                name="pickup_date_exceptions[{{ $index }}][end_time]"
                value="{{ $exception['end_time'] ?? '' }}"
              />
              @error("pickup_date_exceptions.{$index}.end_time")
                <span class="alert alert-error">{{ $message }}</span>
              @enderror
            </div>
          </div>
        @endforeach
      </div>
      @error('pickup_date_exceptions')
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
