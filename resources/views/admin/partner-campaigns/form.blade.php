@php
  $isEdit = $campaign->exists;
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.partner-campaigns.update', $campaign) : route('admin.partner-campaigns.store') }}">
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <div class="form-grid">
    <div class="form-group">
      <label for="partner_id">Parceiro *</label>
      <select id="partner_id" name="partner_id" required>
        <option value="">Selecione…</option>
        @foreach ($partners as $id => $name)
          <option value="{{ $id }}" @selected((string) old('partner_id', $campaign->partner_id) === (string) $id)>{{ $name }}</option>
        @endforeach
      </select>
      @error('partner_id')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="coupon_id">Cupom base *</label>
      <select id="coupon_id" name="coupon_id" required>
        <option value="">Selecione…</option>
        @foreach ($coupons as $id => $title)
          <option value="{{ $id }}" @selected((string) old('coupon_id', $campaign->coupon_id) === (string) $id)>{{ $title }}</option>
        @endforeach
      </select>
      @error('coupon_id')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="public_name">Nome da campanha *</label>
      <input type="text" id="public_name" name="public_name" value="{{ old('public_name', $campaign->public_name) }}" required />
      @error('public_name')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="code">Código *</label>
      <input type="text" id="code" name="code" value="{{ old('code', $campaign->code) }}" required />
      @error('code')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="starts_at">Início</label>
      <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', optional($campaign->starts_at)->format('Y-m-d\TH:i')) }}" />
      @error('starts_at')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="ends_at">Término</label>
      <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', optional($campaign->ends_at)->format('Y-m-d\TH:i')) }}" />
      @error('ends_at')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label class="checkbox-row">
        <input type="checkbox" name="active" value="1" {{ old('active', $campaign->active) ? 'checked' : '' }} />
        Campanha ativa
      </label>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Atualizar' : 'Criar' }} campanha</button>
    <a href="{{ route('admin.partner-campaigns.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
