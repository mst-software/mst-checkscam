{{-- Section 3: evidence images. The script adds drag-drop, paste from clipboard and removable thumbnails. --}}
<section aria-labelledby="report-section-evidence" class="space-y-3">
  <h2 id="report-section-evidence" class="{{ $sectionTitle }}">
    <span class="{{ $sectionNumber }}" aria-hidden="true">3</span>
    Bằng chứng
  </h2>

  <p
    class="bg-warning-soft text-ink rounded-control flex items-start gap-2.5 px-3 py-2.5 text-sm"
    data-evidence-guidance
  >
    <i class="fa-solid fa-circle-info text-warning mt-0.5" aria-hidden="true"></i>
    <span>
      Cần có
      <strong>bill chuyển tiền</strong>
      và
      <strong>ảnh chụp đoạn chat</strong>
      để tố cáo được duyệt. Nên che số dư và mã OTP của bạn.
    </span>
  </p>

  <div>
    <p id="evidence-label" class="{{ $label }}">Ảnh bằng chứng</p>
    <label
      for="evidence_images"
      class="border-line-strong bg-surface-muted/50 rounded-card hover:border-accent has-focus-visible:outline-accent data-[dragover=true]:border-accent data-[dragover=true]:bg-accent-soft flex cursor-pointer flex-col items-center justify-center gap-1.5 border border-dashed px-4 py-7 text-center transition-colors has-focus-visible:outline-2 has-focus-visible:outline-offset-2"
      data-evidence-dropzone
    >
      <i class="fa-solid fa-cloud-arrow-up text-ink-subtle text-2xl" aria-hidden="true"></i>
      <span class="text-ink text-sm font-medium">
        <span class="text-accent underline underline-offset-2">Chọn ảnh</span>
        <span class="hidden sm:inline">, kéo thả hoặc dán (Ctrl+V) vào đây</span>
      </span>
      <span class="text-ink-subtle text-xs">JPG, PNG, GIF, WEBP · tối đa 10 ảnh, mỗi ảnh 5MB</span>
      <input
        id="evidence_images"
        type="file"
        name="evidence_images[]"
        multiple
        required
        accept="image/jpeg,image/png,image/gif,image/webp"
        data-field="evidence_images"
        aria-labelledby="evidence-label"
        aria-describedby="hint-evidence_images error-evidence_images"
        @if ($errors->has('evidence_images') || $errors->has('evidence_images.*')) aria-invalid="true" @endif
        class="sr-only"
      />
    </label>
    <p id="hint-evidence_images" class="{{ $hint }}">
      Ảnh quá nặng? Gửi ảnh qua Zalo cho chính mình rồi tải về, ảnh sẽ nhẹ hơn.
      <span data-evidence-count aria-live="polite"></span>
    </p>
    @include('reports.partials.field-error', ['field' => 'evidence_images'])

    <ul
      class="mt-3 grid grid-cols-3 gap-3 empty:hidden sm:grid-cols-5"
      data-evidence-preview
      aria-label="Ảnh đã chọn"
    ></ul>
  </div>
</section>
