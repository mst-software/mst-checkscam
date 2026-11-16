<section data-step-panel="3" aria-labelledby="step-3-title">
  @include('reports.partials.step-heading', ['number' => 3])

  <div class="space-y-5">
    <div>
      <label for="description" class="{{ $label }}">Diễn biến sự việc</label>
      <textarea
        id="description"
        name="description"
        rows="6"
        required
        minlength="50"
        placeholder="Đối tượng liên hệ bạn qua đâu, hứa hẹn gì, bạn đã chuyển bao nhiêu tiền và khi nào..."
        data-field="description"
        aria-describedby="hint-description error-description"
        @if ($errors->has('description')) aria-invalid="true" @endif
        class="{{ $control }} resize-y py-2.5 leading-relaxed"
      >
{{ old('description') }}</textarea
      >
      <p id="hint-description" class="{{ $hint }} flex flex-wrap justify-between gap-x-3">
        <span>Tối thiểu 50 ký tự. Kể theo trình tự thời gian để kiểm duyệt nhanh hơn.</span>
        <span data-description-count>{{ mb_strlen((string) old('description')) }}/50</span>
      </p>
      @include('reports.partials.field-error', ['field' => 'description'])
    </div>

    <div class="sm:max-w-xs">
      <label for="damage_amount" class="{{ $label }}">
        Số tiền thiệt hại (VNĐ)
        <span class="{{ $optional }}">(không bắt buộc)</span>
      </label>
      <input
        id="damage_amount"
        type="number"
        name="damage_amount"
        value="{{ old('damage_amount') }}"
        min="0"
        step="any"
        inputmode="numeric"
        placeholder="Ví dụ: 500000"
        data-field="damage_amount"
        aria-describedby="hint-damage_amount error-damage_amount"
        @if ($errors->has('damage_amount')) aria-invalid="true" @endif
        class="{{ $control }} h-11"
      />
      <p id="hint-damage_amount" class="{{ $hint }}">Để trống nếu chưa mất tiền hoặc không rõ.</p>
      @include('reports.partials.field-error', ['field' => 'damage_amount'])
    </div>

    <div>
      <p id="evidence-label" class="{{ $label }}">Ảnh bằng chứng</p>
      <label
        for="evidence_images"
        class="border-line-strong bg-surface-muted/50 rounded-card hover:border-accent has-focus-visible:outline-accent data-[dragover=true]:border-accent data-[dragover=true]:bg-accent-soft flex cursor-pointer flex-col items-center justify-center gap-1.5 border border-dashed px-4 py-7 text-center transition-colors has-focus-visible:outline-2 has-focus-visible:outline-offset-2"
        data-evidence-dropzone
      >
        <i class="fa-solid fa-image text-ink-subtle text-2xl" aria-hidden="true"></i>
        <span class="text-ink text-sm font-medium">
          <span class="text-accent underline underline-offset-2">Chọn ảnh</span>
          <span class="hidden sm:inline">hoặc kéo thả vào đây</span>
        </span>
        <span class="text-ink-subtle text-xs">JPG, PNG, GIF · tối đa 10 ảnh, mỗi ảnh 5MB</span>
        <input
          id="evidence_images"
          type="file"
          name="evidence_images[]"
          multiple
          required
          accept="image/jpeg,image/png,image/jpg,image/gif"
          data-field="evidence_images"
          aria-labelledby="evidence-label"
          aria-describedby="hint-evidence_images error-evidence_images"
          @if ($errors->has('evidence_images') || $errors->has('evidence_images.*')) aria-invalid="true" @endif
          class="sr-only"
        />
      </label>
      <p id="hint-evidence_images" class="{{ $hint }}">
        Bill chuyển khoản, đoạn chat, trang cá nhân của đối tượng. Nên che số dư và mã OTP của bạn.
        <span data-evidence-count></span>
      </p>
      @include('reports.partials.field-error', ['field' => 'evidence_images'])

      <ul
        class="mt-3 grid grid-cols-3 gap-3 empty:hidden sm:grid-cols-5"
        data-evidence-preview
        aria-label="Ảnh đã chọn"
      ></ul>
    </div>
  </div>
</section>
