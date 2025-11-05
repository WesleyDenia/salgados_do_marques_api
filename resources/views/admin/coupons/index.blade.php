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

    <table>
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
          <th style="width:170px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($coupons as $coupon)
          <tr>
            <td>{{ $coupon->title }}</td>
            <td>{{ $coupon->code }}</td>
            <td>{{ $coupon->recurrence ?? '—' }}</td>
            <td>{{ $coupon->amount !== null ? number_format($coupon->amount, 2, ',', '.') : '—' }}</td>
            <td>{{ $coupon->type === 'percent' ? 'Percentual' : 'Valor' }}</td>
            <td>
              @if ($coupon->starts_at || $coupon->ends_at)
                {{ optional($coupon->starts_at)->format('d/m/Y') ?? '∞' }}
                &rarr;
                {{ optional($coupon->ends_at)->format('d/m/Y') ?? '∞' }}
              @else
                —
              @endif
            </td>
            <td>{{ $coupon->category?->name ?? '—' }}</td>
            <td>
              @if ($coupon->active)
                <span class="badge badge-success">Ativo</span>
              @else
                <span class="badge badge-muted">Inativo</span>
              @endif
            </td>
            <td>
              <a class="btn btn-secondary" href="{{ route('admin.coupons.edit', $coupon) }}">Editar</a>
              <form
                action="{{ route('admin.coupons.destroy', $coupon) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm('Remover este cupom?');"
              >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align:center; padding:32px 0; color:#6b7280;">
              Nenhum cupom cadastrado.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:18px;">
      {{ $coupons->links() }}
    </div>
  </div>
@endsection
