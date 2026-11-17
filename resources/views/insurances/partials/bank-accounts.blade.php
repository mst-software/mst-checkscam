{{--
  Official payment (bank account) channels of a partner.
  Expects: $insurance, $bankAccounts (Collection of ['channel' => InsuranceChannel, 'bank' => ?string, 'holder' => ?string]).
--}}
@php
  $isTrusted = $insurance->isTrusted();
@endphp

<x-ucs.card data-bank-accounts>
  <x-slot:header>
    <h2 class="text-ink text-base font-semibold">Tài khoản thanh toán</h2>
    <p class="text-ink-subtle mt-0.5 text-sm">Chỉ chuyển tiền vào các tài khoản được liệt kê tại đây.</p>
  </x-slot>

  @if ($bankAccounts->isEmpty())
    <p class="text-ink-subtle text-sm">Thành viên chưa đăng ký tài khoản thanh toán chính thức.</p>
  @else
    <ul class="space-y-2">
      @foreach ($bankAccounts as $account)
        @php
          $channel = $account['channel'];
        @endphp

        <li
          class="border-line rounded-control flex flex-col gap-2 border px-3 py-2.5 sm:flex-row sm:items-center sm:gap-3"
          data-bank-account
        >
          <div class="flex min-w-0 flex-1 items-center gap-3">
            <span
              class="bg-surface-muted text-ink-muted flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
              aria-hidden="true"
            >
              @if ($account['bank'])
                {{ mb_strtoupper(mb_substr($account['bank'], 0, 2)) }}
              @else
                <i class="fa-solid fa-building-columns"></i>
              @endif
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-ink-subtle text-xs">{{ $account['bank'] ?? 'Số tài khoản' }}</p>
              <p class="text-ink truncate font-mono text-sm font-semibold tracking-wide">{{ $channel->value }}</p>
              @if ($account['holder'])
                <p class="text-ink-muted truncate text-xs">{{ $account['holder'] }}</p>
              @endif

              @if ($isTrusted && $channel->verified_at)
                <p class="text-success mt-0.5 flex items-center gap-1 text-xs">
                  <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                  Xác minh ngày {{ $channel->verified_at->format('d/m/Y') }}
                </p>
              @endif
            </div>
          </div>
          <div class="flex shrink-0 items-center gap-2 sm:pl-0">
            <a
              href="{{ route('intermediary.verify', ['q' => $channel->value]) }}"
              class="text-ink-muted hover:bg-surface-muted hover:text-ink border-line rounded-control focus-visible:outline-accent inline-flex h-8 items-center gap-1.5 border px-2.5 text-xs whitespace-nowrap font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
              data-verify-link
            >
              <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
              Kiểm tra kênh
            </a>
            <button
              type="button"
              data-copy="{{ $channel->value }}"
              class="text-ink-muted hover:bg-surface-muted hover:text-ink border-line rounded-control focus-visible:outline-accent inline-flex h-8 cursor-pointer items-center gap-1.5 border px-2.5 text-xs whitespace-nowrap font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
            >
              <i class="fa-regular fa-copy" aria-hidden="true"></i>
              <span data-copy-label aria-live="polite">Sao chép</span>
            </button>
          </div>
        </li>
      @endforeach
    </ul>
  @endif
</x-ucs.card>
