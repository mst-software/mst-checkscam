{{--
  Anti-impersonation notice with the official channels and community links.
  Every row comes from real configuration (app url, site settings); empty settings are hidden.
--}}
@php
  $stripScheme = fn (?string $url) => rtrim((string) preg_replace('#^https?://(www\.)?#i', '', (string) $url), '/');
  $siteHost = parse_url((string) config('app.url'), PHP_URL_HOST);

  $channels = collect([
    ['icon' => 'fa-solid fa-globe', 'label' => $siteHost, 'url' => url('/'), 'external' => false],
    [
      'icon' => 'fa-brands fa-telegram',
      'label' => $stripScheme($siteConfig['telegram_link'] ?? null),
      'url' => $siteConfig['telegram_link'] ?? null,
      'external' => true,
    ],
    [
      'icon' => 'fa-solid fa-phone',
      'label' => $siteConfig['hotline'] ?? null,
      'url' => filled($siteConfig['hotline'] ?? null) ? 'tel:' . preg_replace('/[^\d+]/', '', $siteConfig['hotline']) : null,
      'external' => false,
    ],
    [
      'icon' => 'fa-solid fa-comment-dots',
      'label' => $stripScheme($siteConfig['zalo_link'] ?? null),
      'url' => $siteConfig['zalo_link'] ?? null,
      'external' => true,
    ],
    [
      'icon' => 'fa-solid fa-envelope',
      'label' => $siteConfig['support_email'] ?? null,
      'url' => filled($siteConfig['support_email'] ?? null) ? 'mailto:' . $siteConfig['support_email'] : null,
      'external' => false,
    ],
  ])->filter(fn ($channel) => filled($channel['label']) && filled($channel['url']));

  $communityLinks = collect([
    ['url' => $siteConfig['telegram_link'] ?? null, 'icon' => 'fa-brands fa-telegram', 'label' => 'Nhóm Telegram'],
    ['url' => $siteConfig['facebook_link'] ?? null, 'icon' => 'fa-brands fa-facebook', 'label' => 'Trang Facebook'],
  ])->filter(fn ($link) => filled($link['url']));
@endphp

<section
  aria-label="Cảnh giác mạo danh Scamproof"
  class="border-accent/25 from-accent-soft/50 to-surface rounded-card grid grid-cols-1 gap-6 border bg-linear-to-t p-5 sm:p-6 lg:grid-cols-[1.1fr_1fr]"
>
  <div>
    <h2 class="text-ink flex items-center gap-2.5 text-lg font-bold">
      <i class="fa-solid fa-triangle-exclamation text-accent" aria-hidden="true"></i>
      Cảnh giác mạo danh Scamproof
    </h2>
    <p class="text-ink-muted mt-2 text-sm leading-relaxed">
      Có nhiều trang và tài khoản giả mạo tên chúng tôi để lừa tiền. Scamproof
      <strong class="text-ink">không bao giờ</strong>
      nhắn tin yêu cầu chuyển tiền hay cung cấp mã OTP. Chỉ tin các kênh chính thức được liệt kê ở đây.
    </p>

    @if ($communityLinks->isNotEmpty())
      <div class="border-line mt-5 border-t pt-4">
        <p class="text-ink text-sm font-semibold">Tham gia cộng đồng Scamproof</p>
        <p class="text-ink-muted mt-1 text-sm">Nhận cảnh báo mới và hỏi ý kiến cộng đồng trước khi giao dịch.</p>
        <div class="mt-3 flex flex-wrap gap-2">
          @foreach ($communityLinks as $link)
            <x-ucs.button
              :href="$link['url']"
              variant="secondary"
              size="sm"
              :icon="$link['icon']"
              target="_blank"
              rel="noopener noreferrer"
            >
              {{ $link['label'] }}
            </x-ucs.button>
          @endforeach
        </div>
      </div>
    @endif
  </div>

  @if ($channels->isNotEmpty())
    <ul class="space-y-2" aria-label="Kênh chính thức">
      @foreach ($channels as $channel)
        <li>
          <a
            href="{{ $channel['url'] }}"
            @if ($channel['external']) target="_blank" rel="noopener noreferrer" @endif
            class="border-line bg-surface hover:border-line-strong focus-visible:outline-accent rounded-control flex items-center gap-3 border px-3.5 py-2.5 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2"
            data-official-channel
          >
            <i class="{{ $channel['icon'] }} text-accent w-4 shrink-0 text-center" aria-hidden="true"></i>
            <span class="text-ink min-w-0 truncate font-semibold">{{ $channel['label'] }}</span>
            <span class="text-success ml-auto inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold">
              <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
              Chính thức
            </span>
          </a>
        </li>
      @endforeach
    </ul>
  @endif
</section>
