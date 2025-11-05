<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Painel Administrativo - Login</title>
    <style>
      :root {
        color-scheme: light dark;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }

      body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f7f7f8;
        color: #1e1e20;
      }

      .card {
        width: min(400px, 90vw);
        background: #ffffff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
      }

      h1 {
        font-size: 1.6rem;
        margin-bottom: 16px;
        text-align: center;
      }

      p.subtitle {
        margin: 0 0 24px;
        text-align: center;
        color: #6b7280;
        font-size: 0.95rem;
      }

      label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
      }

      input[type="email"],
      input[type="password"] {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        font-size: 1rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
      }

      input[type="email"]:focus,
      input[type="password"]:focus {
        outline: none;
        border-color: #910202;
        box-shadow: 0 0 0 3px rgba(145, 2, 2, 0.18);
      }

      .form-group {
        margin-bottom: 18px;
      }

      .remember-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 18px;
        font-size: 0.95rem;
        color: #4b5563;
      }

      .error {
        background: rgba(220, 38, 38, 0.08);
        color: #b91c1c;
        border: 1px solid rgba(220, 38, 38, 0.35);
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 16px;
        font-size: 0.95rem;
      }

      button {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 10px;
        background: #910202;
        color: #ffffff;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.15s ease;
      }

      button:hover {
        background: #7a0202;
        transform: translateY(-1px);
      }

      button:active {
        transform: translateY(0);
      }

      footer {
        margin-top: 20px;
        font-size: 0.85rem;
        text-align: center;
        color: #9ca3af;
      }
    </style>
  </head>
  <body>
    <main class="card">
      <h1>Painel Administrativo</h1>
      <p class="subtitle">Entre com suas credenciais para continuar.</p>

      @if ($errors->any())
        <div class="error">
          <strong>Ops!</strong> Verifique os dados e tente novamente.
        </div>
      @endif

      <form method="POST" action="{{ route('admin.login.post') }}">
        @csrf
        <div class="form-group">
          <label for="email">E-mail</label>
          <input
            type="email"
            id="email"
            name="email"
            value="{{ old('email') }}"
            autocomplete="email"
            required
            autofocus
          />
        </div>

        <div class="form-group">
          <label for="password">Senha</label>
          <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
          />
        </div>

        <label class="remember-row">
          <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} />
          Lembrar de mim
        </label>

        <button type="submit">Entrar</button>
      </form>

      <footer>&copy; {{ now()->year }} Salgados do Marquês</footer>
    </main>
  </body>
</html>
