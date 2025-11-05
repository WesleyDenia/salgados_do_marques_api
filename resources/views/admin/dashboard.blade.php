@extends('admin.layout')

@section('title', 'Dashboard - Painel')

@section('content')
  <div class="card">
    <h2 style="margin-top:0;">Olá, {{ auth()->user()->name ?? 'Administrador' }} 👋</h2>
    <p style="color:#6b7280; margin-bottom:28px;">
      Seja bem-vindo ao painel administrativo. Escolha um módulo abaixo para começar.
    </p>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px;">
      <a href="{{ route('admin.content-home.index') }}" class="btn btn-secondary" style="justify-content:flex-start;">
        <span>Gerenciar ContentHome</span>
      </a>
      <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary" style="justify-content:flex-start;">
        <span>Gerenciar Cupons</span>
      </a>
      <a href="{{ route('admin.loyalty-rewards.index') }}" class="btn btn-secondary" style="justify-content:flex-start;">
        <span>Gerenciar Recompensas</span>
      </a>
      <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary" style="justify-content:flex-start;">
        <span>Configurações</span>
      </a>
    </div>
  </div>
@endsection
