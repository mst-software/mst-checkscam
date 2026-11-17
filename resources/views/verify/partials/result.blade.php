{{--
  Result of IntermediaryVerifier::verify(). Expects: $result (App\Services\IntermediaryVerification).
  Wording never calls a result "safe": a verified channel only means it belongs to a listed partner.
--}}
@php
  $insurance = $result->insurance;
  $channelType = $result->channelType();
  $reportsWarning = $result->reportsWarning();
  // Inactive partners (status 0) have no public profile page.
  $profileUrl =
    $insurance && $insurance->isActive() && $insurance->slug
      ? route('insurances.frontend.show', $insurance->slug)
      : null;
@endphp

<section class="space-y-4" aria-labelledby="verify-result-title" data-verify-state="{{ $result->state->value }}">
  <p class="text-ink-subtle text-sm">
    Kết quả cho:
    <span class="text-ink font-semibold break-all">{{ $result->input }}</span>
  </p>

  @if ($reportsWarning)
    <div
      class="border-danger/30 bg-danger-soft rounded-card flex flex-col gap-3 border px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
      role="alert"
      data-reports-warning
    >
      <p class="text-danger flex items-start gap-2 text-sm font-semibold">
        <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
        {{ $reportsWarning }}
      </p>
      <a
        href="{{ route('search.index', ['q' => $result->input]) }}"
        class="text-danger focus-visible:outline-danger inline-flex shrink-0 items-center gap-1 rounded-sm text-sm font-semibold underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2"
      >
        Xem tố cáo
        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
      </a>
    </div>
  @endif

  @if ($result->isVerified())
    <div class="border-success/40 bg-surface rounded-card overflow-hidden border">
      <div class="bg-success-soft flex items-start gap-3 px-4 py-4 sm:px-5">
        <span
          class="bg-success flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white"
          aria-hidden="true"
        >
          <i class="fa-solid fa-check"></i>
        </span>
        <div class="min-w-0">
          <p class="text-success text-xs font-semibold tracking-wide uppercase">{{ $result->state->label() }}</p>
          <h2 id="verify-result-title" class="text-ink mt-0.5 text-base font-semibold sm:text-lg">
            {{ $result->message() }}
          </h2>
        </div>
      </div>

      <div class="p-4 sm:p-5">
        <div class="flex items-center gap-3">
          @include('insurances.partials.avatar', ['insurance' => $insurance])
          <div class="min-w-0">
            <p class="text-ink truncate text-base font-semibold">{{ $insurance->full_name }}</p>
            <x-ucs.badge variant="success" icon="fa-solid fa-circle-check" class="mt-1">Đã xác thực</x-ucs.badge>
          </div>
        </div>

        <dl class="border-line mt-4 grid grid-cols-1 gap-x-6 gap-y-3 border-t pt-4 text-sm sm:grid-cols-2">
          <div>
            <dt class="text-ink-subtle text-xs">Số dư ký quỹ</dt>
            <dd class="text-ink mt-0.5 font-semibold tabular-nums">
              {{ number_format((float) $insurance->amount, 0, ',', '.') }} đ
            </dd>
          </div>
          <div>
            <dt class="text-ink-subtle text-xs">Hiệu lực đến</dt>
            <dd class="text-ink mt-0.5 font-medium tabular-nums">
              {{ $insurance->expired_at ? $insurance->expired_at->format('d/m/Y') : 'Không thời hạn' }}
            </dd>
          </div>
          <div>
            <dt class="text-ink-subtle text-xs">Ngày xác thực GDV</dt>
            <dd class="text-ink mt-0.5 font-medium tabular-nums">
              {{ $insurance->verified_at ? $insurance->verified_at->format('d/m/Y') : 'Chưa cập nhật' }}
            </dd>
          </div>
          <div>
            <dt class="text-ink-subtle text-xs">Kênh khớp</dt>
            <dd class="text-ink mt-0.5 flex items-center gap-1.5 font-medium">
              @include('insurances.partials.channel-icon', ['type' => $channelType, 'class' => 'text-ink-subtle'])
              {{ $channelType?->label() }}
            </dd>
          </div>
        </dl>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row">
          @if ($profileUrl)
            <x-ucs.button :href="$profileUrl" variant="secondary" icon="fa-solid fa-id-card">
              Xem hồ sơ GDV
            </x-ucs.button>
          @endif
        </div>

        <p class="text-ink-subtle mt-4 text-xs leading-relaxed">
          Kết quả chỉ xác nhận kênh này thuộc một GDV đã ký quỹ với Scamproof. Hãy giao dịch qua đúng các kênh được liệt
          kê trên hồ sơ GDV.
        </p>
      </div>
    </div>
  @elseif ($result->isLapsed())
    <div class="border-warning/40 bg-surface rounded-card overflow-hidden border">
      <div class="bg-warning-soft flex items-start gap-3 px-4 py-4 sm:px-5">
        <span
          class="border-warning/40 text-warning bg-surface flex h-10 w-10 shrink-0 items-center justify-center rounded-full border"
          aria-hidden="true"
        >
          <i class="fa-solid fa-clock-rotate-left"></i>
        </span>
        <div class="min-w-0">
          <p class="text-warning text-xs font-semibold tracking-wide uppercase">{{ $result->state->label() }}</p>
          <h2 id="verify-result-title" class="text-ink mt-0.5 text-base font-semibold sm:text-lg">
            Từng là GDV của Scamproof nhưng hiện không còn hiệu lực
          </h2>
        </div>
      </div>

      <div class="p-4 sm:p-5">
        <div class="flex items-center gap-3">
          @include('insurances.partials.avatar', ['insurance' => $insurance])
          <div class="min-w-0">
            <p class="text-ink truncate text-base font-semibold">{{ $insurance->full_name }}</p>
            <x-ucs.badge :variant="$insurance->isSuspended() ? 'danger' : 'neutral'" class="mt-1">
              {{ $insurance->status_label }}
            </x-ucs.badge>
          </div>
        </div>

        <p class="text-ink-muted mt-4 text-sm leading-relaxed">{{ $result->message() }}</p>
        <p class="text-ink mt-2 text-sm">
          <span class="text-ink-subtle">Lý do:</span>
          <span class="font-medium">GDV {{ $result->lapsedReasonLabel() }}</span>
        </p>

        @if ($profileUrl)
          <div class="mt-5">
            <x-ucs.button :href="$profileUrl" variant="secondary" icon="fa-solid fa-id-card">
              Xem hồ sơ GDV
            </x-ucs.button>
          </div>
        @endif
      </div>
    </div>
  @else
    <div class="border-line bg-surface rounded-card overflow-hidden border">
      <div class="bg-surface-muted flex items-start gap-3 px-4 py-4 sm:px-5">
        <span
          class="bg-accent-soft text-accent flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
          aria-hidden="true"
        >
          <i class="fa-solid fa-xmark"></i>
        </span>
        <div class="min-w-0">
          <p class="text-accent text-xs font-semibold tracking-wide uppercase">Không có trong danh sách</p>
          <h2 id="verify-result-title" class="text-ink mt-0.5 text-base font-semibold sm:text-lg">
            {{ $result->message() }}
          </h2>
        </div>
      </div>

      <div class="p-4 sm:p-5">
        <p class="text-ink-muted text-sm leading-relaxed">
          Chỉ giao dịch qua trung gian đã xác thực; kiểm tra thêm bằng
          <a
            href="{{ route('home') }}#searchInput"
            class="text-accent hover:text-accent-hover focus-visible:outline-accent rounded-sm font-semibold underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2"
          >
            ô tra cứu tố cáo
          </a>
        </p>
        <div class="mt-5 flex flex-col gap-2 sm:flex-row">
          <x-ucs.button
            :href="route('search.index', ['q' => $result->input])"
            variant="secondary"
            icon="fa-solid fa-magnifying-glass"
          >
            Tra cứu tố cáo
          </x-ucs.button>
          <x-ucs.button :href="route('insurances.frontend.index')" variant="ghost" icon="fa-solid fa-list">
            Xem danh sách GDV
          </x-ucs.button>
        </div>
      </div>
    </div>
  @endif
</section>
