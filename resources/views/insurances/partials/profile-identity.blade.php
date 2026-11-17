{{--
  Verification "certificate" at the top of a partner profile: avatar, name, status, GDV number and key facts.
  Expects: $insurance, $checkedAt (Carbon, time of this request).
--}}
@php
  $isTrusted = $insurance->isTrusted();
  $memberCode = $insurance->memberCode();
@endphp

<section
  @class([
    'bg-surface rounded-card overflow-hidden border',
    'border-success/40' => $isTrusted,
    'border-danger/30' => ! $isTrusted && $insurance->isSuspended(),
    'border-line' => ! $isTrusted && ! $insurance->isSuspended(),
  ])
  aria-labelledby="identity-title"
  data-identity-card
>
  <div
    @class([
      'flex items-center gap-2 px-4 py-2.5 text-sm font-semibold sm:px-6',
      'bg-success-soft text-success' => $isTrusted,
      'bg-danger-soft text-danger' => ! $isTrusted && $insurance->isSuspended(),
      'bg-warning-soft text-warning' => ! $isTrusted && ! $insurance->isSuspended(),
    ])
    data-status-banner
  >
    @if ($isTrusted)
      <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
      Hồ sơ đã được Scamproof xác thực
    @elseif ($insurance->isSuspended())
      <i class="fa-solid fa-ban" aria-hidden="true"></i>
      Hồ sơ đang bị tạm đình chỉ
    @else
      <i class="fa-regular fa-clock" aria-hidden="true"></i>
      Hồ sơ không còn hiệu lực xác thực
    @endif
  </div>

  <div class="p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-5">
      @include('insurances.partials.avatar', ['insurance' => $insurance, 'size' => 'xl'])

      <div class="min-w-0 flex-1">
        <h2 id="identity-title" class="text-ink text-xl font-semibold tracking-tight break-words sm:text-2xl">
          {{ $insurance->full_name }}
        </h2>

        <div class="mt-2 flex flex-wrap items-center gap-2">
          @if ($isTrusted)
            <span
              class="border-success/30 bg-success-soft text-success inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-semibold"
              data-verification-badge
            >
              <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
              Giao dịch viên đã xác thực
            </span>
          @else
            <span
              @class([
                'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm font-semibold',
                'border-danger/30 bg-danger-soft text-danger' => $insurance->isSuspended(),
                'border-line bg-surface-muted text-ink-muted' => ! $insurance->isSuspended(),
              ])
              data-verification-badge
            >
              <i
                class="{{ $insurance->isSuspended() ? 'fa-solid fa-ban' : 'fa-regular fa-clock' }}"
                aria-hidden="true"
              ></i>
              {{ $insurance->publicStatusLabel() }}
            </span>
          @endif

          <span
            class="border-line bg-surface-muted text-ink inline-flex items-center gap-1 rounded-full border py-0.5 pr-1 pl-3 text-sm"
          >
            <span class="font-semibold" data-member-code>{{ $insurance->memberTitle() }}</span>
            <button
              type="button"
              data-copy="{{ $memberCode }}"
              class="text-ink-subtle hover:bg-surface hover:text-ink focus-visible:outline-accent flex h-7 w-7 cursor-pointer items-center justify-center rounded-full transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
            >
              <i class="fa-regular fa-copy text-xs" aria-hidden="true"></i>
              <span class="sr-only" data-copy-label aria-live="polite">Sao chép số giao dịch viên</span>
            </button>
          </span>
        </div>
      </div>
    </div>

    <dl class="border-line mt-5 grid grid-cols-1 gap-4 border-t pt-5 sm:grid-cols-3" data-key-facts>
      <div>
        <dt class="text-ink-subtle flex items-center gap-1.5 text-xs font-medium">
          <i class="fa-solid fa-vault" aria-hidden="true"></i>
          Ký quỹ
        </dt>
        <dd class="text-ink mt-1 text-xl font-semibold tabular-nums">
          {{ number_format((float) $insurance->amount, 0, ',', '.') }} đ
        </dd>
      </div>
      <div>
        <dt class="text-ink-subtle flex items-center gap-1.5 text-xs font-medium">
          <i class="fa-solid fa-user-check" aria-hidden="true"></i>
          Xác thực từ
        </dt>
        <dd class="text-ink mt-1 text-xl font-semibold tabular-nums">
          {{ $insurance->verified_at ? $insurance->verified_at->format('d/m/Y') : 'Chưa cập nhật' }}
        </dd>
      </div>
      <div>
        <dt class="text-ink-subtle flex items-center gap-1.5 text-xs font-medium">
          <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
          Hiệu lực đến
        </dt>
        <dd class="text-ink mt-1 text-xl font-semibold tabular-nums">
          {{ $insurance->expired_at ? $insurance->expired_at->format('d/m/Y') : 'Không thời hạn' }}
        </dd>
      </div>
    </dl>

    @unless ($isTrusted)
      <p class="text-ink mt-5 text-sm leading-relaxed" data-lapsed-notice>
        <strong class="font-semibold">Thành viên này hiện không phải giao dịch viên đã xác thực của Scamproof.</strong>
        Không dùng hồ sơ này làm căn cứ để giao dịch.
      </p>
    @endunless
  </div>

  <p
    class="border-line bg-surface-muted text-ink-muted flex items-start gap-2 border-t px-4 py-2.5 text-xs sm:px-6"
    data-checked-at
  >
    <i class="fa-solid fa-rotate mt-0.5" aria-hidden="true"></i>
    <span>
      Trạng thái được kiểm tra lúc
      <time datetime="{{ $checkedAt->toIso8601String() }}" class="text-ink font-medium tabular-nums">
        {{ $checkedAt->format('d/m/Y H:i') }}
      </time>
      (kiểm tra lại mỗi lần mở trang)
    </span>
  </p>
</section>
