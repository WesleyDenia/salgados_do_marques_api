@extends('admin.layout')

@section('title', 'Detalhes da Encomenda')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Encomenda #{{ $order->id }}</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Criada em {{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}
        </p>
      </div>
      <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Voltar</a>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:18px;">
      <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
        <div style="color:#6b7280; font-size:0.85rem;">Cliente</div>
        <div style="margin-top:4px; font-weight:600;">{{ $order->user?->name ?? '—' }}</div>
        <div style="margin-top:2px; color:#6b7280; font-size:0.9rem;">{{ $order->user?->email ?? '—' }}</div>
      </div>
      <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
        <div style="color:#6b7280; font-size:0.85rem;">Loja</div>
        <div style="margin-top:4px; font-weight:600;">{{ $order->store?->name ?? '—' }}</div>
        <div style="margin-top:2px; color:#6b7280; font-size:0.9rem;">{{ $order->store?->address ?? '—' }}</div>
      </div>
      <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
        <div style="color:#6b7280; font-size:0.85rem;">Retirada</div>
        <div style="margin-top:4px; font-weight:600;">{{ $order->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</div>
      </div>
      <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
        <div style="color:#6b7280; font-size:0.85rem;">Total</div>
        <div style="margin-top:4px; font-weight:600;">€ {{ number_format((float) $order->total, 2, ',', '.') }}</div>
      </div>
    </div>

    @if ($order->notes)
      <div style="margin-bottom:18px; padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
        <div style="color:#6b7280; font-size:0.85rem;">Observações</div>
        <div style="margin-top:4px;">{{ $order->notes }}</div>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.orders.status', $order) }}" style="margin-bottom:18px;">
      @csrf
      @method('PATCH')
      <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end;">
        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status" required>
            @foreach ($statusLabels as $status => $label)
              @php
                $disabled = !in_array($status, $allowedTransitions, true) && $status !== $order->status;
              @endphp
              <option value="{{ $status }}" @selected(old('status', $order->status) === $status) @disabled($disabled)>
                {{ $label }}
              </option>
            @endforeach
          </select>
          @error('status')
            <span class="alert alert-error">{{ $message }}</span>
          @enderror
        </div>
        <div class="form-group align-end">
          <button type="submit" class="btn btn-primary">Atualizar status</button>
        </div>
      </div>
    </form>

    <h3 style="margin-top:0;">Itens da encomenda</h3>
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Qtd</th>
          <th>Preço</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($order->items as $item)
          <tr>
            <td>
              <strong>{{ $item->name_snapshot }}</strong>
              @if (!empty($item->options['flavors']) && is_array($item->options['flavors']))
                <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">
                  Sabores: {{ implode(', ', $item->options['flavors']) }}
                </div>
              @endif
            </td>
            <td>{{ $item->quantity }}</td>
            <td>€ {{ number_format((float) $item->price_snapshot, 2, ',', '.') }}</td>
            <td>€ {{ number_format((float) $item->total, 2, ',', '.') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" style="text-align:center; padding:24px 0; color:#6b7280;">
              Esta encomenda não possui itens.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection
