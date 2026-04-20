@extends('admin.layout')

@section('title', 'Testers do App')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">Captação de testers</h2>
          <p style="margin:8px 0 0; color:#6b7280; max-width:760px;">
            Lista de clientes inscritos na campanha VIP do novo app. Aqui pode acompanhar os contactos recebidos,
            perceber a procura por sistema operativo e priorizar os convites Android desta fase fechada.
          </p>
        </div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:18px;">
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Total captado</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['total'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Android</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['android'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">iPhone</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['ios'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Elegíveis nesta fase</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['eligible'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Registrados</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['registered'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Conta criada</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['account_created'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Testando</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $stats['testing'] }}</div>
      </div>
    </div>

    <div class="card">
      <form method="GET" class="filter-grid">
        <div class="form-group">
          <label for="search">Pesquisar</label>
          <input
            id="search"
            type="text"
            name="search"
            value="{{ $filters['search'] }}"
            placeholder="Nome, email ou telefone"
          >
        </div>

        <div class="form-group">
          <label for="operating_system">Sistema operativo</label>
          <select id="operating_system" name="operating_system">
            <option value="">Todos</option>
            <option value="android" @selected($filters['operating_system'] === 'android')>Android</option>
            <option value="ios" @selected($filters['operating_system'] === 'ios')>iPhone</option>
          </select>
        </div>

        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="">Todos</option>
            @foreach ($statuses as $status)
              <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group align-end">
          <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
      </form>

      <table>
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Contacto</th>
            <th>Sistema</th>
            <th>Status</th>
            <th>Fase atual</th>
            <th>Consentimento</th>
            <th>Origem</th>
            <th>Captado em</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($testers as $tester)
            <tr>
              <td>
                <strong>{{ $tester->name }}</strong><br>
                <span style="color:#6b7280;">#{{ $tester->id }}</span>
              </td>
              <td>
                <div>{{ $tester->email }}</div>
                <div style="color:#6b7280;">{{ $tester->phone }}</div>
              </td>
              <td>{{ $tester->operating_system === 'android' ? 'Android' : 'iPhone' }}</td>
              <td>
                @if ($tester->status === \App\Models\AppTester::STATUS_TESTING)
                  <span class="badge badge-success">Testando</span>
                @elseif ($tester->status === \App\Models\AppTester::STATUS_ACCOUNT_CREATED)
                  <span class="badge badge-success" style="background:rgba(59,130,246,0.15); color:#1d4ed8;">Conta criada</span>
                @else
                  <span class="badge badge-muted">Registrado</span>
                @endif
              </td>
              <td>
                @if ($tester->is_android_eligible)
                  <span class="badge badge-success">Elegível</span>
                @else
                  <span class="badge badge-muted">Lista futura</span>
                @endif
              </td>
              <td>{{ optional($tester->consent_at)->format('d/m/Y H:i') }}</td>
              <td>{{ $tester->source_path ?: '/testers' }}</td>
              <td>{{ $tester->created_at->format('d/m/Y H:i') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="text-align:center; padding:32px 0; color:#6b7280;">
                Ainda não há testers captados.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div style="margin-top:18px;">
        {{ $testers->links() }}
      </div>
    </div>
  </div>
@endsection
