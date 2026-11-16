{{-- Shown after a claim is submitted. Expects: $submitted (['reference' => ?string, 'partner' => ?string]). --}}
<section
  class="border-success/40 bg-surface rounded-card overflow-hidden border"
  aria-labelledby="claim-success-title"
  role="status"
  data-claim-success
>
  <div class="bg-success-soft flex items-start gap-3 px-5 py-5 sm:px-6">
    <span
      class="bg-success flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white"
      aria-hidden="true"
    >
      <i class="fa-solid fa-check"></i>
    </span>
    <div class="min-w-0">
      <h2 id="claim-success-title" class="text-ink text-lg font-semibold">Đã gửi yêu cầu bồi thường</h2>
      <p class="text-ink-muted mt-1 text-sm leading-relaxed">
        Chúng tôi đã tiếp nhận yêu cầu của bạn
        @if (! empty($submitted['partner']))
          đối với GDV
          <strong class="text-ink font-semibold">{{ $submitted['partner'] }}</strong>
        @endif

        và gửi email xác nhận tới địa chỉ bạn đã nhập.
      </p>
    </div>
  </div>

  <div class="space-y-5 p-5 sm:p-6">
    @if (! empty($submitted['reference']))
      <div
        class="border-line bg-surface-muted rounded-control flex flex-wrap items-center justify-between gap-3 border px-4 py-3"
      >
        <div>
          <p class="text-ink-subtle text-xs">Mã yêu cầu của bạn</p>
          <p class="text-ink font-mono text-xl font-semibold tracking-wide" data-claim-reference>
            {{ $submitted['reference'] }}
          </p>
        </div>
        <x-ucs.button
          variant="secondary"
          size="sm"
          icon="fa-regular fa-copy"
          data-copy="{{ $submitted['reference'] }}"
          aria-label="Sao chép mã yêu cầu {{ $submitted['reference'] }}"
        >
          <span data-copy-label aria-live="polite">Sao chép</span>
        </x-ucs.button>
      </div>
    @endif

    <div>
      <h3 class="text-ink text-sm font-semibold">Tiếp theo sẽ thế nào?</h3>
      <ol class="text-ink-muted mt-2 list-decimal space-y-1.5 pl-5 text-sm leading-relaxed">
        <li>Ban quản trị sẽ xem xét hồ sơ và bằng chứng bạn gửi.</li>
        <li>Nếu cần bổ sung thông tin, chúng tôi sẽ liên hệ qua email.</li>
        <li>Mọi cập nhật về trạng thái yêu cầu được gửi tới email của bạn. Hãy ghi lại mã yêu cầu để tiện trao đổi.</li>
      </ol>
    </div>

    <div class="border-line flex flex-col gap-2 border-t pt-5 sm:flex-row">
      <x-ucs.button :href="route('insurances.frontend.index')" variant="secondary" icon="fa-solid fa-list">
        Về danh sách GDV
      </x-ucs.button>
      <x-ucs.button :href="route('home')" variant="ghost" icon="fa-solid fa-house">Về trang chủ</x-ucs.button>
    </div>
  </div>
</section>

@include('partials.copy-to-clipboard')
