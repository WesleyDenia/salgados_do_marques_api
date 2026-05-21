@extends('admin.layout')

@section('title', 'Planeamento diário')

@php
  $summaryCards = [
      ['label' => 'Encomendas do dia', 'value' => $summary['orderCount'], 'suffix' => $summary['orderCount'] === 1 ? 'encomenda' : 'encomendas'],
      ['label' => 'Itens planeados', 'value' => $summary['itemQuantity'], 'suffix' => $summary['itemQuantity'] === 1 ? 'item' : 'itens'],
      ['label' => 'Pagas', 'value' => $summary['paidCount'], 'suffix' => 'pagas'],
      ['label' => 'A exigir atenção', 'value' => $summary['attentionCount'], 'suffix' => 'em aberto'],
  ];
@endphp

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:22px; flex-wrap:wrap;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Planeamento diário</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Leitura operacional de {{ $selectedDayLabel }} com foco em slot, carga e prioridade de execução.
        </p>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-secondary" href="{{ route('admin.orders.index') }}">Voltar à listagem</a>
      </div>
    </div>

    @if ($errors->any())
      <div style="margin-bottom:16px; border:1px solid #fecaca; background:#fef2f2; color:#991b1b; border-radius:12px; padding:14px 16px;">
        <strong>Não foi possível carregar o planeamento diário.</strong>
        <div style="margin-top:6px;">{{ $errors->first() }}</div>
      </div>
    @endif

    <form method="GET" action="{{ route('admin.orders.daily') }}" class="filter-grid" data-daily-planning-form>
      <div class="form-group">
        <label for="filter_day">Dia operacional</label>
        <input
          type="date"
          id="filter_day"
          name="day"
          value="{{ old('day', $filters['day'] ?? '') }}"
          required
        />
      </div>

      <div class="form-group">
        <label for="filter_store">Loja</label>
        <select id="filter_store" name="store_id">
          <option value="">Todas</option>
          @foreach ($stores as $store)
            <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>
              {{ $store->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label for="filter_slot">Slot</label>
        <select id="filter_slot" name="slot">
          <option value="">Todos</option>
          @foreach ($slotLabels as $slot => $label)
            @continue($slot === 'sem_slot')
            <option value="{{ $slot }}" @selected(($filters['slot'] ?? '') === $slot)>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label for="filter_status">Status</label>
        <select id="filter_status" name="status">
          <option value="">Todos</option>
          @foreach ($statusLabels as $status => $label)
            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-group align-end">
        <button type="submit" class="btn btn-primary" data-submit-label="Atualizar visão diária">
          Atualizar visão diária
        </button>
      </div>
    </form>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:12px; margin:20px 0 22px;">
      @foreach ($summaryCards as $card)
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fcfcfd;">
          <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.06em; color:#6b7280;">
            {{ $card['label'] }}
          </div>
          <div style="margin-top:8px; font-size:1.7rem; font-weight:700; color:#111827;">
            {{ $card['value'] }}
          </div>
          <div style="margin-top:4px; color:#6b7280; font-size:0.92rem;">
            {{ $card['suffix'] }}
          </div>
          <div style="margin-top:4px; color:#9ca3af; font-size:0.82rem;">
            {{ $card['value'] }} {{ $card['suffix'] }}
          </div>
        </div>
      @endforeach
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px;">
      @foreach ($summary['slotCounts'] as $slot => $count)
        <div style="border-radius:999px; padding:8px 12px; background:#f3f4f6; color:#374151; font-size:0.92rem;">
          <strong>{{ $slotLabels[$slot] ?? ucfirst(str_replace('_', ' ', $slot)) }}:</strong> {{ $count }}
        </div>
      @endforeach
    </div>

    <div id="daily-planning-loading" style="display:none; margin-bottom:16px; border:1px solid #dbeafe; background:#eff6ff; color:#1d4ed8; border-radius:12px; padding:12px 16px;">
      A carregar planeamento diário...
    </div>

    <div class="responsive-table-wrap">
      <table class="responsive-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Retirada</th>
            <th>Slot</th>
            <th>Cliente</th>
            <th>Loja</th>
            <th>Carga</th>
            <th>Pagamento</th>
            <th>Status</th>
            <th>Prioridade</th>
            <th style="width:76px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($orders as $order)
            @php
              $scheduledAt = $order->scheduledAtForDisplay();
              $itemQuantity = (int) $order->items->sum('quantity');
              $priorityLabel = match ($order->status) {
                  'ready' => 'Retirar',
                  'accepted' => 'Preparar',
                  'placed' => 'Validar',
                  default => 'Acompanhar',
              };
            @endphp
            <tr>
              <td>
                <span class="stack-table-label">#</span>
                #{{ $order->id }}
              </td>
              <td>
                <span class="stack-table-label">Retirada</span>
                <strong>{{ $scheduledAt?->format('H:i') ?? '—' }}</strong>
                <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">
                  {{ $scheduledAt?->format('d/m/Y') ?? '—' }}
                </div>
              </td>
              <td>
                <span class="stack-table-label">Slot</span>
                <span class="badge badge-muted">{{ $slotLabels[$order->slot ?? 'sem_slot'] ?? 'Sem slot' }}</span>
              </td>
              <td>
                <span class="stack-table-label">Cliente</span>
                <strong>{{ $order->customerNameForDisplay() ?? '—' }}</strong>
                <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">
                  {{ $order->customerContactForDisplay() ?? 'Sem contacto' }}
                </div>
              </td>
              <td>
                <span class="stack-table-label">Loja</span>
                {{ $order->store?->name ?? '—' }}
              </td>
              <td>
                <span class="stack-table-label">Carga</span>
                <strong>{{ $itemQuantity }}</strong> {{ $itemQuantity === 1 ? 'item' : 'itens' }}
                <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">
                  € {{ number_format((float) $order->total, 2, ',', '.') }}
                </div>
              </td>
              <td>
                <span class="stack-table-label">Pagamento</span>
                {{ $order->payment_status ? ucfirst($order->payment_status) : 'Não indicado' }}
              </td>
              <td>
                <span class="stack-table-label">Status</span>
                @if (in_array($order->status, ['done', 'accepted', 'ready'], true))
                  <span class="badge badge-success">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                @else
                  <span class="badge badge-muted">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                @endif
              </td>
              <td>
                <span class="stack-table-label">Prioridade</span>
                <strong>{{ $priorityLabel }}</strong>
              </td>
              <td>
                <span class="stack-table-label">Ações</span>
                <a class="btn btn-secondary" href="{{ route('admin.orders.show', $order) }}">Abrir</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" style="text-align:center; padding:32px 0; color:#6b7280;">
                <strong style="display:block; color:#374151; margin-bottom:6px;">Nenhuma encomenda planeada para o dia selecionado.</strong>
                Ajuste a data ou remova filtros para verificar outras encomendas.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:18px;">
      {{ $orders->links() }}
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.querySelector('[data-daily-planning-form]');
      const loading = document.getElementById('daily-planning-loading');
      const button = form?.querySelector('[data-submit-label]');

      form?.addEventListener('submit', function () {
        if (loading) {
          loading.style.display = 'block';
        }

        if (button) {
          button.setAttribute('disabled', 'disabled');
          button.textContent = 'A carregar...';
        }
      });
    });
  </script>
@endsection
