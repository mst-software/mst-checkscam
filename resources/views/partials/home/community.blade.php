@php
  $communityLinks = collect([
    ['url' => $siteConfig['telegram_link'] ?? null, 'icon' => 'fa-brands fa-telegram', 'label' => 'Nhóm Telegram'],
    ['url' => $siteConfig['facebook_link'] ?? null, 'icon' => 'fa-brands fa-facebook', 'label' => 'Trang Facebook'],
  ])->filter(fn ($link) => filled($link['url']));
@endphp

@if ($communityLinks->isNotEmpty())
  <section
    aria-label="Cộng đồng CheckScam"
    class="border-line bg-surface rounded-card flex flex-col gap-4 border p-5 sm:flex-row sm:items-center sm:justify-between"
  >
    <div class="flex items-start gap-3">
      <i class="fa-solid fa-users text-accent mt-1 w-5 text-center" aria-hidden="true"></i>
      <div>
        <h2 class="text-ink text-base font-semibold">Tham gia cộng đồng CheckScam</h2>
        <p class="text-ink-muted mt-1 text-sm">Nhận cảnh báo mới và hỏi ý kiến cộng đồng trước khi giao dịch.</p>
      </div>
    </div>
    <div class="flex flex-wrap gap-2">
      @foreach ($communityLinks as $link)
        <x-ucs.button
          :href="$link['url']"
          variant="secondary"
          :icon="$link['icon']"
          target="_blank"
          rel="noopener noreferrer"
        >
          {{ $link['label'] }}
        </x-ucs.button>
      @endforeach
    </div>
  </section>
@endif
