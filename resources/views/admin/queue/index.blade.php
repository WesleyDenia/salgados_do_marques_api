@extends('admin.layout')

@section('title', 'Fila ERP')

@php
  $activeTab = request('tab', 'clientes');
  $tabs = [
    'clientes' => ['label' => 'Clientes', 'count' => $stats['missing_users']],
    'cupons' => ['label' => 'Cupons Vendus', 'count' => $stats['coupon_imports_pending'] + $stats['coupon_imports_failed']],
    'jobs' => ['label' => 'Jobs', 'count' => $stats['queued_jobs']],
    'falhas' => ['label' => 'Falhas', 'count' => $stats['failed_jobs']],
  ];
@endphp

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Fila de sincronização ERP</h2>
          <p style="margin:8px 0 0; color:#6b7280; max-width:860px;">
            Gestão centralizada de sincronizações Vendus, incluindo clientes, cupons, jobs pendentes e falhas.
          </p>
        </div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:18px;">
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Clientes pendentes</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['missing_users'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Clientes com erro</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['sync_errors'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Cupons pendentes</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['coupon_imports_pending'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Falhas de fila</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['failed_jobs'] }}</div>
      </div>
    </div>

    <div class="card">
      <div style="display:flex; gap:10px; flex-wrap:wrap; border-bottom:1px solid #e5e7eb; padding-bottom:16px; margin-bottom:20px;">
        @foreach ($tabs as $key => $tab)
          <a
            href="{{ route('admin.queue.index', ['tab' => $key]) }}"
            class="btn {{ $activeTab === $key ? 'btn-primary' : 'btn-secondary' }}"
          >
            {{ $tab['label'] }}
            <span style="opacity:0.75;">{{ $tab['count'] }}</span>
          </a>
        @endforeach
      </div>

      @if ($activeTab === 'clientes')
        <h3 style="margin:0 0 16px; font-size:1.2rem;">Clientes pendentes de sincronização</h3>
        <table>
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Contacto</th>
              <th>NIF</th>
              <th>Status ERP</th>
              <th>Última tentativa</th>
              <th>Erro</th>
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
                <td>
                  @if ($user->erp_sync_status === 'synced')
                    <span class="badge badge-success">Sincronizado</span>
                  @elseif ($user->erp_sync_status === 'failed')
                    <span class="badge" style="background:rgba(239,68,68,0.15); color:#991b1b;">Erro</span>
                  @elseif ($user->erp_sync_status === 'syncing')
                    <span class="badge" style="background:rgba(245,158,11,0.15); color:#92400e;">Sincronizando</span>
                  @else
                    <span class="badge badge-muted">Pendente</span>
                  @endif
                  <div style="margin-top:4px; color:#6b7280;">Tentativas: {{ (int) $user->erp_sync_attempts }}</div>
                </td>
                <td>{{ $user->erp_sync_attempted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td style="max-width:420px;">
                  @if ($user->erp_sync_error)
                    <code style="white-space:normal; color:#991b1b;">{{ $user->erp_sync_error }}</code>
                  @else
                    —
                  @endif
                </td>
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
                <td colspan="8" style="text-align:center; padding:32px 0; color:#6b7280;">
                  Nenhum cliente pendente de sincronização.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
        <div style="margin-top:18px;">
          {{ $missingUsers->appends(['tab' => 'clientes'])->links() }}
        </div>
      @elseif ($activeTab === 'cupons')
        <h3 style="margin:0 0 16px; font-size:1.2rem;">Cupons Vendus baixados</h3>
        <table>
          <thead>
            <tr>
              <th>Vendus</th>
              <th>Status</th>
              <th>Uso</th>
              <th>Cupom local</th>
              <th>Erro</th>
              <th>Baixado em</th>
              <th style="width:230px;">Ações</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($couponImports as $import)
              <tr>
                <td>
                  <strong>{{ $import->external_code ?: 'Sem código' }}</strong><br>
                  <span style="color:#6b7280;">ID {{ $import->external_id ?: '—' }}</span>
                </td>
                <td>
                  @if ($import->sync_status === 'processed')
                    <span class="badge badge-success">Processado</span>
                  @elseif ($import->sync_status === 'failed')
                    <span class="badge" style="background:rgba(239,68,68,0.15); color:#991b1b;">Erro</span>
                  @elseif ($import->sync_status === 'manually_closed')
                    <span class="badge badge-muted">Baixa manual</span>
                  @elseif ($import->sync_status === 'processing')
                    <span class="badge" style="background:rgba(245,158,11,0.15); color:#92400e;">Processando</span>
                  @else
                    <span class="badge badge-muted">{{ $import->sync_status }}</span>
                  @endif
                  <div style="margin-top:4px; color:#6b7280;">Tentativas: {{ (int) $import->sync_attempts }}</div>
                </td>
                <td>
                  <div>{{ $import->vendus_status ?: '—' }}</div>
                  <div style="color:#6b7280;">{{ $import->date_used?->format('d/m/Y H:i') ?? '—' }}</div>
                </td>
                <td>
                  @if ($import->userCoupon)
                    #{{ $import->userCoupon->id }}
                    @if ($import->userCoupon->user)
                      <br><a href="{{ route('admin.users.show', $import->userCoupon->user) }}">Usuário #{{ $import->userCoupon->user->id }}</a>
                    @endif
                  @else
                    —
                  @endif
                </td>
                <td style="max-width:420px;">
                  @if ($import->sync_error)
                    <code style="white-space:normal; color:#991b1b;">{{ $import->sync_error }}</code>
                  @elseif ($import->manual_note)
                    <span style="color:#6b7280;">{{ $import->manual_note }}</span>
                  @else
                    —
                  @endif
                </td>
                <td>{{ $import->downloaded_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>
                  <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    @if (!in_array($import->sync_status, ['processed', 'manually_closed'], true))
                      <form method="POST" action="{{ route('admin.queue.coupon-imports.retry', $import) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Reenfileirar</button>
                      </form>
                      <form method="POST" action="{{ route('admin.queue.coupon-imports.close', $import) }}" onsubmit="return confirm('Dar baixa manual neste cupom Vendus?');">
                        @csrf
                        <input type="hidden" name="manual_note" value="Baixa manual pelo painel administrativo.">
                        <button class="btn btn-secondary" type="submit">Baixa manual</button>
                      </form>
                    @else
                      —
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="text-align:center; padding:32px 0; color:#6b7280;">
                  Nenhum cupom Vendus baixado.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
        <div style="margin-top:18px;">
          {{ $couponImports->appends(['tab' => 'cupons'])->links() }}
        </div>
      @elseif ($activeTab === 'jobs')
        <h3 style="margin:0 0 16px; font-size:1.2rem;">Jobs aguardando execução</h3>
        <table>
          <thead>
            <tr>
              <th>Job</th>
              <th>Registro</th>
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
                    <a href="{{ route('admin.users.show', $job->user_id) }}">Usuário #{{ $job->user_id }}</a>
                  @elseif ($job->import_id)
                    Cupom Vendus #{{ $job->import_id }}
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
                  Nenhum job aguardando execução.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
        <div style="margin-top:18px;">
          {{ $queuedJobs->appends(['tab' => 'jobs'])->links() }}
        </div>
      @elseif ($activeTab === 'falhas')
        <h3 style="margin:0 0 16px; font-size:1.2rem;">Falhas de sincronização</h3>
        <table>
          <thead>
            <tr>
              <th>Job</th>
              <th>Registro</th>
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
                    <a href="{{ route('admin.users.show', $job->user_id) }}">Usuário #{{ $job->user_id }}</a>
                  @elseif ($job->import_id)
                    Cupom Vendus #{{ $job->import_id }}
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
                    @if ($job->user_id || $job->import_id)
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
                  Nenhuma falha de sincronização.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
        <div style="margin-top:18px;">
          {{ $failedJobs->appends(['tab' => 'falhas'])->links() }}
        </div>
      @endif
    </div>
  </div>
@endsection
