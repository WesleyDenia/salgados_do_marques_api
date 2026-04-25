@extends('admin.layout')

@section('title', 'Campanhas de Parceiros')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Campanhas de parceiros</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Configure os códigos promocionais validados no aplicativo.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.partner-campaigns.create') }}">Nova campanha</a>
    </div>

    <div class="responsive-table-wrap">
      <table class="responsive-table">
        <thead>
        <tr>
          <th>Parceiro</th>
          <th>Campanha</th>
          <th>Código</th>
          <th>Cupom base</th>
          <th>Período</th>
          <th>Status</th>
          <th style="width:76px;">Ações</th>
        </tr>
        </thead>
        <tbody>
          @forelse ($campaigns as $campaign)
            <tr>
              <td>
                <span class="stack-table-label">Parceiro</span>
                {{ $campaign->partner?->name ?? '—' }}
              </td>
              <td>
                <span class="stack-table-label">Campanha</span>
                {{ $campaign->public_name }}
              </td>
              <td>
                <span class="stack-table-label">Código</span>
                {{ $campaign->code }}
              </td>
              <td>
                <span class="stack-table-label">Cupom base</span>
                {{ $campaign->coupon?->title ?? '—' }}
              </td>
              <td>
                <span class="stack-table-label">Período</span>
                @if ($campaign->starts_at || $campaign->ends_at)
                  {{ optional($campaign->starts_at)->format('d/m/Y') ?? '∞' }}
                  &rarr;
                  {{ optional($campaign->ends_at)->format('d/m/Y') ?? '∞' }}
                @else
                  —
                @endif
              </td>
              <td>
                <span class="stack-table-label">Status</span>
                {{ $campaign->active ? 'Ativa' : 'Inativa' }}
              </td>
              <td>
                <span class="stack-table-label">Ações</span>
                <details class="action-menu">
                  <summary class="btn action-menu-trigger" aria-label="Abrir ações da campanha">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                      <circle cx="8" cy="3" r="1.4" />
                      <circle cx="8" cy="8" r="1.4" />
                      <circle cx="8" cy="13" r="1.4" />
                    </svg>
                  </summary>

                  <div class="action-menu-panel">
                    <a class="btn action-menu-item" href="{{ route('admin.partner-campaigns.edit', $campaign) }}">Editar</a>
                    <form action="{{ route('admin.partner-campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('Remover esta campanha?');">
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
              <td colspan="7" style="text-align:center; padding:32px 0; color:#6b7280;">
                Nenhuma campanha cadastrada.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div style="margin-top:18px;">
      {{ $campaigns->links() }}
    </div>
  </div>
@endsection
