@extends('admin.layout')

@section('title', 'WhatsApp')

@section('content')
  @php
    $isReady = (bool) ($session['whatsappReady'] ?? false);
    $snapshot = $session['session'] ?? [];
    $status = (string) ($snapshot['status'] ?? ($isReady ? 'ready' : 'offline'));
    $qrDataUrl = (string) ($snapshot['qrDataUrl'] ?? '');
  @endphp

  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-start;">
        <div>
          <h2 style="margin:0;font-size:1.4rem;">WhatsApp</h2>
          <p style="margin:6px 0 0; color:#6b7280; font-size:0.95rem; max-width:760px;">
            Estado da sessão do serviço Node e QR code para autenticação sem acesso direto ao terminal do servidor.
          </p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
          <form method="POST" action="{{ route('admin.whatsapp.health-check') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Health Check</button>
          </form>
          <a class="btn btn-secondary" href="{{ route('admin.whatsapp.index') }}">Atualizar</a>
        </div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px;">
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Estado</div>
        <div style="font-size:1.5rem; font-weight:700; margin-top:8px;">
          @if ($isReady)
            <span class="badge badge-success">Autenticado</span>
          @elseif ($status === 'qr')
            <span class="badge" style="background:rgba(245,158,11,0.15); color:#92400e;">Aguardando scan</span>
          @elseif ($status === 'auth_failure')
            <span class="badge" style="background:rgba(239,68,68,0.15); color:#991b1b;">Falha de autenticação</span>
          @elseif ($status === 'disconnected')
            <span class="badge badge-muted">Desligado</span>
          @else
            <span class="badge badge-muted">{{ $status }}</span>
          @endif
        </div>
      </div>

      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">QR disponível</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $qrDataUrl !== '' ? 'Sim' : 'Não' }}</div>
      </div>

      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Sessão inicializada</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ !empty($snapshot['initialized']) ? 'Sim' : 'Não' }}</div>
      </div>

      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Último QR</div>
        <div style="font-size:1.2rem; font-weight:700; margin-top:8px;">
          {{ $snapshot['qrGeneratedAt'] ? \Carbon\Carbon::parse($snapshot['qrGeneratedAt'])->format('d/m/Y H:i') : '—' }}
        </div>
      </div>
    </div>

    <div class="card" style="display:grid; gap:18px;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h3 style="margin:0;font-size:1.2rem;">Autenticação</h3>
          <p style="margin:6px 0 0; color:#6b7280; max-width:720px;">
            Se o serviço estiver sem sessão ativa, escaneie este QR com o WhatsApp principal. A imagem vem do container Node e é proxada pelo backend.
          </p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.queue.index', ['tab' => 'whatsapp-enviados']) }}">Ver fila WhatsApp</a>
      </div>

      @if ($qrDataUrl !== '' && !$isReady)
        <div style="display:grid; gap:16px; grid-template-columns:minmax(240px, 320px) minmax(0, 1fr); align-items:center;">
          <div style="padding:20px; background:#fff; border:1px solid #e5e7eb; border-radius:18px; display:grid; place-items:center;">
            <img
              src="{{ $qrDataUrl }}"
              alt="QR code de autenticação do WhatsApp"
              style="width:100%; max-width:280px; height:auto; image-rendering:auto;"
            >
          </div>
          <div>
            <div style="font-weight:700; margin-bottom:10px;">Como usar</div>
            <ol style="margin:0; padding-left:1.2rem; color:#374151; line-height:1.6;">
              <li>Abra o WhatsApp no telemóvel principal.</li>
              <li>Entre em Dispositivos ligados.</li>
              <li>Escaneie o código apresentado nesta página.</li>
            </ol>
            <p style="margin:12px 0 0; color:#6b7280;">
              Quando a sessão for validada, o código desaparece e o estado passa para autenticado.
            </p>
          </div>
        </div>
      @elseif ($isReady)
        <div style="padding:18px; border-radius:14px; background:rgba(34,197,94,0.08); color:#166534; border:1px solid rgba(34,197,94,0.18);">
          A sessão do WhatsApp está autenticada e pronta para envio de mensagens.
        </div>
      @else
        <div style="padding:18px; border-radius:14px; background:rgba(245,158,11,0.08); color:#92400e; border:1px solid rgba(245,158,11,0.18);">
          O QR ainda não está disponível. Se o serviço acabou de subir, aguarde alguns segundos e atualize a página.
        </div>
      @endif

      @if (!empty($session['error']))
        <div style="padding:18px; border-radius:14px; background:rgba(239,68,68,0.08); color:#991b1b; border:1px solid rgba(239,68,68,0.18);">
          {{ $session['error'] }}
        </div>
      @endif
    </div>
  </div>
@endsection
