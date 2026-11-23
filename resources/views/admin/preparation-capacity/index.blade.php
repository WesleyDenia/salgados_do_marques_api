@extends('admin.layout')

@section('title', 'Cubas e fritura')

@section('styles')
  <style>
    .preparation-toolbar { display:flex; align-items:end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
    .preparation-store-form { min-width:min(100%, 360px); }
    .slot-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(290px, 1fr)); gap:16px; }
    .slot-card { border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; background:#fff; }
    .slot-card-header { padding:18px; background:#fcfcfd; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
    .slot-card-header h3 { margin:0; font-size:1.1rem; }
    .slot-card-header p { margin:5px 0 0; color:#6b7280; font-size:.9rem; }
    .slot-card-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
    .slot-card-actions .btn { padding:8px 10px; font-size:.85rem; }
    .slot-times { margin:0; padding:0; list-style:none; }
    .slot-time { padding:13px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; border-bottom:1px solid #f1f5f9; }
    .slot-time:last-child { border-bottom:0; }
    .slot-time-name { font-weight:700; }
    .slot-time-meta { margin-top:3px; color:#6b7280; font-size:.86rem; }
    .slot-time-duration { white-space:nowrap; font-weight:700; color:#7f1d1d; }
    .empty-slot { padding:20px 18px; color:#6b7280; line-height:1.5; }
    dialog.preparation-modal { width:min(94vw, 520px); border:0; border-radius:18px; padding:0; box-shadow:0 25px 80px rgba(15,23,42,.35); }
    dialog.preparation-modal::backdrop { background:rgba(15,23,42,.52); }
    .modal-content { padding:24px; }
    .modal-heading { margin:0; font-size:1.25rem; }
    .modal-description { margin:8px 0 20px; color:#6b7280; line-height:1.5; }
    .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:24px; }
    @media (max-width:640px) { .preparation-toolbar { align-items:stretch; } .preparation-toolbar > .btn { width:100%; } .preparation-store-form { width:100%; } .slot-card-header { flex-direction:column; } .slot-card-actions { justify-content:flex-start; } }
  </style>
@endsection

@section('content')
  @php
    $formatDuration = static function (int $seconds): string {
      $minutes = intdiv($seconds, 60);
      $remainingSeconds = $seconds % 60;

      return $remainingSeconds === 0
          ? $minutes . ' min'
          : $minutes . ' min ' . $remainingSeconds . ' s';
    };
  @endphp

  <div class="card">
    <div class="preparation-toolbar">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Cubas e tempos de fritura</h2>
        <p style="margin:6px 0 0;color:#6b7280;max-width:660px;line-height:1.5;">
          Cada loja tem as suas próprias cubas e tempos. Estes dados determinam em que cuba cada lote é preparado.
        </p>
      </div>

      <form method="GET" action="{{ route('admin.preparation-capacity.index') }}" class="preparation-store-form">
        <label for="store_id">Loja</label>
        <select id="store_id" name="store_id" onchange="this.form.submit()">
          @foreach ($stores as $store)
            <option value="{{ $store->id }}" @selected((int) $store->id === (int) $selectedStoreId)>{{ $store->name }}</option>
          @endforeach
        </select>
      </form>

      <button type="button" class="btn btn-primary" data-open-slot-modal>Adicionar cuba</button>
    </div>

    @if ($errors->any())
      <div style="margin-bottom:18px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:12px;padding:14px 16px;">
        <strong>Não foi possível guardar a configuração.</strong>
        <div style="margin-top:6px;">{{ $errors->first() }}</div>
      </div>
    @endif

    <div class="slot-grid">
      @forelse ($slots as $slot)
        <article class="slot-card">
          <header class="slot-card-header">
            <div>
              <h3>{{ $slot->name }}</h3>
              <p>{{ $slot->productSettings->count() }} {{ $slot->productSettings->count() === 1 ? 'artigo configurado' : 'artigos configurados' }} · aquecimento: {{ $formatDuration((int) $slot->heating_time_seconds) }}</p>
            </div>
            <div class="slot-card-actions">
              <button
                type="button"
                class="btn btn-secondary"
                data-open-time-modal
                data-slot-id="{{ $slot->id }}"
                data-slot-name="{{ $slot->name }}"
              >Adicionar tempo</button>
              <button
                type="button"
                class="btn btn-secondary"
                data-open-slot-modal
                data-clone-slot-id="{{ $slot->id }}"
                data-clone-slot-name="{{ $slot->name }}"
                data-clone-slot-heating-minutes="{{ ((int) $slot->heating_time_seconds) / 60 }}"
              >Clonar cuba</button>
            </div>
          </header>

          @if ($slot->productSettings->isEmpty())
            <div class="empty-slot">Ainda não há tempos de fritura. Adicione o primeiro artigo para que esta cuba possa ser usada no planeamento.</div>
          @else
            <ul class="slot-times">
              @foreach ($slot->productSettings->sortBy(fn ($setting) => $setting->product?->name) as $setting)
                <li class="slot-time">
                  <div>
                    <div class="slot-time-name">{{ $setting->product?->name ?? 'Artigo removido' }}</div>
                    <div class="slot-time-meta">Até {{ $setting->batch_size }} {{ $setting->batch_size === 1 ? 'unidade' : 'unidades' }} por fritura</div>
                  </div>
                  <div class="slot-time-duration">{{ $formatDuration((int) $setting->preparation_time_seconds) }}</div>
                </li>
              @endforeach
            </ul>
          @endif
        </article>
      @empty
        <div style="grid-column:1/-1;border:1px dashed #cbd5e1;border-radius:16px;padding:32px;text-align:center;color:#64748b;">
          Esta loja ainda não tem cubas configuradas. Comece por adicionar uma cuba ou clone uma configuração de outra cuba desta loja.
        </div>
      @endforelse
    </div>
  </div>

  <dialog class="preparation-modal" id="slot-modal" aria-labelledby="slot-modal-title">
    <form method="POST" action="{{ route('admin.preparation-capacity.slots.store') }}" class="modal-content">
      @csrf
      <input type="hidden" name="store_id" value="{{ $selectedStoreId }}" />
      <input type="hidden" name="clone_from_slot_id" id="clone_from_slot_id" value="" />
      <h2 class="modal-heading" id="slot-modal-title">Adicionar cuba</h2>
      <p class="modal-description" id="slot-modal-description">Dê um nome claro à cuba, por exemplo “Cuba 1 — Fritos”.</p>
      <div class="form-group">
        <label for="slot_name">Título da cuba</label>
        <input type="text" id="slot_name" name="name" maxlength="80" required autocomplete="off" />
      </div>
      <div class="form-group">
        <label for="heating_minutes">Tempo de aquecimento (minutos)</label>
        <input type="number" id="heating_minutes" name="heating_minutes" min="0" max="1440" step="0.1" value="0" required />
        <small style="display:block;margin-top:5px;color:#6b7280;">É incluído na primeira fritura e após 15 minutos sem fritar.</small>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" data-close-modal="slot-modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="slot-submit">Adicionar cuba</button>
      </div>
    </form>
  </dialog>

  <dialog class="preparation-modal" id="time-modal" aria-labelledby="time-modal-title">
    <form method="POST" action="{{ route('admin.preparation-capacity.times.store') }}" class="modal-content">
      @csrf
      <input type="hidden" name="store_id" value="{{ $selectedStoreId }}" />
      <input type="hidden" name="operational_preparation_slot_id" id="time_slot_id" value="" />
      <h2 class="modal-heading" id="time-modal-title">Adicionar tempo de fritura</h2>
      <p class="modal-description">Cuba selecionada: <strong id="time-slot-name"></strong>. Se o artigo já existir nesta cuba, o novo valor substitui o anterior.</p>
      <div class="form-grid">
        <div class="form-group form-span-full">
          <label for="product_id">Artigo</label>
          <select id="product_id" name="product_id" required>
            <option value="">Selecione um artigo</option>
            @foreach ($products as $product)
              <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label for="preparation_minutes">Tempo de fritura (minutos)</label>
          <input type="number" id="preparation_minutes" name="preparation_minutes" min="0.1" max="1440" step="0.1" required placeholder="Ex.: 3.5" />
        </div>
        <div class="form-group">
          <label for="batch_size">Quantidade por fritura</label>
          <input type="number" id="batch_size" name="batch_size" min="1" max="9999" value="25" required />
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" data-close-modal="time-modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar tempo</button>
      </div>
    </form>
  </dialog>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const slotModal = document.getElementById('slot-modal');
      const timeModal = document.getElementById('time-modal');
      const cloneInput = document.getElementById('clone_from_slot_id');
      const slotNameInput = document.getElementById('slot_name');
      const heatingMinutesInput = document.getElementById('heating_minutes');
      const slotTitle = document.getElementById('slot-modal-title');
      const slotDescription = document.getElementById('slot-modal-description');
      const slotSubmit = document.getElementById('slot-submit');

      document.querySelectorAll('[data-open-slot-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
          const cloneId = button.dataset.cloneSlotId || '';
          const cloneName = button.dataset.cloneSlotName || '';
          const cloneHeatingMinutes = button.dataset.cloneSlotHeatingMinutes || '0';
          cloneInput.value = cloneId;
          slotNameInput.value = cloneName ? cloneName + ' (cópia)' : '';
          heatingMinutesInput.value = cloneHeatingMinutes;
          slotTitle.textContent = cloneId ? 'Clonar cuba' : 'Adicionar cuba';
          slotDescription.textContent = cloneId
            ? 'Os tempos de fritura de “' + cloneName + '” serão copiados. Altere apenas o título da nova cuba.'
            : 'Dê um nome claro à cuba, por exemplo “Cuba 1 — Fritos”.';
          slotSubmit.textContent = cloneId ? 'Clonar cuba' : 'Adicionar cuba';
          slotModal.showModal();
          slotNameInput.focus();
        });
      });

      document.querySelectorAll('[data-open-time-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
          document.getElementById('time_slot_id').value = button.dataset.slotId;
          document.getElementById('time-slot-name').textContent = button.dataset.slotName;
          timeModal.showModal();
          document.getElementById('product_id').focus();
        });
      });

      document.querySelectorAll('[data-close-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
          document.getElementById(button.dataset.closeModal).close();
        });
      });
    });
  </script>
@endsection
