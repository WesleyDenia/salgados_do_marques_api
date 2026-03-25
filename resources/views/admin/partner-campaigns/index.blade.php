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

    <table>
      <thead>
        <tr>
          <th>Parceiro</th>
          <th>Campanha</th>
          <th>Código</th>
          <th>Cupom base</th>
          <th>Período</th>
          <th>Status</th>
          <th style="width:170px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($campaigns as $campaign)
          <tr>
            <td>{{ $campaign->partner?->name ?? '—' }}</td>
            <td>{{ $campaign->public_name }}</td>
            <td>{{ $campaign->code }}</td>
            <td>{{ $campaign->coupon?->title ?? '—' }}</td>
            <td>
              @if ($campaign->starts_at || $campaign->ends_at)
                {{ optional($campaign->starts_at)->format('d/m/Y') ?? '∞' }}
                &rarr;
                {{ optional($campaign->ends_at)->format('d/m/Y') ?? '∞' }}
              @else
                —
              @endif
            </td>
            <td>{{ $campaign->active ? 'Ativa' : 'Inativa' }}</td>
            <td>
              <a class="btn btn-secondary" href="{{ route('admin.partner-campaigns.edit', $campaign) }}">Editar</a>
              <form action="{{ route('admin.partner-campaigns.destroy', $campaign) }}" method="POST" class="inline" onsubmit="return confirm('Remover esta campanha?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
              </form>
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

    <div style="margin-top:18px;">
      {{ $campaigns->links() }}
    </div>
  </div>
@endsection
