@extends('admin.layout')

@section('title', 'Recompensas de Fidelidade')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Recompensas</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Cadastre e gerencie as recompensas disponíveis no programa de fidelidade.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.loyalty-rewards.create') }}">Nova recompensa</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>Nome</th>
          <th>Pontos necessários</th>
          <th>Valor (€)</th>
          <th>Status</th>
          <th style="width:170px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rewards as $reward)
          <tr>
            <td>{{ $reward->name }}</td>
            <td>{{ $reward->threshold }}</td>
            <td>{{ number_format((float) $reward->value, 2, ',', '.') }}</td>
            <td>
              @if ($reward->active)
                <span class="badge badge-success">Ativo</span>
              @else
                <span class="badge badge-muted">Inativo</span>
              @endif
            </td>
            <td>
              <a class="btn btn-secondary" href="{{ route('admin.loyalty-rewards.edit', $reward) }}">Editar</a>
              <form
                action="{{ route('admin.loyalty-rewards.destroy', $reward) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm('Remover esta recompensa?');"
              >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Excluir</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center; padding:32px 0; color:#6b7280;">
              Nenhuma recompensa cadastrada.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:18px;">
      {{ $rewards->links() }}
    </div>
  </div>
@endsection
