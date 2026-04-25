@extends('admin.layout')

@section('title', 'Cupons de Desconto')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Cupons de desconto</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Gerencie os cupons exibidos no aplicativo e painel do cliente.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.coupons.create') }}">Novo cupom</a>
    </div>

    <div class="responsive-table-wrap">
      <table class="responsive-table">
        <thead>
        <tr>
          <th>Título</th>
          <th>Código</th>
          <th>Recorrência</th>
          <th>Valor</th>
          <th>Tipo</th>
          <th>Período</th>
          <th>Categoria</th>
          <th>Status</th>
          <th style="width:76px;">Ações</th>
        </tr>
        </thead>
        <tbody>
          @forelse ($coupons as $coupon)
            @php
              $assignedCount = (int) $coupon->user_coupons_count;
              $usedCount = (int) $coupon->used_user_coupons_count;
              $activeUserCouponCount = (int) $coupon->active_user_coupons_count;
              $allAssignedCouponsUsed = $assignedCount > 0 && $usedCount >= $assignedCount;
              $isIndividualCoupon = (bool) $coupon->is_loyalty_reward || $assignedCount === 1;
            @endphp
            <tr>
              <td>
                <span class="stack-table-label">Título</span>
                {{ $coupon->title }}
              </td>
              <td>
                <span class="stack-table-label">Código</span>
                {{ $coupon->code }}
              </td>
              <td>
                <span class="stack-table-label">Recorrência</span>
                {{ $coupon->recurrence ?? '—' }}
              </td>
              <td>
                <span class="stack-table-label">Valor</span>
                {{ $coupon->amount !== null ? number_format($coupon->amount, 2, ',', '.') : '—' }}
              </td>
              <td>
                <span class="stack-table-label">Tipo</span>
                {{ $coupon->type === 'percent' ? 'Percentual' : 'Valor' }}
              </td>
              <td>
                <span class="stack-table-label">Período</span>
                @if ($coupon->starts_at || $coupon->ends_at)
                  {{ optional($coupon->starts_at)->format('d/m/Y') ?? '∞' }}
                  &rarr;
                  {{ optional($coupon->ends_at)->format('d/m/Y') ?? '∞' }}
                @else
                  —
                @endif
              </td>
              <td>
                <span class="stack-table-label">Categoria</span>
                {{ $coupon->category?->name ?? '—' }}
              </td>
              <td>
                <span class="stack-table-label">Status</span>
                @if ($isIndividualCoupon && $allAssignedCouponsUsed)
                  <span class="badge badge-muted">Usado</span>
                @elseif ($activeUserCouponCount > 0)
                  <span class="badge badge-success">Disponível</span>
                @elseif ($coupon->active)
                  <span class="badge badge-success">Ativo</span>
                @else
                  <span class="badge badge-muted">Inativo</span>
                @endif
                @if ($assignedCount > 0)
                  <div style="margin-top:4px; color:#6b7280; font-size:0.85rem;">
                    {{ $usedCount }} / {{ $assignedCount }} usados
                  </div>
                @endif
              </td>
              <td>
                <span class="stack-table-label">Ações</span>
                <details class="action-menu">
                  <summary class="btn action-menu-trigger" aria-label="Abrir ações do cupom">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                      <circle cx="8" cy="3" r="1.4" />
                      <circle cx="8" cy="8" r="1.4" />
                      <circle cx="8" cy="13" r="1.4" />
                    </svg>
                  </summary>

                  <div class="action-menu-panel">
                    <a class="btn action-menu-item" href="{{ route('admin.coupons.edit', $coupon) }}">Editar</a>
                    <form
                      action="{{ route('admin.coupons.destroy', $coupon) }}"
                      method="POST"
                      onsubmit="return confirm('Remover este cupom?');"
                    >
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn action-menu-item action-menu-item-danger">Excluir</button>
                    </form>
                  </div>
                </details>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" style="text-align:center; padding:32px 0; color:#6b7280;">
                Nenhum cupom cadastrado.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:18px;">
      {{ $coupons->links() }}
    </div>
  </div>
@endsection
