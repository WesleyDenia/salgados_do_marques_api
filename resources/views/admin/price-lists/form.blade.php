@php
  $isEdit = $priceList->exists;
  $existingAmounts = $priceList->relationLoaded('entries')
    ? $priceList->entries->mapWithKeys(fn ($entry) => [($entry->product_variant_id ? 'v-' . $entry->product_variant_id : 'p-' . $entry->product_id) => $entry->amount])->all()
    : [];
  $selectedTags = old('tag_ids', $priceList->relationLoaded('tags') ? $priceList->tags->pluck('id')->all() : []);
  $isDefault = (bool) $priceList->is_default;
  $cloneSourceData = $cloneSources->map(fn ($source) => [
    'id' => $source->id,
    'name' => $source->name,
    'entries' => $source->entries->mapWithKeys(fn ($entry) => [
      $entry->product_variant_id ? 'v-' . $entry->product_variant_id : 'p-' . $entry->product_id => (float) $entry->amount,
    ]),
  ])->values();
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.price-lists.update', $priceList) : route('admin.price-lists.store') }}">
  @csrf
  @if ($isEdit) @method('PUT') @endif
  <div class="form-grid">
    <div class="form-group"><label for="name">Nome *</label><input id="name" name="name" value="{{ old('name', $priceList->name) }}" required /></div>
    <div class="form-group"><label for="priority">Prioridade *</label><input type="number" min="{{ $isDefault ? 0 : 1 }}" id="priority" name="priority" value="{{ old('priority', $priceList->priority ?: 1) }}" {{ $isDefault ? 'readonly' : 'required' }} /></div>
    <div class="form-group"><label for="starts_at">Início</label><input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $priceList->starts_at?->format('Y-m-d\\TH:i')) }}" {{ $isDefault ? 'readonly' : '' }} /></div>
    <div class="form-group"><label for="ends_at">Fim</label><input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $priceList->ends_at?->format('Y-m-d\\TH:i')) }}" {{ $isDefault ? 'readonly' : '' }} /></div>
    <div class="form-group form-span-full">
      <label>Tags de encomenda</label>
      <p class="inline-note">Sem tags, a lista é global. Com tags, basta existir uma tag correspondente na encomenda do staff.</p>
      @foreach ($tags as $tag)
        <label class="checkbox-row" style="display:inline-flex; margin-right:14px;"><input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" {{ in_array($tag->id, $selectedTags) && ! $isDefault ? 'checked' : '' }} {{ $isDefault ? 'disabled' : '' }} />{{ $tag->name }}</label>
      @endforeach
    </div>
    <div class="form-group form-span-full"><label class="checkbox-row"><input type="checkbox" name="active" value="1" {{ old('active', $priceList->active) ? 'checked' : '' }} {{ $isDefault ? 'disabled' : '' }} />Lista ativa</label></div>
  </div>

  <h3 style="margin:28px 0 8px;">Preços</h3>
  <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
    <p class="inline-note" style="margin:0;">Deixe em branco para não incluir o item nesta campanha. A lista Normal deve conter todos os itens.</p>
    @if (! $isEdit && $cloneSourceData->isNotEmpty())
      <button class="btn btn-secondary" type="button" id="clone-prices">Clonar preços</button>
    @endif
  </div>
  @error('entries')<div class="alert alert-error">{{ $message }}</div>@enderror
  <div class="variant-table-wrap"><table class="variant-table"><thead><tr><th>Item</th><th>Tipo</th><th>Preço (€)</th></tr></thead><tbody>
    @php($index = 0)
    @foreach ($products as $product)
      @php($key = 'p-' . $product->id)
      <tr><td>{{ $product->name }}</td><td>Produto simples</td><td><input type="hidden" name="entries[{{ $index }}][product_id]" value="{{ $product->id }}" /><input data-price-target="{{ $key }}" type="number" step="0.01" min="0" name="entries[{{ $index }}][amount]" value="{{ old("entries.$index.amount", $existingAmounts[$key] ?? '') }}" {{ $isDefault ? 'required' : '' }} /></td></tr>
      @php($index++)
      @foreach ($product->variants as $variant)
        @php($key = 'v-' . $variant->id)
        <tr><td>{{ $product->name }} — {{ $variant->name }}</td><td>Pack</td><td><input type="hidden" name="entries[{{ $index }}][product_variant_id]" value="{{ $variant->id }}" /><input data-price-target="{{ $key }}" type="number" step="0.01" min="0" name="entries[{{ $index }}][amount]" value="{{ old("entries.$index.amount", $existingAmounts[$key] ?? '') }}" {{ $isDefault ? 'required' : '' }} /></td></tr>
        @php($index++)
      @endforeach
    @endforeach
  </tbody></table></div>
  <div class="form-actions"><button class="btn btn-primary" type="submit">{{ $isEdit ? 'Atualizar' : 'Criar' }} lista</button><a class="btn btn-secondary" href="{{ route('admin.price-lists.index') }}">Cancelar</a></div>
</form>

@if (! $isEdit && $cloneSourceData->isNotEmpty())
  <script>
    (() => {
      const sources = @json($cloneSourceData);
      document.getElementById('clone-prices')?.addEventListener('click', () => {
        const choices = sources.map(source => `${source.id}: ${source.name}`).join('\n');
        const selectedId = window.prompt(`Importar preços de qual lista?\n\n${choices}`);
        const source = sources.find(item => String(item.id) === String(selectedId).trim());

        if (!source) return;

        document.querySelectorAll('[data-price-target]').forEach(input => {
          const value = source.entries[input.dataset.priceTarget];
          input.value = value === undefined ? '' : Number(value).toFixed(2);
        });
      });
    })();
  </script>
@endif
