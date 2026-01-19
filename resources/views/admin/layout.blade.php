<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Painel Administrativo')</title>
    <style>
      :root {
        color-scheme: light;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }

      body {
        margin: 0;
        min-height: 100vh;
        background: #f4f4f8;
        color: #1f2937;
      }

      header {
        background: #910202;
        color: #ffffff;
        padding: 18px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      header h1 {
        margin: 0;
        font-size: 1.3rem;
      }

      header nav a,
      header nav form button {
        color: #ffffff;
        text-decoration: none;
        font-weight: 600;
        margin-left: 16px;
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
      }

      header nav form {
        display: inline;
      }

      main {
        padding: 32px;
        max-width: 1100px;
        margin: 0 auto;
      }

      .card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.15);
        padding: 28px;
      }

      table {
        width: 100%;
        border-collapse: collapse;
      }

      table thead {
        background: #f9fafb;
      }

      table th,
      table td {
        padding: 12px 10px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
        font-size: 0.95rem;
      }

      .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 10px;
        padding: 10px 16px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        font-size: 0.95rem;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
      }

      .btn-primary {
        background: #910202;
        color: #fff;
      }

      .btn-secondary {
        background: #f3f4f6;
        color: #1f2933;
      }

      .btn-danger {
        background: #ef4444;
        color: #fff;
      }

      .btn:hover {
        transform: translateY(-1px);
      }

      .alert {
        padding: 14px 18px;
        border-radius: 12px;
        margin-bottom: 18px;
        font-size: 0.95rem;
        font-weight: 500;
      }

      .alert-success {
        background: rgba(34, 197, 94, 0.12);
        color: #166534;
        border: 1px solid rgba(34, 197, 94, 0.3);
      }

      .alert-error {
        background: rgba(239, 68, 68, 0.12);
        color: #b91c1c;
        border: 1px solid rgba(239, 68, 68, 0.3);
      }

      form.inline {
        display: inline;
      }

      .form-grid {
        display: grid;
        gap: 18px;
      }

      .filter-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        margin-bottom: 24px;
      }

      .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
      }

      label {
        font-weight: 600;
        font-size: 0.95rem;
      }

      input[type="text"],
      input[type="number"],
      input[type="url"],
      input[type="datetime-local"],
      textarea,
      select {
        padding: 12px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        font-size: 1rem;
      }

      textarea {
        min-height: 120px;
        resize: vertical;
      }

      .form-actions {
        margin-top: 24px;
        display: flex;
        gap: 12px;
      }

      .checkbox-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
      }

      .form-group.align-end {
        align-self: flex-end;
      }

      .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: 600;
      }

      .badge-success {
        background: rgba(22, 163, 74, 0.15);
        color: #166534;
      }

      .badge-muted {
        background: rgba(107, 114, 128, 0.15);
        color: #4b5563;
      }
    </style>
    @yield('styles')
  </head>
  <body>
    <header>
      <h1>Painel Administrativo</h1>
      <nav>
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.content-home.index') }}">ContentHome</a>
        <a href="{{ route('admin.stores.index') }}">Lojas</a>
        <a href="{{ route('admin.categories.index') }}">Categorias</a>
        <a href="{{ route('admin.products.index') }}">Produtos</a>
        <a href="{{ route('admin.flavors.index') }}">Sabores</a>
        <a href="{{ route('admin.coupons.index') }}">Cupons</a>
        <a href="{{ route('admin.loyalty-rewards.index') }}">Recompensas</a>
        <a href="{{ route('admin.settings.index') }}">Configurações</a>
        <form action="{{ route('admin.logout') }}" method="POST">
          @csrf
          <button type="submit">Sair</button>
        </form>
      </nav>
    </header>

    <main>
      @if (session('status'))
        <div class="alert alert-success">
          {{ session('status') }}
        </div>
      @endif
      @if (session('error'))
        <div class="alert alert-error">
          {{ session('error') }}
        </div>
      @endif

      @yield('content')
    </main>
  </body>
</html>
