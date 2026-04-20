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

      *,
      *::before,
      *::after {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        min-height: 100vh;
        background: #f4f4f8;
        color: #1f2937;
      }

      .admin-shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 280px minmax(0, 1fr);
      }

      .admin-sidebar {
        background: #910202;
        color: #ffffff;
        padding: 28px 20px;
        display: flex;
        flex-direction: column;
        gap: 24px;
        position: sticky;
        top: 0;
        min-height: 100vh;
      }

      .admin-brand h1 {
        margin: 0;
        font-size: 1.35rem;
      }

      .admin-brand p {
        margin: 8px 0 0;
        color: rgba(255, 232, 232, 0.82);
        font-size: 0.95rem;
        line-height: 1.5;
      }

      .admin-nav {
        display: grid;
        gap: 18px;
      }

      .nav-section {
        display: grid;
        gap: 8px;
      }

      .nav-section-label {
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255, 220, 220, 0.68);
        padding: 0 10px;
      }

      .nav-link,
      .nav-link-button {
        display: flex;
        align-items: center;
        width: 100%;
        min-height: 42px;
        padding: 10px 12px;
        border-radius: 12px;
        color: #fff7f7;
        text-decoration: none;
        font-weight: 600;
        background: transparent;
        border: 1px solid transparent;
        transition: background-color 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
      }

      .nav-link:hover,
      .nav-link-button:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.18);
        transform: translateX(2px);
      }

      .nav-link.active,
      .nav-link-button.active {
        background: rgba(255, 247, 247, 0.18);
        border-color: rgba(255, 255, 255, 0.22);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
      }

      .nav-link-button {
        appearance: none;
        text-align: left;
        cursor: pointer;
      }

      .sidebar-footer {
        margin-top: auto;
        padding-top: 8px;
        border-top: 1px solid rgba(255, 255, 255, 0.12);
      }

      .admin-content {
        min-width: 0;
      }

      main {
        padding: 32px 36px;
        max-width: 1320px;
        margin: 0 auto;
        width: 100%;
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

      .action-menu {
        position: relative;
        display: inline-block;
      }

      .action-menu summary {
        list-style: none;
      }

      .action-menu summary::-webkit-details-marker {
        display: none;
      }

      .action-menu-trigger {
        width: 40px;
        height: 40px;
        padding: 0;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        background: #ffffff;
        color: #374151;
        box-shadow: 0 10px 25px -20px rgba(15, 23, 42, 0.5);
      }

      .action-menu-trigger:hover,
      .action-menu[open] .action-menu-trigger {
        background: #f9fafb;
        border-color: #cbd5e1;
      }

      .action-menu-trigger:focus-visible {
        outline: 2px solid #910202;
        outline-offset: 2px;
      }

      .action-menu-panel {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        min-width: 168px;
        padding: 8px;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        box-shadow: 0 20px 45px -18px rgba(15, 23, 42, 0.28);
        z-index: 20;
      }

      .action-menu-item {
        width: 100%;
        justify-content: flex-start;
        background: transparent;
        color: #1f2937;
        border-radius: 10px;
        padding: 10px 12px;
      }

      .action-menu-item:hover {
        background: #f3f4f6;
      }

      .action-menu-item-danger {
        color: #b91c1c;
      }

      .action-menu-item-danger:hover {
        background: rgba(239, 68, 68, 0.12);
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

      .form-grid-product {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
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
      input[type="file"],
      input[type="url"],
      input[type="datetime-local"],
      textarea,
      select {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        font-size: 1rem;
        background: #ffffff;
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

      .form-section {
        display: grid;
        gap: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fcfcfe;
      }

      .form-section-title {
        margin: 0;
        font-size: 1rem;
      }

      .form-section-description {
        margin: -8px 0 0;
        color: #6b7280;
        font-size: 0.95rem;
        line-height: 1.5;
      }

      .form-span-full {
        grid-column: 1 / -1;
      }

      .variant-table-wrap {
        overflow: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
      }

      .variant-table {
        width: 100%;
        min-width: 720px;
        border-collapse: collapse;
      }

      .stack-table-label {
        display: none;
        font-size: 0.78rem;
        font-weight: 700;
        color: #6b7280;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
      }

      .inline-note {
        margin: 6px 0 12px;
        color: #6b7280;
        font-size: 0.95rem;
      }

      @media (max-width: 860px) {
        .admin-shell {
          grid-template-columns: 1fr;
        }

        .admin-sidebar {
          position: static;
          min-height: auto;
          padding: 20px 16px;
        }

        main {
          padding: 18px 14px;
        }

        .form-grid-product {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 1100px) {
        .variant-table,
        .variant-table thead,
        .variant-table tbody,
        .variant-table tr,
        .variant-table th,
        .variant-table td {
          display: block;
          width: 100%;
        }

        .variant-table {
          min-width: 0;
        }

        .variant-table thead {
          display: none;
        }

        .variant-table tbody {
          padding: 12px;
        }

        .variant-table tr {
          border: 1px solid #e5e7eb;
          border-radius: 14px;
          padding: 14px;
          background: #ffffff;
        }

        .variant-table tr + tr {
          margin-top: 12px;
        }

        .variant-table td {
          padding: 0;
          border: none;
        }

        .variant-table td + td {
          margin-top: 12px;
        }

        .stack-table-label {
          display: block;
        }
      }
    </style>
    @yield('styles')
  </head>
  <body>
    <div class="admin-shell">
      <aside class="admin-sidebar">
        <div class="admin-brand">
          <h1>Painel Administrativo</h1>
          <p>Operação direta para gerir catálogo, pedidos e conteúdos sem desperdício de espaço.</p>
        </div>

        <nav class="admin-nav" aria-label="Navegação principal do painel">
          <div class="nav-section">
            <span class="nav-section-label">Principal</span>
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">Encomendas</a>
            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Usuários</a>
            <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">Produtos</a>
            <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">Categorias</a>
            <a class="nav-link {{ request()->routeIs('admin.flavors.*') ? 'active' : '' }}" href="{{ route('admin.flavors.index') }}">Sabores</a>
            <a class="nav-link {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}" href="{{ route('admin.stores.index') }}">Lojas</a>
            <a class="nav-link {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}" href="{{ route('admin.partners.index') }}">Parceiros</a>
            <a class="nav-link {{ request()->routeIs('admin.partner-campaigns.*') ? 'active' : '' }}" href="{{ route('admin.partner-campaigns.index') }}">Campanhas de Parceiros</a>
            <a class="nav-link {{ request()->routeIs('admin.app-testers.*') ? 'active' : '' }}" href="{{ route('admin.app-testers.index') }}">Testers do App</a>
          </div>

          <div class="nav-section">
            <span class="nav-section-label">Administração</span>
            <a class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">Cupons</a>
            <a class="nav-link {{ request()->routeIs('admin.loyalty-rewards.*') ? 'active' : '' }}" href="{{ route('admin.loyalty-rewards.index') }}">Recompensas</a>
            <a class="nav-link {{ request()->routeIs('admin.content-home.*') ? 'active' : '' }}" href="{{ route('admin.content-home.index') }}">Content Home</a>
            <a class="nav-link {{ request()->routeIs('admin.home-components.*') ? 'active' : '' }}" href="{{ route('admin.home-components.index') }}">Componentes da Home</a>
            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">Configurações</a>
          </div>
        </nav>

        <div class="sidebar-footer">
          <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="nav-link-button">Sair</button>
          </form>
        </div>
      </aside>

      <div class="admin-content">
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
      </div>
    </div>
  </body>
</html>
