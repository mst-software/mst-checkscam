@use('App\Enums\ScamType')

@php
  $selectedScamType = ScamType::tryFrom((string) old('scam_type'));
  $optionClass = 'border-line bg-surface rounded-card has-checked:border-accent has-checked:bg-accent-soft hover:border-line-strong has-focus-visible:outline-accent flex cursor-pointer items-center gap-3 border px-3 py-2.5 transition-colors has-focus-visible:outline-2 has-focus-visible:outline-offset-2';
@endphp

<section data-step-panel="2" aria-labelledby="step-2-title">
  @include('reports.partials.step-heading', ['number' => 2])

  <fieldset aria-describedby="hint-scam_type error-scam_type">
    <legend class="{{ $label }}">
      Chọn hình thức lừa đảo
      <span class="{{ $optional }}">(không bắt buộc)</span>
    </legend>

    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
      @foreach (ScamType::cases() as $scamType)
        <label class="{{ $optionClass }}">
          <input
            type="radio"
            name="scam_type"
            value="{{ $scamType->value }}"
            class="accent-accent size-4 shrink-0"
            data-field="scam_type"
            data-label="{{ $scamType->label() }}"
            @checked($selectedScamType === $scamType)
          />
          <span class="text-ink text-sm">{{ $scamType->label() }}</span>
        </label>
      @endforeach

      <label class="{{ $optionClass }}">
        <input
          type="radio"
          name="scam_type"
          value=""
          class="accent-accent size-4 shrink-0"
          data-field="scam_type"
          @checked($selectedScamType === null)
        />
        <span class="text-ink-muted text-sm">Chưa rõ, để kiểm duyệt viên phân loại</span>
      </label>
    </div>

    <p id="hint-scam_type" class="{{ $hint }}">Không chắc chắn? Bạn có thể bỏ qua bước này.</p>
    @include('reports.partials.field-error', ['field' => 'scam_type'])
  </fieldset>

  {{-- Legacy free-text category, filled from the chosen scam type label by the step script. --}}
  <input type="hidden" name="category" value="{{ old('category') }}" data-category-input />
</section>
