{{--
    Claim form. Field names and the honeypot ("website") are used by InsuranceClaimController.
    Expects: $partner (Insurance|null), $partners (Collection).
--}}
@php
  $inputClass = "border-line bg-surface text-ink placeholder:text-ink-subtle rounded-control focus-visible:outline-accent aria-invalid:border-danger w-full border px-3 py-2.5 text-base sm:text-sm focus-visible:outline-2 focus-visible:outline-offset-1";
  $labelClass = "text-ink mb-1.5 block text-sm font-medium";
  $evidenceError = $errors->first("evidence_images") ?: $errors->first("evidence_images.*");
  $minDescription = \App\Http\Controllers\InsuranceClaimController::MIN_DESCRIPTION_LENGTH;
@endphp

<section
  id="gui-yeu-cau"
  class="border-line bg-surface rounded-card scroll-mt-24 border p-5 sm:p-6"
  aria-labelledby="claim-form-title"
>
  <h2 id="claim-form-title" class="text-ink text-lg font-semibold">Thông tin yêu cầu</h2>
  <p class="text-ink-subtle mt-1 text-sm">
    Các mục có dấu
    <span class="text-danger" aria-hidden="true">*</span>
    là bắt buộc.
  </p>

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
    action="{{ route('insurance-claims.store') }}"
    method="POST"
    enctype="multipart/form-data"
    class="mt-6 space-y-5"
    data-scroll-to-error
  >
    @csrf

    {{-- Honeypot: hidden from real users --}}
    <div class="hidden" aria-hidden="true">
      <label for="claim_website">Website</label>
      <input id="claim_website" type="text" name="website" tabindex="-1" autocomplete="off" />
    </div>

    <div>
      @if ($partner)
        <input type="hidden" name="partner" value="{{ $partner->slug }}" />
        <p class="{{ $labelClass }}">Giao dịch viên</p>
        <div class="border-line bg-surface-muted rounded-control flex items-center gap-3 border px-3 py-3">
          @include("insurances.partials.avatar", ["insurance" => $partner])
          <div class="min-w-0 flex-1">
            <p class="text-ink truncate text-sm font-semibold">{{ $partner->full_name }}</p>
            <p class="text-ink-subtle truncate text-xs">Mã: {{ $partner->slug }} · {{ $partner->status_label }}</p>
          </div>
          <a
            href="{{ route('insurance-claims.create') }}"
            class="text-accent hover:text-accent-hover focus-visible:outline-accent shrink-0 rounded-sm text-sm font-medium underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
          >
            Đổi GDV
          </a>
        </div>
      @else
        <label for="partner" class="{{ $labelClass }}">
          Giao dịch viên
          <span class="text-danger" aria-hidden="true">*</span>
        </label>
        <select
          id="partner"
          name="partner"
          required
          class="{{ $inputClass }} cursor-pointer"
          aria-describedby="partner-hint @error("partner") partner-error @enderror"
          @error("partner")
              aria-invalid="true"
          @enderror
        >
          <option value="">Chọn GDV bạn đã giao dịch</option>
          @foreach ($partners as $option)
            <option value="{{ $option->slug }}" @selected(old("partner") === $option->slug)>
              {{ $option->full_name }} ({{ $option->slug }})
            </option>
          @endforeach
        </select>
        <p id="partner-hint" class="text-ink-subtle mt-1.5 text-xs">
          Không chắc người đó có phải GDV?
          <a
            href="{{ route('intermediary.verify') }}"
            class="text-accent hover:text-accent-hover focus-visible:outline-accent rounded-sm font-medium underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2"
          >
            Kiểm tra GDV
          </a>
        </p>
      @endif
      @error("partner")
        <p id="partner-error" class="text-danger mt-1.5 text-sm">{{ $message }}</p>
      @enderror
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      <div>
        <label for="claimant_name" class="{{ $labelClass }}">
          Họ và tên
          <span class="text-danger" aria-hidden="true">*</span>
        </label>
        <input
          id="claimant_name"
          type="text"
          name="claimant_name"
          value="{{ old('claimant_name') }}"
          required
          maxlength="255"
          autocomplete="name"
          placeholder="Nguyễn Văn A"
          class="{{ $inputClass }}"
          @error("claimant_name")
              aria-invalid="true"
              aria-describedby="claimant_name-error"
          @enderror
        />
        @error("claimant_name")
          <p id="claimant_name-error" class="text-danger mt-1.5 text-sm">{{ $message }}</p>
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
        <label for="amount_claimed" class="{{ $labelClass }}">
          Số tiền yêu cầu bồi thường
          <span class="text-danger" aria-hidden="true">*</span>
        </label>
        <div class="relative">
          <input
            id="amount_claimed"
            type="text"
            name="amount_claimed"
            value="{{ old('amount_claimed') }}"
            required
            inputmode="numeric"
            maxlength="20"
            autocomplete="off"
            placeholder="2.000.000"
            class="{{ $inputClass }} pr-10 tabular-nums"
            aria-describedby="amount_claimed-hint @error("amount_claimed") amount_claimed-error @enderror"
            @error("amount_claimed")
                aria-invalid="true"
            @enderror
          />
          <span
            class="text-ink-subtle pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm"
            aria-hidden="true"
          >
            đ
          </span>
        </div>
        <p id="amount_claimed-hint" class="text-ink-subtle mt-1.5 text-xs">Số tiền thiệt hại, tính bằng VNĐ.</p>
        @error("amount_claimed")
          <p id="amount_claimed-error" class="text-danger mt-1 text-sm">{{ $message }}</p>
        @enderror
      </div>
    </div>

    <div>
      <label for="description" class="{{ $labelClass }}">
        Mô tả sự việc
        <span class="text-danger" aria-hidden="true">*</span>
      </label>
      <textarea
        id="description"
        name="description"
        rows="6"
        required
        minlength="{{ $minDescription }}"
        maxlength="5000"
        placeholder="Thời gian giao dịch, nội dung đã thỏa thuận với GDV, số tiền đã chuyển và chuyện gì đã xảy ra..."
        class="{{ $inputClass }} resize-y leading-relaxed"
        aria-describedby="description-hint @error("description") description-error @enderror"
        @error("description")
            aria-invalid="true"
        @enderror
      >
{{ old("description") }}</textarea
      >
      <p id="description-hint" class="text-ink-subtle mt-1.5 text-xs">Tối thiểu {{ $minDescription }} ký tự.</p>
      @error("description")
        <p id="description-error" class="text-danger mt-1 text-sm">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="claim_evidence" class="{{ $labelClass }}">Ảnh bằng chứng</label>
      <input
        id="claim_evidence"
        type="file"
        name="evidence_images[]"
        multiple
        accept="image/jpeg,image/png,image/jpg,image/gif"
        class="text-ink-muted file:border-line file:bg-surface-muted file:text-ink file:rounded-control rounded-control focus-visible:outline-accent block w-full text-sm file:mr-3 file:cursor-pointer file:border file:px-3 file:py-2 file:text-sm file:font-medium focus-visible:outline-2 focus-visible:outline-offset-2"
        aria-describedby="evidence-hint @if ($evidenceError) evidence-error @endif"
        @if ($evidenceError) aria-invalid="true" @endif
      />
      <p id="evidence-hint" class="text-ink-subtle mt-1.5 text-xs">
        Tối đa 5 ảnh JPG, PNG hoặc GIF, mỗi ảnh tối đa 5MB: tin nhắn trao đổi, biên lai chuyển khoản...
      </p>
      @if ($evidenceError)
        <p id="evidence-error" class="text-danger mt-1 text-sm">{{ $evidenceError }}</p>
      @endif
    </div>

    <div class="border-line bg-surface-muted rounded-control flex items-start gap-2.5 border px-4 py-3">
      <i class="fa-solid fa-lock text-ink-subtle mt-0.5 text-sm" aria-hidden="true"></i>
      <p class="text-ink-muted text-sm leading-relaxed">
        Email và số điện thoại chỉ ban quản trị xem được, không hiển thị công khai. Khi yêu cầu có kết quả, hồ sơ GDV
        chỉ hiển thị tên đã che bớt, số tiền, trạng thái và phần tóm tắt do ban quản trị viết.
      </p>
    </div>

    <div>
      <label for="consent" class="flex cursor-pointer items-start gap-2.5">
        <input
          id="consent"
          type="checkbox"
          name="consent"
          value="1"
          required
          @checked(old("consent"))
          class="border-line accent-accent mt-0.5 h-4 w-4 shrink-0 cursor-pointer"
          @error("consent")
              aria-invalid="true"
              aria-describedby="consent-error"
          @enderror
        />
        <span class="text-ink text-sm leading-relaxed">
          Tôi cam kết thông tin và bằng chứng cung cấp là trung thực, và đồng ý để ban quản trị Scamproof dùng chúng để
          xem xét yêu cầu này.
        </span>
      </label>
      @error("consent")
        <p id="consent-error" class="text-danger mt-1.5 text-sm">{{ $message }}</p>
      @enderror
    </div>

    <div class="border-line flex justify-end border-t pt-5">
      <button
        type="submit"
        class="bg-hero-action hover:bg-hero-action-hover rounded-control focus-visible:outline-hero-action inline-flex h-11 w-full cursor-pointer items-center justify-center gap-2 px-5 text-base font-semibold text-white transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 sm:w-auto"
      >
        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
        Gửi yêu cầu bồi thường
      </button>
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
