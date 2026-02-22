@extends('admin.layout')

@section('title', 'Encomendas')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Encomendas</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Acompanhe os pedidos de retirada e atualize o fluxo operacional.
        </p>
      </div>
    </div>

    <form method="GET" action="{{ route('admin.orders.index') }}" class="filter-grid">
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
        <label for="filter_from">Retirada de</label>
        <input
          type="datetime-local"
          id="filter_from"
          name="scheduled_from"
          value="{{ old('scheduled_from', $filters['scheduled_from'] ?? '') }}"
        />
      </div>

      <div class="form-group">
        <label for="filter_to">Retirada até</label>
        <input
          type="datetime-local"
          id="filter_to"
          name="scheduled_to"
          value="{{ old('scheduled_to', $filters['scheduled_to'] ?? '') }}"
        />
      </div>

      <div class="form-group align-end">
        <button type="submit" class="btn btn-secondary">Filtrar</button>
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Cliente</th>
          <th>Loja</th>
          <th>Retirada</th>
          <th>Total</th>
          <th>Status</th>
          <th>Criado em</th>
          <th style="width:150px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($orders as $order)
          <tr>
            <td>#{{ $order->id }}</td>
            <td>
              <strong>{{ $order->user?->name ?? '—' }}</strong>
              <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">
                {{ $order->user?->email ?? '—' }}
              </div>
            </td>
            <td>{{ $order->store?->name ?? '—' }}</td>
            <td>{{ $order->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td>€ {{ number_format((float) $order->total, 2, ',', '.') }}</td>
            <td>
              @if (in_array($order->status, ['done', 'accepted', 'ready'], true))
                <span class="badge badge-success">{{ $statusLabels[$order->status] ?? $order->status }}</span>
              @else
                <span class="badge badge-muted">{{ $statusLabels[$order->status] ?? $order->status }}</span>
              @endif
            </td>
            <td>{{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td>
              <a class="btn btn-secondary" href="{{ route('admin.orders.show', $order) }}">Detalhes</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align:center; padding:32px 0; color:#6b7280;">
              Nenhuma encomenda encontrada.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:18px;">
      {{ $orders->links() }}
    </div>
  </div>
@endsection
