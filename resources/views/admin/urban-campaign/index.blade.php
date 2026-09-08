@extends('admin.layout')

@section('title', 'Campanha Urbana')

@section('styles')
  <style>
    .campaign-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 18px;
      margin-bottom: 22px;
    }

    .campaign-header h2 {
      margin: 0;
      font-size: 1.4rem;
    }

    .campaign-header p,
    .campaign-helper {
      margin: 6px 0 0;
      color: #6b7280;
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .campaign-tabs {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      border-bottom: 1px solid #e5e7eb;
      margin-bottom: 24px;
    }

    .campaign-tab {
      display: inline-flex;
      align-items: center;
      min-height: 42px;
      padding: 10px 14px;
      border-radius: 12px 12px 0 0;
      color: #4b5563;
      text-decoration: none;
      font-weight: 700;
      border: 1px solid transparent;
      border-bottom: none;
    }

    .campaign-tab.active {
      color: #910202;
      background: #fff7f7;
      border-color: #f1d2d2;
    }

    .campaign-panel-grid {
      display: grid;
      grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
      gap: 24px;
      align-items: start;
    }

    .campaign-form-title {
      margin: 0 0 14px;
      font-size: 1.05rem;
    }

    .campaign-table-note {
      color: #6b7280;
      font-size: 0.88rem;
      line-height: 1.45;
    }

    @media (max-width: 980px) {
      .campaign-header,
      .campaign-panel-grid {
        display: grid;
        grid-template-columns: 1fr;
      }
    }
  </style>
@endsection

@section('content')
  @php
    $tabs = [
      'qr-codes' => 'QR Codes',
      'questions' => 'Perguntas',
      'responses' => 'Respostas',
      'coupon-configs' => 'Cupons',
    ];
    $discountTypes = [
      'percent' => 'Percentual',
      'money' => 'Valor fixo',
    ];
    $activeTab = array_key_exists($activeTab, $tabs) ? $activeTab : 'qr-codes';
    $editingQrCode = $editQrCode->exists;
    $editingQuestion = $editQuestion->exists;
    $editingResponse = $editResponse->exists;
    $editingCouponConfig = $editCouponConfig->exists;
  @endphp

  <div class="card">
    <div class="campaign-header">
      <div>
        <h2>Campanha Urbana</h2>
        <p>Gerencie códigos QR, desafios do quiz e opções de resposta em um único lugar.</p>
      </div>
      <a class="btn btn-secondary" href="/campanha-urbana?code=QRXPTO" target="_blank" rel="noopener noreferrer">
        Abrir página pública
      </a>
    </div>

    <nav class="campaign-tabs" aria-label="Áreas de gestão da campanha urbana">
      @foreach ($tabs as $tabKey => $tabLabel)
        <a
          class="campaign-tab {{ $activeTab === $tabKey ? 'active' : '' }}"
          href="{{ route('admin.urban-campaign.index', ['tab' => $tabKey]) }}"
        >
          {{ $tabLabel }}
        </a>
      @endforeach
    </nav>

    @if ($activeTab === 'qr-codes')
      <section class="campaign-panel-grid">
        <div class="form-section">
          <h3 class="campaign-form-title">{{ $editingQrCode ? 'Editar QR Code' : 'Novo QR Code' }}</h3>
          <p class="form-section-description">O código é a entrada pública da URL e o tipo define qual desafio será carregado.</p>

          <form method="POST" action="{{ $editingQrCode ? route('admin.urban-campaign.qr-codes.update', $editQrCode) : route('admin.urban-campaign.qr-codes.store') }}">
            @csrf
            @if ($editingQrCode)
              @method('PUT')
            @endif

            <div class="form-grid">
              <div class="form-group">
                <label for="qr_code">Código *</label>
                <input type="text" id="qr_code" name="code" value="{{ old('code', $editQrCode->code) }}" placeholder="QRXPTO" required />
                @error('code')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="qr_type">Tipo *</label>
                <input type="text" id="qr_type" name="type" value="{{ old('type', $editQrCode->type) }}" placeholder="kibe" list="code-type-options" required />
                @error('type')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label class="checkbox-row">
                  <input type="checkbox" name="active" value="1" {{ old('active', $editQrCode->active ?? true) ? 'checked' : '' }} />
                  QR Code ativo
                </label>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">{{ $editingQrCode ? 'Atualizar' : 'Criar' }} QR Code</button>
              @if ($editingQrCode)
                <a href="{{ route('admin.urban-campaign.index', ['tab' => 'qr-codes']) }}" class="btn btn-secondary">Cancelar edição</a>
              @endif
            </div>
          </form>
        </div>

        <div>
          <div class="responsive-table-wrap">
            <table class="responsive-table">
              <thead>
              <tr>
                <th>Código</th>
                <th>Tipo</th>
                <th>Status</th>
                <th style="width:76px;">Ações</th>
              </tr>
              </thead>
              <tbody>
                @forelse ($qrCodes as $qrCode)
                  <tr>
                    <td>
                      <span class="stack-table-label">Código</span>
                      <strong>{{ $qrCode->code }}</strong>
                      <div class="campaign-table-note">/campanha-urbana?code={{ $qrCode->code }}</div>
                    </td>
                    <td>
                      <span class="stack-table-label">Tipo</span>
                      {{ $qrCode->type }}
                    </td>
                    <td>
                      <span class="stack-table-label">Status</span>
                      @if ($qrCode->active)
                        <span class="badge badge-success">Ativo</span>
                      @else
                        <span class="badge badge-muted">Inativo</span>
                      @endif
                    </td>
                    <td>
                      <span class="stack-table-label">Ações</span>
                      <details class="action-menu">
                        <summary class="btn action-menu-trigger" aria-label="Abrir ações do QR Code">
                          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <circle cx="8" cy="3" r="1.4" />
                            <circle cx="8" cy="8" r="1.4" />
                            <circle cx="8" cy="13" r="1.4" />
                          </svg>
                        </summary>

                        <div class="action-menu-panel">
                          <a class="btn action-menu-item" href="{{ route('admin.urban-campaign.qr-codes.edit', $qrCode) }}">Editar</a>
                          <form action="{{ route('admin.urban-campaign.qr-codes.destroy', $qrCode) }}" method="POST" onsubmit="return confirm('Remover este QR Code?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn action-menu-item action-menu-item-danger">Excluir</button>
                          </form>
                        </div>
                      </details>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" style="text-align:center; padding:32px 0; color:#6b7280;">Nenhum QR Code cadastrado.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
    @endif

    @if ($activeTab === 'questions')
      <section class="campaign-panel-grid">
        <div class="form-section">
          <h3 class="campaign-form-title">{{ $editingQuestion ? 'Editar pergunta' : 'Nova pergunta' }}</h3>
          <p class="form-section-description">A pergunta fica associada ao tipo do QR Code. Use uma linha por pista da adivinha.</p>

          <form method="POST" action="{{ $editingQuestion ? route('admin.urban-campaign.questions.update', $editQuestion) : route('admin.urban-campaign.questions.store') }}">
            @csrf
            @if ($editingQuestion)
              @method('PUT')
            @endif

            <div class="form-grid">
              <div class="form-group">
                <label for="question_code_type">Tipo do QR *</label>
                <input type="text" id="question_code_type" name="code_type" value="{{ old('code_type', $editQuestion->code_type) }}" placeholder="kibe" list="code-type-options" required />
                @error('code_type')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="secret_number">Número do segredo *</label>
                <input type="number" min="1" max="255" id="secret_number" name="secret_number" value="{{ old('secret_number', $editQuestion->secret_number) }}" required />
                @error('secret_number')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="collection_label">Rótulo da coleção *</label>
                <input type="text" id="collection_label" name="collection_label" value="{{ old('collection_label', $editQuestion->collection_label) }}" placeholder="Kibe" required />
                @error('collection_label')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="eyebrow">Subtítulo da pista</label>
                <input type="text" id="eyebrow" name="eyebrow" value="{{ old('eyebrow', $editQuestion->eyebrow) }}" placeholder="Uma pista de forma comprida" />
                @error('eyebrow')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="reward_when_correct">Desconto se acertar (%) *</label>
                <input type="number" min="0" max="100" id="reward_when_correct" name="reward_when_correct" value="{{ old('reward_when_correct', $editQuestion->reward_when_correct) }}" required />
                @error('reward_when_correct')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="reward_when_wrong">Desconto se errar (%) *</label>
                <input type="number" min="0" max="100" id="reward_when_wrong" name="reward_when_wrong" value="{{ old('reward_when_wrong', $editQuestion->reward_when_wrong) }}" required />
                @error('reward_when_wrong')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="question_display_order">Ordem</label>
                <input type="number" min="0" id="question_display_order" name="display_order" value="{{ old('display_order', $editQuestion->display_order) }}" />
                @error('display_order')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label class="checkbox-row">
                  <input type="checkbox" name="active" value="1" {{ old('active', $editQuestion->active ?? true) ? 'checked' : '' }} />
                  Pergunta ativa
                </label>
              </div>

              <div class="form-group form-span-full">
                <label for="question">Texto da pergunta *</label>
                <textarea id="question" name="question" required>{{ old('question', $editQuestion->question) }}</textarea>
                @error('question')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">{{ $editingQuestion ? 'Atualizar' : 'Criar' }} pergunta</button>
              @if ($editingQuestion)
                <a href="{{ route('admin.urban-campaign.index', ['tab' => 'questions']) }}" class="btn btn-secondary">Cancelar edição</a>
              @endif
            </div>
          </form>
        </div>

        <div>
          <div class="responsive-table-wrap">
            <table class="responsive-table">
              <thead>
              <tr>
                <th>Segredo</th>
                <th>Tipo</th>
                <th>Pergunta</th>
                <th>Descontos</th>
                <th>Status</th>
                <th style="width:76px;">Ações</th>
              </tr>
              </thead>
              <tbody>
                @forelse ($questions as $question)
                  <tr>
                    <td>
                      <span class="stack-table-label">Segredo</span>
                      #{{ $question->secret_number }}<br>
                      <span class="campaign-table-note">{{ $question->collection_label }}</span>
                    </td>
                    <td>
                      <span class="stack-table-label">Tipo</span>
                      {{ $question->code_type }}
                    </td>
                    <td>
                      <span class="stack-table-label">Pergunta</span>
                      <strong>{{ $question->eyebrow ?: 'Sem subtítulo' }}</strong>
                      <div class="campaign-table-note">{{ \Illuminate\Support\Str::limit(str_replace(["\r", "\n"], ' ', $question->question), 120) }}</div>
                      <div class="campaign-table-note">{{ $question->responses_count }} resposta(s)</div>
                    </td>
                    <td>
                      <span class="stack-table-label">Descontos</span>
                      {{ $question->reward_when_correct }}% / {{ $question->reward_when_wrong }}%
                    </td>
                    <td>
                      <span class="stack-table-label">Status</span>
                      @if ($question->active)
                        <span class="badge badge-success">Ativa</span>
                      @else
                        <span class="badge badge-muted">Inativa</span>
                      @endif
                    </td>
                    <td>
                      <span class="stack-table-label">Ações</span>
                      <details class="action-menu">
                        <summary class="btn action-menu-trigger" aria-label="Abrir ações da pergunta">
                          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <circle cx="8" cy="3" r="1.4" />
                            <circle cx="8" cy="8" r="1.4" />
                            <circle cx="8" cy="13" r="1.4" />
                          </svg>
                        </summary>

                        <div class="action-menu-panel">
                          <a class="btn action-menu-item" href="{{ route('admin.urban-campaign.questions.edit', $question) }}">Editar</a>
                          <form action="{{ route('admin.urban-campaign.questions.destroy', $question) }}" method="POST" onsubmit="return confirm('Remover esta pergunta e suas respostas?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn action-menu-item action-menu-item-danger">Excluir</button>
                          </form>
                        </div>
                      </details>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" style="text-align:center; padding:32px 0; color:#6b7280;">Nenhuma pergunta cadastrada.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
    @endif

    @if ($activeTab === 'responses')
      <section class="campaign-panel-grid">
        <div class="form-section">
          <h3 class="campaign-form-title">{{ $editingResponse ? 'Editar resposta' : 'Nova resposta' }}</h3>
          <p class="form-section-description">Apenas uma resposta correta fica ativa por pergunta; marcar uma nova correta desmarca as restantes.</p>

          <form method="POST" action="{{ $editingResponse ? route('admin.urban-campaign.responses.update', $editResponse) : route('admin.urban-campaign.responses.store') }}">
            @csrf
            @if ($editingResponse)
              @method('PUT')
            @endif

            <div class="form-grid">
              <div class="form-group">
                <label for="response_question_id">Pergunta *</label>
                <select id="response_question_id" name="question_id" required>
                  <option value="">Selecione...</option>
                  @foreach ($questionOptions as $questionOption)
                    <option value="{{ $questionOption->id }}" @selected((string) old('question_id', $editResponse->question_id) === (string) $questionOption->id)>
                      #{{ $questionOption->secret_number }} {{ $questionOption->collection_label }} ({{ $questionOption->code_type }})
                    </option>
                  @endforeach
                </select>
                @error('question_id')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="response">Resposta *</label>
                <input type="text" id="response" name="response" value="{{ old('response', $editResponse->response) }}" required />
                @error('response')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="response_display_order">Ordem</label>
                <input type="number" min="0" id="response_display_order" name="display_order" value="{{ old('display_order', $editResponse->display_order) }}" />
                @error('display_order')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label class="checkbox-row">
                  <input type="checkbox" name="is_correct" value="1" {{ old('is_correct', $editResponse->is_correct ?? false) ? 'checked' : '' }} />
                  Resposta correta
                </label>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">{{ $editingResponse ? 'Atualizar' : 'Criar' }} resposta</button>
              @if ($editingResponse)
                <a href="{{ route('admin.urban-campaign.index', ['tab' => 'responses']) }}" class="btn btn-secondary">Cancelar edição</a>
              @endif
            </div>
          </form>
        </div>

        <div>
          <div class="responsive-table-wrap">
            <table class="responsive-table">
              <thead>
              <tr>
                <th>Pergunta</th>
                <th>Resposta</th>
                <th>Status</th>
                <th>Ordem</th>
                <th style="width:76px;">Ações</th>
              </tr>
              </thead>
              <tbody>
                @forelse ($responses as $response)
                  <tr>
                    <td>
                      <span class="stack-table-label">Pergunta</span>
                      #{{ $response->question?->secret_number }} {{ $response->question?->collection_label ?? 'Pergunta removida' }}
                      <div class="campaign-table-note">{{ $response->question?->code_type }}</div>
                    </td>
                    <td>
                      <span class="stack-table-label">Resposta</span>
                      <strong>{{ $response->response }}</strong>
                    </td>
                    <td>
                      <span class="stack-table-label">Status</span>
                      @if ($response->is_correct)
                        <span class="badge badge-success">Correta</span>
                      @else
                        <span class="badge badge-muted">Errada</span>
                      @endif
                    </td>
                    <td>
                      <span class="stack-table-label">Ordem</span>
                      {{ $response->display_order }}
                    </td>
                    <td>
                      <span class="stack-table-label">Ações</span>
                      <details class="action-menu">
                        <summary class="btn action-menu-trigger" aria-label="Abrir ações da resposta">
                          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <circle cx="8" cy="3" r="1.4" />
                            <circle cx="8" cy="8" r="1.4" />
                            <circle cx="8" cy="13" r="1.4" />
                          </svg>
                        </summary>

                        <div class="action-menu-panel">
                          <a class="btn action-menu-item" href="{{ route('admin.urban-campaign.responses.edit', $response) }}">Editar</a>
                          <form action="{{ route('admin.urban-campaign.responses.destroy', $response) }}" method="POST" onsubmit="return confirm('Remover esta resposta?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn action-menu-item action-menu-item-danger">Excluir</button>
                          </form>
                        </div>
                      </details>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" style="text-align:center; padding:32px 0; color:#6b7280;">Nenhuma resposta cadastrada.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
    @endif

    @if ($activeTab === 'coupon-configs')
      <section class="campaign-panel-grid">
        <div class="form-section">
          <h3 class="campaign-form-title">{{ $editingCouponConfig ? 'Editar configuração' : 'Nova configuração' }}</h3>
          <p class="form-section-description">Configure o cupom que será criado no Vendus para cada tipo de QR Code.</p>

          <form method="POST" action="{{ $editingCouponConfig ? route('admin.urban-campaign.coupon-configs.update', $editCouponConfig) : route('admin.urban-campaign.coupon-configs.store') }}">
            @csrf
            @if ($editingCouponConfig)
              @method('PUT')
            @endif

            <div class="form-grid">
              <div class="form-group">
                <label for="coupon_type">Tipo do cupom *</label>
                <input type="text" id="coupon_type" name="coupon_type" value="{{ old('coupon_type', $editCouponConfig->coupon_type) }}" placeholder="kibe" list="code-type-options" required />
                @error('coupon_type')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="coupon_title">Título *</label>
                <input type="text" id="coupon_title" name="title" value="{{ old('title', $editCouponConfig->title) }}" placeholder="Campanha Urbana - Kibe" required />
                @error('title')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="discount_type">Tipo de desconto *</label>
                <select id="discount_type" name="discount_type" required>
                  @foreach ($discountTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('discount_type', $editCouponConfig->discount_type ?? 'percent') === $value)>
                      {{ $label }}
                    </option>
                  @endforeach
                </select>
                @error('discount_type')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="coupon_amount">Valor *</label>
                <input type="number" step="0.01" min="0.01" id="coupon_amount" name="amount" value="{{ old('amount', $editCouponConfig->amount) }}" required />
                @error('amount')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="coupon_starts_at">Início</label>
                <input type="datetime-local" id="coupon_starts_at" name="starts_at" value="{{ old('starts_at', optional($editCouponConfig->starts_at)->format('Y-m-d\TH:i')) }}" />
                @error('starts_at')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label for="coupon_ends_at">Término</label>
                <input type="datetime-local" id="coupon_ends_at" name="ends_at" value="{{ old('ends_at', optional($editCouponConfig->ends_at)->format('Y-m-d\TH:i')) }}" />
                @error('ends_at')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group form-span-full">
                <label for="coupon_description">Descrição</label>
                <textarea id="coupon_description" name="description" placeholder="Texto enviado para o Vendus como observação do cupom.">{{ old('description', $editCouponConfig->description) }}</textarea>
                @error('description')
                  <span class="alert alert-error">{{ $message }}</span>
                @enderror
              </div>

              <div class="form-group">
                <label class="checkbox-row">
                  <input type="checkbox" name="active" value="1" {{ old('active', $editCouponConfig->active ?? true) ? 'checked' : '' }} />
                  Configuração ativa
                </label>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">{{ $editingCouponConfig ? 'Atualizar' : 'Criar' }} configuração</button>
              @if ($editingCouponConfig)
                <a href="{{ route('admin.urban-campaign.index', ['tab' => 'coupon-configs']) }}" class="btn btn-secondary">Cancelar edição</a>
              @endif
            </div>
          </form>
        </div>

        <div>
          <div class="responsive-table-wrap">
            <table class="responsive-table">
              <thead>
              <tr>
                <th>Tipo</th>
                <th>Desconto</th>
                <th>Vigência</th>
                <th>Status</th>
                <th style="width:76px;">Ações</th>
              </tr>
              </thead>
              <tbody>
                @forelse ($couponConfigs as $config)
                  <tr>
                    <td>
                      <span class="stack-table-label">Tipo</span>
                      <strong>{{ $config->coupon_type }}</strong>
                      <div class="campaign-table-note">{{ $config->title }}</div>
                    </td>
                    <td>
                      <span class="stack-table-label">Desconto</span>
                      {{ $discountTypes[$config->discount_type] ?? $config->discount_type }}
                      <div class="campaign-table-note">
                        @if ($config->discount_type === 'percent')
                          {{ number_format((float) $config->amount, 2, ',', '.') }}%
                        @else
                          €{{ number_format((float) $config->amount, 2, ',', '.') }}
                        @endif
                      </div>
                    </td>
                    <td>
                      <span class="stack-table-label">Vigência</span>
                      <div class="campaign-table-note">Início: {{ optional($config->starts_at)->format('d/m/Y H:i') ?? 'imediato' }}</div>
                      <div class="campaign-table-note">Fim: {{ optional($config->ends_at)->format('d/m/Y H:i') ?? 'sem prazo' }}</div>
                    </td>
                    <td>
                      <span class="stack-table-label">Status</span>
                      @if ($config->active)
                        <span class="badge badge-success">Ativa</span>
                      @else
                        <span class="badge badge-muted">Inativa</span>
                      @endif
                    </td>
                    <td>
                      <span class="stack-table-label">Ações</span>
                      <details class="action-menu">
                        <summary class="btn action-menu-trigger" aria-label="Abrir ações da configuração">
                          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <circle cx="8" cy="3" r="1.4" />
                            <circle cx="8" cy="8" r="1.4" />
                            <circle cx="8" cy="13" r="1.4" />
                          </svg>
                        </summary>

                        <div class="action-menu-panel">
                          <a class="btn action-menu-item" href="{{ route('admin.urban-campaign.coupon-configs.edit', $config) }}">Editar</a>
                          <form action="{{ route('admin.urban-campaign.coupon-configs.destroy', $config) }}" method="POST" onsubmit="return confirm('Remover esta configuração de cupom?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn action-menu-item action-menu-item-danger">Excluir</button>
                          </form>
                        </div>
                      </details>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" style="text-align:center; padding:32px 0; color:#6b7280;">Nenhuma configuração de cupom cadastrada.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <h3 class="campaign-form-title" style="margin-top:28px;">Resgates recentes</h3>
          <div class="responsive-table-wrap">
            <table class="responsive-table">
              <thead>
              <tr>
                <th>Telemóvel</th>
                <th>Tipo</th>
                <th>Código Vendus</th>
                <th>Status</th>
              </tr>
              </thead>
              <tbody>
                @forelse ($couponClaims as $claim)
                  <tr>
                    <td>
                      <span class="stack-table-label">Telemóvel</span>
                      {{ $claim->phone }}
                    </td>
                    <td>
                      <span class="stack-table-label">Tipo</span>
                      {{ $claim->coupon_type }}
                    </td>
                    <td>
                      <span class="stack-table-label">Código Vendus</span>
                      <strong>{{ $claim->code ?? 'Pendente' }}</strong>
                      <div class="campaign-table-note">{{ $claim->external_id }}</div>
                    </td>
                    <td>
                      <span class="stack-table-label">Status</span>
                      @if ($claim->status === 'synced')
                        <span class="badge badge-success">Sincronizado</span>
                      @elseif ($claim->status === 'failed_erp')
                        <span class="badge badge-muted">Falhou</span>
                      @else
                        <span class="badge badge-muted">{{ $claim->status }}</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" style="text-align:center; padding:32px 0; color:#6b7280;">Nenhum resgate registrado.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </section>
    @endif

    <datalist id="code-type-options">
      @foreach ($codeTypeOptions as $option)
        <option value="{{ $option->type }}"></option>
      @endforeach
    </datalist>
  </div>
@endsection
