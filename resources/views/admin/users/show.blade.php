@extends('admin.layout')

@section('title', 'Detalhes do Usuário')

@section('content')
  <div style="display:grid; gap:24px;">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
          <h2 style="margin:0; font-size:1.5rem;">{{ $user->name }}</h2>
          <p style="margin:8px 0 0; color:#6b7280;">
            Utilizador #{{ $user->id }} criado em {{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}
          </p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Voltar para a lista</a>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:18px;">
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

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;">
      <div class="card">
        <h3 style="margin-top:0;">Dados do usuário</h3>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">Email</div>
            <div style="margin-top:4px; font-weight:600;">{{ $user->email }}</div>
          </div>
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">Telefone</div>
            <div style="margin-top:4px; font-weight:600;">{{ $user->phone ?: '—' }}</div>
          </div>
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">NIF</div>
            <div style="margin-top:4px; font-weight:600;">{{ $user->nif ?: '—' }}</div>
          </div>
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">Perfil</div>
            <div style="margin-top:4px; font-weight:600;">{{ ucfirst($user->role) }}</div>
          </div>
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">Status</div>
            <div style="margin-top:4px; font-weight:600;">{{ $user->active ? 'Ativo' : 'Inativo' }}</div>
          </div>
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">Nascimento</div>
            <div style="margin-top:4px; font-weight:600;">{{ $user->birth_date?->format('d/m/Y') ?? '—' }}</div>
          </div>
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">Morada</div>
            <div style="margin-top:4px; font-weight:600;">{{ $user->street ?: '—' }}</div>
          </div>
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">Cidade / CP</div>
            <div style="margin-top:4px; font-weight:600;">
              {{ trim(($user->city ?: '') . ' ' . ($user->postal_code ?: '')) ?: '—' }}
            </div>
          </div>
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">ID externo</div>
            <div style="margin-top:4px; font-weight:600;">{{ $user->external_id ?: '—' }}</div>
          </div>
          <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
            <div style="color:#6b7280; font-size:0.85rem;">Consentimento LGPD</div>
            <div style="margin-top:4px; font-weight:600;">{{ $user->lgpd_consent_at?->format('d/m/Y H:i') ?? '—' }}</div>
          </div>
        </div>
      </div>

      <div style="display:grid; gap:24px;">
        <div class="card">
          <h3 style="margin-top:0;">Adicionar Coinxinhas</h3>
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
          <h3 style="margin-top:0;">Adicionar cupom</h3>

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
      <h3 style="margin-top:0;">Cupons do usuário</h3>
      <table>
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
                <strong>{{ $userCoupon->coupon?->title ?? 'Cupom removido' }}</strong><br>
                <span style="color:#6b7280;">{{ $userCoupon->coupon?->code ?? '—' }}</span>
              </td>
              <td>{{ $userCoupon->type ?: 'regular' }}</td>
              <td>
                @if ($userCoupon->loyaltyReward)
                  Recompensa: {{ $userCoupon->loyaltyReward->name }}
                @elseif ($userCoupon->partnerCampaign)
                  Parceiro: {{ $userCoupon->partnerCampaign->partner?->name ?? $userCoupon->partnerCampaign->public_name }}
                @else
                  Painel / app
                @endif
              </td>
              <td>{{ (int) $userCoupon->usage_count }} / {{ $userCoupon->usage_limit ?: '∞' }}</td>
              <td>{{ $userCoupon->expires_at?->format('d/m/Y H:i') ?? '—' }}</td>
              <td>
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

    <div class="card">
      <h3 style="margin-top:0;">Histórico de fidelidade</h3>
      <table>
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
              <td>{{ $transaction->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
              <td>{{ ucfirst($transaction->type) }}</td>
              <td>{{ $transaction->points > 0 ? '+' : '' }}{{ number_format((int) $transaction->points, 0, ',', '.') }}</td>
              <td>{{ $transaction->reason ?: '—' }}</td>
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

      <div style="margin-top:18px;">
        {{ $transactions->links() }}
      </div>
    </div>
  </div>
@endsection
