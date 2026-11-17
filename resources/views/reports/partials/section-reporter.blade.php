{{-- Section 4: the reporter. None of these fields are shown publicly. --}}
@php
  $role = in_array(old('reporter_role'), ['victim', 'proxy'], true) ? old('reporter_role') : 'victim';
  $roleOption = 'border-line bg-surface rounded-card has-checked:border-accent has-checked:bg-accent-soft has-focus-visible:outline-accent flex cursor-pointer items-start gap-2.5 border p-3 has-focus-visible:outline-2 has-focus-visible:outline-offset-2';
@endphp

<section aria-labelledby="report-section-reporter" class="space-y-5">
  <h2 id="report-section-reporter" class="{{ $sectionTitle }}">
    <span class="{{ $sectionNumber }}" aria-hidden="true">4</span>
    Người gửi
  </h2>

  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
      <label for="reporter_email" class="{{ $label }}">Email nhận kết quả</label>
      <input
        id="reporter_email"
        type="email"
        name="reporter_email"
        value="{{ old('reporter_email') }}"
        required
        maxlength="255"
        autocomplete="email"
        inputmode="email"
        placeholder="ban@gmail.com"
        data-field="reporter_email"
        aria-describedby="hint-reporter_email error-reporter_email"
        @if ($errors->has('reporter_email')) aria-invalid="true" @endif
        class="{{ $control }} h-11"
      />
      <p id="hint-reporter_email" class="{{ $hint }}">Báo cho bạn khi tố cáo được duyệt hoặc bị từ chối.</p>
      @include('reports.partials.field-error', ['field' => 'reporter_email'])
    </div>

    <div>
      <label for="reporter_contact" class="{{ $label }}">Zalo / SĐT liên hệ</label>
      <input
        id="reporter_contact"
        type="text"
        name="reporter_contact"
        value="{{ old('reporter_contact') }}"
        required
        maxlength="255"
        autocomplete="tel"
        inputmode="tel"
        placeholder="Ví dụ: 0987654321"
        data-field="reporter_contact"
        aria-describedby="error-reporter_contact"
        @if ($errors->has('reporter_contact')) aria-invalid="true" @endif
        class="{{ $control }} h-11"
      />
      @include('reports.partials.field-error', ['field' => 'reporter_contact'])
    </div>

    <div class="sm:col-span-2">
      <label for="reporter_name" class="{{ $label }}">
        Tên của bạn
        <span class="{{ $optional }}">(không bắt buộc)</span>
      </label>
      <input
        id="reporter_name"
        type="text"
        name="reporter_name"
        value="{{ old('reporter_name') }}"
        maxlength="255"
        autocomplete="name"
        placeholder="Để trống sẽ hiện là Người dùng"
        data-field="reporter_name"
        aria-describedby="error-reporter_name"
        @if ($errors->has('reporter_name')) aria-invalid="true" @endif
        class="{{ $control }} h-11"
      />
      @include('reports.partials.field-error', ['field' => 'reporter_name'])
    </div>
  </div>

  <fieldset aria-describedby="error-reporter_role">
    <legend class="{{ $label }}">Bạn là</legend>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
      <label class="{{ $roleOption }}">
        <input
          type="radio"
          name="reporter_role"
          value="victim"
          class="accent-accent mt-0.5 size-4 shrink-0"
          data-field="reporter_role"
          @checked($role === 'victim')
        />
        <span class="text-ink text-sm">Tôi là nạn nhân, chịu trách nhiệm về nội dung này.</span>
      </label>
      <label class="{{ $roleOption }}">
        <input
          type="radio"
          name="reporter_role"
          value="proxy"
          class="accent-accent mt-0.5 size-4 shrink-0"
          data-field="reporter_role"
          @checked($role === 'proxy')
        />
        <span class="text-ink text-sm">Tôi chỉ đăng hộ người bị lừa.</span>
      </label>
    </div>
    @include('reports.partials.field-error', ['field' => 'reporter_role'])
  </fieldset>

  <p
    class="bg-success-soft text-ink rounded-control flex items-start gap-2.5 px-3 py-2.5 text-sm"
    data-reporter-privacy-note
  >
    <i class="fa-solid fa-lock text-success mt-0.5" aria-hidden="true"></i>
    <span>
      Tên, email và thông tin liên hệ của bạn không hiển thị công khai; chỉ quản trị viên xem được để xác minh tố cáo.
      Bạn chịu trách nhiệm về tính trung thực của nội dung đã gửi.
      <a
        href="{{ route('moderation.policy') }}"
        class="text-accent hover:text-accent-hover focus-visible:outline-accent rounded-sm font-semibold underline-offset-2 hover:underline focus-visible:outline-2"
        target="_blank"
        rel="noopener"
      >
        Xem quy trình kiểm duyệt
      </a>
    </span>
  </p>

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
      <span class="text-ink text-sm">Tôi cam đoan nội dung tố cáo là sự thật.</span>
    </label>
    @include('reports.partials.field-error', ['field' => 'confirm'])
  </div>
</section>
