@extends('admin.layout')

@section('title', 'Dashboard - Painel')

@section('styles')
  <style>
    .dashboard-overview {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
    }

    .dashboard-metric {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 16px;
      background: #fff;
      min-width: 0;
    }

    .dashboard-metric-label {
      font-size: 0.9rem;
      color: #6b7280;
    }

    .dashboard-metric-value {
      font-size: clamp(1.6rem, 5vw, 2rem);
      font-weight: 700;
      margin-top: 8px;
      word-break: break-word;
      line-height: 1.1;
    }

    @media (max-width: 640px) {
      .dashboard-overview {
        grid-template-columns: 1fr;
      }

      .dashboard-metric {
        padding: 14px;
      }
    }
  </style>
@endsection

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Dashboard</h2>
    <p style="color:#6b7280; margin-bottom:22px;">
      Visão geral dos indicadores principais do aplicativo.
    </p>

    <div class="dashboard-overview">
      <article class="dashboard-metric">
        <div class="dashboard-metric-label">Quantidade de usuários</div>
        <div class="dashboard-metric-value">
          {{ number_format($metrics['users_count']) }}
        </div>
      </article>

      <article class="dashboard-metric">
        <div class="dashboard-metric-label">Total de coins geradas</div>
        <div class="dashboard-metric-value" style="color:#14532d;">
          {{ number_format($metrics['coins_generated_total']) }}
        </div>
      </article>

      <article class="dashboard-metric">
        <div class="dashboard-metric-label">Total de coins usadas</div>
        <div class="dashboard-metric-value" style="color:#7f1d1d;">
          {{ number_format($metrics['coins_used_total']) }}
        </div>
      </article>

      <article class="dashboard-metric">
        <div class="dashboard-metric-label">Coins por usar</div>
        <div class="dashboard-metric-value" style="color:#1d4ed8;">
          {{ number_format($metrics['coins_available_total']) }}
        </div>
      </article>
    </div>
  </div>
@endsection
