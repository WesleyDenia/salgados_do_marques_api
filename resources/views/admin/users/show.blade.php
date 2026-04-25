@extends('admin.layout')

@section('title', 'Detalhes do Usuário')

@section('styles')
  <style>
    .detail-page {
      display: grid;
      gap: 24px;
    }

    .detail-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      flex-wrap: wrap;
    }

    .detail-header-copy {
      min-width: 0;
    }

    .detail-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .detail-stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 18px;
    }

    .detail-section-grid {
      display: grid;
      grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
      gap: 24px;
      align-items: start;
    }

    .detail-info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 12px;
    }

    .detail-meta-card {
      padding: 12px;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      background: #fcfcfe;
      min-width: 0;
      overflow-wrap: anywhere;
    }

    .detail-meta-label {
      color: #6b7280;
      font-size: 0.85rem;
      overflow-wrap: anywhere;
    }

    .detail-meta-value {
      margin-top: 4px;
      font-weight: 600;
      overflow-wrap: anywhere;
    }

    .detail-section-title {
      margin: 0;
      font-size: 1.15rem;
    }

    .detail-section-note {
      margin: 6px 0 0;
      color: #6b7280;
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .detail-form {
      margin-top: 18px;
    }

    .detail-table-wrap {
      margin-top: 14px;
      overflow: hidden;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      background: #ffffff;
    }

    .detail-pagination {
      margin-top: 18px;
    }

    @media (max-width: 860px) {
      .detail-section-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 640px) {
      .detail-header,
      .detail-actions {
        width: 100%;
      }

      .detail-header {
        flex-direction: column;
      }

      .detail-actions .btn {
        width: 100%;
      }

      .detail-stats-grid,
      .detail-info-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
@endsection

@section('content')
  <div class="detail-page">
    <div class="card">
      <div class="detail-header">
        <div class="detail-header-copy">
          <h2 style="margin:0; font-size:1.5rem;">{{ $user->name }}</h2>
          <p style="margin:8px 0 0; color:#6b7280;">
            Utilizador #{{ $user->id }} criado em {{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}
          </p>
        </div>
        <div class="detail-actions">
          <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Editar dados</a>
          <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Voltar para a lista</a>
        </div>
      </div>
    </div>

    <div class="detail-stats-grid">
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Coinxinhas</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ number_format($loyaltyPoints, 0, ',', '.') }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Cupons totais</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $couponStats['total'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Disponíveis</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $couponStats['available'] }}</div>
      </div>
      <div class="card">
        <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:0.08em; color:#9ca3af;">Usados</div>
        <div style="font-size:2rem; font-weight:700; margin-top:8px;">{{ $couponStats['used'] }}</div>
      </div>
    </div>

    <div class="detail-section-grid">
      <div class="card">
        <h3 class="detail-section-title">Dados do usuário</h3>
        <p class="detail-section-note">
          Os dados permanecem agrupados por contexto para facilitar leitura rápida em ecrãs menores.
        </p>

        <div class="detail-info-grid" style="margin-top:18px;">
          <div class="detail-meta-card">
            <div class="detail-meta-label">Email</div>
            <div class="detail-meta-value">{{ $user->email }}</div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">Telefone</div>
            <div class="detail-meta-value">{{ $user->phone ?: '—' }}</div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">NIF</div>
            <div class="detail-meta-value">{{ $user->nif ?: '—' }}</div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">Perfil</div>
            <div class="detail-meta-value">{{ ucfirst($user->role) }}</div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">Status</div>
            <div class="detail-meta-value">{{ $user->active ? 'Ativo' : 'Inativo' }}</div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">Último login</div>
            <div class="detail-meta-value">{{ $user->last_login?->format('d/m/Y H:i') ?? '—' }}</div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">Nascimento</div>
            <div class="detail-meta-value">{{ $user->birth_date?->format('d/m/Y') ?? '—' }}</div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">Morada</div>
            <div class="detail-meta-value">{{ $user->street ?: '—' }}</div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">Cidade / CP</div>
            <div class="detail-meta-value">
              {{ trim(($user->city ?: '') . ' ' . ($user->postal_code ?: '')) ?: '—' }}
            </div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">ID externo</div>
            <div class="detail-meta-value">{{ $user->external_id ?: '—' }}</div>
          </div>
          <div class="detail-meta-card">
            <div class="detail-meta-label">Consentimento LGPD</div>
            <div class="detail-meta-value">{{ $user->lgpd_consent_at?->format('d/m/Y H:i') ?? '—' }}</div>
          </div>
        </div>
      </div>

      <div style="display:grid; gap:24px;">
        <div class="card">
          <h3 class="detail-section-title">Adicionar Coinxinhas</h3>
          <p class="detail-section-note">
            O formulário mantém as ações de crédito visíveis e fáceis de usar com o polegar.
          </p>

          <form method="POST" action="{{ route('admin.users.loyalty.store', $user) }}" class="form-grid">
            @csrf

            <div class="form-group">
              <label for="points">Pontos</label>
              <input id="points" type="number" min="1" name="points" value="{{ old('points') }}" required>
              @error('points')
                <span class="alert alert-error">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <label for="reason">Motivo</label>
              <textarea id="reason" name="reason" required>{{ old('reason') }}</textarea>
              @error('reason')
                <span class="alert alert-error">{{ $message }}</span>
              @enderror
            </div>

            <button type="submit" class="btn btn-primary">Creditar pontos</button>
          </form>
        </div>

        <div class="card">
          <h3 class="detail-section-title">Adicionar cupom</h3>
          <p class="detail-section-note">
            A atribuição manual continua disponível mesmo em ecrãs estreitos.
          </p>

          @if ($availableCoupons->isEmpty())
            <p style="margin:0; color:#6b7280;">
              Não há cupons ativos disponíveis para atribuição manual a este usuário.
            </p>
          @else
            <form method="POST" action="{{ route('admin.users.coupons.store', $user) }}" class="form-grid">
              @csrf

              <div class="form-group">
                <label for="coupon_id">Cupom</label>
                <select id="coupon_id" name="coupon_id" required>
                  <option value="">Selecione um cupom</option>
                  @foreach ($availableCoupons as $coupon)
                    <option value="{{ $coupon->id }}" @selected((string) old('coupon_id') === (string) $coupon->id)>
                      {{ $coupon->title }}{{ $coupon->code ? ' • ' . $coupon->code : '' }}
                    </option>
                  @endforeach
                </select>
                @error('coupon_id')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <button type="submit" class="btn btn-primary">Atribuir cupom</button>
            </form>
          @endif
        </div>
      </div>
    </div>

    <div class="card">
      <h3 class="detail-section-title">Cupons do usuário</h3>
      <p class="detail-section-note">
        A tabela usa labels empilhadas no mobile para evitar colunas comprimidas e perda de contexto.
      </p>

      <div class="responsive-table-wrap detail-table-wrap">
        <table class="responsive-table">
          <thead>
            <tr>
              <th>Cupom</th>
              <th>Tipo</th>
              <th>Origem</th>
              <th>Uso</th>
              <th>Expira</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($userCoupons as $userCoupon)
              <tr>
                <td>
                  <span class="stack-table-label">Cupom</span>
                  <strong>{{ $userCoupon->coupon?->title ?? 'Cupom removido' }}</strong><br>
                  <span style="color:#6b7280; overflow-wrap:anywhere;">{{ $userCoupon->coupon?->code ?? '—' }}</span>
                </td>
                <td>
                  <span class="stack-table-label">Tipo</span>
                  {{ $userCoupon->type ?: 'regular' }}
                </td>
                <td>
                  <span class="stack-table-label">Origem</span>
                  @if ($userCoupon->loyaltyReward)
                    Recompensa: {{ $userCoupon->loyaltyReward->name }}
                  @elseif ($userCoupon->partnerCampaign)
                    Parceiro: {{ $userCoupon->partnerCampaign->partner?->name ?? $userCoupon->partnerCampaign->public_name }}
                  @else
                    Painel / app
                  @endif
                </td>
                <td>
                  <span class="stack-table-label">Uso</span>
                  {{ (int) $userCoupon->usage_count }} / {{ $userCoupon->usage_limit ?: '∞' }}
                </td>
                <td>
                  <span class="stack-table-label">Expira</span>
                  {{ $userCoupon->expires_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td>
                  <span class="stack-table-label">Status</span>
                  @if (($userCoupon->status ?? 'pending') === 'done')
                    <span class="badge badge-muted">Usado</span>
                  @elseif ($userCoupon->active)
                    <span class="badge badge-success">Disponível</span>
                  @else
                    <span class="badge badge-muted">Inativo</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" style="text-align:center; padding:32px 0; color:#6b7280;">
                  Este usuário ainda não possui cupons atribuídos.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h3 class="detail-section-title">Histórico de fidelidade</h3>
      <p class="detail-section-note">
        O histórico continua paginado, mas agora mantém a legibilidade no telemóvel.
      </p>

      <div class="responsive-table-wrap detail-table-wrap">
        <table class="responsive-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Tipo</th>
              <th>Pontos</th>
              <th>Motivo</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($transactions as $transaction)
              <tr>
                <td>
                  <span class="stack-table-label">Data</span>
                  {{ $transaction->created_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td>
                  <span class="stack-table-label">Tipo</span>
                  {{ ucfirst($transaction->type) }}
                </td>
                <td>
                  <span class="stack-table-label">Pontos</span>
                  {{ $transaction->points > 0 ? '+' : '' }}{{ number_format((int) $transaction->points, 0, ',', '.') }}
                </td>
                <td>
                  <span class="stack-table-label">Motivo</span>
                  {{ $transaction->reason ?: '—' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" style="text-align:center; padding:32px 0; color:#6b7280;">
                  Ainda não existem movimentações de fidelidade para este usuário.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="detail-pagination">
        {{ $transactions->links() }}
      </div>
    </div>
  </div>
@endsection
