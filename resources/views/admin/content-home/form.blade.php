@php
  $isEdit = $item->exists;
  $availableComponents = $components ?? [];
  $defaultComponentProps = $item->component_props
    ? json_encode($item->component_props, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    : '';
  $componentPropsValue = old('component_props', $defaultComponentProps);
@endphp

<form
  method="POST"
  action="{{ $isEdit ? route('admin.content-home.update', $item) : route('admin.content-home.store') }}"
  enctype="multipart/form-data"
>
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <div class="form-grid">
    <div class="form-group">
      <label for="display_order">Ordem de exibição *</label>
      <input
        type="number"
        min="0"
        id="display_order"
        name="display_order"
        value="{{ old('display_order', $item->display_order) }}"
        required
      />
      @error('display_order')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="type">Tipo *</label>
      <select id="type" name="type" required>
        @foreach (['text' => 'Texto', 'image' => 'Imagem + texto', 'only_image' => 'Somente imagem', 'component' => 'Componente'] as $value => $label)
          <option value="{{ $value }}" @selected(old('type', $item->type) === $value)>
            {{ $label }}
          </option>
        @endforeach
      </select>
      @error('type')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="layout">Layout *</label>
      <input
        type="text"
        id="layout"
        name="layout"
        value="{{ old('layout', $item->layout) }}"
        required
      />
      @error('layout')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="component_name">Componente (quando tipo = componente)</label>
      <select id="component_name" name="component_name">
        <option value="">Selecione um componente</option>
        @foreach ($availableComponents as $key => $label)
          <option value="{{ $key }}" @selected(old('component_name', $item->component_name) === $key)>
            {{ $label }}
          </option>
        @endforeach
      </select>
      @error('component_name')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
      <p style="font-size:0.85rem; color:#6b7280; margin-top:6px;">
        Utilize apenas quando o tipo selecionado for <strong>Componente</strong>.
      </p>
    </div>

    <div class="form-group">
      <label for="title">Título</label>
      <input
        type="text"
        id="title"
        name="title"
        value="{{ old('title', $item->title) }}"
        maxlength="255"
      />
      @error('title')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="cta_label">CTA Label</label>
      <input
        type="text"
        id="cta_label"
        name="cta_label"
        value="{{ old('cta_label', $item->cta_label) }}"
      />
      @error('cta_label')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="cta_url">CTA URL</label>
      <input
        type="url"
        id="cta_url"
        name="cta_url"
        placeholder="https://"
        value="{{ old('cta_url', $item->cta_url) }}"
      />
      @error('cta_url')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label class="checkbox-row" style="margin-top:24px;">
        <input
          type="checkbox"
          name="cta_image_only"
          value="1"
          {{ old('cta_image_only', $item->cta_image_only ?? false) ? 'checked' : '' }}
        />
        Somente imagem clicável (esconde o botão)
      </label>
      @error('cta_image_only')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
      <p style="font-size:0.85rem; color:#6b7280; margin-top:6px;">
        Quando marcado, apenas a imagem abrirá o link interno/externo, sem renderizar o botão de CTA.
      </p>
    </div>

    <div class="form-group">
      <label for="background_color">Cor de fundo (hex)</label>
      <input
        type="text"
        id="background_color"
        name="background_color"
        placeholder="#ffffff"
        value="{{ old('background_color', $item->background_color) }}"
      />
      @error('background_color')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group">
      <label for="publish_at">Publicar em</label>
      <input
        type="datetime-local"
        id="publish_at"
        name="publish_at"
        value="{{ old('publish_at', optional($item->publish_at)->format('Y-m-d\TH:i')) }}"
      />
      @error('publish_at')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="text_body">Texto</label>
      <textarea id="text_body" name="text_body">{{ old('text_body', $item->text_body) }}</textarea>
      @error('text_body')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="component_props">Propriedades do componente (JSON opcional)</label>
      <textarea
        id="component_props"
        name="component_props"
        rows="6"
        placeholder='Ex.: {"title":"Meu título"}'
      >{{ $componentPropsValue }}</textarea>
      @error('component_props')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
      <p style="font-size:0.85rem; color:#6b7280; margin-top:6px;">
        Informe um objeto JSON com as propriedades adicionais do componente, quando aplicável.
      </p>
    </div>

    <div class="form-group" style="grid-column:1/-1;">
      <label for="image">Imagem</label>
      @if ($item->image_url)
        <div style="margin-bottom:12px;">
          <img
            src="{{ $item->image_url }}"
            alt="Imagem atual"
            style="max-width:100%; border-radius:12px; border:1px solid #e5e7eb;"
          />
        </div>
      @endif
      <input type="file" id="image" name="image" accept="image/*" />
      @error('image')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror

      @if ($item->image_url)
        <label class="checkbox-row" style="margin-top:12px;">
          <input type="checkbox" name="remove_image" value="1" />
          Remover imagem atual
        </label>
      @endif
    </div>

    <div class="form-group">
      <label class="checkbox-row">
        <input
          type="checkbox"
          name="is_active"
          value="1"
          {{ old('is_active', $item->is_active) ? 'checked' : '' }}
        />
        Conteúdo ativo
      </label>
      @error('is_active')
        <span class="alert alert-error">{{ $message }}</span>
      @enderror
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">
      {{ $isEdit ? 'Atualizar' : 'Criar' }} conteúdo
    </button>
    <a href="{{ route('admin.content-home.index') }}" class="btn btn-secondary">Cancelar</a>
  </div>
</form>
