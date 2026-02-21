@extends('admin.layout')

@section('title', 'Dashboard - Painel')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Dashboard</h2>
    <p style="color:#6b7280; margin-bottom:22px;">
      Visão geral dos indicadores principais do aplicativo.
    </p>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
      <article style="border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#fff;">
        <div style="font-size:0.9rem; color:#6b7280;">Quantidade de usuários</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">
          {{ number_format($metrics['users_count']) }}
        </div>
      </article>

      <article style="border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#fff;">
        <div style="font-size:0.9rem; color:#6b7280;">Total de coins geradas</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px; color:#14532d;">
          {{ number_format($metrics['coins_generated_total']) }}
        </div>
      </article>

      <article style="border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#fff;">
        <div style="font-size:0.9rem; color:#6b7280;">Total de coins usadas</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px; color:#7f1d1d;">
          {{ number_format($metrics['coins_used_total']) }}
        </div>
      </article>

      <article style="border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#fff;">
        <div style="font-size:0.9rem; color:#6b7280;">Coins por usar</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px; color:#1d4ed8;">
          {{ number_format($metrics['coins_available_total']) }}
        </div>
      </article>
    </div>
  </div>
@endsection
