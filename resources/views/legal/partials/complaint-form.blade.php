@use("App\Helpers\StringHelper")

{{-- Complaint form. Field names, honeypot ("website") and the #gui-khieu-nai anchor are used by ComplaintController. --}}
@php
  $reportSlug = old("report", $report?->slug);
  $inputClass = "border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control focus-visible:outline-accent aria-invalid:border-danger w-full border px-3 py-2.5 text-base sm:text-sm focus-visible:outline-2 focus-visible:outline-offset-1";
  $labelClass = "text-ink mb-1.5 block text-sm font-medium";
  $evidenceError = $errors->first("evidence_images") ?: $errors->first("evidence_images.*");
@endphp

<section
  id="gui-khieu-nai"
  class="border-line bg-surface rounded-card scroll-mt-24 border p-5 sm:p-6"
  aria-labelledby="complaint-form-title"
>
  <h2 id="complaint-form-title" class="text-ink text-lg font-semibold">Gửi khiếu nại hoặc phản hồi báo cáo sai</h2>
  <p class="text-ink-subtle mt-1 text-sm">
    Các mục có dấu
    <span class="text-danger" aria-hidden="true">*</span>
    là bắt buộc. Ban quản trị sẽ phản hồi qua email.
  </p>

  @if (session("success"))
    <div
      class="border-success/30 bg-success-soft text-success rounded-control mt-5 flex items-start gap-2 border px-4 py-3 text-sm font-medium"
      role="status"
    >
      <i class="fa-solid fa-circle-check mt-0.5" aria-hidden="true"></i>
      {{ session("success") }}
    </div>
  @endif

  @if (session("error"))
    <div
      class="border-danger/30 bg-danger-soft text-danger rounded-control mt-5 flex items-start gap-2 border px-4 py-3 text-sm font-medium"
      role="alert"
    >
      <i class="fa-solid fa-circle-xmark mt-0.5" aria-hidden="true"></i>
      {{ session("error") }}
    </div>
  @endif

  @if ($errors->any())
    <div class="border-danger/30 bg-danger-soft rounded-control mt-5 border px-4 py-3" role="alert">
      <p class="text-danger text-sm font-semibold">Vui lòng kiểm tra lại {{ $errors->count() }} mục bên dưới.</p>
    </div>
  @endif

  <form
    action="{{ route('complaints.store') }}"
    method="POST"
    enctype="multipart/form-data"
    class="mt-6 space-y-5"
    data-scroll-to-error
  >
    @csrf

    @if ($reportSlug)
      <input type="hidden" name="report" value="{{ $reportSlug }}" />
    @endif

    @if ($report)
      <div class="border-line bg-surface-muted rounded-control border px-4 py-3 text-sm">
        <span class="text-ink-subtle">Báo cáo được khiếu nại:</span>
        <a
          href="{{ route('scammer.show', $report->slug) }}"
          class="text-accent hover:text-accent-hover rounded-sm font-medium underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        >
          {{ StringHelper::mask_name($report->target_name) }} -
          {{ StringHelper::mask_id($report->target_id, $report->type) }}
        </a>
      </div>
    @endif

    @error("report")
      <p class="text-danger text-sm" id="report-error">{{ $message }}</p>
    @enderror

    {{-- Honeypot: hidden from real users --}}
    <div class="hidden" aria-hidden="true">
      <label for="complaint_website">Website</label>
      <input id="complaint_website" type="text" name="website" tabindex="-1" autocomplete="off" />
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label for="contact_name" class="{{ $labelClass }}">
          Họ và tên
          <span class="text-danger" aria-hidden="true">*</span>
        </label>
        <input
          id="contact_name"
          type="text"
          name="contact_name"
          value="{{ old('contact_name') }}"
          required
          maxlength="255"
          autocomplete="name"
          placeholder="Nguyễn Văn A"
          class="{{ $inputClass }}"
          @error("contact_name")
              aria-invalid="true"
              aria-describedby="contact_name-error"
          @enderror
        />
        @error("contact_name")
          <p id="contact_name-error" class="text-danger mt-1.5 text-sm">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="contact_email" class="{{ $labelClass }}">
          Email
          <span class="text-danger" aria-hidden="true">*</span>
        </label>
        <input
          id="contact_email"
          type="email"
          name="contact_email"
          value="{{ old('contact_email') }}"
          required
          maxlength="255"
          autocomplete="email"
          placeholder="email@example.com"
          class="{{ $inputClass }}"
          @error("contact_email")
              aria-invalid="true"
              aria-describedby="contact_email-error"
          @enderror
        />
        @error("contact_email")
          <p id="contact_email-error" class="text-danger mt-1.5 text-sm">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="contact_phone" class="{{ $labelClass }}">Số điện thoại / Zalo</label>
        <input
          id="contact_phone"
          type="tel"
          name="contact_phone"
          value="{{ old('contact_phone') }}"
          maxlength="20"
          autocomplete="tel"
          placeholder="09xxxxxxxx"
          class="{{ $inputClass }}"
          @error("contact_phone")
              aria-invalid="true"
              aria-describedby="contact_phone-error"
          @enderror
        />
        @error("contact_phone")
          <p id="contact_phone-error" class="text-danger mt-1.5 text-sm">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="reason" class="{{ $labelClass }}">
          Lý do khiếu nại
          <span class="text-danger" aria-hidden="true">*</span>
        </label>
        <select
          id="reason"
          name="reason"
          required
          class="{{ $inputClass }} cursor-pointer"
          @error("reason")
              aria-invalid="true"
              aria-describedby="reason-error"
          @enderror
        >
          <option value="">Chọn lý do</option>
          @foreach ($reasons as $reason)
            <option value="{{ $reason->value }}" @selected(old("reason") === $reason->value)>
              {{ $reason->label() }}
            </option>
          @endforeach
        </select>
        @error("reason")
          <p id="reason-error" class="text-danger mt-1.5 text-sm">{{ $message }}</p>
        @enderror
      </div>
    </div>

    <div>
      <label for="message" class="{{ $labelClass }}">
        Nội dung khiếu nại
        <span class="text-danger" aria-hidden="true">*</span>
      </label>
      <textarea
        id="message"
        name="message"
        rows="6"
        required
        minlength="20"
        maxlength="5000"
        placeholder="Trình bày rõ thông tin sai, kèm mã giao dịch hoặc bằng chứng đã giải quyết..."
        class="{{ $inputClass }} resize-y leading-relaxed"
        aria-describedby="message-hint @error("message") message-error @enderror"
        @error("message")
            aria-invalid="true"
        @enderror
      >
{{ old("message") }}</textarea
      >
      <p id="message-hint" class="text-ink-subtle mt-1.5 text-xs">Tối thiểu 20 ký tự.</p>
      @error("message")
        <p id="message-error" class="text-danger mt-1 text-sm">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="complaint_evidence" class="{{ $labelClass }}">Ảnh bằng chứng</label>
      <input
        id="complaint_evidence"
        type="file"
        name="evidence_images[]"
        multiple
        accept="image/jpeg,image/png,image/jpg,image/gif"
        class="text-ink-muted file:border-line file:bg-surface-muted file:text-ink file:rounded-control rounded-control focus-visible:outline-accent block w-full text-sm file:mr-3 file:cursor-pointer file:border file:px-3 file:py-2 file:text-sm file:font-medium focus-visible:outline-2 focus-visible:outline-offset-2"
        aria-describedby="evidence-hint @if ($evidenceError) evidence-error @endif"
        @if ($evidenceError) aria-invalid="true" @endif
      />
      <p id="evidence-hint" class="text-ink-subtle mt-1.5 text-xs">
        Tối đa 5 ảnh JPG, PNG hoặc GIF, mỗi ảnh tối đa 5MB.
      </p>
      @if ($evidenceError)
        <p id="evidence-error" class="text-danger mt-1 text-sm">{{ $evidenceError }}</p>
      @endif
    </div>

    <div class="border-line flex justify-end border-t pt-5">
      <x-ucs.button type="submit" size="lg" icon="fa-solid fa-paper-plane" class="w-full sm:w-auto">
        Gửi khiếu nại
      </x-ucs.button>
    </div>
  </form>
</section>

@push("scripts")
  <script>
    // After a failed submit, bring the first invalid field into view and focus it.
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.querySelector('[data-scroll-to-error]');
      const firstInvalid = form ? form.querySelector('[aria-invalid="true"]') : null;
      if (!firstInvalid) {
        return;
      }
      firstInvalid.scrollIntoView({ block: 'center' });
      firstInvalid.focus({ preventScroll: true });
    });
  </script>
@endpush
