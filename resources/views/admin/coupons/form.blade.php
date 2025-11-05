@php
  $isEdit = $coupon->exists;
  $recurrences = ['none' => 'Sem recorrência', 'daily' => 'Diário', 'weekly' => 'Semanal', 'monthly' => 'Mensal', 'yearly' => 'Anual'];
  $types = ['money' => 'Valor fixo', 'percent' => 'Percentual'];
@endphp

<form
  method="POST"
  action="{{ $isEdit ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}"
  enctype="multipart/form-data"
>
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <div class="form-grid">
    <div class="form-group">
      <label for="title">Título *</label>
      <input
        type="text"
        id="title"
        name="title"
        value="{{ old('title', $coupon->title) }}"
        required
      />
      @error('title')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="code">Código *</label>
      <input
        type="text"
        id="code"
        name="code"
        value="{{ old('code', $coupon->code) }}"
        required
      />
      @error('code')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="amount">Valor *</label>
      <input
        type="number"
        step="0.01"
        min="0"
        id="amount"
        name="amount"
        value="{{ old('amount', $coupon->amount) }}"
        required
      />
      @error('amount')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="type">Tipo *</label>
      <select id="type" name="type" required>
        @foreach ($types as $value => $label)
          <option value="{{ $value }}" @selected(old('type', $coupon->type ?? 'money') === $value)>
            {{ $label }}
          </option>
        @endforeach
      </select>
      @error('type')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="recurrence">Recorrência</label>
      <select id="recurrence" name="recurrence">
        <option value="">Selecione…</option>
        @foreach ($recurrences as $value => $label)
          <option value="{{ $value }}" @selected(old('recurrence', $coupon->recurrence) === $value)>
            {{ $label }}
          </option>
        @endforeach
      </select>
      @error('recurrence')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="category_id">Categoria</label>
      <select id="category_id" name="category_id">
        <option value="">Sem categoria</option>
        @foreach ($categories as $id => $name)
          <option value="{{ $id }}" @selected((string) old('category_id', $coupon->category_id) === (string) $id)>
            {{ $name }}
          </option>
        @endforeach
      </select>
      @error('category_id')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="starts_at">Início</label>
      <input
        type="datetime-local"
        id="starts_at"
        name="starts_at"
        value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d\TH:i')) }}"
      />
      @error('starts_at')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="ends_at">Término</label>
      <input
        type="datetime-local"
        id="ends_at"
        name="ends_at"
        value="{{ old('ends_at', optional($coupon->ends_at)->format('Y-m-d\TH:i')) }}"
      />
      @error('ends_at')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="body">Descrição *</label>
      <textarea id="body" name="body" required>{{ old('body', $coupon->body) }}</textarea>
      @error('body')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="image">Imagem</label>
      @if ($coupon->image_url)
        <div style="margin-bottom:12px;">
          <img
            src="{{ $coupon->image_url }}"
            alt="Imagem atual"
            style="max-width:100%; border-radius:12px; border:1px solid #e5e7eb;"
          />
        </div>
      @endif
      <input type="file" id="image" name="image" accept="image/*" />
      @error('image')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
      @if ($coupon->image_url)
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
          {{ old('active', $coupon->active) ? 'checked' : '' }}
        />
        Cupom ativo
      </label>
      @error('active')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      {{ $isEdit ? 'Atualizar' : 'Criar' }} cupom
    </button>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
