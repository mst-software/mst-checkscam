{{-- Insurance member identity and deposit summary. Expects: $insurance, $siteName. --}}
<section class="border-line bg-surface rounded-card border p-4 sm:p-5" aria-labelledby="fund-title">
  <div class="flex items-start gap-4">
    @if ($insurance->avatar_url)
      <img
        src="{{ $insurance->avatar_url }}"
        alt="Ảnh đại diện của {{ $insurance->full_name }}"
        width="64"
        height="64"
        class="border-line bg-surface-muted h-16 w-16 shrink-0 rounded-full border object-cover"
      />
    @else
      <span
        class="bg-surface-muted text-ink-muted flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-xl font-semibold"
        aria-hidden="true"
      >
        {{ mb_strtoupper(mb_substr($insurance->full_name, 0, 1)) }}
      </span>
    @endif

    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-2">
        <h2 id="fund-title" class="text-ink text-base font-semibold">Quỹ bảo hiểm</h2>
        @if ($insurance->isExpired())
          <x-ucs.badge variant="neutral" icon="fa-regular fa-clock">Đã hết hạn</x-ucs.badge>
        @else
          <x-ucs.badge variant="success" icon="fa-solid fa-circle-check">Đã xác thực</x-ucs.badge>
        @endif
      </div>
      <p class="text-success mt-2 text-2xl font-semibold tabular-nums sm:text-3xl">
        {{ number_format((float) $insurance->amount, 0, ',', '.') }} đ
      </p>
      <p class="text-ink-subtle mt-1 text-xs">Số dư ký quỹ đang được ban quản trị giữ</p>
    </div>
  </div>

  <dl class="border-line mt-4 grid grid-cols-1 gap-3 border-t pt-4 text-sm sm:grid-cols-2">
    <div>
      <dt class="text-ink-subtle text-xs">Ngày ký quỹ</dt>
      <dd class="text-ink mt-0.5 font-medium tabular-nums">
        {{ $insurance->insurance_date ? $insurance->insurance_date->format('d/m/Y') : 'Chưa cập nhật' }}
      </dd>
    </div>
    @if ($insurance->expired_at)
      <div>
        <dt class="text-ink-subtle text-xs">Hiệu lực đến</dt>
        <dd class="text-ink mt-0.5 font-medium tabular-nums">{{ $insurance->expired_at->format('d/m/Y') }}</dd>
      </div>
    @endif
  </dl>

  <p class="text-ink-muted mt-4 text-sm leading-relaxed">
    {{ $siteName }} đứng ra bảo lãnh cho thành viên
    <strong class="text-ink font-semibold">{{ $insurance->full_name }}</strong>
    trong phạm vi số tiền ký quỹ nêu trên.
  </p>
</section>
