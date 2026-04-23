@extends('admin.layout')

@section('title', 'Fila ERP')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Fila de sincronização ERP</h2>
          <p style="margin:8px 0 0; color:#6b7280; max-width:860px;">
            Acompanhe cadastros ainda sem vínculo Vendus, jobs aguardando execução e falhas que precisam de intervenção.
          </p>
        </div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:18px;">
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Sem Vendus ID</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['missing_users'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Na fila</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['queued_jobs'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Falhados</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['failed_jobs'] }}</div>
      </div>
    </div>

    <div class="card">
      <h3 style="margin:0 0 16px; font-size:1.2rem;">Cadastros sem sincronização Vendus</h3>
      <table>
        <thead>
          <tr>
            <th>Usuário</th>
            <th>Contacto</th>
            <th>NIF</th>
            <th>Criado em</th>
            <th style="width:160px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($missingUsers as $user)
            <tr>
              <td>
                <strong>{{ $user->name }}</strong><br>
                <span style="color:#6b7280;">#{{ $user->id }}</span>
              </td>
              <td>
                <div>{{ $user->email }}</div>
                <div style="color:#6b7280;">{{ $user->phone ?: '—' }}</div>
              </td>
              <td>{{ $user->nif ?: '—' }}</td>
              <td>{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
              <td>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                  <a class="btn btn-secondary" href="{{ route('admin.users.show', $user) }}">Abrir</a>
                  <form method="POST" action="{{ route('admin.queue.users.sync', $user) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Sincronizar</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="text-align:center; padding:32px 0; color:#6b7280;">
                Nenhum usuário pendente de sincronização.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
      <div style="margin-top:18px;">
        {{ $missingUsers->links() }}
      </div>
    </div>

    <div class="card">
      <h3 style="margin:0 0 16px; font-size:1.2rem;">Jobs aguardando execução</h3>
      <table>
        <thead>
          <tr>
            <th>Job</th>
            <th>Usuário</th>
            <th>Fila</th>
            <th>Tentativas</th>
            <th>Disponível em</th>
            <th>Reservado em</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($queuedJobs as $job)
            <tr>
              <td>{{ class_basename($job->display_name) }}</td>
              <td>
                @if ($job->user_id)
                  <a href="{{ route('admin.users.show', $job->user_id) }}">#{{ $job->user_id }}</a>
                @else
                  —
                @endif
              </td>
              <td>{{ $job->queue }}</td>
              <td>{{ $job->attempts }}</td>
              <td>{{ $job->available_at_human }}</td>
              <td>{{ $job->reserved_at_human ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="text-align:center; padding:32px 0; color:#6b7280;">
                Nenhum job de cliente aguardando execução.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
      <div style="margin-top:18px;">
        {{ $queuedJobs->links() }}
      </div>
    </div>

    <div class="card">
      <h3 style="margin:0 0 16px; font-size:1.2rem;">Falhas de sincronização</h3>
      <table>
        <thead>
          <tr>
            <th>Job</th>
            <th>Usuário</th>
            <th>Fila</th>
            <th>Falhou em</th>
            <th>Erro</th>
            <th style="width:190px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($failedJobs as $job)
            <tr>
              <td>{{ class_basename($job->display_name) }}</td>
              <td>
                @if ($job->user_id)
                  <a href="{{ route('admin.users.show', $job->user_id) }}">#{{ $job->user_id }}</a>
                @else
                  —
                @endif
              </td>
              <td>{{ $job->queue }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($job->failed_at)->format('d/m/Y H:i') }}</td>
              <td style="max-width:520px;">
                <code style="white-space:normal; color:#991b1b;">{{ $job->exception_summary }}</code>
              </td>
              <td>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                  @if ($job->user_id)
                    <form method="POST" action="{{ route('admin.queue.failed.retry', $job->id) }}">
                      @csrf
                      <button class="btn btn-primary" type="submit">Reenfileirar</button>
                    </form>
                  @endif
                  <form method="POST" action="{{ route('admin.queue.failed.destroy', $job->id) }}" onsubmit="return confirm('Remover esta falha?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">Remover</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="text-align:center; padding:32px 0; color:#6b7280;">
                Nenhuma falha de sincronização de cliente.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
      <div style="margin-top:18px;">
        {{ $failedJobs->links() }}
      </div>
    </div>
  </div>
@endsection
