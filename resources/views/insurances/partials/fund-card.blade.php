{{-- Partner identity, verification status and deposit summary. Expects: $insurance, $siteName, $isReconciled. --}}
@php
  $isTrusted = $insurance->isTrusted();
@endphp

<section class="border-line bg-surface rounded-card border p-4 sm:p-5" aria-labelledby="fund-title">
  <div class="flex items-start gap-4">
    @include('insurances.partials.avatar', ['insurance' => $insurance, 'size' => 'lg'])

    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-2">
        <h2 id="fund-title" class="text-ink text-base font-semibold">Quỹ bảo hiểm</h2>
        @if ($isTrusted)
          <x-ucs.badge variant="success" icon="fa-solid fa-circle-check" data-verification-badge>
            Đã xác thực
          </x-ucs.badge>
        @else
          <x-ucs.badge
            :variant="$insurance->isSuspended() ? 'danger' : 'neutral'"
            :icon="$insurance->isSuspended() ? 'fa-solid fa-ban' : 'fa-regular fa-clock'"
            data-verification-badge
          >
            {{ $insurance->status_label }}
          </x-ucs.badge>
        @endif
      </div>
      <p
        @class(['mt-2 text-2xl font-semibold tabular-nums sm:text-3xl', 'text-success' => $isTrusted, 'text-ink' => ! $isTrusted])
      >
        {{ number_format((float) $insurance->amount, 0, ',', '.') }} đ
      </p>
      <p class="text-ink-subtle mt-1 text-xs">Số dư ký quỹ hiện tại, do ban quản trị ghi nhận</p>
      @if ($isReconciled)
        <p class="text-ink-muted mt-2 flex items-center gap-1.5 text-xs" data-ledger-reconciled>
          <i class="fa-solid fa-scale-balanced text-ink-subtle" aria-hidden="true"></i>
          Số dư hiện tại khớp sổ quỹ
        </p>
      @endif
    </div>
  </div>

  <dl class="border-line mt-4 grid grid-cols-1 gap-3 border-t pt-4 text-sm sm:grid-cols-3">
    <div>
      <dt class="text-ink-subtle text-xs">Ngày ký quỹ</dt>
      <dd class="text-ink mt-0.5 font-medium tabular-nums">
        {{ $insurance->insurance_date ? $insurance->insurance_date->format('d/m/Y') : 'Chưa cập nhật' }}
      </dd>
    </div>
    <div>
      <dt class="text-ink-subtle text-xs">Ngày xác thực</dt>
      <dd class="text-ink mt-0.5 font-medium tabular-nums">
        {{ $insurance->verified_at ? $insurance->verified_at->format('d/m/Y') : 'Chưa cập nhật' }}
      </dd>
    </div>
    <div>
      <dt class="text-ink-subtle text-xs">Hiệu lực đến</dt>
      <dd class="text-ink mt-0.5 font-medium tabular-nums">
        {{ $insurance->expired_at ? $insurance->expired_at->format('d/m/Y') : 'Không thời hạn' }}
      </dd>
    </div>
  </dl>

  @if ($isTrusted)
    <p class="text-ink-muted mt-4 text-sm leading-relaxed">
      {{ $siteName }} đứng ra bảo lãnh cho thành viên
      <strong class="text-ink font-semibold">{{ $insurance->full_name }}</strong>
      trong phạm vi số tiền ký quỹ nêu trên.
    </p>
  @endif
</section>
