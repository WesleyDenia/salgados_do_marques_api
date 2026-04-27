@extends('admin.layout')

@section('title', 'Fila ERP')

@php
  $activeTab = request('tab', 'clientes');
  $tabs = [
    'clientes' => ['label' => 'Clientes', 'count' => $stats['missing_users']],
    'cupons' => ['label' => 'Cupons Vendus', 'count' => $stats['coupon_imports_pending'] + $stats['coupon_imports_failed']],
    'whatsapp' => ['label' => 'WhatsApp', 'count' => $stats['whatsapp_open']],
    'jobs' => ['label' => 'Tasks ativas', 'count' => $stats['queued_tasks']],
    'falhas' => ['label' => 'Tasks com falha', 'count' => $stats['failed_tasks']],
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
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['failed_tasks'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">WhatsApp em aberto</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['whatsapp_open'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">WhatsApp com erro</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['whatsapp_failed'] }}</div>
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
        <div class="responsive-table-wrap">
        <table class="responsive-table">
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
                  <span class="stack-table-label">Cliente</span>
                  <strong>{{ $user->name }}</strong><br>
                  <span style="color:#6b7280;">#{{ $user->id }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Contacto</span>
                  <div>{{ $user->email }}</div>
                  <div style="color:#6b7280;">{{ $user->phone ?: '—' }}</div>
                </td>
                <td>
                  <span class="stack-table-label">NIF</span>
                  {{ $user->nif ?: '—' }}
                </td>
                <td>
                  <span class="stack-table-label">Status ERP</span>
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
                <td>
                  <span class="stack-table-label">Última tentativa</span>
                  {{ $user->erp_sync_attempted_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td style="max-width:420px;">
                  <span class="stack-table-label">Erro</span>
                  @if ($user->erp_sync_error)
                    <code style="white-space:normal; word-break:break-word; color:#991b1b;">{{ $user->erp_sync_error }}</code>
                  @else
                    —
                  @endif
                </td>
                <td>
                  <span class="stack-table-label">Criado em</span>
                  {{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td>
                  <span class="stack-table-label">Ações</span>
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
        </div>
        <div style="margin-top:18px;">
          {{ $missingUsers->appends(['tab' => 'clientes'])->links() }}
        </div>
      @elseif ($activeTab === 'cupons')
        <h3 style="margin:0 0 16px; font-size:1.2rem;">Cupons Vendus baixados</h3>
        <form method="GET" action="{{ route('admin.queue.index') }}" class="filter-grid" style="align-items:end;">
          <input type="hidden" name="tab" value="cupons">
          <div class="form-group">
            <label for="coupon_code">Código Vendus</label>
            <input
              id="coupon_code"
              type="text"
              name="coupon_code"
              value="{{ $couponFilters['code'] }}"
              placeholder="Ex.: 39-260422-49"
            >
          </div>
          <div class="form-group">
            <label for="coupon_status">Status</label>
            <select id="coupon_status" name="coupon_status">
              <option value="">Todos exceto baixa manual</option>
              @foreach ($couponStatusOptions as $status => $label)
                <option value="{{ $status }}" @selected($couponFilters['status'] === $status)>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="form-group" style="flex-direction:row; gap:10px;">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-secondary" href="{{ route('admin.queue.index', ['tab' => 'cupons']) }}">Limpar</a>
          </div>
        </form>
        <div class="responsive-table-wrap">
        <table class="responsive-table">
          <thead>
            <tr>
              <th>Vendus</th>
              <th>Status</th>
              <th>Uso</th>
              <th>User</th>
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
                  <span class="stack-table-label">Vendus</span>
                  <strong>{{ $import->external_code ?: 'Sem código' }}</strong><br>
                  <span style="color:#6b7280;">ID {{ $import->external_id ?: '—' }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Status</span>
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
                  <span class="stack-table-label">Uso</span>
                  <div>{{ $import->vendus_status ?: '—' }}</div>
                  <div style="color:#6b7280;">{{ $import->date_used?->format('d/m/Y H:i') ?? '—' }}</div>
                </td>
                <td>
                  <span class="stack-table-label">User</span>
                  @php
                    $matchedUserCoupon = $import->userCoupon ?: $import->matchedUserCoupon;
                    $matchedUser = $matchedUserCoupon?->user;
                  @endphp
                  @if ($matchedUser)
                    <a href="{{ route('admin.users.show', $matchedUser) }}">{{ $matchedUser->name }}</a><br>
                    <span style="color:#6b7280;">#{{ $matchedUser->id }} · {{ $matchedUser->email }}</span>
                  @else
                    —
                  @endif
                </td>
                <td>
                  <span class="stack-table-label">Cupom local</span>
                  @if ($matchedUserCoupon)
                    #{{ $matchedUserCoupon->id }}
                  @else
                    —
                  @endif
                </td>
                <td style="max-width:420px;">
                  <span class="stack-table-label">Erro</span>
                  @if ($import->sync_error)
                    <code style="white-space:normal; word-break:break-word; color:#991b1b;">{{ $import->sync_error }}</code>
                  @elseif ($import->manual_note)
                    <span style="color:#6b7280;">{{ $import->manual_note }}</span>
                  @else
                    —
                  @endif
                </td>
                <td>
                  <span class="stack-table-label">Baixado em</span>
                  {{ $import->downloaded_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td>
                  <span class="stack-table-label">Ações</span>
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
                <td colspan="8" style="text-align:center; padding:32px 0; color:#6b7280;">
                  Nenhum cupom Vendus baixado.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
        </div>
        <div style="margin-top:18px;">
          {{ $couponImports->appends(array_merge(request()->except('coupons_page'), ['tab' => 'cupons']))->links() }}
        </div>
      @elseif ($activeTab === 'whatsapp')
        <h3 style="margin:0 0 16px; font-size:1.2rem;">Fila WhatsApp</h3>
        <form method="GET" action="{{ route('admin.queue.index') }}" class="filter-grid" style="align-items:end;">
          <input type="hidden" name="tab" value="whatsapp">
          <div class="form-group">
            <label for="whatsapp_type">Tipo</label>
            <select id="whatsapp_type" name="whatsapp_type">
              <option value="">Todos</option>
              @foreach ($whatsappTypeOptions as $type => $label)
                <option value="{{ $type }}" @selected($whatsappFilters['type'] === $type)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label for="whatsapp_status">Status</label>
            <select id="whatsapp_status" name="whatsapp_status">
              <option value="">Todos exceto baixa manual</option>
              @foreach ($whatsappStatusOptions as $status => $label)
                <option value="{{ $status }}" @selected($whatsappFilters['status'] === $status)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group" style="flex-direction:row; gap:10px;">
            <button class="btn btn-primary" type="submit">Filtrar</button>
            <a class="btn btn-secondary" href="{{ route('admin.queue.index', ['tab' => 'whatsapp']) }}">Limpar</a>
          </div>
        </form>
        <div class="responsive-table-wrap">
        <table class="responsive-table">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Destino</th>
              <th>Mensagem</th>
              <th>Status</th>
              <th>Erro</th>
              <th>Enfileirada em</th>
              <th style="width:230px;">Ações</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($whatsappItems as $item)
              <tr>
                <td>
                  <span class="stack-table-label">Tipo</span>
                  <strong>{{ $whatsappTypeOptions[$item->type] ?? $item->type }}</strong><br>
                  <span style="color:#6b7280;">#{{ $item->id }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Destino</span>
                  @if ($item->entity_type === 'order')
                    <a href="{{ route('admin.orders.show', $item->entity_id) }}">Pedido #{{ $item->entity_id }}</a>
                  @elseif ($item->entity_type === 'user')
                    <a href="{{ route('admin.users.show', $item->entity_id) }}">Cliente #{{ $item->entity_id }}</a>
                  @else
                    {{ $item->entity_type ?: '—' }} #{{ $item->entity_id ?: '—' }}
                  @endif
                  <div style="color:#6b7280;">{{ $item->recipient_name ?: '—' }}</div>
                  <div style="color:#6b7280;">{{ $item->phone }}</div>
                </td>
                <td style="max-width:420px;">
                  <span class="stack-table-label">Mensagem</span>
                  <code style="white-space:normal; word-break:break-word;">{{ \Illuminate\Support\Str::limit($item->message, 240) }}</code>
                </td>
                <td>
                  <span class="stack-table-label">Status</span>
                  @if ($item->status === 'sent')
                    <span class="badge badge-success">Enviado</span>
                  @elseif ($item->status === 'failed')
                    <span class="badge" style="background:rgba(239,68,68,0.15); color:#991b1b;">Erro</span>
                  @elseif ($item->status === 'manually_closed')
                    <span class="badge badge-muted">Baixa manual</span>
                  @elseif ($item->status === 'processing')
                    <span class="badge" style="background:rgba(245,158,11,0.15); color:#92400e;">Processando</span>
                  @else
                    <span class="badge badge-muted">Enfileirado</span>
                  @endif
                  <div style="margin-top:4px; color:#6b7280;">Tentativas: {{ (int) $item->attempts }}</div>
                </td>
                <td style="max-width:420px;">
                  <span class="stack-table-label">Erro</span>
                  @if ($item->last_error)
                    <code style="white-space:normal; word-break:break-word; color:#991b1b;">{{ $item->last_error }}</code>
                  @elseif ($item->manual_note)
                    <span style="color:#6b7280;">{{ $item->manual_note }}</span>
                  @else
                    —
                  @endif
                </td>
                <td>
                  <span class="stack-table-label">Enfileirada em</span>
                  {{ $item->queued_at?->format('d/m/Y H:i') ?? '—' }}
                  <div style="color:#6b7280;">Processada: {{ $item->started_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </td>
                <td>
                  <span class="stack-table-label">Ações</span>
                  <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    @if ($item->status === 'failed')
                      <form method="POST" action="{{ route('admin.queue.whatsapp.retry', $item) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Reenfileirar</button>
                      </form>
                    @endif
                    @if (!in_array($item->status, ['sent', 'manually_closed'], true))
                      <form method="POST" action="{{ route('admin.queue.whatsapp.close', $item) }}" onsubmit="return confirm('Dar baixa manual nesta mensagem WhatsApp?');">
                        @csrf
                        <input type="hidden" name="manual_note" value="Baixa manual pelo painel administrativo.">
                        <button class="btn btn-secondary" type="submit">Baixa manual</button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="text-align:center; padding:32px 0; color:#6b7280;">
                  Nenhuma mensagem WhatsApp na fila.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
        </div>
        <div style="margin-top:18px;">
          {{ $whatsappItems->appends(array_merge(request()->except('whatsapp_page'), ['tab' => 'whatsapp']))->links() }}
        </div>
      @elseif ($activeTab === 'jobs')
        <h3 style="margin:0 0 16px; font-size:1.2rem;">Tasks ERP ativas</h3>
        <div class="responsive-table-wrap">
        <table class="responsive-table">
          <thead>
            <tr>
              <th>Operação</th>
              <th>Entidade</th>
              <th>Status</th>
              <th>Tentativas</th>
              <th>Enfileirada em</th>
              <th>Iniciada em</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($queuedTasks as $task)
              <tr>
                <td>
                  <span class="stack-table-label">Operação</span>
                  <strong>{{ $task->operation }}</strong><br>
                  <span style="color:#6b7280;">#{{ $task->id }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Entidade</span>
                  @if ($task->entity_type === 'user')
                    <a href="{{ route('admin.users.show', $task->entity_id) }}">Usuário #{{ $task->entity_id }}</a>
                  @elseif ($task->entity_type === 'user_coupon')
                    Cupom privado #{{ $task->entity_id }}
                  @elseif ($task->entity_type === 'vendus_discount_card_import')
                    Cupom Vendus #{{ $task->entity_id }}
                  @else
                    {{ $task->entity_type }} #{{ $task->entity_id }}
                  @endif
                  <div style="color:#6b7280;">{{ $task->external_code ?: $task->external_id ?: '—' }}</div>
                </td>
                <td>
                  <span class="stack-table-label">Status</span>
                  <span class="badge badge-muted">{{ $task->status }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Tentativas</span>
                  {{ $task->attempts }}
                </td>
                <td>
                  <span class="stack-table-label">Enfileirada em</span>
                  {{ $task->queued_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td>
                  <span class="stack-table-label">Iniciada em</span>
                  {{ $task->started_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" style="text-align:center; padding:32px 0; color:#6b7280;">
                  Nenhuma task ERP ativa.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
        </div>
        <div style="margin-top:18px;">
          {{ $queuedTasks->appends(['tab' => 'jobs'])->links() }}
        </div>
      @elseif ($activeTab === 'falhas')
        <h3 style="margin:0 0 16px; font-size:1.2rem;">Falhas de sincronização ERP</h3>
        <div class="responsive-table-wrap">
        <table class="responsive-table">
          <thead>
            <tr>
              <th>Operação</th>
              <th>Entidade</th>
              <th>Status</th>
              <th>Falhou em</th>
              <th>Erro</th>
              <th style="width:190px;">Ações</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($failedTasks as $task)
              <tr>
                <td>
                  <span class="stack-table-label">Operação</span>
                  <strong>{{ $task->operation }}</strong><br>
                  <span style="color:#6b7280;">#{{ $task->id }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Entidade</span>
                  @if ($task->entity_type === 'user')
                    <a href="{{ route('admin.users.show', $task->entity_id) }}">Usuário #{{ $task->entity_id }}</a>
                  @elseif ($task->entity_type === 'user_coupon')
                    Cupom privado #{{ $task->entity_id }}
                  @elseif ($task->entity_type === 'vendus_discount_card_import')
                    Cupom Vendus #{{ $task->entity_id }}
                  @else
                    {{ $task->entity_type }} #{{ $task->entity_id }}
                  @endif
                  <div style="color:#6b7280;">{{ $task->external_code ?: $task->external_id ?: '—' }}</div>
                </td>
                <td>
                  <span class="stack-table-label">Status</span>
                  <span class="badge" style="background:rgba(239,68,68,0.15); color:#991b1b;">{{ $task->status }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Falhou em</span>
                  {{ $task->finished_at?->format('d/m/Y H:i') ?? $task->updated_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td style="max-width:520px;">
                  <span class="stack-table-label">Erro</span>
                  <code style="white-space:normal; word-break:break-word; color:#991b1b;">{{ $task->last_error ?: 'Erro sem detalhe.' }}</code>
                </td>
                <td>
                  <span class="stack-table-label">Ações</span>
                  <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    @if ($task->status !== 'manual_review')
                      <form method="POST" action="{{ route('admin.queue.tasks.retry', $task) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Reenfileirar</button>
                      </form>
                    @else
                      <span style="color:#6b7280;">Reabertura manual necessária</span>
                    @endif
                    @if ($task->operation === 'create_discount_card' && $task->entity_type === 'user_coupon' && $task->status !== 'cancelled')
                      <form method="POST" action="{{ route('admin.queue.tasks.status', $task) }}">
                        @csrf
                        <input type="hidden" name="target_status" value="manual_review">
                        <input type="hidden" name="manual_note" value="Encaminhado para revisão manual pelo painel administrativo.">
                        <button class="btn btn-secondary" type="submit">Revisão manual</button>
                      </form>
                      <form method="POST" action="{{ route('admin.queue.tasks.status', $task) }}" onsubmit="return confirm('Cancelar esta tarefa ERP e encerrar o cupom local?');">
                        @csrf
                        <input type="hidden" name="target_status" value="cancelled">
                        <input type="hidden" name="manual_note" value="Cancelado manualmente pelo painel administrativo.">
                        <button class="btn btn-secondary" type="submit">Cancelar</button>
                      </form>
                    @endif
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
        </div>
        <div style="margin-top:18px;">
          {{ $failedTasks->appends(['tab' => 'falhas'])->links() }}
        </div>
      @endif
    </div>
  </div>
@endsection
