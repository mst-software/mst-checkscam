{{-- Section 2: what happened. The scam type picker is hidden for "Acc bị back", which the server always classifies. --}}
@use('App\Enums\ScamType')

@php
  $selectedScamType = ScamType::tryFrom((string) old('scam_type'));
  $chip = 'border-line bg-surface text-ink has-checked:border-accent has-checked:bg-accent-soft has-checked:text-accent hover:border-line-strong has-focus-visible:outline-accent inline-flex cursor-pointer items-center rounded-full border px-3 py-1.5 text-sm transition-colors has-focus-visible:outline-2 has-focus-visible:outline-offset-2';
  $descriptionPlaceholders = [
    'account' => 'Đối tượng liên hệ bạn qua đâu, hứa hẹn gì, bạn chuyển bao nhiêu tiền, khi nào, rồi bị chặn ra sao...',
    'website' => 'Bạn vào website từ đâu, đã mua / nạp gì, thanh toán bao nhiêu, sau đó website xử lý thế nào...',
    'back' => 'Bạn mua acc gì, giá bao nhiêu, ngày nào; người bán lấy lại acc bằng cách nào (đổi mật khẩu, khôi phục qua SĐT/email gốc)...',
  ];
@endphp

<section aria-labelledby="report-section-story" class="space-y-5">
  <h2 id="report-section-story" class="{{ $sectionTitle }}">
    <span class="{{ $sectionNumber }}" aria-hidden="true">2</span>
    Chuyện gì đã xảy ra?
  </h2>

  <fieldset data-show-for="account website" aria-describedby="error-scam_type">
    <legend class="{{ $label }}">
      Hình thức lừa đảo
      <span class="{{ $optional }}">(không bắt buộc)</span>
    </legend>
    <div class="flex flex-wrap gap-2">
      @foreach (ScamType::cases() as $scamType)
        @continue($scamType === ScamType::AccountBack)
        <label class="{{ $chip }}">
          <input
            type="radio"
            name="scam_type"
            value="{{ $scamType->value }}"
            class="sr-only"
            data-field="scam_type"
            data-label="{{ $scamType->label() }}"
            @checked($selectedScamType === $scamType)
          />
          {{ $scamType->label() }}
        </label>
      @endforeach

      <label class="{{ $chip }} text-ink-muted">
        <input
          type="radio"
          name="scam_type"
          value=""
          class="sr-only"
          data-field="scam_type"
          @checked($selectedScamType === null)
        />
        Chưa rõ
      </label>
    </div>
    @include('reports.partials.field-error', ['field' => 'scam_type'])
  </fieldset>

  {{-- Legacy free-text category, filled from the chosen scam type label by the script. --}}
  <input type="hidden" name="category" value="{{ old('category') }}" data-category-input />

  <div class="sm:max-w-xs">
    <label for="damage_amount" class="{{ $label }}">Số tiền thiệt hại</label>
    <div class="relative">
      <input
        id="damage_amount"
        type="text"
        name="damage_amount"
        value="{{ old('damage_amount') }}"
        required
        inputmode="numeric"
        autocomplete="off"
        placeholder="Ví dụ: 1.500.000"
        data-field="damage_amount"
        aria-describedby="hint-damage_amount error-damage_amount"
        @if ($errors->has('damage_amount')) aria-invalid="true" @endif
        class="{{ $control }} h-11 pr-14 tabular-nums"
      />
      <span class="text-ink-subtle pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm">VNĐ</span>
    </div>
    <p id="hint-damage_amount" class="{{ $hint }}">Nhập 0 nếu chưa mất tiền.</p>
    @include('reports.partials.field-error', ['field' => 'damage_amount'])
  </div>

  <div>
    <label for="description" class="{{ $label }}">Kể lại sự việc</label>
    <textarea
      id="description"
      name="description"
      rows="5"
      required
      minlength="20"
      maxlength="5000"
      placeholder="{{ $descriptionPlaceholders[$type] }}"
      data-placeholders="{{ json_encode($descriptionPlaceholders) }}"
      data-field="description"
      aria-describedby="hint-description error-description"
      @if ($errors->has('description')) aria-invalid="true" @endif
      class="{{ $control }} resize-y py-2.5 leading-relaxed"
    >
{{ old('description') }}</textarea
    >
    <p id="hint-description" class="{{ $hint }} flex flex-wrap justify-between gap-x-3">
      <span>
        <span data-show-for="account website">
          Tối thiểu 20 ký tự. Kể theo trình tự thời gian để kiểm duyệt nhanh hơn.
        </span>
        <span data-show-for="back">Tối thiểu 20 ký tự. Ghi rõ acc đã mua, giá tiền và cách người bán lấy lại.</span>
      </span>
      <span data-description-count aria-hidden="true">{{ mb_strlen(trim((string) old('description'))) }}/20</span>
    </p>
    @include('reports.partials.field-error', ['field' => 'description'])
  </div>
</section>
