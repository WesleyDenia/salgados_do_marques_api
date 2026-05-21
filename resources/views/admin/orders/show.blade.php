@extends('admin.layout')

@section('title', 'Detalhes da Encomenda')

@section('styles')
  <style>
    .detail-page {
      display: grid;
      gap: 24px;
    }

    .detail-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }

    .detail-header-copy {
      min-width: 0;
    }

    .detail-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .detail-meta-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
    }

    .detail-meta-card {
      padding: 12px;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      background: #fcfcfe;
      min-width: 0;
      overflow-wrap: anywhere;
    }

    .detail-meta-label {
      color: #6b7280;
      font-size: 0.85rem;
    }

    .detail-meta-value {
      margin-top: 4px;
      font-weight: 600;
      overflow-wrap: anywhere;
    }

    .detail-section-title {
      margin: 0;
      font-size: 1.15rem;
    }

    .detail-section-note {
      margin: 6px 0 0;
      color: #6b7280;
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .detail-form {
      margin-bottom: 0;
    }

    .detail-form-actions {
      margin-top: 18px;
      display: flex;
      justify-content: flex-start;
    }

    .detail-table-wrap {
      margin-top: 14px;
      overflow: hidden;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      background: #ffffff;
    }

    .history-list {
      display: grid;
      gap: 16px;
      margin-top: 18px;
    }

    .history-card {
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 16px;
      background: #fcfcfe;
    }

    .history-header {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      align-items: flex-start;
    }

    .history-meta {
      color: #6b7280;
      font-size: 0.92rem;
      margin-top: 4px;
    }

    .history-change-list {
      margin: 14px 0 0;
      padding-left: 1.1rem;
    }

    .history-change-list li + li {
      margin-top: 8px;
    }

    .history-change-field {
      font-weight: 600;
    }

    .history-change-values {
      color: #4b5563;
      font-size: 0.95rem;
      margin-top: 2px;
      overflow-wrap: anywhere;
    }

    @media (max-width: 640px) {
      .detail-header,
      .detail-actions,
      .detail-form-actions {
        width: 100%;
      }

      .detail-header {
        flex-direction: column;
      }

      .detail-actions .btn,
      .detail-form-actions .btn {
        width: 100%;
      }

      .detail-meta-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
@endsection

@section('content')
  <div class="detail-page">
    <div class="card">
      <div class="detail-header">
        <div class="detail-header-copy">
          <h2 style="margin:0;font-size:1.4rem;">Encomenda #{{ $order->id }}</h2>
          <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
            Criada em {{ $order->created_at?->format('d/m/Y H:i') ?? '—' }}
          </p>
        </div>
        <div class="detail-actions">
          <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Voltar</a>
        </div>
      </div>
    </div>

    <div class="card">
      <h3 class="detail-section-title">Atualizar status</h3>
      <p class="detail-section-note">
        Selecione um novo estado para a encomenda. As transições indisponíveis permanecem bloqueadas.
      </p>

      <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="detail-form">
        @csrf
        @method('PATCH')
        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); align-items:end; margin-top:18px;">
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
          <div class="detail-form-actions">
            <button type="submit" class="btn btn-primary">Atualizar status</button>
          </div>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="detail-meta-grid" style="margin-top:20px;">
        <div class="detail-meta-card">
          <div class="detail-meta-label">Cliente</div>
          <div class="detail-meta-value">{{ $order->user?->name ?? '—' }}</div>
          <div style="margin-top:2px; color:#6b7280; font-size:0.9rem;">{{ $order->user?->email ?? '—' }}</div>
        </div>
        <div class="detail-meta-card">
          <div class="detail-meta-label">Loja</div>
          <div class="detail-meta-value">{{ $order->store?->name ?? '—' }}</div>
          <div style="margin-top:2px; color:#6b7280; font-size:0.9rem;">{{ $order->store?->address ?? '—' }}</div>
        </div>
        <div class="detail-meta-card">
          <div class="detail-meta-label">Retirada</div>
          <div class="detail-meta-value">{{ $order->scheduledAtForDisplay()?->format('d/m/Y H:i') ?? '—' }}</div>
        </div>
        <div class="detail-meta-card">
          <div class="detail-meta-label">Total</div>
          <div class="detail-meta-value">€ {{ number_format((float) $order->total, 2, ',', '.') }}</div>
        </div>
      </div>
    </div>

    @if ($order->notes)
      <div class="card">
        <div class="detail-meta-label">Observações</div>
        <div style="margin-top:4px;">{{ $order->notes }}</div>
      </div>
    @endif

    <div class="card">
      <h3 class="detail-section-title">Histórico relevante</h3>
      <p class="detail-section-note">
        Consulte quem alterou a encomenda, quando alterou e quais campos relevantes mudaram.
      </p>

      @php
        $actionLabels = [
          'updated' => 'Correção de encomenda',
          'status_changed' => 'Mudança de estado',
        ];

        $fieldLabels = [
          'customer_name' => 'Nome do cliente',
          'customer_contact' => 'Contacto do cliente',
          'store_id' => 'Loja',
          'payment_status' => 'Estado do pagamento',
          'slot' => 'Slot operacional',
          'scheduled_at' => 'Data/hora de retirada',
          'notes' => 'Observações',
          'total' => 'Total',
          'status' => 'Estado',
          'cancelled_at' => 'Cancelada em',
          'items' => 'Itens',
        ];

        $formatHistoryValue = function ($value) {
          if ($value === null || $value === '') {
            return '—';
          }

          if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
          }

          if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          }

          return (string) $value;
        };
      @endphp

      @if ($order->history->isEmpty())
        <div style="margin-top:18px; color:#6b7280;">
          Esta encomenda ainda não possui alterações relevantes registadas.
        </div>
      @else
        <div class="history-list">
          @foreach ($order->history as $historyEntry)
            <div class="history-card">
              <div class="history-header">
                <div>
                  <strong>{{ $actionLabels[$historyEntry->action] ?? \Illuminate\Support\Str::headline($historyEntry->action) }}</strong>
                  <div class="history-meta">
                    {{ $historyEntry->user?->name ?? ($historyEntry->user_id ? "Utilizador #{$historyEntry->user_id}" : 'Sistema') }}
                    @if ($historyEntry->user?->email)
                      · {{ $historyEntry->user->email }}
                    @endif
                  </div>
                </div>
                <div class="history-meta">
                  {{ $historyEntry->created_at?->timezone('Europe/Lisbon')->format('d/m/Y H:i') ?? '—' }}
                </div>
              </div>

              <ul class="history-change-list">
                @foreach (($historyEntry->changes ?? []) as $field => $change)
                  <li>
                    <div class="history-change-field">{{ $fieldLabels[$field] ?? \Illuminate\Support\Str::headline($field) }}</div>
                    <div class="history-change-values">
                      De: {{ $formatHistoryValue($change['from'] ?? null) }}
                      <br>
                      Para: {{ $formatHistoryValue($change['to'] ?? null) }}
                    </div>
                  </li>
                @endforeach
              </ul>
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <div class="card">
      <h3 class="detail-section-title">Itens da encomenda</h3>

      <div class="responsive-table-wrap detail-table-wrap">
        <table class="responsive-table">
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
                  <span class="stack-table-label">Item</span>
                  <strong>{{ $item->name_snapshot }}</strong>
                  @if (!empty($item->options['flavors']) && is_array($item->options['flavors']))
                    @php
                      $resolvedFlavors = collect($item->options['flavors'])
                        ->map(function ($flavorId) use ($flavorNamesById) {
                          $flavorId = (int) $flavorId;

                          return $flavorNamesById[$flavorId] ?? $flavorId;
                        })
                        ->all();
                    @endphp
                    <div style="color:#40444c; font-size:1rem; margin-top:4px; overflow-wrap:anywhere;">
                      Sabores:
                      <ul style="margin:4px 0 0; padding-left:1.1rem; list-style:disc;">
                        @foreach ($resolvedFlavors as $resolvedFlavor)
                          <li style="margin:2px 0; overflow-wrap:anywhere;">{{ $resolvedFlavor }}</li>
                        @endforeach
                      </ul>
                    </div>
                  @endif
                </td>
                <td>
                  <span class="stack-table-label">Qtd</span>
                  {{ $item->quantity }}
                </td>
                <td>
                  <span class="stack-table-label">Preço</span>
                  € {{ number_format((float) $item->price_snapshot, 2, ',', '.') }}
                </td>
                <td>
                  <span class="stack-table-label">Total</span>
                  € {{ number_format((float) $item->total, 2, ',', '.') }}
                </td>
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
    </div>
  </div>
@endsection
