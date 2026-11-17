{{--
  Official contact channels of a partner, grouped by type, plus the anti-impersonation callout.
  Bank accounts are listed in the payment card instead.
  Expects: $insurance, $contactChannels (Collection grouped by ChannelType value), $profileUrl.
--}}
@php
  $isTrusted = $insurance->isTrusted();
  $contactTypes = collect(\App\Enums\ChannelType::cases())
    ->reject(fn ($type) => $type === \App\Enums\ChannelType::BankAccount)
    ->filter(fn ($type) => $contactChannels->has($type->value));
  $displayUrl = preg_replace('~^https?://~', '', $profileUrl);
@endphp

<x-ucs.card data-official-channels>
  <x-slot:header>
    <h2 class="text-ink text-base font-semibold">Kênh liên hệ chính thức</h2>
    <p class="text-ink-subtle mt-0.5 text-sm">
      @if ($isTrusted)
        Các kênh quản trị viên Scamproof đã ghi nhận cho thành viên này.
      @else
          Các kênh từng được ghi nhận; hiện không còn là kênh của giao dịch viên đã xác thực.
      @endif
    </p>
  </x-slot>

  <div
    class="border-danger/30 bg-danger-soft rounded-control mb-5 flex items-start gap-3 border px-3 py-3 sm:px-4"
    data-impersonation-callout
  >
    <i class="fa-solid fa-triangle-exclamation text-danger mt-0.5" aria-hidden="true"></i>
    <div class="min-w-0 flex-1">
      <p class="text-ink text-sm leading-relaxed font-medium">
        @if ($isTrusted)
          Chỉ giao dịch qua các kênh liệt kê trên trang này. Mọi tài khoản, số điện thoại hoặc link khác đều không phải
          của thành viên.
        @else
            Hồ sơ này hiện không còn hiệu lực xác thực. Không dựa vào trang này để chuyển tiền hay giao dịch qua các
            kênh bên dưới.
        @endif
      </p>
      <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
        <span class="text-ink-muted">Địa chỉ trang xác thực chính thức:</span>
        <span class="text-ink font-mono text-xs font-semibold break-all sm:text-sm" data-official-url>
          {{ $displayUrl }}
        </span>
        <button
          type="button"
          data-copy="{{ $profileUrl }}"
          class="text-ink-muted hover:text-ink focus-visible:outline-accent inline-flex cursor-pointer items-center gap-1 rounded-sm text-xs font-medium underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2"
        >
          <i class="fa-regular fa-copy" aria-hidden="true"></i>
          <span data-copy-label aria-live="polite">Sao chép</span>
        </button>
      </div>
    </div>
  </div>

  @if ($contactTypes->isEmpty())
    <p class="text-ink-subtle text-sm">Thành viên chưa đăng ký kênh liên hệ chính thức.</p>
  @else
    <div class="space-y-5">
      @foreach ($contactTypes as $type)
        <div>
          <h3 class="text-ink-subtle flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase">
            @include('insurances.partials.channel-icon', ['type' => $type])
            {{ $type->label() }}
          </h3>
          <ul class="mt-2 space-y-2">
            @foreach ($contactChannels[$type->value] as $channel)
              @php
                $href = match (true) {
                  $type === \App\Enums\ChannelType::Phone => 'tel:' . preg_replace('/[^\d+]/', '', $channel->value),
                  (bool) preg_match('~^https?://~i', $channel->value) => $channel->value,
                  default => null,
                };
              @endphp

              <li
                class="border-line rounded-control flex flex-col gap-2 border px-3 py-2.5 sm:flex-row sm:items-center sm:gap-3"
                data-channel
              >
                <div class="flex min-w-0 flex-1 items-center gap-3">
                  <span
                    class="bg-surface-muted text-ink-muted flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm"
                    aria-hidden="true"
                  >
                    @include('insurances.partials.channel-icon', ['type' => $type])
                  </span>
                  <div class="min-w-0 flex-1">
                    @if ($href)
                      <a
                        href="{{ $href }}"
                        @if (str_starts_with($href, 'http')) target="_blank" rel="noopener noreferrer nofollow" @endif
                        class="text-ink hover:text-accent focus-visible:outline-accent block truncate rounded-sm text-sm font-semibold underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                      >
                        {{ $channel->value }}
                      </a>
                    @else
                      <p class="text-ink truncate text-sm font-semibold">{{ $channel->value }}</p>
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
        </div>
      @endforeach
    </div>
  @endif
</x-ucs.card>
