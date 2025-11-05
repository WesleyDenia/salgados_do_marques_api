@extends('admin.layout')

@section('title', 'Configurações Gerais')

@section('content')
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
      <div>
        <h2 style="margin:0;font-size:1.4rem;">Configurações</h2>
        <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem;">
          Ajuste parâmetros globais do sistema. Valores marcados como não editáveis são apenas para consulta.
        </p>
      </div>
      <a class="btn btn-primary" href="{{ route('admin.settings.create') }}">Nova configuração</a>
    </div>
  
    <table>
      <thead>
        <tr>
          <th>Chave</th>
          <th>Valor</th>
          <th>Tipo</th>
          <th>Editável</th>
          <th style="width:140px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($settings as $setting)
          <tr>
            <td>{{ $setting->key }}</td>
            <td>
              @if ($setting->type === 'boolean')
                {{ $setting->value ? 'true' : 'false' }}
              @elseif ($setting->type === 'json')
                <code style="font-size:0.85rem;">{{ json_encode($setting->value) }}</code>
              @else
                {{ $setting->value }}
              @endif
            </td>
            <td>{{ $setting->type }}</td>
            <td>
              @if ($setting->editable)
                <span class="badge badge-success">Sim</span>
              @else
                <span class="badge badge-muted">Não</span>
              @endif
            </td>
            <td>
              @if ($setting->editable)
                <a class="btn btn-secondary" href="{{ route('admin.settings.edit', $setting) }}">Editar</a>
              @else
                <span style="color:#9ca3af; font-size:0.9rem;">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align:center; padding:32px 0; color:#6b7280;">
              Nenhuma configuração encontrada.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:18px;">
      {{ $settings->links() }}
    </div>
  </div>
@endsection
