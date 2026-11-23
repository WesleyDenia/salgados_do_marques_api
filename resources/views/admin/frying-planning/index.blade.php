@extends('admin.layout')

@section('title', 'Planeamento de fritura')

@section('styles')
  <style>
    .frying-summary { display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:12px; margin:22px 0; }
    .frying-summary-card { border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fcfcfd; }
    .frying-summary-label { color:#6b7280; font-size:.78rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
    .frying-summary-value { margin-top:8px; color:#111827; font-size:1.65rem; font-weight:700; }
    .frying-summary-detail { margin-top:4px; color:#6b7280; font-size:.88rem; }
    .frying-cuba-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(330px, 1fr)); gap:16px; }
    .frying-cuba { overflow:hidden; border:1px solid #e5e7eb; border-radius:16px; background:#fff; }
    .frying-cuba-title { display:flex; justify-content:space-between; gap:14px; align-items:baseline; padding:17px 18px; background:#7f1d1d; color:#fff; }
    .frying-cuba-title h3 { margin:0; font-size:1.08rem; }
    .frying-cuba-title span { color:#fee2e2; font-size:.88rem; }
    .frying-task { display:grid; grid-template-columns:84px minmax(0, 1fr); gap:12px; padding:15px 18px; border-bottom:1px solid #f1f5f9; }
    .frying-task:last-child { border-bottom:0; }
    .frying-task-time { font-weight:800; color:#7f1d1d; font-size:1.02rem; white-space:nowrap; }
    .frying-task-time small { display:block; margin-top:4px; color:#6b7280; font-size:.78rem; font-weight:600; }
    .frying-task-product { font-weight:700; color:#1f2937; }
    .frying-task-details { margin-top:4px; color:#64748b; font-size:.87rem; line-height:1.4; }
    .frying-task-ready { margin-top:7px; font-size:.82rem; color:#166534; font-weight:700; }
    .frying-empty { grid-column:1/-1; border:1px dashed #cbd5e1; border-radius:16px; padding:34px; text-align:center; color:#64748b; line-height:1.5; }
    .frying-warning { margin:0 0 12px; border:1px solid #fde68a; border-radius:12px; padding:13px 15px; color:#854d0e; background:#fffbeb; line-height:1.45; }
    @media (max-width:640px) { .frying-task { grid-template-columns:1fr; gap:5px; } .frying-task-time small { display:inline; margin-left:7px; } }
  </style>
@endsection

@section('content')
  @php
    $formatDuration = static function (int $seconds): string {
      $minutes = intdiv($seconds, 60);
      $remainingSeconds = $seconds % 60;

      return $remainingSeconds === 0 ? $minutes . ' min' : $minutes . ' min ' . $remainingSeconds . ' s';
    };
  @endphp

  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Planeamento de fritura</h2>
        <p style="margin:6px 0 0;color:#6b7280;line-height:1.5;max-width:720px;">
          O plano é calculado de trás para a frente. Cada lote termina até à janela de entrega de 30 minutos e nunca há sobreposição na mesma cuba.
        </p>
      </div>
      <a class="btn btn-secondary" href="{{ route('admin.preparation-capacity.index', ['store_id' => $filters['store_id']]) }}">Configurar cubas</a>
    </div>

    <form method="GET" action="{{ route('admin.frying-planning.index') }}" class="filter-grid" style="margin-top:22px;">
      <div class="form-group">
        <label for="date">Data</label>
        <input type="date" id="date" name="date" value="{{ $filters['date'] }}" required />
      </div>
      <div class="form-group">
        <label for="store_id">Loja</label>
        <select id="store_id" name="store_id" required>
          @forelse ($stores as $store)
            <option value="{{ $store->id }}" @selected((int) $filters['store_id'] === (int) $store->id)>{{ $store->name }}</option>
          @empty
            <option value="">Sem lojas ativas</option>
          @endforelse
        </select>
      </div>
      <div class="form-group align-end">
        <button type="submit" class="btn btn-primary" @disabled($stores->isEmpty())>Ver planeamento</button>
      </div>
    </form>

    @if ($plan === null)
      <div class="frying-empty" style="margin-top:22px;">Não há lojas ativas para planear frituras.</div>
    @else
      <div class="frying-summary">
        <div class="frying-summary-card">
          <div class="frying-summary-label">Encomendas em aberto</div>
          <div class="frying-summary-value">{{ $plan['orders_count'] }}</div>
          <div class="frying-summary-detail">realizadas ou aceites</div>
        </div>
        <div class="frying-summary-card">
          <div class="frying-summary-label">Lotes a fritar</div>
          <div class="frying-summary-value">{{ $plan['tasks_count'] }}</div>
          <div class="frying-summary-detail">distribuídos pelas cubas</div>
        </div>
        <div class="frying-summary-card">
          <div class="frying-summary-label">Primeiro início</div>
          <div class="frying-summary-value">
            @php
              $firstTask = collect($plan['slots'])->flatMap(fn ($slot) => $slot['tasks'])->sortBy(fn ($task) => $task['start_at']->getTimestamp())->first();
            @endphp
            {{ $firstTask ? $firstTask['start_at']->format('H:i') : '—' }}
          </div>
          <div class="frying-summary-detail">{{ $firstTask ? 'hora de começar a fritura' : 'sem frituras planeadas' }}</div>
        </div>
      </div>

      @foreach ($plan['warnings'] as $warning)
        <div class="frying-warning">{{ $warning }}</div>
      @endforeach

      <div class="frying-cuba-grid">
        @forelse ($plan['slots'] as $slot)
          <section class="frying-cuba">
            <header class="frying-cuba-title">
              <h3>{{ $slot['name'] }}</h3>
              <span>{{ count($slot['tasks']) }} {{ count($slot['tasks']) === 1 ? 'lote' : 'lotes' }}</span>
            </header>
            @foreach ($slot['tasks'] as $task)
              <article class="frying-task">
                <div class="frying-task-time">
                  {{ $task['start_at']->format('H:i') }}
                  <small>até {{ $task['finish_at']->format('H:i') }}</small>
                </div>
                <div>
                  <div class="frying-task-product">{{ $task['batch_units'] }}× {{ $task['product_name'] }}</div>
                  <div class="frying-task-details">
                    Encomenda #{{ $task['order_id'] }}{{ $task['customer_name'] ? ' · ' . $task['customer_name'] : '' }} · {{ $formatDuration($task['preparation_time_seconds']) }}
                  </div>
                  <div class="frying-task-ready">Pronto até {{ $task['ready_by']->format('H:i') }} · levantamento {{ $task['pickup_at']?->format('H:i') ?? '—' }}</div>
                </div>
              </article>
            @endforeach
          </section>
        @empty
          <div class="frying-empty">
            Não existem lotes de fritura para esta loja e data. Confirme se as encomendas têm artigos e tempos configurados nas cubas.
          </div>
        @endforelse
      </div>
    @endif
  </div>
@endsection
