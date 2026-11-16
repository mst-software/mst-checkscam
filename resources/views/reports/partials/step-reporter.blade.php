@php
  $reviewRows = [
    ['key' => 'type', 'label' => 'Loại', 'step' => 1],
    ['key' => 'target_id', 'label' => 'Tài khoản / SĐT / link', 'step' => 1],
    ['key' => 'target_name', 'label' => 'Tên đối tượng', 'step' => 1],
    ['key' => 'target_bank', 'label' => 'Ngân hàng / ví', 'step' => 1],
    ['key' => 'scam_type', 'label' => 'Hình thức', 'step' => 2],
    ['key' => 'damage_amount', 'label' => 'Số tiền thiệt hại', 'step' => 3],
    ['key' => 'description', 'label' => 'Diễn biến', 'step' => 3],
    ['key' => 'evidence_images', 'label' => 'Ảnh bằng chứng', 'step' => 3],
  ];
@endphp

<section data-step-panel="4" aria-labelledby="step-4-title">
  @include('reports.partials.step-heading', ['number' => 4])

  <div class="space-y-5">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label for="reporter_name" class="{{ $label }}">Họ và tên của bạn</label>
        <input
          id="reporter_name"
          type="text"
          name="reporter_name"
          value="{{ old('reporter_name') }}"
          required
          maxlength="255"
          autocomplete="name"
          placeholder="Nguyễn Văn A"
          data-field="reporter_name"
          aria-describedby="error-reporter_name"
          @if ($errors->has('reporter_name')) aria-invalid="true" @endif
          class="{{ $control }} h-11"
        />
        @include('reports.partials.field-error', ['field' => 'reporter_name'])
      </div>

      <div>
        <label for="reporter_contact" class="{{ $label }}">Số Zalo / cách liên hệ</label>
        <input
          id="reporter_contact"
          type="text"
          name="reporter_contact"
          value="{{ old('reporter_contact') }}"
          required
          maxlength="255"
          autocomplete="tel"
          placeholder="Ví dụ: 0987654321"
          data-field="reporter_contact"
          aria-describedby="error-reporter_contact"
          @if ($errors->has('reporter_contact')) aria-invalid="true" @endif
          class="{{ $control }} h-11"
        />
        @include('reports.partials.field-error', ['field' => 'reporter_contact'])
      </div>
    </div>

    <p class="bg-success-soft text-ink rounded-control flex items-start gap-2.5 px-3 py-2.5 text-sm">
      <i class="fa-solid fa-lock text-success mt-0.5" aria-hidden="true"></i>
      <span>Thông tin liên hệ của bạn chỉ dùng để xác minh nội bộ và không bao giờ được công khai.</span>
    </p>

    {{-- Review summary, filled by the step script before submitting --}}
    <div class="border-line rounded-card border" data-js-only>
      <h3 class="border-line text-ink border-b px-4 py-3 text-sm font-semibold">Kiểm tra lại trước khi gửi</h3>
      <dl class="divide-line divide-y text-sm">
        @foreach ($reviewRows as $row)
          <div
            class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-3 gap-y-0.5 px-4 py-2.5 sm:grid-cols-[11rem_minmax(0,1fr)_auto]"
          >
            <dt class="text-ink-subtle col-start-1 row-start-1 text-xs sm:text-sm">{{ $row['label'] }}</dt>
            <dd
              class="text-ink col-start-1 row-start-2 min-w-0 break-words whitespace-pre-line sm:col-start-2 sm:row-start-1"
              data-review="{{ $row['key'] }}"
            >
              —
            </dd>
            <dd class="col-start-2 row-start-1 sm:col-start-3">
              <button
                type="button"
                class="text-accent hover:text-accent-hover cursor-pointer rounded-sm text-xs font-medium underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-accent"
                data-step-target="{{ $row['step'] }}"
                data-edit-link
              >
                Sửa
                <span class="sr-only">{{ mb_strtolower($row['label']) }}</span>
              </button>
            </dd>
          </div>
        @endforeach
      </dl>
    </div>

    <div>
      <label class="flex cursor-pointer items-start gap-3">
        <input
          id="confirm_truth"
          type="checkbox"
          required
          class="accent-accent mt-0.5 size-4 shrink-0"
          data-field="confirm"
          aria-describedby="error-confirm"
        />
        <span class="text-ink text-sm">
          Tôi cam đoan nội dung tố cáo là sự thật và chịu trách nhiệm về thông tin đã cung cấp.
        </span>
      </label>
      @include('reports.partials.field-error', ['field' => 'confirm'])
    </div>
  </div>
</section>
